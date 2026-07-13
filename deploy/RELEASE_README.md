# zerp-release

**Generated repo — do not edit by hand.** It is rebuilt and pushed by
`deploy/publish.sh` in [zerp-pk/zerp](https://github.com/zerp-pk/zerp). Any commit
you make directly here is wiped by the next publish.

This is the tree Hostinger clones. It differs from the source repo in three ways:

- `public/build/` (the compiled Vite bundle) **is committed** — the server has no Node.
- `composer.json` pulls the `zerp/*` modules from **Packagist**, not from the
  `../ZerpPackages` path repositories, which don't exist on the server.
- `vendor/` is **not** committed; run `composer install` on the server.

## First-time setup on Hostinger

Add your Hostinger SSH key as a **deploy key** on this repo (Settings → Deploy keys),
then over SSH:

```bash
cd ~
git clone git@github.com:zerp-pk/zerp-release.git zerp
cd zerp

cp .env.example .env
# Set APP_KEY (php artisan key:generate), APP_ENV=production, APP_DEBUG=false,
# APP_URL, and the DB_* credentials from hPanel.
php artisan key:generate

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache route:cache view:cache
```

If Composer is killed for memory, raise its limit for that one command:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

### Document root

Laravel must be served from `public/`, never the project root — otherwise `.env`
and the whole source tree are downloadable over HTTP. Point the domain's document
root at `~/zerp/public` in hPanel. If Hostinger pins you to `public_html`, symlink
it instead:

```bash
rm -rf ~/public_html && ln -s ~/zerp/public ~/public_html
```

Verify before you announce the site: `curl -I https://yourdomain/.env` must **not**
return 200.

## Every deploy after that

```bash
cd ~/zerp && ./deploy.sh
```

## Module images

`public/packages/local/<Module>/` holds relative symlinks into `vendor/zerp/<pkg>`,
committed to this repo. They resolve once `composer install` has run. If module
images 404, check for dangling links with `find public/packages -xtype l` — output
there means a module failed to install.
