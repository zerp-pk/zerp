#!/usr/bin/env bash
#
# Build the frontend and publish a server-ready tree to the private zerp-release
# repo, which is what Hostinger clones.
#
# Hostinger shared hosting has PHP + Composer + SSH, but no Node. So the Vite
# build happens HERE and public/build is committed into the release repo. The
# release repo's composer.json also drops the ../ZerpPackages path repositories
# (which don't exist on the server) and pulls the zerp/* modules from Packagist.
#
# Usage:  ./deploy/publish.sh ["commit message"]
set -euo pipefail

# The PHP version Hostinger runs. The lockfile is resolved against this, NOT
# against your local PHP — check it on the server with `php -v` and fix here if
# it ever changes, or you'll ship a lock the server can't install.
PHP_PLATFORM="${PHP_PLATFORM:-8.2.0}"

RELEASE_REPO="git@github.com:zerp-pk/zerp-release.git"
MODULE_CONSTRAINT="^1.0"

APP="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MSG="${1:-Release $(date -u +%Y-%m-%d\ %H:%M) UTC}"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

cd "$APP"

echo "==> Building frontend assets"
npm ci
npm run build
[ -f public/build/manifest.json ] || { echo "!! vite produced no manifest.json"; exit 1; }

echo "==> Assembling release tree"
git clone --quiet "$RELEASE_REPO" "$STAGE/repo"
cd "$STAGE/repo"
# Drop everything tracked, so files deleted upstream disappear from the release
# too. .git survives, so history is preserved.
git ls-files -z | xargs -0 -r rm -f
cd "$APP"

# Tracked files only — never node_modules/ or vendor/. -x preserves the relative
# symlinks under public/packages, which resolve once composer install has run.
git archive HEAD | tar -x -C "$STAGE/repo"
cp -r public/build "$STAGE/repo/public/build"

cd "$STAGE/repo"
rm -rf tests .github phpunit.xml           # not needed to serve the app

# The server gets deploy.sh at the root; the build tooling itself doesn't ship.
cp "$APP/deploy/deploy.sh" deploy.sh
chmod +x deploy.sh
rm -rf deploy

echo "==> Rewriting composer.json for the server"
python3 - "$PHP_PLATFORM" "$MODULE_CONSTRAINT" <<'PY'
import json, sys
platform, constraint = sys.argv[1], sys.argv[2]
c = json.load(open('composer.json'))

# The path repositories point at ../ZerpPackages/*, which does not exist on the
# server. Resolve the modules from Packagist instead.
c.pop('repositories', None)
c['require'] = {k: (constraint if k.startswith('zerp/') else v)
                for k, v in c['require'].items()}

# Pin resolution to the server's PHP so the lock is installable there.
c.setdefault('config', {})['platform'] = {'php': platform}
c.pop('require-dev', None)

json.dump(c, open('composer.json', 'w'), indent=4)
open('composer.json', 'a').write('\n')
PY

# public/build is gitignored in the source repo; it is the whole point here.
sed -i '\#^/public/build$#d' .gitignore

echo "==> Resolving dependencies from Packagist (writing composer.lock)"
composer update --no-install --no-interaction --quiet
composer validate --no-check-publish --quiet

echo "==> Publishing"
cp "$APP/deploy/RELEASE_README.md" README.md
git add -A
if git diff --cached --quiet; then
  echo "    nothing changed — release is already current"
  exit 0
fi
git -c user.email=hafizmoazkhalid@gmail.com commit --quiet -m "$MSG"
git push --quiet origin HEAD
echo "==> Pushed. On Hostinger:  cd ~/zerp && git pull && ./deploy.sh"
