-- helper functions (SECURITY DEFINER, fixed search path)
create or replace function pmb.is_staff() returns boolean
language sql stable security definer set search_path = pmb, pg_temp as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active)
$$;
create or replace function pmb.is_manager() returns boolean
language sql stable security definer set search_path = pmb, pg_temp as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role in ('owner','manager'))
$$;
create or replace function pmb.is_owner() returns boolean
language sql stable security definer set search_path = pmb, pg_temp as $$
  select exists (select 1 from pmb.staff s where s.user_id = (select auth.uid()) and s.active and s.role = 'owner')
$$;
create or replace function pmb.my_role() returns text
language sql stable security definer set search_path = pmb, pg_temp as $$
  select s.role from pmb.staff s where s.user_id = (select auth.uid()) and s.active
$$;

-- lock everything down first
revoke all on schema pmb from public;
grant usage on schema pmb to anon, authenticated;
revoke all on all tables in schema pmb from public, anon, authenticated;
revoke all on all functions in schema pmb from public, anon, authenticated;
alter default privileges in schema pmb revoke all on tables from public, anon, authenticated;
alter default privileges in schema pmb revoke all on functions from public, anon, authenticated;

grant execute on function pmb.is_staff(), pmb.is_manager(), pmb.is_owner(), pmb.my_role() to authenticated;

-- enable RLS everywhere
do $$
declare t text;
begin
  for t in select tablename from pg_tables where schemaname='pmb' loop
    execute format('alter table pmb.%I enable row level security', t);
  end loop;
end $$;

-- catalogue: public can read live items; managers write
grant select on pmb.categories, pmb.products, pmb.services, pmb.product_options, pmb.product_option_values, pmb.price_tiers, pmb.delivery_zones to anon, authenticated;
grant insert, update, delete on pmb.categories, pmb.products, pmb.services, pmb.product_options, pmb.product_option_values, pmb.price_tiers, pmb.delivery_zones to authenticated;

create policy categories_read on pmb.categories for select to anon, authenticated using (is_active or (select pmb.is_staff()));
create policy categories_write on pmb.categories for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy products_read on pmb.products for select to anon, authenticated using ((status = 'ACTIVE' and is_available) or (select pmb.is_staff()));
create policy products_write on pmb.products for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy services_read on pmb.services for select to anon, authenticated using ((status = 'ACTIVE' and is_available) or (select pmb.is_staff()));
create policy services_write on pmb.services for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy options_read on pmb.product_options for select to anon, authenticated using (exists (select 1 from pmb.products p where p.id = product_id and ((p.status='ACTIVE' and p.is_available) or (select pmb.is_staff()))));
create policy options_write on pmb.product_options for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy option_values_read on pmb.product_option_values for select to anon, authenticated using (is_available or (select pmb.is_staff()));
create policy option_values_write on pmb.product_option_values for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy tiers_read on pmb.price_tiers for select to anon, authenticated using (true);
create policy tiers_write on pmb.price_tiers for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

create policy zones_read on pmb.delivery_zones for select to anon, authenticated using (is_active or (select pmb.is_staff()));
create policy zones_write on pmb.delivery_zones for all to authenticated using ((select pmb.is_manager())) with check ((select pmb.is_manager()));

-- settings: staff read, owner update
grant select, update on pmb.settings to authenticated;
create policy settings_read on pmb.settings for select to authenticated using ((select pmb.is_staff()));
create policy settings_update on pmb.settings for update to authenticated using ((select pmb.is_owner())) with check ((select pmb.is_owner()));

-- staff table: staff see the team; changes only through owner functions
grant select on pmb.staff to authenticated;
create policy staff_read on pmb.staff for select to authenticated using ((select pmb.is_staff()));

-- operational tables: staff can read; all writes go through functions
grant select on pmb.customers, pmb.requests, pmb.request_items, pmb.request_events,
  pmb.quotations, pmb.quotation_items, pmb.quotation_events,
  pmb.orders, pmb.order_items, pmb.order_events,
  pmb.payments, pmb.payment_events, pmb.fulfillments, pmb.fulfillment_events to authenticated;

do $$
declare t text;
begin
  foreach t in array array['customers','requests','request_items','request_events','quotations','quotation_items','quotation_events','orders','order_items','order_events','payments','payment_events','fulfillments','fulfillment_events'] loop
    execute format('create policy %1$s_staff_read on pmb.%1$s for select to authenticated using ((select pmb.is_staff()))', t);
  end loop;
end $$;
-- doc_counters has RLS on and no policy: unreachable except through definer functions.
