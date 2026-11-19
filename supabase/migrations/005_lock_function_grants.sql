alter default privileges for role postgres in schema pmb revoke execute on functions from public;
alter default privileges for role postgres in schema pmb revoke all on tables from public;
revoke execute on all functions in schema pmb from public, anon, authenticated;

-- public (anonymous) surface: pricing preview, request submission, tokened page, accept/decline, public settings, delivery fee preview
grant execute on function pmb.public_settings(), pmb.price_item(text,bigint,numeric,jsonb), pmb.submit_request(jsonb,jsonb,jsonb,text),
  pmb.public_request(uuid), pmb.public_decide_quote(uuid,text,text), pmb.delivery_fee(bigint,text,numeric) to anon, authenticated;

-- signed-in surface (each function still checks the staff role inside)
grant execute on function pmb.is_staff(), pmb.is_manager(), pmb.is_owner(), pmb.my_role(),
  pmb.set_request_status(uuid,text,text), pmb.build_quotation(uuid), pmb.quotation_set_terms(uuid,numeric,numeric,text,text),
  pmb.quotation_set_line(uuid,numeric,numeric), pmb.quotation_add_line(uuid,text,numeric,numeric,text), pmb.quotation_remove_line(uuid),
  pmb.send_quotation(uuid), pmb.withdraw_quotation(uuid,text), pmb.record_payment(uuid,numeric,text,text,timestamptz,text,boolean),
  pmb.confirm_payment(uuid), pmb.reject_payment(uuid,text), pmb.reverse_payment(uuid,text), pmb.advance_order(uuid,text,text),
  pmb.cancel_order(uuid,text), pmb.add_staff_by_email(text,text,text), pmb.update_staff(uuid,text,boolean), pmb.staff_dashboard() to authenticated;
