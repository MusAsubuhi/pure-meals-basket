#!/usr/bin/env bash
#
# Deploy / update the PMB Laravel app on Truehost (cPanel).
#
# Run from anywhere; it works on the app folder that contains this script's parent
# (expected: /home/wikitabu/pmb-app). Safe to run again after every update.
#
# Requires a finished .env in the app folder (see deploy/env.production.example).
# The first run also seeds roles and the first administrator from ADMIN_EMAIL /
# ADMIN_PASSWORD in .env; later runs skip seeding.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

PHP="${PHP:-/opt/cpanel/ea-php83/root/usr/bin/php}"
[ -x "$PHP" ] || PHP="$(command -v php)"

echo "==> Using $($PHP -r 'echo PHP_VERSION;') in $APP_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env not found. Copy deploy/env.production.example to .env and fill it in." >&2
  exit 1
fi

if ! $PHP -m | grep -qi '^pdo_pgsql$'; then
  echo "ERROR: the pdo_pgsql PHP extension is not enabled for this PHP version." >&2
  echo "       Ask Truehost support to enable ea-php83-php-pgsql, then run this again." >&2
  exit 1
fi

echo "==> Installing PHP dependencies"
if command -v composer >/dev/null 2>&1; then
  COMPOSER=("$PHP" "$(command -v composer)")
else
  if [ ! -f composer.phar ]; then
    curl -fsSL https://getcomposer.org/installer | "$PHP" -- --install-dir=. --filename=composer.phar
  fi
  COMPOSER=("$PHP" composer.phar)
fi
"${COMPOSER[@]}" install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "==> Generating APP_KEY"
  "$PHP" artisan key:generate --force
fi

echo "==> Running database migrations"
"$PHP" artisan migrate --force

if [ ! -f storage/.seeded ]; then
  echo "==> Seeding roles and the first administrator (first run only)"
  "$PHP" artisan db:seed --force
  touch storage/.seeded
fi

"$PHP" artisan storage:link >/dev/null 2>&1 || true

echo "==> Caching configuration"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

chmod -R ug+rwX storage bootstrap/cache

echo "==> Done. Visit your site, and sign in to /admin with ADMIN_EMAIL."
