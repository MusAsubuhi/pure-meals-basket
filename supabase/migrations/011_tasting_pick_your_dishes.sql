-- Wedding Tasting Box: customer picks the dishes; KES 500 preparation fee plus KES 400 per dish (placeholders), credited against the feast if booked within 60 days.
do $$
declare pid bigint; oid bigint; d record; i int := 0;
begin
  select id into pid from pmb.products where slug = 'wedding-tasting-box';
  delete from pmb.product_option_values where product_option_id in (select id from pmb.product_options where product_id = pid);
  delete from pmb.product_options where product_id = pid;
  update pmb.products set base_price = 500,
    short_description = 'Pick the dishes you want to taste. Small portions, credited if you book the feast.'
  where id = pid; -- description text updated in the same migration as applied live
  for d in select * from (values
    ('Grilled Snapper, Lemon Butter Sauce, Rice or Pilau, Steamed Veggies'),('Fried Fish, Tartar Sauce, Baked Potato Wedges, Sauteed Cabbage'),
    ('Grilled Chicken, Chimichurri, Roast Potato, Stir-Fried Vegetables'),('Chicken Tikka, Cilantro-Mint Chutney, Naan Bread, Mchicha'),
    ('Roasted Chicken, Honey Garlic Sauce, Mashed Potato, Roasted Veggies'),('Chicken Stew, Ugali, Sauteed Cabbage'),
    ('Chicken Stir Fry, Noodles, Stir-Fried Vegetables'),('Beef Fillet, Bearnaise Sauce, Roast Potato, Roasted Veggies'),
    ('Beef Stir Fry, Mushroom Sauce, Noodles, Stir-Fried Vegetables'),('Beef Stew, Ugali or Rice, Steamed Veggies'),
    ('Beef Curry, Rice or Pilau, Creamed Spinach'),('Pork Chops, Classic BBQ Glaze, Baked Potato Wedges, Roasted Veggies'),
    ('Vegetable Stew, Ugali or Rice, Mchicha'),('Lentil Curry, Rice or Pilau, Sauteed Cabbage')) as t(n) loop
    i := i + 1;
    insert into pmb.product_options(product_id, name, is_required, per_unit, sort_order) values (pid, d.n, false, false, i) returning id into oid;
    insert into pmb.product_option_values(product_option_id, name, price_modifier, is_available, sort_order) values (oid, 'Add to my tasting', 400, true, 1);
  end loop;
end $$;
