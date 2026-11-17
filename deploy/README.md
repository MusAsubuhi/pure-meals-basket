# Deploying PMB to Truehost (cPanel) with Supabase Postgres

Target: `app.puremealsbasket.co.ke`, document root `/home/wikitabu/pmb-app/public`,
PHP 8.3, database in the `pmb` schema of the Supabase project.

## One-time setup

1. **Database password.** In the Supabase SQL Editor run
   `alter role pmb_app with login password '<choose a long random password>';`
   Then copy the exact session-pooler host from Supabase > Connect.
2. **Get the code onto the server.** In cPanel open *Git Version Control*, clone
   `https://github.com/MusAsubuhi/pure-meals-basket` (branch `supabase-postgres-compat`)
   into a new folder, then move or clone so the app lives at `/home/wikitabu/pmb-app`
   with `public/` inside it. (Remove the empty `public` folder cPanel created there first.)
3. **Settings.** In *File Manager*, copy `deploy/env.production.example` to `.env` in the
   app folder and fill in the empty values. Never commit `.env`.
4. **Run the deploy script** (SSH or cPanel Terminal):
   `bash deploy/truehost-deploy.sh`
   It installs dependencies, runs the migrations in the `pmb` schema, creates the first
   administrator from `ADMIN_EMAIL` / `ADMIN_PASSWORD`, and caches the configuration.
5. **Background jobs.** Emails and payment webhooks are queued. In cPanel *Cron Jobs* add,
   every minute:
   `cd /home/wikitabu/pmb-app && /opt/cpanel/ea-php83/root/usr/bin/php artisan queue:work --stop-when-empty --max-time=55`
   and, every minute:
   `cd /home/wikitabu/pmb-app && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run`
6. In *Domains*, turn on **Force HTTPS Redirect** for `app.puremealsbasket.co.ke` once its
   SSL certificate is issued.

## Updating later

Pull the new code (cPanel *Git Version Control* > Update from Remote), then
`bash deploy/truehost-deploy.sh` again.

## Before taking real orders

- In the admin, create at least one **Delivery Zone**; submitting a request is blocked
  for addresses outside every active zone.
- Add products/services in the catalogue.
- Send yourself a test order and check the confirmation email arrives.
