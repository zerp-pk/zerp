# Zerp — Local Setup

Laravel 12 + Inertia + React (TypeScript) ERP/SaaS application, with
feature modules (HRM, CRM, Accounting, POS, Recruitment, Support
Ticket, etc.) pulled in as separate Composer packages — see the
`repositories` entries in `composer.json` for the full module list.

## Prerequisites

- PHP 8.2+ (tested with 8.5) with the `pdo_mysql` extension enabled
- Composer
- Node.js + npm
- MySQL/MariaDB server running

Check `pdo_mysql` is enabled:

```bash
php -m | grep pdo_mysql
```

If missing, enable it in `/etc/php/php.ini` (or your distro's
`conf.d`) with `extension=pdo_mysql`, or pass it ad hoc:
`php -d extension=pdo_mysql artisan ...`.

The CLI `memory_limit` also needs to be reasonably high (this app's
class map is large) — 512M is safe:
`php -d memory_limit=512M artisan ...`.

## 1. Get the module packages

Feature modules (HRM, CRM, Accounting, POS, Recruitment, Support
Ticket, etc.) are **not** part of this repo — each is its own Composer
package, referenced in `composer.json` as a local `path` repository
pointing at a **sibling** directory:

```json
{ "type": "path", "url": "../ZerpPackages/hrm", "options": { "symlink": true } }
```

So before installing, lay out the two repos next to each other:

```
some-folder/
├── zerp/            (this repo)
└── ZerpPackages/
    ├── hrm/
    ├── account/
    ├── pos/
    └── ...           (one directory per module, matching composer.json)
```

Clone/place every module listed in `composer.json`'s `repositories`
array under `ZerpPackages/`. `composer install` (next step) symlinks
each one into `vendor/zerp/<module>/`.

## 2. Install dependencies

```bash
composer install
npm install
```

## 3. Configure environment

```bash
cp .env.example .env   # if .env doesn't already exist
```

Set your database credentials in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zerp
DB_USERNAME=<your_db_user>
DB_PASSWORD=<your_db_password>
```

Make sure `APP_URL` matches the port you'll run `artisan serve` on
(e.g. `http://localhost:8000`).

Create the database and a user (adjust to your MySQL setup):

```sql
CREATE DATABASE IF NOT EXISTS zerp;
CREATE USER IF NOT EXISTS 'test'@'127.0.0.1' IDENTIFIED BY '<strong_password>';
GRANT ALL PRIVILEGES ON zerp.* TO 'test'@'127.0.0.1';
FLUSH PRIVILEGES;
```

> Note: MySQL's default password policy rejects weak passwords (e.g.
> plain `test`) — use something with mixed case, digits, and symbols.

Generate an app key if `.env` doesn't already have one:

```bash
php artisan key:generate
```

## 4. Install the application

Use the app's own installer command — **do not** run `migrate` +
`db:seed` manually instead of this, it also registers every feature
module into the `add_ons` table, which the `/plans` page needs to
show plan features. Skipping it leaves the plans page with an empty
feature list.

```bash
php artisan app:install --force
```

This runs `migrate:fresh`, `db:seed`, and installs/enables every
module, then marks the app as installed by creating `storage/installed`.

⚠️ `app:install` runs `migrate:fresh`, which **drops all tables**.
Only run it on a fresh/disposable database.

Then link storage for public file access:

```bash
php artisan storage:link
```

## 5. Run the app

Backend (Laravel):

```bash
php artisan serve --port=8000
```

Frontend (Vite, HMR dev server):

```bash
npm run dev
```

Visit `http://localhost:8000`.

## 6. Log in

Default seeded company/super-admin account:

- Email: `company@example.com`
- Password: `1234`

## Troubleshooting

- **`could not find driver` on any DB command** — `pdo_mysql` isn't
  loaded; see Prerequisites above.
- **`Allowed memory size exhausted` running `artisan tinker` or other
  commands** — bump CLI memory limit: `php -d memory_limit=512M artisan ...`.
- **Redirected to `/install` in the browser after already
  installing** — the `storage/installed` marker file is missing;
  either re-run `php artisan app:install --force` or, if the DB is
  already migrated/seeded and you just need to skip the wizard,
  `touch storage/installed` (only do this if you're sure the DB is in
  a complete, installed state, including module registration — see
  step 4).
- **Plans page shows no features for any plan** — `db:seed` (which
  `app:install` runs) registers every module found under
  `packages/local/*` and `vendor/zerp/*` into the `add_ons` table via
  `PackageSeeder`. If it's still empty, confirm the module packages
  are actually present under `vendor/zerp/` (see step 1 — `composer
  install` symlinks them from the sibling `ZerpPackages/` directory;
  if that directory is missing, nothing gets installed) and re-run
  `php artisan db:seed --force` or `php artisan app:install --force`.
