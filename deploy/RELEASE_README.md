# zerp-release

**Generated repo — do not edit by hand.** It is rebuilt and published by
`deploy/publish.sh` in [zerp-pk/zerp](https://github.com/zerp-pk/zerp). Any commit
made directly here is wiped by the next publish.

This is the tree Hostinger clones. It differs from the source repo in three ways:

- `public/build/` (the compiled Vite bundle) **is committed** — the server has no Node.
- `composer.json` pulls the `zerp/*` modules from **Packagist**, not from the
  `../ZerpPackages` path repositories, which don't exist on the server.
- `vendor/` is **not** committed; `composer install` runs on the server.

## First-time setup on Hostinger

The app is cloned **directly into `public_html`**, so it is served at `https://zerp.pk/`
— not `zerp.pk/public/`. The committed root `.htaccess` and `index.php` handle that:
requests are rewritten into `public/`, and the backend tree is blocked from the web
(see [Security](#security-check-do-this-before-announcing-the-site)). You do **not**
need to change the document root.

Add the Hostinger SSH key to this repo as a **deploy key** (Settings → Deploy keys),
with the `github-zerp` host alias in `~/.ssh/config` on the server:

```
Host github-zerp
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_zerp
  IdentitiesOnly yes
```

Then over SSH on the server:

```bash
cd ~/public_html
git clone git@github-zerp:zerp-pk/zerp-release.git .   # note the trailing dot

cp .env.example .env
php artisan key:generate
# Then set in .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://zerp.pk,
# and the DB_* credentials from hPanel.

COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`public_html` must be empty before the clone — Git refuses to clone into a directory
that already has files. Remove Hostinger's `default.php` / `index.html` placeholders first.

### Security check — do this before announcing the site

The whole app lives under the document root, so the `.htaccess` deny rules are the
only thing keeping the source private. Verify they are active. Every one of these
must return **403**:

```bash
for p in .env .git/config storage/logs/laravel.log config/database.php composer.lock; do
  echo "$p -> $(curl -s -o /dev/null -w '%{http_code}' https://zerp.pk/$p)"
done
```

If any returns 200, Apache is ignoring `.htaccess` (`AllowOverride` is off) — stop and
fix that before going live, or your source and DB credentials are downloadable. These
must still return **200**: `https://zerp.pk/build/manifest.json` and the landing page.

## Every deploy after that

```bash
cd ~/public_html && ./deploy.sh
```

It pulls, reinstalls dependencies, migrates, re-caches, and fails loudly if the
frontend bundle or a module's assets are missing.

## Module images

`public/packages/local/<Module>/` holds relative symlinks into `vendor/zerp/<pkg>`,
committed to this repo. They resolve once `composer install` has run. If module images
404, look for dangling links with `find public/packages -xtype l` — output there means
a module failed to install.
