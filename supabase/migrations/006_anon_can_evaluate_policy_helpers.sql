-- The catalogue read policies call pmb.is_staff() / pmb.is_manager(). A visitor who is not signed in must be able to run
-- these helpers (they simply return false) or every catalogue read fails with "permission denied".
grant execute on function pmb.is_staff() to anon;
grant execute on function pmb.is_manager() to anon;
grant execute on function pmb.is_owner() to anon;
grant execute on function pmb.my_role() to anon;
