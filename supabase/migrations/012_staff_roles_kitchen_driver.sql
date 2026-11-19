-- Staff roles: owner (super admin), manager, staff (front desk), cashier, cook, driver.
-- Cooks and drivers never read tables directly; they use narrow functions that return only what their job needs.

do $$ declare c record; begin
  for c in select conname from pg_constraint where conrelid = 'pmb.staff'::regclass and contype = 'c' loop
    execute format('alter table pmb.staff drop constraint %I', c.conname);
  end loop;
end $$;
alter table pmb.staff add constraint staff_role_check check (role in ('owner','manager','staff','cashier','cook','driver'));
alter table pmb.fulfillments add column if not exists driver_id uuid references auth.users(id) on delete set null;

-- Office roles keep their wide read access. Cooks and drivers are NOT office roles.
create or replace function pmb.is_staff() returns boolean language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role in ('owner','manager','staff','cashier')) $$;
create or replace function pmb.is_kitchen() returns boolean language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role = 'cook') $$;
create or replace function pmb.is_driver() returns boolean language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role = 'driver') $$;
create or replace function pmb.is_finance() returns boolean language sql stable security definer set search_path to 'pmb','pg_temp' as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role in ('owner','manager','cashier')) $$;
create or replace function pmb._need_finance() returns void language plpgsql stable security definer set search_path to 'pmb','pg_temp' as $$
begin if not pmb.is_finance() then raise exception 'Finance roles only' using errcode = '42501'; end if; end $$;

-- Cashiers can confirm, reject and reverse payments (managers and owners still can).
do $$ declare f text; d text; begin
  foreach f in array array['confirm_payment','reject_payment','reverse_payment'] loop
    select pg_get_functiondef(p.oid) into d from pg_proc p where p.pronamespace = 'pmb'::regnamespace and p.proname = f;
    d := replace(d, 'pmb._need_manager()', 'pmb._need_finance()');
    execute d;
  end loop;
end $$;

create or replace function pmb.add_staff_by_email(p_email text, p_name text, p_role text) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare v_uid uuid;
begin
  perform pmb._need_owner();
  if p_role not in ('owner','manager','staff','cashier','cook','driver') then raise exception 'Invalid role'; end if;
  select id into v_uid from auth.users where lower(email) = lower(btrim(p_email));
  if v_uid is null then raise exception 'That person has no login yet. Invite them first'; end if;
  insert into pmb.staff(user_id, full_name, role) values (v_uid, btrim(p_name), p_role)
  on conflict (user_id) do update set full_name = excluded.full_name, role = excluded.role, active = true;
end $$;
create or replace function pmb.update_staff(p_user uuid, p_role text, p_active boolean) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
begin
  perform pmb._need_owner();
  if p_user = auth.uid() then raise exception 'You cannot change your own access'; end if;
  if p_role not in ('owner','manager','staff','cashier','cook','driver') then raise exception 'Invalid role'; end if;
  update pmb.staff set role = p_role, active = p_active where user_id = p_user;
  if not exists (select 1 from pmb.staff where role = 'owner' and active) then raise exception 'There must always be one active owner'; end if;
end $$;

-- Moving an order along: office roles any step; cooks only start preparing and mark ready; drivers only their own deliveries.
create or replace function pmb.advance_order(p_order uuid, p_status text, p_note text default null) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare o record; ok boolean; f_status text;
begin
  select * into o from pmb.orders where id = p_order for update;
  if not found then raise exception 'Order not found'; end if;
  if not pmb.is_staff() then
    if pmb.is_kitchen() then
      if not (o.status in ('CONFIRMED','PREPARING') and p_status in ('PREPARING','READY')) then raise exception 'Cooks can start preparing and mark an order ready'; end if;
    elsif pmb.is_driver() then
      if o.fulfillment_method <> 'DELIVERY' or p_status not in ('OUT_FOR_DELIVERY','DELIVERED','READY') then raise exception 'Drivers only handle deliveries'; end if;
      if (select driver_id from pmb.fulfillments where order_id = o.id) is distinct from auth.uid() then raise exception 'Take this delivery first'; end if;
    else
      raise exception 'Not allowed' using errcode = '42501';
    end if;
  end if;
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

-- Kitchen board: what to prepare, with no prices, phone numbers or addresses.
create or replace function pmb.kitchen_board() returns jsonb language plpgsql stable security definer set search_path to 'pmb','pg_temp' as $$
declare v_today date := (now() at time zone 'Africa/Nairobi')::date; v_orders jsonb; v_tot jsonb;
begin
  if not (pmb.is_staff() or pmb.is_kitchen()) then raise exception 'Not allowed' using errcode = '42501'; end if;
  select coalesce(jsonb_agg(x order by (x->>'event_date') nulls last, (x->>'event_time') nulls last, (x->>'reference')), '[]'::jsonb) into v_orders from (
    select jsonb_build_object('id', o.id, 'reference', o.reference, 'status', o.status, 'event_date', o.event_date, 'event_time', o.event_time,
      'fulfillment_method', o.fulfillment_method, 'notes', r.notes,
      'items', coalesce((select jsonb_agg(jsonb_build_object('name', ri.name, 'quantity', ri.quantity, 'unit', ri.unit, 'notes', ri.notes,
          'choices', ri.options) order by ri.created_at)
        from pmb.request_items ri where ri.request_id = o.request_id), '[]'::jsonb)) x
    from pmb.orders o join pmb.requests r on r.id = o.request_id
    where o.status in ('CONFIRMED','PREPARING','READY') and (o.event_date is null or o.event_date >= v_today - 1)) s;
  select coalesce(jsonb_agg(jsonb_build_object('event_date', t.event_date, 'name', t.name, 'unit', t.unit, 'quantity', t.q) order by t.event_date nulls last, t.name), '[]'::jsonb) into v_tot from (
    select o.event_date, ri.name, ri.unit, sum(ri.quantity) q
    from pmb.orders o join pmb.request_items ri on ri.request_id = o.request_id
    where o.status in ('CONFIRMED','PREPARING','READY') and (o.event_date is null or o.event_date >= v_today - 1)
    group by o.event_date, ri.name, ri.unit) t;
  return jsonb_build_object('orders', v_orders, 'totals', v_tot);
end $$;

-- Driver board: deliveries with the contact and address needed to deliver, and nothing about prices except the balance.
create or replace function pmb.driver_board() returns jsonb language plpgsql stable security definer set search_path to 'pmb','pg_temp' as $$
declare v_today date := (now() at time zone 'Africa/Nairobi')::date; v_uid uuid := auth.uid();
begin
  if not (pmb.is_staff() or pmb.is_driver()) then raise exception 'Not allowed' using errcode = '42501'; end if;
  return coalesce((select jsonb_agg(x order by (x->>'event_date') nulls last, (x->>'event_time') nulls last, (x->>'reference')) from (
    select jsonb_build_object('id', o.id, 'reference', o.reference, 'status', o.status, 'event_date', o.event_date, 'event_time', o.event_time,
      'zone', z.name, 'delivery_type', o.delivery_type, 'address', o.delivery_address, 'location', o.location, 'delivery_notes', o.delivery_notes,
      'customer_name', o.customer_name, 'customer_phone', o.customer_phone, 'balance_due', o.balance_due,
      'driver_id', f.driver_id, 'driver_name', st.full_name, 'mine', f.driver_id = v_uid,
      'items', coalesce((select jsonb_agg(jsonb_build_object('name', oi.name, 'quantity', oi.quantity, 'unit', oi.unit)) from pmb.order_items oi where oi.order_id = o.id), '[]'::jsonb)) x
    from pmb.orders o left join pmb.delivery_zones z on z.id = o.delivery_zone_id
      left join pmb.fulfillments f on f.order_id = o.id left join pmb.staff st on st.user_id = f.driver_id
    where o.fulfillment_method = 'DELIVERY' and o.status in ('CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY','DELIVERED')
      and (o.event_date is null or o.event_date >= v_today - 1)
      and (pmb.is_staff() or f.driver_id is null or f.driver_id = v_uid)) s), '[]'::jsonb);
end $$;

create or replace function pmb.claim_delivery(p_order uuid) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare o record;
begin
  if not pmb.is_driver() then raise exception 'Drivers only' using errcode = '42501'; end if;
  select * into o from pmb.orders where id = p_order for update;
  if not found or o.fulfillment_method <> 'DELIVERY' or o.status not in ('CONFIRMED','PREPARING','READY') then raise exception 'This delivery cannot be taken now'; end if;
  update pmb.fulfillments set driver_id = auth.uid() where order_id = p_order and driver_id is null;
  if not found then raise exception 'Someone else already has this delivery'; end if;
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, 'DRIVER_ASSIGNED', 'Driver took the delivery', auth.uid());
end $$;

create or replace function pmb.assign_driver(p_order uuid, p_driver uuid) returns void language plpgsql security definer set search_path to 'pmb','pg_temp' as $$
declare o record; n text;
begin
  perform pmb._need_staff();
  select * into o from pmb.orders where id = p_order for update;
  if not found or o.fulfillment_method <> 'DELIVERY' then raise exception 'Only deliveries have a driver'; end if;
  if o.status in ('CANCELLED','COMPLETED','DELIVERED') then raise exception 'This order is already finished'; end if;
  if p_driver is not null then
    select full_name into n from pmb.staff where user_id = p_driver and active and role = 'driver';
    if n is null then raise exception 'That person is not an active driver'; end if;
  end if;
  update pmb.fulfillments set driver_id = p_driver where order_id = p_order;
  insert into pmb.order_events(order_id, event, detail, actor) values (p_order, 'DRIVER_ASSIGNED', coalesce(n, 'Driver removed'), auth.uid());
end $$;

revoke execute on function pmb.is_kitchen(), pmb.is_driver(), pmb.is_finance(), pmb._need_finance(), pmb.kitchen_board(), pmb.driver_board(), pmb.claim_delivery(uuid), pmb.assign_driver(uuid, uuid) from public, anon;
grant execute on function pmb.is_kitchen(), pmb.is_driver(), pmb.is_finance(), pmb.kitchen_board(), pmb.driver_board(), pmb.claim_delivery(uuid), pmb.assign_driver(uuid, uuid) to authenticated;
