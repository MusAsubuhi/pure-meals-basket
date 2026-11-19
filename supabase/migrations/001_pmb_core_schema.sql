-- PMB core schema. Everything lives in schema "pmb"; nothing in "public" is touched.
create schema if not exists pmb;

-- ---------- staff and roles ----------
create table if not exists pmb.staff (
  user_id uuid primary key references auth.users(id) on delete restrict,
  full_name text not null,
  role text not null check (role in ('owner','manager','staff')),
  active boolean not null default true,
  created_at timestamptz not null default now()
);

create table if not exists pmb.settings (
  id int primary key default 1 check (id = 1),
  business_name text not null default 'Pure Meals Basket',
  deposit_percent numeric(5,2) not null default 70 check (deposit_percent between 0 and 100),
  quote_valid_days int not null default 7 check (quote_valid_days between 1 and 60),
  payment_instructions text not null default 'Pay by M-Pesa and quote your order reference. PMB will confirm your payment.',
  contact_phone text,
  contact_email text,
  updated_at timestamptz not null default now()
);
insert into pmb.settings (id) values (1) on conflict do nothing;

-- ---------- catalogue ----------
create table if not exists pmb.categories (
  id bigint generated always as identity primary key,
  name text not null unique,
  slug text not null unique,
  description text,
  image_url text,
  is_active boolean not null default true,
  sort_order int not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists pmb.products (
  id bigint generated always as identity primary key,
  category_id bigint not null references pmb.categories(id) on delete restrict,
  name text not null,
  slug text not null unique,
  description text,
  short_description text,
  image_url text,
  pricing_type text not null check (pricing_type in ('FIXED','CUSTOM','PER_UNIT','PER_WEIGHT','PER_VOLUME','PER_PERSON','TIERED')),
  base_price numeric(12,2),
  unit text,
  minimum_quantity numeric(10,3),
  maximum_quantity numeric(10,3),
  is_available boolean not null default true,
  requires_review boolean not null default false,
  status text not null default 'DRAFT' check (status in ('DRAFT','ACTIVE','ARCHIVED')),
  sort_order int not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists products_category_idx on pmb.products(category_id, status);

create table if not exists pmb.services (
  id bigint generated always as identity primary key,
  category_id bigint references pmb.categories(id) on delete set null,
  name text not null,
  slug text not null unique,
  description text,
  short_description text,
  pricing_type text not null check (pricing_type in ('FIXED','CUSTOM','PER_UNIT','PER_WEIGHT','PER_VOLUME','PER_PERSON','TIERED')),
  base_price numeric(12,2),
  unit text,
  minimum_quantity numeric(10,3),
  maximum_quantity numeric(10,3),
  is_available boolean not null default true,
  requires_review boolean not null default false,
  status text not null default 'DRAFT' check (status in ('DRAFT','ACTIVE','ARCHIVED')),
  sort_order int not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists pmb.product_options (
  id bigint generated always as identity primary key,
  product_id bigint not null references pmb.products(id) on delete cascade,
  name text not null,
  is_required boolean not null default false,
  sort_order int not null default 0
);
create index if not exists product_options_product_idx on pmb.product_options(product_id);

create table if not exists pmb.product_option_values (
  id bigint generated always as identity primary key,
  product_option_id bigint not null references pmb.product_options(id) on delete cascade,
  name text not null,
  price_modifier numeric(12,2) not null default 0,
  is_available boolean not null default true,
  sort_order int not null default 0
);
create index if not exists option_values_option_idx on pmb.product_option_values(product_option_id);

create table if not exists pmb.price_tiers (
  id bigint generated always as identity primary key,
  priceable_type text not null check (priceable_type in ('product','service')),
  priceable_id bigint not null,
  min_quantity numeric(10,3) not null,
  max_quantity numeric(10,3),
  unit_price numeric(12,2) not null,
  label text,
  check (max_quantity is null or max_quantity >= min_quantity)
);
create index if not exists price_tiers_priceable_idx on pmb.price_tiers(priceable_type, priceable_id);

create table if not exists pmb.delivery_zones (
  id bigint generated always as identity primary key,
  name text not null,
  slug text not null unique,
  description text,
  keywords text[] not null default '{}',
  base_charge numeric(10,2) not null default 0,
  door_to_door_surcharge numeric(10,2) not null default 0,
  beyond_stage_surcharge numeric(10,2) not null default 0,
  free_delivery_above numeric(10,2),
  is_active boolean not null default true,
  sort_order int not null default 0,
  created_at timestamptz not null default now()
);

-- ---------- customers (no login; reached through tokened links) ----------
create table if not exists pmb.customers (
  id bigint generated always as identity primary key,
  name text not null,
  phone text not null,
  phone_key text generated always as (right(regexp_replace(phone, '\D', '', 'g'), 9)) stored,
  email text,
  created_at timestamptz not null default now()
);
create index if not exists customers_phone_key_idx on pmb.customers(phone_key);

-- ---------- requests ----------
create table if not exists pmb.doc_counters (
  kind text not null,
  year int not null,
  last_value int not null default 0,
  primary key (kind, year)
);

create table if not exists pmb.requests (
  id uuid primary key default gen_random_uuid(),
  reference text not null unique,
  access_token uuid not null unique default gen_random_uuid(),
  customer_id bigint not null references pmb.customers(id) on delete restrict,
  status text not null default 'SUBMITTED' check (status in ('DRAFT','SUBMITTED','UNDER_REVIEW','NEEDS_INFORMATION','QUOTATION_REQUIRED','READY_FOR_CHECKOUT','DECLINED','CANCELLED')),
  fulfillment_method text not null default 'DELIVERY' check (fulfillment_method in ('DELIVERY','CUSTOMER_COLLECTION','ON_SITE_SERVICE')),
  delivery_zone_id bigint references pmb.delivery_zones(id) on delete restrict,
  delivery_type text check (delivery_type in ('standard','door_to_door','beyond_stage')),
  delivery_address text,
  event_date date,
  event_time time,
  location text,
  notes text,
  staff_message text,
  submitted_at timestamptz not null default now(),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists requests_status_idx on pmb.requests(status, submitted_at desc);
create index if not exists requests_customer_idx on pmb.requests(customer_id);

create table if not exists pmb.request_items (
  id uuid primary key default gen_random_uuid(),
  request_id uuid not null references pmb.requests(id) on delete cascade,
  item_type text not null check (item_type in ('product','service')),
  product_id bigint references pmb.products(id) on delete restrict,
  service_id bigint references pmb.services(id) on delete restrict,
  name text not null,
  quantity numeric(10,3) not null default 1 check (quantity > 0),
  unit text,
  options jsonb not null default '[]'::jsonb,
  pricing_type text,
  pricing_status text not null default 'CALCULATED' check (pricing_status in ('CALCULATED','QUOTATION_REQUIRED')),
  unit_price numeric(12,2),
  subtotal numeric(12,2),
  pricing_breakdown jsonb,
  notes text,
  created_at timestamptz not null default now(),
  constraint request_items_product_or_service check ((product_id is not null and service_id is null and item_type='product') or (service_id is not null and product_id is null and item_type='service'))
);
create index if not exists request_items_request_idx on pmb.request_items(request_id);

create table if not exists pmb.request_events (
  id bigint generated always as identity primary key,
  request_id uuid not null references pmb.requests(id) on delete cascade,
  event text not null,
  detail text,
  actor uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);
create index if not exists request_events_idx on pmb.request_events(request_id, created_at);

-- ---------- quotations ----------
create table if not exists pmb.quotations (
  id uuid primary key default gen_random_uuid(),
  request_id uuid not null references pmb.requests(id) on delete restrict,
  reference text not null unique,
  status text not null default 'DRAFT' check (status in ('DRAFT','SENT','ACCEPTED','DECLINED','WITHDRAWN','EXPIRED')),
  valid_until timestamptz,
  sent_at timestamptz,
  accepted_at timestamptz,
  declined_at timestamptz,
  withdrawn_at timestamptz,
  expired_at timestamptz,
  subtotal numeric(12,2) not null default 0,
  discount numeric(12,2) not null default 0 check (discount >= 0),
  delivery_fee numeric(12,2) not null default 0 check (delivery_fee >= 0),
  total numeric(12,2) not null default 0,
  notes text,
  customer_message text,
  created_by uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists quotations_request_idx on pmb.quotations(request_id);
create index if not exists quotations_status_idx on pmb.quotations(status, valid_until);
-- only one live quotation per request
create unique index if not exists quotations_one_live_idx on pmb.quotations(request_id) where status in ('DRAFT','SENT','ACCEPTED');

create table if not exists pmb.quotation_items (
  id uuid primary key default gen_random_uuid(),
  quotation_id uuid not null references pmb.quotations(id) on delete cascade,
  request_item_id uuid references pmb.request_items(id) on delete set null,
  name text not null,
  description text,
  quantity numeric(10,3) not null default 1 check (quantity > 0),
  unit text,
  unit_price numeric(12,2) not null default 0 check (unit_price >= 0),
  subtotal numeric(12,2) not null default 0,
  options jsonb not null default '[]'::jsonb,
  sort_order int not null default 0
);
create index if not exists quotation_items_q_idx on pmb.quotation_items(quotation_id);

create table if not exists pmb.quotation_events (
  id bigint generated always as identity primary key,
  quotation_id uuid not null references pmb.quotations(id) on delete cascade,
  event text not null,
  detail text,
  actor uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);

-- ---------- orders ----------
create table if not exists pmb.orders (
  id uuid primary key default gen_random_uuid(),
  request_id uuid not null unique references pmb.requests(id) on delete restrict,
  quotation_id uuid unique references pmb.quotations(id) on delete restrict,
  reference text not null unique,
  status text not null default 'PENDING_PAYMENT' check (status in ('DRAFT','PENDING_PAYMENT','CONFIRMED','PREPARING','READY','OUT_FOR_DELIVERY','DELIVERED','COMPLETED','CANCELLED')),
  payment_status text not null default 'UNPAID' check (payment_status in ('UNPAID','PARTIALLY_PAID','PAID','REFUNDED')),
  fulfillment_method text not null check (fulfillment_method in ('DELIVERY','CUSTOMER_COLLECTION','ON_SITE_SERVICE')),
  delivery_zone_id bigint references pmb.delivery_zones(id) on delete restrict,
  delivery_type text,
  event_date date,
  event_time time,
  location text,
  delivery_address text,
  delivery_notes text,
  customer_id bigint not null references pmb.customers(id) on delete restrict,
  customer_name text not null,
  customer_phone text not null,
  customer_email text,
  subtotal numeric(12,2) not null default 0,
  discount numeric(12,2) not null default 0,
  delivery_fee numeric(12,2) not null default 0,
  total numeric(12,2) not null default 0,
  payment_required numeric(12,2) not null default 0,
  amount_paid numeric(12,2) not null default 0,
  balance_due numeric(12,2) not null default 0,
  notes text,
  cancel_reason text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists orders_status_idx on pmb.orders(status, payment_status);
create index if not exists orders_customer_idx on pmb.orders(customer_id);

create table if not exists pmb.order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references pmb.orders(id) on delete cascade,
  quotation_item_id uuid references pmb.quotation_items(id) on delete set null,
  name text not null,
  description text,
  quantity numeric(10,3) not null default 1,
  unit text,
  unit_price numeric(12,2) not null default 0,
  subtotal numeric(12,2) not null default 0,
  options jsonb not null default '[]'::jsonb
);
create index if not exists order_items_order_idx on pmb.order_items(order_id);

create table if not exists pmb.order_events (
  id bigint generated always as identity primary key,
  order_id uuid not null references pmb.orders(id) on delete cascade,
  event text not null,
  detail text,
  actor uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);
create index if not exists order_events_idx on pmb.order_events(order_id, created_at);

-- ---------- payments (manual recording; gateway-ready) ----------
create table if not exists pmb.payments (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references pmb.orders(id) on delete restrict,
  reference text not null unique,
  method text not null check (method in ('MPESA','CASH')),
  status text not null default 'PENDING' check (status in ('PENDING','PROCESSING','SUCCESS','FAILED','CANCELLED','REVERSED')),
  amount numeric(12,2) not null check (amount > 0),
  currency text not null default 'KES',
  provider_reference text,
  paid_at timestamptz,
  note text,
  recorded_by uuid references auth.users(id) on delete set null,
  confirmed_by uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);
create index if not exists payments_order_idx on pmb.payments(order_id, status);
create unique index if not exists payments_provider_ref_idx on pmb.payments(method, provider_reference) where provider_reference is not null and status in ('PENDING','PROCESSING','SUCCESS');

create table if not exists pmb.payment_events (
  id bigint generated always as identity primary key,
  payment_id uuid not null references pmb.payments(id) on delete cascade,
  event text not null,
  detail text,
  actor uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);

-- ---------- fulfilment ----------
create table if not exists pmb.fulfillments (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null unique references pmb.orders(id) on delete restrict,
  method text not null check (method in ('DELIVERY','CUSTOMER_COLLECTION','ON_SITE_SERVICE')),
  status text not null default 'PENDING' check (status in ('PENDING','PREPARING','READY','OUT_FOR_DELIVERY','DELIVERED','COLLECTED','SERVICE_IN_PROGRESS','COMPLETED','DELIVERY_FAILED','CANCELLED')),
  delivery_fee numeric(10,2) default 0,
  scheduled_at timestamptz,
  started_at timestamptz,
  ready_at timestamptz,
  dispatched_at timestamptz,
  delivered_at timestamptz,
  collected_at timestamptz,
  completed_at timestamptz,
  delivery_address text,
  contact_name text,
  contact_phone text,
  recipient_name text,
  failure_reason text,
  notes text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists pmb.fulfillment_events (
  id bigint generated always as identity primary key,
  fulfillment_id uuid not null references pmb.fulfillments(id) on delete cascade,
  event text not null,
  detail text,
  actor uuid references auth.users(id) on delete set null,
  created_at timestamptz not null default now()
);

-- ---------- touch trigger ----------
create or replace function pmb.touch_updated() returns trigger
language plpgsql set search_path = pmb, pg_temp as $$
begin new.updated_at := now(); return new; end $$;

do $$
declare t text;
begin
  foreach t in array array['categories','products','services','requests','quotations','orders','fulfillments','settings'] loop
    execute format('drop trigger if exists touch_%1$s on pmb.%1$s', t);
    execute format('create trigger touch_%1$s before update on pmb.%1$s for each row execute function pmb.touch_updated()', t);
  end loop;
end $$;
