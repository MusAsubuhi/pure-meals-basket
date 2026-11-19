# Pure Meals Basket app (Supabase + Vercel)

Static front end (vanilla JS, no build step) talking to the `pmb` schema of the Supabase project through its API.
All permissions are enforced in the database (row-level security and security-definer functions).

- `app.html`, `css/`, `js/` : the app (customer menu, request builder, tokened order page, staff area)
- `supabase/migrations/001_pmb_core_schema.sql` : first schema file. Later migrations (002 to 005) are recorded in the Supabase project history.
- Roles: owner, manager, staff (table `pmb.staff`). Customers have no login; they use a private link per request.
- Payments are recorded by staff and confirmed by a manager or the owner. No payment gateway is connected.

The old Laravel version lives on branches `mcbanks` and `supabase-postgres-compat` and is not used.

## Team page copy
`team.html` is a copy of `app.html` with team-specific head tags (noindex, team preview image). Vercel serves it for team.puremealsbasket.co.ke at `/`. When `app.html` script or style versions (`?v=`) change, make the same change in `team.html`.
