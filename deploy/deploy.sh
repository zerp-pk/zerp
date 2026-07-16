#!/usr/bin/env bash
#
# Run this ON THE SERVER (Hostinger), from the clone in public_html.
# No npm anywhere: the frontend bundle arrives precompiled in public/build.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

# Hostinger's default `php` on PATH is 8.1, and shell aliases do NOT apply inside
# scripts - so both PHP and Composer must be pointed at a real 8.2+ binary here.
# Override with:  PHP_BIN=/opt/alt/php83/usr/bin/php ./deploy.sh
if [ -z "${PHP_BIN:-}" ]; then
  for c in /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php; do
    [ -x "$c" ] && { PHP_BIN="$c"; break; }
  done
fi
PHP_BIN="${PHP_BIN:-$(command -v php)}"

# The app requires >= 8.2. Fail here with a clear message rather than deep inside
# composer's platform_check.
"$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || {
  echo "!! $PHP_BIN is $("$PHP_BIN" -r 'echo PHP_VERSION;') - Zerp needs >= 8.2"
  echo "   Set PHP_BIN to an 8.2+ binary, e.g. PHP_BIN=/opt/alt/php84/usr/bin/php ./deploy.sh"
  exit 1
}

COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer)}"
composer_run() { "$PHP_BIN" "$COMPOSER_BIN" "$@"; }
artisan()      { "$PHP_BIN" artisan "$@"; }

echo "==> PHP $("$PHP_BIN" -r 'echo PHP_VERSION;') ($PHP_BIN)"

[ -f .env ] || { echo "!! no .env - see README.md, first-time setup"; exit 1; }

artisan down --retry=15 || true
trap 'artisan up || true' EXIT

git pull --ff-only

# Shared hosting often kills composer at the default memory limit.
COMPOSER_MEMORY_LIMIT=-1 composer_run install --no-dev --optimize-autoloader --no-interaction

artisan migrate --force

artisan config:cache
artisan route:cache
artisan view:cache

# Fail loudly rather than serving a half-broken page.
[ -f public/build/manifest.json ] || { echo "!! public/build/manifest.json missing - republish"; exit 1; }
if find public/packages -xtype l | grep -q .; then
  echo "!! dangling module asset symlinks (a module failed to install):"
  find public/packages -xtype l
fi

echo "==> Deployed."
