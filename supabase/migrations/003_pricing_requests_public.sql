alter table pmb.quotation_items add column if not exists needs_price boolean not null default false;

create or replace function pmb._need_staff() returns void
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
begin if not pmb.is_staff() then raise exception 'Not allowed' using errcode = '42501'; end if; end $$;
create or replace function pmb._need_manager() returns void
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
begin if not pmb.is_manager() then raise exception 'Managers only' using errcode = '42501'; end if; end $$;
create or replace function pmb._need_owner() returns void
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
begin if not pmb.is_owner() then raise exception 'Owner only' using errcode = '42501'; end if; end $$;

create or replace function pmb.next_ref(p_kind text, p_prefix text) returns text
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare v_year int := extract(year from (now() at time zone 'Africa/Nairobi'))::int; v_n int;
begin
  insert into pmb.doc_counters(kind, year, last_value) values (p_kind, v_year, 1)
  on conflict (kind, year) do update set last_value = pmb.doc_counters.last_value + 1
  returning last_value into v_n;
  return p_prefix || '-' || v_year || '-' || lpad(v_n::text, 4, '0');
end $$;

create or replace function pmb.public_settings() returns jsonb
language sql stable security definer set search_path = pmb, pg_temp as $$
  select jsonb_build_object('business_name', business_name, 'deposit_percent', deposit_percent,
    'quote_valid_days', quote_valid_days, 'contact_phone', contact_phone, 'contact_email', contact_email)
  from pmb.settings where id = 1
$$;

create or replace function pmb.delivery_fee(p_zone bigint, p_type text, p_subtotal numeric) returns numeric
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
declare z record; v numeric;
begin
  if p_zone is null then return 0; end if;
  select * into z from pmb.delivery_zones where id = p_zone;
  if not found then return 0; end if;
  if z.free_delivery_above is not null and coalesce(p_subtotal,0) >= z.free_delivery_above then return 0; end if;
  v := z.base_charge;
  if p_type = 'door_to_door' then v := v + z.door_to_door_surcharge;
  elsif p_type = 'beyond_stage' then v := v + z.beyond_stage_surcharge; end if;
  return round(v, 2);
end $$;

-- single pricing authority
create or replace function pmb.price_item(p_type text, p_id bigint, p_qty numeric, p_options jsonb default '[]'::jsonb) returns jsonb
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
declare
  r record; v_cat_active boolean; v_qty numeric; v_unit numeric; v_sub numeric;
  v_opt numeric := 0; v_status text := 'CALCULATED'; v_break jsonb := '[]'::jsonb;
  v_ids bigint[] := '{}'; v_cnt int; v_bad int; o record; v_tier record; v_opt_names text[] := '{}';
begin
  if p_type = 'product' then
    select p.* into r from pmb.products p where p.id = p_id;
    if not found then raise exception 'Item not found'; end if;
    select c.is_active into v_cat_active from pmb.categories c where c.id = r.category_id;
    if r.status <> 'ACTIVE' or not r.is_available or not coalesce(v_cat_active, false) then
      raise exception '% is currently unavailable', r.name;
    end if;
  elsif p_type = 'service' then
    select s.* into r from pmb.services s where s.id = p_id;
    if not found then raise exception 'Item not found'; end if;
    if r.status <> 'ACTIVE' or not r.is_available then raise exception '% is currently unavailable', r.name; end if;
  else
    raise exception 'Invalid item type';
  end if;

  if p_options is not null and jsonb_typeof(p_options) <> 'array' then raise exception 'Options must be a list'; end if;
  select coalesce(array_agg(x::bigint), '{}') into v_ids from jsonb_array_elements_text(coalesce(p_options, '[]'::jsonb)) x;

  if p_type = 'service' and cardinality(v_ids) > 0 then raise exception 'This item has no options'; end if;

  if p_type = 'product' then
    -- every chosen value must belong to this product, be available, and at most one per option
    select count(*) into v_bad from unnest(v_ids) vid
      where not exists (select 1 from pmb.product_option_values v join pmb.product_options po on po.id = v.product_option_id
                        where v.id = vid and po.product_id = p_id and v.is_available);
    if v_bad > 0 then raise exception 'One of the selected options is not valid for %', r.name; end if;
    select count(*) into v_bad from (select po.id from pmb.product_option_values v join pmb.product_options po on po.id = v.product_option_id
                        where v.id = any(v_ids) group by po.id having count(*) > 1) d;
    if v_bad > 0 then raise exception 'Choose only one value per option'; end if;
    for o in select po.id, po.name from pmb.product_options po where po.product_id = p_id and po.is_required loop
      if not exists (select 1 from pmb.product_option_values v where v.product_option_id = o.id and v.id = any(v_ids)) then
        raise exception 'Please choose %', o.name;
      end if;
    end loop;
    select coalesce(sum(v.price_modifier), 0), coalesce(array_agg(po.name || ': ' || v.name), '{}')
      into v_opt, v_opt_names
      from pmb.product_option_values v join pmb.product_options po on po.id = v.product_option_id where v.id = any(v_ids);
  end if;

  if r.pricing_type = 'CUSTOM' then
    v_qty := coalesce(p_qty, 1);
    if v_qty <= 0 then raise exception 'Quantity must be greater than zero'; end if;
    v_status := 'QUOTATION_REQUIRED';
    v_break := jsonb_build_array('Custom item. PMB will quote the price.');
    return jsonb_build_object('name', r.name, 'unit', r.unit, 'quantity', v_qty, 'pricing_type', r.pricing_type,
      'pricing_status', v_status, 'unit_price', null, 'subtotal', null, 'breakdown', v_break, 'options', to_jsonb(v_ids), 'option_names', to_jsonb(v_opt_names));
  elsif r.pricing_type = 'FIXED' then
    if r.base_price is null then raise exception '% has no price set', r.name; end if;
    v_qty := 1; v_unit := r.base_price; v_sub := round(r.base_price + v_opt, 2);
    v_break := jsonb_build_array('Fixed price ' || to_char(r.base_price, 'FM999,999,990.00'));
  else
    v_qty := p_qty;
    if v_qty is null or v_qty <= 0 then raise exception 'Please enter a quantity for %', r.name; end if;
    if (r.minimum_quantity is not null and v_qty < r.minimum_quantity) or (r.maximum_quantity is not null and v_qty > r.maximum_quantity) then
      raise exception 'Quantity for % must be between % and %', r.name, coalesce(r.minimum_quantity::text, 'any'), coalesce(r.maximum_quantity::text, 'any');
    end if;
    if r.pricing_type = 'TIERED' then
      select t.* into v_tier from pmb.price_tiers t
        where t.priceable_type = p_type and t.priceable_id = p_id and t.min_quantity <= v_qty and (t.max_quantity is null or v_qty <= t.max_quantity)
        order by t.min_quantity desc limit 1;
      if not found then
        return jsonb_build_object('name', r.name, 'unit', r.unit, 'quantity', v_qty, 'pricing_type', r.pricing_type,
          'pricing_status', 'QUOTATION_REQUIRED', 'unit_price', null, 'subtotal', null,
          'breakdown', jsonb_build_array('Quantity is above our standard brackets. PMB will quote the price.'), 'options', to_jsonb(v_ids), 'option_names', to_jsonb(v_opt_names));
      end if;
      v_unit := v_tier.unit_price;
    else
      if r.base_price is null then raise exception '% has no price set', r.name; end if;
      v_unit := r.base_price;
    end if;
    v_sub := round(round(v_unit * v_qty, 2) + v_opt, 2);
    v_break := jsonb_build_array(v_qty::text || ' x ' || to_char(v_unit, 'FM999,999,990.00'));
  end if;
  if v_opt <> 0 then v_break := v_break || to_jsonb('Options +' || to_char(v_opt, 'FM999,999,990.00')); end if;
  if r.requires_review then v_status := 'QUOTATION_REQUIRED'; v_break := v_break || to_jsonb('PMB will confirm this price in your quotation.'::text); end if;
  return jsonb_build_object('name', r.name, 'unit', r.unit, 'quantity', v_qty, 'pricing_type', r.pricing_type,
    'pricing_status', v_status, 'unit_price', v_unit, 'subtotal', v_sub, 'breakdown', v_break, 'options', to_jsonb(v_ids), 'option_names', to_jsonb(v_opt_names));
end $$;

-- public submission of a request
create or replace function pmb.submit_request(p_customer jsonb, p_items jsonb, p_details jsonb, p_honeypot text default null) returns jsonb
language plpgsql security definer set search_path = pmb, pg_temp as $$
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
  if v_method = 'DELIVERY' then
    if v_zone is null or not exists (select 1 from pmb.delivery_zones z where z.id = v_zone and z.is_active) then raise exception 'Please choose a delivery area'; end if;
    if v_addr is null then raise exception 'Please enter a delivery address'; end if;
    if v_dtype is null then v_dtype := 'standard'; end if;
    if v_dtype not in ('standard','door_to_door','beyond_stage') then raise exception 'Invalid delivery type'; end if;
  else
    v_zone := null; v_dtype := null; v_addr := null;
  end if;
  if (select count(*) from pmb.requests r join pmb.customers c on c.id = r.customer_id where c.phone_key = v_key and r.created_at > now() - interval '1 hour') >= 5
     or (select count(*) from pmb.requests where created_at > now() - interval '1 hour') >= 300 then
    raise exception 'Too many requests right now. Please call us to place your order';
  end if;

  insert into pmb.customers(name, phone, email) values (v_name, v_phone, v_email) returning id into v_cust;
  v_ref := pmb.next_ref('request', 'REQ');
  insert into pmb.requests(reference, customer_id, fulfillment_method, delivery_zone_id, delivery_type, delivery_address, event_date, event_time, location, notes)
  values (v_ref, v_cust, v_method, v_zone, v_dtype, v_addr, v_date, v_time, nullif(btrim(coalesce(p_details->>'location','')), ''), nullif(btrim(coalesce(p_details->>'notes','')), ''))
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
  insert into pmb.request_events(request_id, event, detail) values (v_req, 'SUBMITTED', 'Request submitted by customer');
  return jsonb_build_object('reference', v_ref, 'token', v_token);
end $$;

create or replace function pmb.expire_quotations() returns int
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare v_n int;
begin
  with e as (update pmb.quotations set status = 'EXPIRED', expired_at = now() where status = 'SENT' and valid_until < now() returning id)
  insert into pmb.quotation_events(quotation_id, event, detail) select id, 'EXPIRED', 'Validity period ended' from e;
  get diagnostics v_n = row_count;
  return v_n;
end $$;

-- order creation (internal)
create or replace function pmb._create_order(p_quotation uuid, p_actor uuid) returns uuid
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record; r record; c record; v_id uuid; v_dep numeric; v_pct numeric; v_total numeric;
begin
  select * into q from pmb.quotations where id = p_quotation for update;
  select * into r from pmb.requests where id = q.request_id;
  select * into c from pmb.customers where id = r.customer_id;
  if exists (select 1 from pmb.orders where request_id = r.id) then raise exception 'An order already exists for this request'; end if;
  select deposit_percent into v_pct from pmb.settings where id = 1;
  v_total := round(q.subtotal - q.discount + q.delivery_fee, 2);
  v_dep := round(v_total * v_pct / 100, 2);
  insert into pmb.orders(request_id, quotation_id, reference, status, payment_status, fulfillment_method, delivery_zone_id, delivery_type,
    event_date, event_time, location, delivery_address, customer_id, customer_name, customer_phone, customer_email,
    subtotal, discount, delivery_fee, total, payment_required, amount_paid, balance_due)
  values (r.id, q.id, pmb.next_ref('order', 'ORD'), 'PENDING_PAYMENT', 'UNPAID', r.fulfillment_method, r.delivery_zone_id, r.delivery_type,
    r.event_date, r.event_time, r.location, r.delivery_address, c.id, c.name, c.phone, c.email,
    q.subtotal, q.discount, q.delivery_fee, v_total, v_dep, 0, v_total)
  returning id into v_id;
  insert into pmb.order_items(order_id, quotation_item_id, name, description, quantity, unit, unit_price, subtotal, options)
    select v_id, qi.id, qi.name, qi.description, qi.quantity, qi.unit, qi.unit_price, qi.subtotal, qi.options from pmb.quotation_items qi where qi.quotation_id = q.id order by qi.sort_order;
  insert into pmb.order_events(order_id, event, detail, actor) values (v_id, 'CREATED', 'Order created from quotation ' || q.reference, p_actor);
  update pmb.requests set status = 'READY_FOR_CHECKOUT' where id = r.id;
  return v_id;
end $$;

-- tokened public page
create or replace function pmb.public_request(p_token uuid) returns jsonb
language plpgsql security definer set search_path = pmb, pg_temp as $$
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

create or replace function pmb.public_decide_quote(p_token uuid, p_decision text, p_message text default null) returns jsonb
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare r record; q record; v_order uuid;
begin
  if p_decision not in ('accept','decline') then raise exception 'Invalid decision'; end if;
  perform pmb.expire_quotations();
  select * into r from pmb.requests where access_token = p_token for update;
  if not found then raise exception 'Link not found'; end if;
  select * into q from pmb.quotations where request_id = r.id and status = 'SENT' for update;
  if not found then raise exception 'There is no open quotation to respond to'; end if;
  if q.valid_until is not null and q.valid_until < now() then raise exception 'This quotation has expired. Please contact us for a new one'; end if;
  if p_decision = 'accept' then
    update pmb.quotations set status = 'ACCEPTED', accepted_at = now() where id = q.id;
    insert into pmb.quotation_events(quotation_id, event, detail) values (q.id, 'ACCEPTED', left(p_message, 500));
    v_order := pmb._create_order(q.id, null);
    insert into pmb.request_events(request_id, event, detail) values (r.id, 'QUOTATION_ACCEPTED', 'Customer accepted ' || q.reference);
    return jsonb_build_object('result', 'accepted');
  else
    update pmb.quotations set status = 'DECLINED', declined_at = now() where id = q.id;
    insert into pmb.quotation_events(quotation_id, event, detail) values (q.id, 'DECLINED', left(p_message, 500));
    update pmb.requests set status = 'UNDER_REVIEW' where id = r.id;
    insert into pmb.request_events(request_id, event, detail) values (r.id, 'QUOTATION_DECLINED', left(coalesce(p_message, 'Customer declined the quotation'), 500));
    return jsonb_build_object('result', 'declined');
  end if;
end $$;

-- grants
revoke all on function pmb._need_staff(), pmb._need_manager(), pmb._need_owner(), pmb.next_ref(text,text), pmb.delivery_fee(bigint,text,numeric), pmb.expire_quotations(), pmb._create_order(uuid,uuid) from public, anon, authenticated;
grant execute on function pmb.public_settings(), pmb.price_item(text,bigint,numeric,jsonb), pmb.submit_request(jsonb,jsonb,jsonb,text), pmb.public_request(uuid), pmb.public_decide_quote(uuid,text,text) to anon, authenticated;
grant execute on function pmb.delivery_fee(bigint,text,numeric) to anon, authenticated;
