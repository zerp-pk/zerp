#!/usr/bin/env bash
#
# Check that a deployed zerp is not serving its own source.
#
# Run this against any host before announcing it, whatever web server is in
# front. It encodes the checklist from RELEASE_README.md so it cannot drift
# from the vhosts in this directory.
#
# Usage:  ./verify-hardening.sh https://zerp.example.com
#
# Exit status is 1 if anything is exposed, so it can gate a deploy.
set -uo pipefail

BASE="${1:-}"
if [ -z "$BASE" ]; then
    echo "usage: $0 <base-url>" >&2
    exit 2
fi
BASE="${BASE%/}"

fail=0

# Paths that must not be readable. A 403 or a 404 both pass: on a document root
# pointed at public/ these are not under the webroot at all and the app answers
# 404, which is as private as a deny.
DENY=(
    ".env"
    ".git/config"
    ".git/HEAD"
    "storage/logs/laravel.log"
    "storage/framework/sessions"
    "config/database.php"
    "app/Http/Kernel.php"
    "bootstrap/app.php"
    "database/seeders/DatabaseSeeder.php"
    "resources/views/app.blade.php"
    "routes/web.php"
    "vendor/autoload.php"
    "vendor/composer/installed.json"
    "composer.json"
    "composer.lock"
    "package.json"
    "artisan"
    "deploy.sh"
    "docker-compose.yml"
    "Dockerfile"
    "phpunit.xml"
    "tsconfig.json"
    "README.md"
    "packages/local/Pos/composer.json"
)

# Paths that must keep working, or the hardening has broken the app.
ALLOW=(
    ""
    "build/manifest.json"
)

echo "Checking $BASE"
echo

echo "Must NOT be readable (403 or 404):"
for p in "${DENY[@]}"; do
    code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "$BASE/$p" 2>/dev/null)
    # A 5xx from the gateway means the request reached PHP and the app is
    # unhealthy, not that the file was handed out. Worth failing on, but it is
    # a different problem from an exposed file and saying so saves a panic.
    case "$code" in
        401|403|404)     verdict="ok" ;;
        502|503|504)     verdict="app down, cannot tell"; fail=1 ;;
        000)             verdict="unreachable"; fail=1 ;;
        *)               verdict="EXPOSED"; fail=1 ;;
    esac
    printf "  %-45s %-4s %s\n" "/$p" "$code" "$verdict"
done

echo
echo "Must still be served (200):"
for p in "${ALLOW[@]}"; do
    code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "$BASE/$p" 2>/dev/null)
    if [ "$code" = "200" ]; then verdict="ok"; else verdict="BROKEN"; fail=1; fi
    printf "  %-45s %-4s %s\n" "/$p" "$code" "$verdict"
done

echo
if [ "$fail" -eq 0 ]; then
    echo "PASS - nothing exposed, the app still serves."
else
    echo "FAIL - see the lines marked EXPOSED or BROKEN above."
    echo
    echo "If everything says \"app down\", PHP is not answering and this says"
    echo "nothing about the hardening either way. Fix that and re-run."
    echo
    echo "On Apache: the .htaccess files are being ignored, most likely because"
    echo "AllowOverride is off for this directory."
    echo "On nginx:  .htaccess is never read. Install one of the vhosts in this"
    echo "directory (see README.md) - without one, nothing is denied at all."
fi

exit "$fail"
