-- Open Basket is for breakfast or lunch; delivery zones from Likoni; contact and payment details.

-- 1. Open Basket: rename, add a required "meal" choice, make the breakfast picks optional.
update pmb.categories set name = 'Open Basket',
  description = 'Breakfast or lunch your way: you choose, we price and deliver.'
where slug = 'open-basket-breakfast';

update pmb.products set
  name = 'Open Basket',
  short_description = 'Breakfast or lunch, your way: you choose, we price and deliver.',
  description = 'Build your own basket for breakfast or lunch. Choose which meal it is for, tick what you would like from the breakfast list below, or tell us in the notes what you want for lunch or anything else. We price it, confirm everything in your quotation, and deliver. Breakfast choices are priced per person, plus a flat packaging and service fee of KES 250 per order. Tags: Vegetarian means no meat or fish; Contains pork and Contains fish are marked where they apply, and any option without a pork tag has no pork ingredients. PMB is not halal certified. Cakes, breads and sauces may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes.'
where slug = 'open-basket-breakfast';

update pmb.product_options set is_required = false
where product_id = (select id from pmb.products where slug = 'open-basket-breakfast')
  and name in ('Drink','Starch','Protein','Accompaniment','Fruit');

with o as (
  insert into pmb.product_options(product_id, name, is_required, per_unit, sort_order)
  select id, 'Which meal is this basket for?', true, false, 0 from pmb.products where slug = 'open-basket-breakfast'
  returning id)
insert into pmb.product_option_values(product_option_id, name, price_modifier, is_available, sort_order)
select o.id, v.n, 0, true, v.s from o, (values ('Breakfast',1),('Lunch',2)) as v(n,s);

-- 2. Delivery zones from Likoni. Rule of thumb: KES 250 base + KES 40 per km one way (covers the empty return),
-- plus the KES 250 ferry for the north coast, rounded to the nearest 50.
insert into pmb.delivery_zones(name, slug, description, keywords, base_charge, door_to_door_surcharge, beyond_stage_surcharge, free_delivery_above, is_active, sort_order) values
('Likoni, Shelly Beach and Mtongwe', 'likoni-local', 'Home base, about 4 km', array['likoni','shelly beach','mtongwe','shika adabu','timbwani'], 400, 100, 200, null, true, 1),
('South Coast: up to Tiwi', 'south-to-tiwi', 'About 8 to 22 km from Likoni', array['tiwi','kombani','mwangala'], 850, 100, 200, null, true, 2),
('South Coast: Tiwi to Ukunda', 'south-to-ukunda', 'About 22 to 32 km from Likoni, up to Ukunda', array['ukunda','diani','tiwi beach'], 1400, 100, 200, null, true, 3),
('Mombasa Island: Town, Old Town, Kizingo and Tudor', 'north-island', 'Across the ferry, about 9 km. Includes the KES 250 ferry cost', array['mombasa town','cbd','old town','kizingo','tudor','mvita'], 850, 100, 200, null, true, 4),
('Mombasa North: Makupa, Kongowea and Mkomani', 'north-mid', 'Across the ferry, about 14 km. Includes the KES 250 ferry cost', array['makupa','kongowea','mkomani','majengo','kisauni road'], 1050, 100, 200, null, true, 5),
('Nyali', 'north-nyali', 'Across the ferry, about 19 km, up to Nyali. Includes the KES 250 ferry cost', array['nyali','links road','nyali centre','cinemax'], 1250, 100, 200, null, true, 6);

-- 3. Contact and payment details.
update pmb.settings set
  contact_phone = '+254 737 953 292',
  contact_email = 'hello@puremealsbasket.co.ke',
  payment_instructions = 'Pay by M-Pesa to 0710 814455 and quote your order reference. PMB will confirm your payment.',
  updated_at = now()
where id = 1;
