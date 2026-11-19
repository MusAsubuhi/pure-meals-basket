-- 013: customer accounts (history, receipts, reorder) and printable receipts.
-- Customers are still created by an order request. An account is linked to a customer record
-- only by proving possession of that request's private tracking token.

alter table pmb.customers add column if not exists user_id uuid references auth.users(id) on delete set null;
create index if not exists customers_user_idx on pmb.customers(user_id);

create or replace function pmb._my_customers() returns setof bigint
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select id from pmb.customers where user_id = auth.uid()
$$;

-- Link the signed-in account to the customers behind the given tracking tokens.
create or replace function pmb.claim_customer_account(p_tokens uuid[]) returns jsonb
language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare v_uid uuid := auth.uid(); c record; v_new int := 0; v_taken int := 0;
begin
  if v_uid is null then raise exception 'Please sign in first.'; end if;
  if coalesce(array_length(p_tokens, 1), 0) > 50 then raise exception 'Too many links at once.'; end if;
  for c in select distinct cu.id, cu.user_id from pmb.requests r join pmb.customers cu on cu.id = r.customer_id where r.access_token = any(p_tokens) loop
    if c.user_id is null then
      update pmb.customers set user_id = v_uid where id = c.id; v_new := v_new + 1;
    elsif c.user_id <> v_uid then
      v_taken := v_taken + 1;
    end if;
  end loop;
  return jsonb_build_object('added', v_new, 'unavailable', v_taken,
    'linked', (select count(*) from pmb.customers where user_id = v_uid));
end $$;

create or replace function pmb.my_account() returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select jsonb_build_object(
    'email', auth.jwt() ->> 'email',
    'name', (select name from pmb.customers where user_id = auth.uid() order by created_at limit 1),
    'linked', (select count(*) from pmb.customers where user_id = auth.uid()))
  where auth.uid() is not null
$$;

create or replace function pmb.my_orders() returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select coalesce(jsonb_agg(x order by x.created_at desc), '[]'::jsonb) from (
    select r.id, r.reference, r.access_token as token, r.status, r.fulfillment_method, r.event_date, r.created_at,
      (select string_agg(ri.name, ', ' order by ri.created_at) from pmb.request_items ri where ri.request_id = r.id) as summary,
      (select q.total from pmb.quotations q where q.request_id = r.id and q.status in ('SENT','ACCEPTED') order by q.created_at desc limit 1) as quoted_total,
      o.reference as order_reference, o.status as order_status, o.payment_status, o.total, o.amount_paid, o.balance_due
    from pmb.requests r left join pmb.orders o on o.request_id = r.id
    where r.customer_id in (select pmb._my_customers())
  ) x
$$;

create or replace function pmb.my_receipts() returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select coalesce(jsonb_agg(jsonb_build_object('id', p.id, 'receipt_no', p.reference, 'order_reference', o.reference,
    'amount', p.amount, 'method', p.method, 'paid_at', coalesce(p.paid_at, p.created_at)) order by coalesce(p.paid_at, p.created_at) desc), '[]'::jsonb)
  from pmb.payments p join pmb.orders o on o.id = p.order_id
  where p.status = 'SUCCESS' and o.customer_id in (select pmb._my_customers())
$$;

-- A receipt exists only for a confirmed payment (status SUCCESS). Reversed or rejected payments have none.
create or replace function pmb._receipt_json(p_payment uuid) returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select jsonb_build_object('receipt_no', p.reference, 'paid_at', coalesce(p.paid_at, p.created_at), 'amount', p.amount,
    'currency', p.currency, 'method', p.method, 'provider_reference', p.provider_reference,
    'order_reference', o.reference, 'order_total', o.total, 'amount_paid', o.amount_paid, 'balance_due', o.balance_due,
    'customer_name', o.customer_name,
    'business', jsonb_build_object('name', s.business_name, 'phone', s.contact_phone, 'email', s.contact_email))
  from pmb.payments p join pmb.orders o on o.id = p.order_id cross join pmb.settings s
  where p.id = p_payment and p.status = 'SUCCESS' and s.id = 1
$$;

create or replace function pmb.my_receipt(p_payment uuid) returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select pmb._receipt_json(p.id) from pmb.payments p join pmb.orders o on o.id = p.order_id
  where p.id = p_payment and o.customer_id in (select pmb._my_customers())
$$;

create or replace function pmb.public_receipt(p_token uuid, p_payment uuid) returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select pmb._receipt_json(p.id) from pmb.payments p
  join pmb.orders o on o.id = p.order_id join pmb.requests r on r.id = o.request_id
  where p.id = p_payment and r.access_token = p_token
$$;

-- Items of an earlier request, for "Order again". The site re-prices every line from the live catalogue.
create or replace function pmb.my_reorder(p_request uuid) returns jsonb
language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select coalesce(jsonb_agg(jsonb_build_object('type', ri.item_type, 'id', coalesce(ri.product_id, ri.service_id), 'name', ri.name,
    'quantity', ri.quantity, 'unit', ri.unit, 'options', coalesce(ri.options, '[]'::jsonb), 'notes', ri.notes,
    'collect', case when ri.item_type = 'product' then coalesce((select c.allow_collection from pmb.products pr join pmb.categories c on c.id = pr.category_id where pr.id = ri.product_id), true) else true end,
    'available', case when ri.item_type = 'product' then coalesce((select pr.status = 'ACTIVE' from pmb.products pr where pr.id = ri.product_id), false)
                      else coalesce((select sv.status = 'ACTIVE' from pmb.services sv where sv.id = ri.service_id), false) end)
    order by ri.created_at), '[]'::jsonb)
  from pmb.request_items ri join pmb.requests r on r.id = ri.request_id
  where ri.request_id = p_request and r.customer_id in (select pmb._my_customers())
$$;

-- Tracking page: expose the receipt id of each payment and the customer's email for the account prompt.
create or replace function pmb.public_request(p_token uuid) returns jsonb
language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
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
    'customer_email', (select email from pmb.customers where id = r.customer_id),
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
      'payments', coalesce((select jsonb_agg(jsonb_build_object('id', p.id, 'reference', p.reference, 'amount', p.amount, 'method', p.method, 'status', p.status, 'paid_at', p.paid_at) order by p.created_at) from pmb.payments p where p.order_id = o.id and p.status in ('PENDING','SUCCESS')), '[]'::jsonb),
      'timeline', coalesce((select jsonb_agg(jsonb_build_object('event', e.event, 'at', e.created_at) order by e.created_at) from pmb.order_events e where e.order_id = o.id and e.event in ('CREATED','CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY','DELIVERED','COMPLETED','CANCELLED')), '[]'::jsonb)));
  end if;
  return v_out;
end $$;

revoke execute on function pmb._my_customers(), pmb._receipt_json(uuid), pmb.claim_customer_account(uuid[]), pmb.my_account(), pmb.my_orders(), pmb.my_receipts(), pmb.my_receipt(uuid), pmb.my_reorder(uuid), pmb.public_receipt(uuid, uuid) from public, anon;
grant execute on function pmb.claim_customer_account(uuid[]), pmb.my_account(), pmb.my_orders(), pmb.my_receipts(), pmb.my_receipt(uuid), pmb.my_reorder(uuid) to authenticated;
grant execute on function pmb.public_receipt(uuid, uuid) to anon, authenticated;
