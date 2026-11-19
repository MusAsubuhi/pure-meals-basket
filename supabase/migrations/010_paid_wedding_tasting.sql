-- Paid wedding tasting (KES 3,500 for up to 4 people, credited against the feast if booked within 60 days) and updated feast wording.
with c as (select id from pmb.categories where slug = 'weddings'),
p as (insert into pmb.products(category_id, name, slug, description, short_description, image_url, pricing_type, base_price, unit, minimum_quantity, is_available, requires_review, status, sort_order)
  select c.id, 'Wedding Tasting Box', 'wedding-tasting-box',
   'Taste the menu before you book. We prepare sample portions of the dishes you are considering and deliver them to you, enough for up to 4 people. The tasting fee is paid with your request and is credited in full against your Wedding Feast if you book with us within 60 days. PMB is not halal certified. Dishes may contain egg, dairy, gluten or nuts, so tell us any allergy in your notes.',
   'Sample portions for up to 4 people. The fee is credited if you book the feast.', '/img/wedding-feast.jpg', 'FIXED', 3500, 'tasting', 1, true, false, 'ACTIVE', 5 from c returning id),
o as (insert into pmb.product_options(product_id, name, is_required, per_unit, sort_order) select id, 'Tasting for', true, false, 1 from p returning id)
insert into pmb.product_option_values(product_option_id, name, price_modifier, is_available, sort_order)
  select o.id, 'Up to 4 people', 0, true, 1 from o;
-- Wedding Feast description also updated live to mention the tasting box and credit.
