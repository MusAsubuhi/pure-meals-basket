create or replace function pmb._recalc_quotation(p_q uuid) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare v_sub numeric; q record;
begin
  select coalesce(sum(subtotal), 0) into v_sub from pmb.quotation_items where quotation_id = p_q;
  select * into q from pmb.quotations where id = p_q;
  if q.discount > v_sub then raise exception 'Discount cannot be more than the items total'; end if;
  update pmb.quotations set subtotal = round(v_sub, 2), total = round(v_sub - q.discount + q.delivery_fee, 2) where id = p_q;
end $$;

create or replace function pmb.set_request_status(p_request uuid, p_status text, p_message text default null) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare r record; ok boolean;
begin
  perform pmb._need_staff();
  select * into r from pmb.requests where id = p_request for update;
  if not found then raise exception 'Request not found'; end if;
  if exists (select 1 from pmb.orders where request_id = r.id) then raise exception 'This request already has an order'; end if;
  ok := case r.status
    when 'SUBMITTED' then p_status in ('UNDER_REVIEW','NEEDS_INFORMATION','DECLINED','CANCELLED')
    when 'UNDER_REVIEW' then p_status in ('NEEDS_INFORMATION','DECLINED','CANCELLED')
    when 'NEEDS_INFORMATION' then p_status in ('UNDER_REVIEW','DECLINED','CANCELLED')
    else false end;
  if not ok then raise exception 'A request that is % cannot move to %', r.status, p_status; end if;
  if p_status in ('DECLINED','CANCELLED','NEEDS_INFORMATION') and btrim(coalesce(p_message, '')) = '' then raise exception 'Please write a message for the customer'; end if;
  if p_status in ('DECLINED','CANCELLED') then
    update pmb.quotations set status = 'WITHDRAWN', withdrawn_at = now() where request_id = r.id and status in ('DRAFT','SENT');
  end if;
  update pmb.requests set status = p_status, staff_message = coalesce(nullif(btrim(p_message), ''), staff_message) where id = r.id;
  insert into pmb.request_events(request_id, event, detail, actor) values (r.id, p_status, left(p_message, 500), auth.uid());
end $$;

create or replace function pmb.build_quotation(p_request uuid) returns uuid
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare r record; v_q uuid; v_sub numeric; v_fee numeric;
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
  perform pmb._recalc_quotation(v_q);
  if r.status = 'SUBMITTED' then update pmb.requests set status = 'UNDER_REVIEW' where id = r.id; end if;
  insert into pmb.quotation_events(quotation_id, event, actor) values (v_q, 'CREATED', auth.uid());
  insert into pmb.request_events(request_id, event, detail, actor) values (r.id, 'QUOTATION_DRAFTED', 'Quotation drafted', auth.uid());
  return v_q;
end $$;

create or replace function pmb._draft_quotation(p_q uuid) returns record
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record;
begin
  perform pmb._need_staff();
  select * into q from pmb.quotations where id = p_q for update;
  if not found then raise exception 'Quotation not found'; end if;
  if q.status <> 'DRAFT' then raise exception 'Only a draft quotation can be edited'; end if;
  return q;
end $$;

create or replace function pmb.quotation_set_terms(p_q uuid, p_discount numeric, p_delivery_fee numeric, p_notes text, p_message text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record;
begin
  q := pmb._draft_quotation(p_q);
  if coalesce(p_discount, 0) < 0 or coalesce(p_delivery_fee, 0) < 0 then raise exception 'Amounts cannot be negative'; end if;
  update pmb.quotations set discount = round(coalesce(p_discount, 0), 2), delivery_fee = round(coalesce(p_delivery_fee, 0), 2),
    notes = nullif(btrim(p_notes), ''), customer_message = nullif(btrim(p_message), '') where id = p_q;
  perform pmb._recalc_quotation(p_q);
end $$;

create or replace function pmb.quotation_set_line(p_item uuid, p_unit_price numeric, p_quantity numeric) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare qi record; q record;
begin
  select * into qi from pmb.quotation_items where id = p_item;
  if not found then raise exception 'Line not found'; end if;
  q := pmb._draft_quotation(qi.quotation_id);
  if p_unit_price is null or p_unit_price < 0 or p_quantity is null or p_quantity <= 0 then raise exception 'Enter a valid price and quantity'; end if;
  update pmb.quotation_items set unit_price = round(p_unit_price, 2), quantity = p_quantity, subtotal = round(p_unit_price * p_quantity, 2), needs_price = false where id = p_item;
  perform pmb._recalc_quotation(qi.quotation_id);
end $$;

create or replace function pmb.quotation_add_line(p_q uuid, p_name text, p_quantity numeric, p_unit_price numeric, p_description text default null) returns uuid
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record; v_id uuid;
begin
  q := pmb._draft_quotation(p_q);
  if btrim(coalesce(p_name, '')) = '' then raise exception 'Enter a name for the line'; end if;
  if p_unit_price is null or p_unit_price < 0 or p_quantity is null or p_quantity <= 0 then raise exception 'Enter a valid price and quantity'; end if;
  insert into pmb.quotation_items(quotation_id, name, description, quantity, unit_price, subtotal, sort_order)
  values (p_q, btrim(p_name), nullif(btrim(p_description), ''), p_quantity, round(p_unit_price, 2), round(p_unit_price * p_quantity, 2),
    coalesce((select max(sort_order) + 1 from pmb.quotation_items where quotation_id = p_q), 1)) returning id into v_id;
  perform pmb._recalc_quotation(p_q);
  return v_id;
end $$;

create or replace function pmb.quotation_remove_line(p_item uuid) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare qi record; q record;
begin
  select * into qi from pmb.quotation_items where id = p_item;
  if not found then raise exception 'Line not found'; end if;
  q := pmb._draft_quotation(qi.quotation_id);
  delete from pmb.quotation_items where id = p_item;
  perform pmb._recalc_quotation(qi.quotation_id);
end $$;

create or replace function pmb.send_quotation(p_q uuid) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record; v_days int;
begin
  q := pmb._draft_quotation(p_q);
  if not exists (select 1 from pmb.quotation_items where quotation_id = p_q) then raise exception 'Add at least one line before sending'; end if;
  if exists (select 1 from pmb.quotation_items where quotation_id = p_q and needs_price) then raise exception 'Set a price for every custom line before sending'; end if;
  if (select total from pmb.quotations where id = p_q) <= 0 then raise exception 'The total must be more than zero'; end if;
  select quote_valid_days into v_days from pmb.settings where id = 1;
  update pmb.quotations set status = 'SENT', sent_at = now(), valid_until = now() + make_interval(days => v_days) where id = p_q;
  insert into pmb.quotation_events(quotation_id, event, actor) values (p_q, 'SENT', auth.uid());
  insert into pmb.request_events(request_id, event, detail, actor) values (q.request_id, 'QUOTATION_SENT', q.reference, auth.uid());
end $$;

create or replace function pmb.withdraw_quotation(p_q uuid, p_reason text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare q record;
begin
  perform pmb._need_staff();
  select * into q from pmb.quotations where id = p_q for update;
  if not found then raise exception 'Quotation not found'; end if;
  if q.status not in ('DRAFT','SENT') then raise exception 'A % quotation cannot be withdrawn', q.status; end if;
  update pmb.quotations set status = 'WITHDRAWN', withdrawn_at = now() where id = p_q;
  insert into pmb.quotation_events(quotation_id, event, detail, actor) values (p_q, 'WITHDRAWN', left(p_reason, 500), auth.uid());
end $$;

-- orders and payments
create or replace function pmb._recompute_order(p_order uuid) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare o record; v_paid numeric; v_ps text;
begin
  select * into o from pmb.orders where id = p_order for update;
  select coalesce(sum(amount), 0) into v_paid from pmb.payments where order_id = p_order and status = 'SUCCESS';
  v_ps := case when v_paid >= o.total and o.total > 0 then 'PAID' when v_paid > 0 then 'PARTIALLY_PAID' else 'UNPAID' end;
  update pmb.orders set amount_paid = v_paid, balance_due = greatest(0, o.total - v_paid), payment_status = v_ps where id = p_order;
  if o.status = 'PENDING_PAYMENT' and v_paid >= o.payment_required then
    update pmb.orders set status = 'CONFIRMED' where id = p_order;
    insert into pmb.order_events(order_id, event, detail) values (p_order, 'CONFIRMED', 'Required payment received');
    insert into pmb.fulfillments(order_id, method, delivery_fee, delivery_address, contact_name, contact_phone, scheduled_at)
      values (p_order, o.fulfillment_method, o.delivery_fee, o.delivery_address, o.customer_name, o.customer_phone,
        case when o.event_date is not null then ((o.event_date::text || ' ' || coalesce(o.event_time::text, '00:00:00'))::timestamp at time zone 'Africa/Nairobi') end)
      on conflict (order_id) do update set status = 'PENDING' where pmb.fulfillments.status = 'CANCELLED';
  end if;
end $$;

create or replace function pmb.record_payment(p_order uuid, p_amount numeric, p_method text, p_provider_ref text default null, p_paid_at timestamptz default null, p_note text default null, p_confirm boolean default false) returns uuid
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare o record; v_pending numeric; v_id uuid; v_ref text;
begin
  perform pmb._need_staff();
  select * into o from pmb.orders where id = p_order for update;
  if not found then raise exception 'Order not found'; end if;
  if o.status in ('CANCELLED','COMPLETED','DRAFT') then raise exception 'Payments cannot be recorded on a % order', o.status; end if;
  if p_method not in ('MPESA','CASH') then raise exception 'Choose M-Pesa or Cash'; end if;
  if p_amount is null or p_amount <= 0 then raise exception 'Enter an amount above zero'; end if;
  if p_paid_at is not null and p_paid_at > now() + interval '5 minutes' then raise exception 'The payment date cannot be in the future'; end if;
  if p_method = 'MPESA' and btrim(coalesce(p_provider_ref, '')) = '' then raise exception 'Enter the M-Pesa transaction code'; end if;
  select coalesce(sum(amount), 0) into v_pending from pmb.payments where order_id = p_order and status = 'PENDING';
  if p_amount > o.balance_due - v_pending then raise exception 'That is more than the balance still to be paid (%)', (o.balance_due - v_pending); end if;
  v_ref := pmb.next_ref('payment', 'PAY');
  begin
    insert into pmb.payments(order_id, reference, method, status, amount, provider_reference, paid_at, note, recorded_by)
    values (p_order, v_ref, p_method, 'PENDING', round(p_amount, 2), upper(nullif(btrim(p_provider_ref), '')), coalesce(p_paid_at, now()), left(p_note, 500), auth.uid())
    returning id into v_id;
  exception when unique_violation then
    raise exception 'That M-Pesa code has already been recorded';
  end;
  insert into pmb.payment_events(payment_id, event, detail, actor) values (v_id, 'RECORDED', p_method, auth.uid());
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, 'PAYMENT_RECORDED', v_ref || ' ' || p_amount::text, auth.uid());
  if p_confirm and pmb.is_manager() then perform pmb.confirm_payment(v_id); end if;
  return v_id;
end $$;

create or replace function pmb.confirm_payment(p_payment uuid) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare p record; o record;
begin
  perform pmb._need_manager();
  select * into p from pmb.payments where id = p_payment for update;
  if not found then raise exception 'Payment not found'; end if;
  if p.status <> 'PENDING' then raise exception 'Only a pending payment can be confirmed'; end if;
  if p.recorded_by is not distinct from auth.uid() and not pmb.is_owner() then raise exception 'Another manager must confirm payments you recorded'; end if;
  select * into o from pmb.orders where id = p.order_id for update;
  if p.amount > o.balance_due then raise exception 'This payment is more than the balance due'; end if;
  update pmb.payments set status = 'SUCCESS', confirmed_by = auth.uid() where id = p_payment;
  insert into pmb.payment_events(payment_id, event, actor) values (p_payment, 'CONFIRMED', auth.uid());
  insert into pmb.order_events(order_id, event, detail, actor) values (p.order_id, 'PAYMENT_CONFIRMED', p.reference, auth.uid());
  perform pmb._recompute_order(p.order_id);
end $$;

create or replace function pmb.reject_payment(p_payment uuid, p_reason text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare p record;
begin
  perform pmb._need_manager();
  select * into p from pmb.payments where id = p_payment for update;
  if not found then raise exception 'Payment not found'; end if;
  if p.status <> 'PENDING' then raise exception 'Only a pending payment can be rejected'; end if;
  if btrim(coalesce(p_reason, '')) = '' then raise exception 'Give a reason'; end if;
  update pmb.payments set status = 'FAILED', confirmed_by = auth.uid() where id = p_payment;
  insert into pmb.payment_events(payment_id, event, detail, actor) values (p_payment, 'REJECTED', left(p_reason, 500), auth.uid());
  insert into pmb.order_events(order_id, event, detail, actor) values (p.order_id, 'PAYMENT_REJECTED', p.reference, auth.uid());
end $$;

create or replace function pmb.reverse_payment(p_payment uuid, p_reason text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare p record; o record; v_paid numeric;
begin
  perform pmb._need_owner();
  select * into p from pmb.payments where id = p_payment for update;
  if not found then raise exception 'Payment not found'; end if;
  if p.status <> 'SUCCESS' then raise exception 'Only a confirmed payment can be reversed'; end if;
  if btrim(coalesce(p_reason, '')) = '' then raise exception 'Give a reason'; end if;
  select * into o from pmb.orders where id = p.order_id for update;
  if o.status not in ('PENDING_PAYMENT','CONFIRMED','CANCELLED') then raise exception 'A payment cannot be reversed once preparation has started'; end if;
  update pmb.payments set status = 'REVERSED' where id = p_payment;
  insert into pmb.payment_events(payment_id, event, detail, actor) values (p_payment, 'REVERSED', left(p_reason, 500), auth.uid());
  perform pmb._recompute_order(p.order_id);
  select amount_paid into v_paid from pmb.orders where id = p.order_id;
  if o.status = 'CONFIRMED' and v_paid < o.payment_required then
    update pmb.orders set status = 'PENDING_PAYMENT' where id = p.order_id;
    update pmb.fulfillments set status = 'CANCELLED' where order_id = p.order_id and status = 'PENDING';
    insert into pmb.order_events(order_id, event, detail, actor) values (p.order_id, 'PAYMENT_REVERSED', 'Order is awaiting payment again', auth.uid());
  end if;
end $$;

create or replace function pmb.advance_order(p_order uuid, p_status text, p_note text default null) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare o record; ok boolean; f_status text;
begin
  perform pmb._need_staff();
  select * into o from pmb.orders where id = p_order for update;
  if not found then raise exception 'Order not found'; end if;
  ok := case
    when o.status = 'CONFIRMED' then p_status = 'PREPARING'
    when o.status = 'PREPARING' then p_status = 'READY'
    when o.status = 'READY' and o.fulfillment_method = 'DELIVERY' then p_status = 'OUT_FOR_DELIVERY'
    when o.status = 'READY' then p_status = 'COMPLETED'
    when o.status = 'OUT_FOR_DELIVERY' then p_status in ('DELIVERED','READY')
    when o.status = 'DELIVERED' then p_status = 'COMPLETED'
    else false end;
  if not ok then raise exception 'An order that is % cannot move to %', o.status, p_status; end if;
  if p_status = 'COMPLETED' and o.balance_due > 0 then raise exception 'Collect the remaining balance of % before completing this order', o.balance_due; end if;
  if o.status = 'OUT_FOR_DELIVERY' and p_status = 'READY' and btrim(coalesce(p_note, '')) = '' then raise exception 'Say why the delivery failed'; end if;
  update pmb.orders set status = p_status where id = p_order;
  f_status := case when o.status = 'OUT_FOR_DELIVERY' and p_status = 'READY' then 'DELIVERY_FAILED' else p_status end;
  update pmb.fulfillments set status = f_status,
    started_at = case when p_status = 'PREPARING' then now() else started_at end,
    ready_at = case when p_status = 'READY' and f_status = 'READY' then now() else ready_at end,
    dispatched_at = case when p_status = 'OUT_FOR_DELIVERY' then now() else dispatched_at end,
    delivered_at = case when p_status = 'DELIVERED' then now() else delivered_at end,
    collected_at = case when p_status = 'COMPLETED' and o.fulfillment_method = 'CUSTOMER_COLLECTION' then now() else collected_at end,
    completed_at = case when p_status = 'COMPLETED' then now() else completed_at end,
    failure_reason = case when f_status = 'DELIVERY_FAILED' then left(p_note, 500) else failure_reason end,
    recipient_name = case when p_status = 'DELIVERED' then coalesce(nullif(btrim(p_note), ''), recipient_name) else recipient_name end
    where order_id = p_order;
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, case when f_status = 'DELIVERY_FAILED' then 'DELIVERY_FAILED' else p_status end, left(p_note, 500), auth.uid());
end $$;

create or replace function pmb.cancel_order(p_order uuid, p_reason text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare o record;
begin
  perform pmb._need_staff();
  select * into o from pmb.orders where id = p_order for update;
  if not found then raise exception 'Order not found'; end if;
  if o.status not in ('PENDING_PAYMENT','CONFIRMED') then raise exception 'An order that is % can no longer be cancelled here', o.status; end if;
  if btrim(coalesce(p_reason, '')) = '' then raise exception 'Give a reason'; end if;
  if o.amount_paid > 0 and not pmb.is_owner() then raise exception 'Only the owner can cancel an order that has payments. Refunds are handled outside the app'; end if;
  update pmb.orders set status = 'CANCELLED', cancel_reason = left(p_reason, 500) where id = p_order;
  update pmb.fulfillments set status = 'CANCELLED' where order_id = p_order;
  update pmb.payments set status = 'CANCELLED' where order_id = p_order and status = 'PENDING';
  update pmb.requests set status = 'CANCELLED', staff_message = left(p_reason, 500) where id = o.request_id;
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, 'CANCELLED', left(p_reason, 500), auth.uid());
end $$;

-- staff management
create or replace function pmb.add_staff_by_email(p_email text, p_name text, p_role text) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
declare v_uid uuid;
begin
  perform pmb._need_owner();
  if p_role not in ('owner','manager','staff') then raise exception 'Invalid role'; end if;
  select id into v_uid from auth.users where lower(email) = lower(btrim(p_email));
  if v_uid is null then raise exception 'That person has no login yet. Invite them first'; end if;
  insert into pmb.staff(user_id, full_name, role) values (v_uid, btrim(p_name), p_role)
  on conflict (user_id) do update set full_name = excluded.full_name, role = excluded.role, active = true;
end $$;

create or replace function pmb.update_staff(p_user uuid, p_role text, p_active boolean) returns void
language plpgsql security definer set search_path = pmb, pg_temp as $$
begin
  perform pmb._need_owner();
  if p_user = auth.uid() then raise exception 'You cannot change your own access'; end if;
  if p_role not in ('owner','manager','staff') then raise exception 'Invalid role'; end if;
  update pmb.staff set role = p_role, active = p_active where user_id = p_user;
  if not exists (select 1 from pmb.staff where role = 'owner' and active) then raise exception 'There must always be one active owner'; end if;
end $$;

create or replace function pmb.staff_dashboard() returns jsonb
language plpgsql stable security definer set search_path = pmb, pg_temp as $$
declare m_start timestamptz := date_trunc('month', now() at time zone 'Africa/Nairobi') at time zone 'Africa/Nairobi';
begin
  perform pmb._need_staff();
  return jsonb_build_object(
    'new_requests', (select count(*) from pmb.requests where status = 'SUBMITTED'),
    'under_review', (select count(*) from pmb.requests where status in ('UNDER_REVIEW','NEEDS_INFORMATION') and not exists (select 1 from pmb.quotations q where q.request_id = requests.id and q.status in ('DRAFT','SENT','ACCEPTED'))),
    'quotes_draft', (select count(*) from pmb.quotations where status = 'DRAFT'),
    'quotes_waiting', (select count(*) from pmb.quotations where status = 'SENT'),
    'quotes_expiring', (select count(*) from pmb.quotations where status = 'SENT' and valid_until < now() + interval '48 hours'),
    'awaiting_payment', (select count(*) from pmb.orders where status = 'PENDING_PAYMENT'),
    'payments_to_confirm', (select count(*) from pmb.payments where status = 'PENDING'),
    'in_progress', (select count(*) from pmb.orders where status in ('CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY')),
    'upcoming_7d', (select count(*) from pmb.orders where status in ('CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY') and event_date between (now() at time zone 'Africa/Nairobi')::date and (now() at time zone 'Africa/Nairobi')::date + 7),
    'balance_outstanding', (select coalesce(sum(balance_due), 0) from pmb.orders where status not in ('CANCELLED','DRAFT')),
    'collected_this_month', (select coalesce(sum(amount), 0) from pmb.payments where status = 'SUCCESS' and paid_at >= m_start));
end $$;

-- grants
revoke all on function pmb._recalc_quotation(uuid), pmb._draft_quotation(uuid), pmb._recompute_order(uuid) from public, anon, authenticated;
grant execute on function pmb.set_request_status(uuid,text,text), pmb.build_quotation(uuid), pmb.quotation_set_terms(uuid,numeric,numeric,text,text),
  pmb.quotation_set_line(uuid,numeric,numeric), pmb.quotation_add_line(uuid,text,numeric,numeric,text), pmb.quotation_remove_line(uuid),
  pmb.send_quotation(uuid), pmb.withdraw_quotation(uuid,text), pmb.record_payment(uuid,numeric,text,text,timestamptz,text,boolean),
  pmb.confirm_payment(uuid), pmb.reject_payment(uuid,text), pmb.reverse_payment(uuid,text), pmb.advance_order(uuid,text,text),
  pmb.cancel_order(uuid,text), pmb.add_staff_by_email(text,text,text), pmb.update_staff(uuid,text,boolean), pmb.staff_dashboard() to authenticated;

-- first owner: Asubuhi (existing login)
insert into pmb.staff(user_id, full_name, role)
select id, 'Asubuhi', 'owner' from auth.users where lower(email) = 'musasubuhi@gmail.com'
on conflict (user_id) do nothing;

-- hourly expiry of old quotations
select cron.schedule('pmb-expire-quotations', '7 * * * *', $c$select pmb.expire_quotations()$c$);
