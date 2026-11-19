alter table pmb.product_options add column if not exists per_unit boolean not null default false;
comment on column pmb.product_options.per_unit is 'When true, the choice price is charged for every unit (for example every person) instead of once per item.';

create or replace function pmb.price_item(p_type text, p_id bigint, p_qty numeric, p_options jsonb default '[]'::jsonb)
 returns jsonb language plpgsql stable security definer set search_path to 'pmb', 'pg_temp'
as $function$
declare
  r record; v_cat_active boolean; v_qty numeric; v_unit numeric; v_sub numeric;
  v_opt numeric := 0; v_opt_flat numeric := 0; v_opt_unit numeric := 0;
  v_status text := 'CALCULATED'; v_break jsonb := '[]'::jsonb;
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
    select coalesce(sum(v.price_modifier) filter (where not po.per_unit), 0),
           coalesce(sum(v.price_modifier) filter (where po.per_unit), 0),
           coalesce(array_agg(po.name || ': ' || v.name), '{}')
      into v_opt_flat, v_opt_unit, v_opt_names
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
    v_qty := 1; v_unit := r.base_price;
    v_opt := v_opt_flat + v_opt_unit;
    v_sub := round(r.base_price + v_opt, 2);
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
    v_opt := v_opt_flat + round(v_opt_unit * v_qty, 2);
    v_sub := round(round(v_unit * v_qty, 2) + v_opt, 2);
    v_break := jsonb_build_array(v_qty::text || ' x ' || to_char(v_unit, 'FM999,999,990.00'));
    if v_opt_unit <> 0 then v_break := v_break || to_jsonb(v_qty::text || ' x ' || to_char(v_opt_unit, 'FM999,999,990.00') || ' for your choices'); end if;
  end if;
  if v_opt <> 0 and not (r.pricing_type not in ('FIXED','CUSTOM') and v_opt_unit <> 0 and v_opt_flat = 0) then
    v_break := v_break || to_jsonb('Options +' || to_char(v_opt, 'FM999,999,990.00'));
  end if;
  if r.requires_review then v_status := 'QUOTATION_REQUIRED'; v_break := v_break || to_jsonb('PMB will confirm this price in your quotation.'::text); end if;
  return jsonb_build_object('name', r.name, 'unit', r.unit, 'quantity', v_qty, 'pricing_type', r.pricing_type,
    'pricing_status', v_status, 'unit_price', v_unit, 'subtotal', v_sub, 'breakdown', v_break, 'options', to_jsonb(v_ids), 'option_names', to_jsonb(v_opt_names));
end $function$;
grant execute on function pmb.price_item(text, bigint, numeric, jsonb) to anon, authenticated;
