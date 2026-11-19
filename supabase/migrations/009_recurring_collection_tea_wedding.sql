-- Recurring orders with a discount, collection only for cakes and juices, Tea Baskets, Weddings section.

-- 1. Settings, categories, requests, quotations
alter table pmb.settings add column if not exists recurring_discount_percent numeric not null default 10 check (recurring_discount_percent between 0 and 50);
alter table pmb.settings add column if not exists recurring_min_deliveries int not null default 4 check (recurring_min_deliveries between 2 and 52);
alter table pmb.categories add column if not exists allow_collection boolean not null default false;
alter table pmb.requests add column if not exists recurrence_frequency text check (recurrence_frequency in ('WEEKLY','FORTNIGHTLY','MONTHLY'));
alter table pmb.requests add column if not exists recurrence_count int check (recurrence_count between 2 and 52);
alter table pmb.requests add column if not exists recurrence_parent_id uuid references pmb.requests(id) on delete restrict;
alter table pmb.requests add column if not exists delivery_number int;
alter table pmb.quotations add column if not exists auto_recurring boolean not null default false;

update pmb.categories set allow_collection = true where slug in ('cakes','juices');

create or replace function pmb.public_settings() returns jsonb language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select jsonb_build_object('business_name', business_name, 'deposit_percent', deposit_percent, 'quote_valid_days', quote_valid_days,
    'contact_phone', contact_phone, 'contact_email', contact_email,
    'recurring_discount_percent', recurring_discount_percent, 'recurring_min_deliveries', recurring_min_deliveries)
  from pmb.settings where id = 1 $$;

-- 2. Quotation maths: automatic recurring discount
create or replace function pmb._recalc_quotation(p_q uuid) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare v_sub numeric; q record; v_pct numeric;
begin
  select coalesce(sum(subtotal), 0) into v_sub from pmb.quotation_items where quotation_id = p_q;
  select * into q from pmb.quotations where id = p_q;
  if q.auto_recurring then
    select recurring_discount_percent into v_pct from pmb.settings where id = 1;
    update pmb.quotations set discount = round(v_sub * v_pct / 100, 2) where id = p_q;
    q.discount := round(v_sub * v_pct / 100, 2);
  end if;
  if q.discount > v_sub then raise exception 'Discount cannot be more than the items total'; end if;
  update pmb.quotations set subtotal = round(v_sub, 2), total = round(v_sub - q.discount + q.delivery_fee, 2) where id = p_q;
end $$;

create or replace function pmb.quotation_set_terms(p_q uuid, p_discount numeric, p_delivery_fee numeric, p_notes text, p_message text) returns void
language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare q record;
begin
  q := pmb._draft_quotation(p_q);
  if coalesce(p_discount, 0) < 0 or coalesce(p_delivery_fee, 0) < 0 then raise exception 'Amounts cannot be negative'; end if;
  update pmb.quotations set discount = round(coalesce(p_discount, 0), 2), delivery_fee = round(coalesce(p_delivery_fee, 0), 2),
    auto_recurring = (auto_recurring and round(coalesce(p_discount, 0), 2) = discount),
    notes = nullif(btrim(p_notes), ''), customer_message = nullif(btrim(p_message), '') where id = p_q;
  perform pmb._recalc_quotation(p_q);
end $$;

create or replace function pmb.build_quotation(p_request uuid) returns uuid language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare r record; v_q uuid; v_sub numeric; v_fee numeric; v_pct numeric; v_msg text;
begin
  perform pmb._need_staff();
  select * into r from pmb.requests where id = p_request for update;
  if not found then raise exception 'Request not found'; end if;
  if r.status in ('DECLINED','CANCELLED','DRAFT') then raise exception 'This request is %', r.status; end if;
  if exists (select 1 from pmb.orders where request_id = r.id) then raise exception 'This request already has an order'; end if;
  if exists (select 1 from pmb.quotations where request_id = r.id and status in ('DRAFT','SENT','ACCEPTED')) then raise exception 'This request already has an open quotation'; end if;
  insert into pmb.quotations(request_id, reference, created_by) values (r.id, pmb.next_ref('quotation', 'QUO'), auth.uid()) returning id into v_q;
  insert into pmb.quotation_items(quotation_id, request_item_id, name, description, quantity, unit, unit_price, subtotal, options, needs_price, sort_order)
    select v_q, ri.id, ri.name,
      (select string_agg(po.name || ': ' || v.name, ', ') from pmb.product_option_values v join pmb.product_options po on po.id = v.product_option_id
        where v.id in (select (x)::bigint from jsonb_array_elements_text(ri.options) x)),
      ri.quantity, ri.unit, coalesce(ri.unit_price, 0), coalesce(ri.subtotal, 0), ri.options, ri.subtotal is null, row_number() over (order by ri.created_at)
    from pmb.request_items ri where ri.request_id = r.id;
  select coalesce(sum(subtotal), 0) into v_sub from pmb.quotation_items where quotation_id = v_q;
  v_fee := case when r.fulfillment_method = 'DELIVERY' then pmb.delivery_fee(r.delivery_zone_id, r.delivery_type, v_sub) else 0 end;
  update pmb.quotations set delivery_fee = v_fee where id = v_q;
  if r.recurrence_frequency is not null then
    select recurring_discount_percent into v_pct from pmb.settings where id = 1;
    v_msg := 'Regular order: ' || r.recurrence_count || ' deliveries, ' ||
      case r.recurrence_frequency when 'WEEKLY' then 'every week' when 'FORTNIGHTLY' then 'every 2 weeks' else 'every month' end ||
      ', starting ' || to_char(r.event_date, 'DD Mon YYYY') || '. The prices below are for one delivery and include a ' || v_pct ||
      '% regular-order discount on the food. Delivery is charged on each delivery.';
    update pmb.quotations set auto_recurring = true, customer_message = v_msg where id = v_q;
  end if;
  perform pmb._recalc_quotation(v_q);
  if r.status = 'SUBMITTED' then update pmb.requests set status = 'UNDER_REVIEW' where id = r.id; end if;
  insert into pmb.quotation_events(quotation_id, event, actor) values (v_q, 'CREATED', auth.uid());
  insert into pmb.request_events(request_id, event, detail, actor) values (r.id, 'QUOTATION_DRAFTED', 'Quotation drafted', auth.uid());
  return v_q;
end $$;

-- 3. Customer page shows the repeat plan
create or replace function pmb.public_request(p_token uuid) returns jsonb language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare r record; q record; o record; s record; v_out jsonb;
begin
  perform pmb.expire_quotations();
  select * into r from pmb.requests where access_token = p_token;
  if not found then return null; end if;
  select * into s from pmb.settings where id = 1;
  select * into q from pmb.quotations where request_id = r.id and status in ('SENT','ACCEPTED','DECLINED','EXPIRED') order by created_at desc limit 1;
  select * into o from pmb.orders where request_id = r.id;
  v_out := jsonb_build_object(
    'reference', r.reference, 'status', r.status, 'fulfillment_method', r.fulfillment_method, 'event_date', r.event_date, 'event_time', r.event_time,
    'location', r.location, 'delivery_address', r.delivery_address, 'notes', r.notes, 'staff_message', r.staff_message, 'submitted_at', r.submitted_at,
    'customer_name', (select name from pmb.customers where id = r.customer_id),
    'items', coalesce((select jsonb_agg(jsonb_build_object('name', ri.name, 'quantity', ri.quantity, 'unit', ri.unit, 'unit_price', ri.unit_price, 'subtotal', ri.subtotal, 'pricing_status', ri.pricing_status, 'notes', ri.notes) order by ri.created_at) from pmb.request_items ri where ri.request_id = r.id), '[]'::jsonb),
    'business', jsonb_build_object('name', s.business_name, 'phone', s.contact_phone, 'email', s.contact_email, 'deposit_percent', s.deposit_percent));
  if r.recurrence_frequency is not null then
    v_out := v_out || jsonb_build_object('recurrence', jsonb_build_object('frequency', r.recurrence_frequency, 'count', r.recurrence_count, 'discount_percent', s.recurring_discount_percent));
  end if;
  if q.id is not null then
    v_out := v_out || jsonb_build_object('quotation', jsonb_build_object(
      'reference', q.reference, 'status', q.status, 'valid_until', q.valid_until, 'subtotal', q.subtotal, 'discount', q.discount, 'delivery_fee', q.delivery_fee, 'total', q.total,
      'customer_message', q.customer_message,
      'items', coalesce((select jsonb_agg(jsonb_build_object('name', qi.name, 'description', qi.description, 'quantity', qi.quantity, 'unit', qi.unit, 'unit_price', qi.unit_price, 'subtotal', qi.subtotal) order by qi.sort_order) from pmb.quotation_items qi where qi.quotation_id = q.id), '[]'::jsonb)));
  end if;
  if o.id is not null then
    v_out := v_out || jsonb_build_object('order', jsonb_build_object(
      'reference', o.reference, 'status', o.status, 'payment_status', o.payment_status, 'total', o.total, 'payment_required', o.payment_required,
      'amount_paid', o.amount_paid, 'balance_due', o.balance_due, 'delivery_fee', o.delivery_fee,
      'payment_instructions', s.payment_instructions,
      'payments', coalesce((select jsonb_agg(jsonb_build_object('reference', p.reference, 'amount', p.amount, 'method', p.method, 'status', p.status, 'paid_at', p.paid_at) order by p.created_at) from pmb.payments p where p.order_id = o.id and p.status in ('PENDING','SUCCESS')), '[]'::jsonb),
      'timeline', coalesce((select jsonb_agg(jsonb_build_object('event', e.event, 'at', e.created_at) order by e.created_at) from pmb.order_events e where e.order_id = o.id and e.event in ('CREATED','CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY','DELIVERED','COMPLETED','CANCELLED')), '[]'::jsonb)));
  end if;
  return v_out;
end $$;

-- 4. submit_request: repeat plan, and collection only for categories that allow it
create or replace function pmb.submit_request(p_customer jsonb, p_items jsonb, p_details jsonb, p_honeypot text default null) returns jsonb
language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare
  v_name text := btrim(coalesce(p_customer->>'name', ''));
  v_phone text := btrim(coalesce(p_customer->>'phone', ''));
  v_email text := nullif(btrim(coalesce(p_customer->>'email', '')), '');
  v_method text := coalesce(nullif(p_details->>'fulfillment_method', ''), 'DELIVERY');
  v_zone bigint := nullif(p_details->>'delivery_zone_id', '')::bigint;
  v_dtype text := nullif(p_details->>'delivery_type', '');
  v_addr text := nullif(btrim(coalesce(p_details->>'delivery_address', '')), '');
  v_date date := nullif(p_details->>'event_date', '')::date;
  v_time time := nullif(p_details->>'event_time', '')::time;
  v_freq text := nullif(upper(btrim(coalesce(p_details->>'recurrence_frequency', ''))), '');
  v_cnt int := nullif(p_details->>'recurrence_count', '')::int;
  v_min int;
  v_cust bigint; v_req uuid; v_ref text; v_token uuid; i jsonb; p jsonb; v_n int := 0; v_key text;
begin
  if coalesce(p_honeypot, '') <> '' then raise exception 'Could not submit your request'; end if;
  if length(v_name) < 2 or length(v_name) > 120 then raise exception 'Please enter your name'; end if;
  v_key := right(regexp_replace(v_phone, '\D', '', 'g'), 9);
  if length(v_key) < 9 or length(v_phone) > 20 then raise exception 'Please enter a valid phone number'; end if;
  if v_email is not null and (v_email !~* '^[^@\s]+@[^@\s]+\.[^@\s]+$' or length(v_email) > 200) then raise exception 'Please enter a valid email address'; end if;
  if v_method not in ('DELIVERY','CUSTOMER_COLLECTION','ON_SITE_SERVICE') then raise exception 'Invalid fulfilment method'; end if;
  if p_items is null or jsonb_typeof(p_items) <> 'array' or jsonb_array_length(p_items) < 1 then raise exception 'Please add at least one item'; end if;
  if jsonb_array_length(p_items) > 30 then raise exception 'Too many items in one request'; end if;
  if length(coalesce(p_details->>'notes', '')) > 2000 or length(coalesce(p_details->>'location', '')) > 300 then raise exception 'Text is too long'; end if;
  if v_date is not null and v_date < (now() at time zone 'Africa/Nairobi')::date then raise exception 'The event date cannot be in the past'; end if;
  if v_freq is not null then
    if v_freq not in ('WEEKLY','FORTNIGHTLY','MONTHLY') then raise exception 'Invalid repeat schedule'; end if;
    select recurring_min_deliveries into v_min from pmb.settings where id = 1;
    if v_cnt is null or v_cnt < v_min or v_cnt > 52 then raise exception 'A regular order needs between % and 52 deliveries', v_min; end if;
    if v_date is null then raise exception 'Please choose the date of your first delivery'; end if;
  else
    v_cnt := null;
  end if;
  if v_method = 'DELIVERY' then
    if v_zone is null or not exists (select 1 from pmb.delivery_zones z where z.id = v_zone and z.is_active) then raise exception 'Please choose a delivery area'; end if;
    if v_addr is null then raise exception 'Please enter a delivery address'; end if;
    if v_dtype is null then v_dtype := 'standard'; end if;
    if v_dtype not in ('standard','door_to_door','beyond_stage') then raise exception 'Invalid delivery type'; end if;
  else
    v_zone := null; v_dtype := null; v_addr := null;
  end if;
  if v_method = 'CUSTOMER_COLLECTION' and exists (
    select 1 from jsonb_array_elements(p_items) it join pmb.products pr on pr.id = (it->>'id')::bigint and coalesce(it->>'type', 'product') = 'product'
    join pmb.categories c on c.id = pr.category_id where not c.allow_collection) then
    raise exception 'Our catering is delivered by our team so it arrives looking its best. Please choose delivery or on-site service';
  end if;
  if (select count(*) from pmb.requests r join pmb.customers c on c.id = r.customer_id where c.phone_key = v_key and r.created_at > now() - interval '1 hour') >= 5
     or (select count(*) from pmb.requests where created_at > now() - interval '1 hour') >= 300 then
    raise exception 'Too many requests right now. Please call us to place your order';
  end if;

  insert into pmb.customers(name, phone, email) values (v_name, v_phone, v_email) returning id into v_cust;
  v_ref := pmb.next_ref('request', 'REQ');
  insert into pmb.requests(reference, customer_id, fulfillment_method, delivery_zone_id, delivery_type, delivery_address, event_date, event_time, location, notes, recurrence_frequency, recurrence_count)
  values (v_ref, v_cust, v_method, v_zone, v_dtype, v_addr, v_date, v_time, nullif(btrim(coalesce(p_details->>'location','')), ''), nullif(btrim(coalesce(p_details->>'notes','')), ''), v_freq, v_cnt)
  returning id, access_token into v_req, v_token;

  for i in select * from jsonb_array_elements(p_items) loop
    v_n := v_n + 1;
    p := pmb.price_item(coalesce(i->>'type', 'product'), (i->>'id')::bigint, nullif(i->>'quantity', '')::numeric, coalesce(i->'options', '[]'::jsonb));
    insert into pmb.request_items(request_id, item_type, product_id, service_id, name, quantity, unit, options, pricing_type, pricing_status, unit_price, subtotal, pricing_breakdown, notes)
    values (v_req, coalesce(i->>'type', 'product'),
      case when coalesce(i->>'type', 'product') = 'product' then (i->>'id')::bigint end,
      case when i->>'type' = 'service' then (i->>'id')::bigint end,
      p->>'name', (p->>'quantity')::numeric, p->>'unit', p->'options', p->>'pricing_type', p->>'pricing_status',
      (p->>'unit_price')::numeric, (p->>'subtotal')::numeric, p->'breakdown', left(nullif(btrim(coalesce(i->>'notes','')), ''), 500));
  end loop;
  insert into pmb.request_events(request_id, event, detail) values (v_req, 'SUBMITTED', 'Request submitted by customer' || case when v_freq is not null then ' (regular order: ' || v_cnt || ' deliveries, ' || lower(v_freq) || ')' else '' end);
  return jsonb_build_object('reference', v_ref, 'token', v_token);
end $$;

-- 5. Staff: create the repeat deliveries once the first quotation is accepted
create or replace function pmb.schedule_recurring(p_order uuid) returns int language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare o record; r record; q record; i int; v_date date; v_req uuid; v_q uuid; v_n int := 0;
begin
  perform pmb._need_staff();
  select * into o from pmb.orders where id = p_order for update;
  if not found then raise exception 'Order not found'; end if;
  select * into r from pmb.requests where id = o.request_id;
  if r.recurrence_frequency is null then raise exception 'This is not a regular order'; end if;
  if o.status = 'CANCELLED' then raise exception 'This order is cancelled'; end if;
  if exists (select 1 from pmb.requests where recurrence_parent_id = r.id) then raise exception 'The repeat deliveries are already scheduled'; end if;
  select * into q from pmb.quotations where id = o.quotation_id;
  for i in 1 .. r.recurrence_count - 1 loop
    v_date := case r.recurrence_frequency when 'WEEKLY' then r.event_date + 7 * i when 'FORTNIGHTLY' then r.event_date + 14 * i
      else (r.event_date + make_interval(months => i))::date end;
    insert into pmb.requests(reference, customer_id, status, fulfillment_method, delivery_zone_id, delivery_type, delivery_address, event_date, event_time, location, notes, recurrence_parent_id, delivery_number)
    values (pmb.next_ref('request', 'REQ'), r.customer_id, 'READY_FOR_CHECKOUT', r.fulfillment_method, r.delivery_zone_id, r.delivery_type, r.delivery_address, v_date, r.event_time, r.location, r.notes, r.id, i + 1)
    returning id into v_req;
    insert into pmb.request_items(request_id, item_type, product_id, service_id, name, quantity, unit, options, pricing_type, pricing_status, unit_price, subtotal, pricing_breakdown, notes)
      select v_req, item_type, product_id, service_id, name, quantity, unit, options, pricing_type, pricing_status, unit_price, subtotal, pricing_breakdown, notes from pmb.request_items where request_id = r.id;
    insert into pmb.quotations(request_id, reference, status, sent_at, accepted_at, valid_until, subtotal, discount, delivery_fee, total, notes, customer_message, created_by)
      values (v_req, pmb.next_ref('quotation', 'QUO'), 'ACCEPTED', now(), now(), now(), q.subtotal, q.discount, q.delivery_fee, q.total,
        'Regular order, delivery ' || (i + 1) || ' of ' || r.recurrence_count || ', same terms as ' || q.reference, q.customer_message, auth.uid())
      returning id into v_q;
    insert into pmb.quotation_items(quotation_id, name, description, quantity, unit, unit_price, subtotal, options, needs_price, sort_order)
      select v_q, name, description, quantity, unit, unit_price, subtotal, options, false, sort_order from pmb.quotation_items where quotation_id = q.id;
    perform pmb._create_order(v_q, auth.uid());
    v_n := v_n + 1;
  end loop;
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, 'REPEATS_SCHEDULED', v_n || ' repeat deliveries created', auth.uid());
  insert into pmb.request_events(request_id, event, detail, actor) values (r.id, 'REPEATS_SCHEDULED', v_n || ' repeat deliveries created', auth.uid());
  return v_n;
end $$;
revoke execute on function pmb.schedule_recurring(uuid) from public, anon;
grant execute on function pmb.schedule_recurring(uuid) to authenticated;

-- 6. Menu: Tea Baskets, Weddings section (Wedding Feast plus the tiered cake), category order
update pmb.categories set sort_order = 4 where slug = 'open-basket-breakfast';
update pmb.categories set sort_order = 5 where slug = 'cakes';
update pmb.categories set sort_order = 7 where slug = 'juices';
update pmb.categories set name = 'Weddings', slug = 'weddings', sort_order = 6, description = 'The feast, the cake and the drinks for your big day' where slug = 'wedding-cakes';
insert into pmb.categories(name, slug, description, is_active, sort_order, allow_collection) values ('Tea Baskets', 'tea-baskets', 'Tea breaks for meetings, fellowships and AGMs', true, 3, false);

with c as (select id from pmb.categories where slug = 'tea-baskets'),
p as (insert into pmb.products(category_id, name, slug, description, short_description, image_url, pricing_type, base_price, unit, minimum_quantity, is_available, requires_review, status, sort_order)
  select c.id, v.name, v.slug, v.descr, v.short, '/img/tea-basket.jpg', 'PER_PERSON', v.price, 'person', 20, true, false, 'ACTIVE', v.so from c,
  (values
   ('Small Tea Basket','small-tea-basket',60,1,'Tea and one snack: Mandazi.','Your choice of tea with a Mandazi, served per person and delivered ready to pour. Snacks may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes. PMB is not halal certified.'),
   ('Medium Tea Basket','medium-tea-basket',105,2,'Tea with Mandazi and Samosa.','Your choice of tea with a Mandazi and a Samosa, served per person and delivered ready to pour. Snacks may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes. PMB is not halal certified.'),
   ('Large Tea Basket','large-tea-basket',165,3,'Tea with Mandazi, Samosa and Bhajia.','Your choice of tea with a Mandazi, a Samosa and Bhajia, made for longer meetings and AGMs. Snacks may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes. PMB is not halal certified.')
  ) as v(name, slug, price, so, short, descr) returning id),
o as (insert into pmb.product_options(product_id, name, is_required, per_unit, sort_order) select id, 'Tea', true, false, 1 from p returning id)
insert into pmb.product_option_values(product_option_id, name, price_modifier, is_available, sort_order)
  select o.id, v.n, 0, true, v.s from o, (values ('Spiced Tea',1),('Black Tea',2),('White Tea (with milk)',3)) as v(n, s);

with c as (select id from pmb.categories where slug = 'weddings'),
p as (insert into pmb.products(category_id, name, slug, description, short_description, image_url, pricing_type, base_price, unit, minimum_quantity, is_available, requires_review, status, sort_order)
  select c.id, 'Wedding Feast', 'wedding-feast',
   'A full meal for your guests, planned with you and served by our team. Tell us your guest count and how you would like it served, then use the notes to share the menu you have in mind, or ask for our suggestions. We build the menu with you, arrange a tasting, and send a full quotation covering the food, our service team and delivery. Add a Tiered Wedding Cake and a Fresh Juice Dispenser to complete the day. PMB is not halal certified. Dishes may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes.',
   'Planned with you, served by our team. We quote after a chat.', '/img/wedding-feast.jpg', 'PER_PERSON', 0, 'person', 50, true, true, 'ACTIVE', 10 from c returning id),
o as (insert into pmb.product_options(product_id, name, is_required, per_unit, sort_order) select id, 'How should it be served?', true, false, 1 from p returning id)
insert into pmb.product_option_values(product_option_id, name, price_modifier, is_available, sort_order)
  select o.id, v.n, 0, true, v.s from o, (values ('Buffet served by our team',1),('Plated service by our team',2),('Packed per guest',3)) as v(n, s);
