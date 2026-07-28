-- Pure Meals Basket — Supabase schema
-- Run this in the Supabase SQL Editor (Project > SQL Editor > New query) once,
-- right after creating the "pure-meals-basket" project.

create table public.orders (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  name text not null,
  phone text not null,
  service_type text not null,
  event_type text,
  event_date date,
  attendee_count integer,
  venue text,
  dietary_notes text,
  juice_quantity_litres numeric,
  juice_flavours text,
  juice_delivery text,
  cake_occasion text,
  cake_size text,
  cake_flavour text,
  cake_decoration_notes text,
  delivery_date date,
  referral_source text
);

create table public.feedback (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),
  name text not null,
  phone text not null,
  event_type text not null,
  experience text not null,
  star_rating integer not null check (star_rating between 1 and 5)
);

-- Row Level Security: allow anyone to submit (insert), but nobody can read,
-- update, or delete via the public anon key. Only accessible from the
-- Supabase dashboard / service role key.
alter table public.orders enable row level security;
alter table public.feedback enable row level security;

create policy "Allow public insert on orders"
  on public.orders for insert
  to anon
  with check (true);

create policy "Allow public insert on feedback"
  on public.feedback for insert
  to anon
  with check (true);

-- RLS policies only govern row-level access; the anon role also needs the
-- underlying table-level privilege granted (tables created via the SQL
-- Editor don't get this automatically the way Table Editor ones do).
grant insert on public.orders to anon;
grant insert on public.feedback to anon;
