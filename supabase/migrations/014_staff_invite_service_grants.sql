-- The pmb-invite-staff Edge Function runs with the service role and records the team member.
grant usage on schema pmb to service_role;
grant select, insert, update on pmb.staff to service_role;
