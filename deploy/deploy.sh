#!/usr/bin/env bash
#
# Run this ON THE SERVER (Hostinger), from the clone of zerp-release.
# No npm anywhere: the frontend bundle arrives precompiled in public/build.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

[ -f .env ] || { echo "!! no .env — see README.md, first-time setup"; exit 1; }

php artisan down --retry=15 || true
trap 'php artisan up || true' EXIT

git pull --ff-only

# Shared hosting often kills composer at the default memory limit.
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fails loudly rather than serving a half-broken page.
[ -f public/build/manifest.json ] || { echo "!! public/build/manifest.json missing — republish"; exit 1; }
if find public/packages -xtype l | grep -q .; then
  echo "!! dangling module asset symlinks (a module failed to install):"
  find public/packages -xtype l
fi

echo "==> Deployed."
