# Zerp

Laravel 12 + Inertia + React (TypeScript) ERP/SaaS platform. Core
business logic (auth, settings, billing, the Media Library, etc.)
lives in this repo; every feature module is a separate Composer
package.

## Modules

| Module | Alias | Repository | Composer package |
|---|---|---|---|
| account | Accounting | [zerp-pk/account](https://github.com/zerp-pk/account) | `zerp/account` |
| aiassistant | AI Assistant | [zerp-pk/aiassistant](https://github.com/zerp-pk/aiassistant) | `zerp/aiassistant` |
| budget-planner | Budget Planner | [zerp-pk/budget-planner](https://github.com/zerp-pk/budget-planner) | `zerp/budget-planner` |
| calendar | Calendar | [zerp-pk/calendar](https://github.com/zerp-pk/calendar) | `zerp/calendar` |
| contract | Contract | [zerp-pk/contract](https://github.com/zerp-pk/contract) | `zerp/contract` |
| double-entry | Double Entry | [zerp-pk/double-entry](https://github.com/zerp-pk/double-entry) | `zerp/double-entry` |
| form-builder | Form Builder | [zerp-pk/form-builder](https://github.com/zerp-pk/form-builder) | `zerp/form-builder` |
| goal | Financial Goal | [zerp-pk/goal](https://github.com/zerp-pk/goal) | `zerp/goal` |
| google-captcha | Google Captcha | [zerp-pk/google-captcha](https://github.com/zerp-pk/google-captcha) | `zerp/google-captcha` |
| google-meet | Google Meet | [zerp-pk/google-meet](https://github.com/zerp-pk/google-meet) | `zerp/google-meet` |
| hrm | HRM | [zerp-pk/hrm](https://github.com/zerp-pk/hrm) | `zerp/hrm` |
| jitsi | Jitsi Meet | [zerp-pk/jitsi](https://github.com/zerp-pk/jitsi) | `zerp/jitsi` |
| landing-page | CMS | [zerp-pk/landing-page](https://github.com/zerp-pk/landing-page) | `zerp/landing-page` |
| lead | CRM | [zerp-pk/lead](https://github.com/zerp-pk/lead) | `zerp/lead` |
| paypal | Paypal | [zerp-pk/paypal](https://github.com/zerp-pk/paypal) | `zerp/paypal` |
| performance | Performance | [zerp-pk/performance](https://github.com/zerp-pk/performance) | `zerp/performance` |
| pos | POS | [zerp-pk/pos](https://github.com/zerp-pk/pos) | `zerp/pos` |
| product-service | Product & Service | [zerp-pk/product-service](https://github.com/zerp-pk/product-service) | `zerp/product-service` |
| quotation | Quotation | [zerp-pk/quotation](https://github.com/zerp-pk/quotation) | `zerp/quotation` |
| recruitment | Recruitment | [zerp-pk/recruitment](https://github.com/zerp-pk/recruitment) | `zerp/recruitment` |
| slack | Slack | [zerp-pk/slack](https://github.com/zerp-pk/slack) | `zerp/slack` |
| stripe | Stripe | [zerp-pk/stripe](https://github.com/zerp-pk/stripe) | `zerp/stripe` |
| support-ticket | Support Ticket | [zerp-pk/support-ticket](https://github.com/zerp-pk/support-ticket) | `zerp/support-ticket` |
| taskly | Project | [zerp-pk/taskly](https://github.com/zerp-pk/taskly) | `zerp/taskly` |
| telegram | Telegram | [zerp-pk/telegram](https://github.com/zerp-pk/telegram) | `zerp/telegram` |
| timesheet | Timesheet | [zerp-pk/timesheet](https://github.com/zerp-pk/timesheet) | `zerp/timesheet` |
| training | Training | [zerp-pk/training](https://github.com/zerp-pk/training) | `zerp/training` |
| twilio | Twilio | [zerp-pk/twilio](https://github.com/zerp-pk/twilio) | `zerp/twilio` |
| webhook | Webhook | [zerp-pk/webhook](https://github.com/zerp-pk/webhook) | `zerp/webhook` |
| zoom-meeting | Zoom Meeting | [zerp-pk/zoom-meeting](https://github.com/zerp-pk/zoom-meeting) | `zerp/zoom-meeting` |

Each module is referenced in this repo's `composer.json` as a local
`path` repository pointing at a **sibling** directory (see "Get the
module packages" below) — `composer install` symlinks them into
`vendor/zerp/<module>/`.

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

## Get the module packages

Every module in the table above needs to be cloned as a **sibling**
of this repo, matching the layout its `composer.json` path
repositories expect:

```
some-folder/
├── zerp/            (this repo)
└── ZerpPackages/
    ├── hrm/
    ├── account/
    ├── pos/
    └── ...           (one directory per module, matching composer.json)
```

This is required for every installation method below, including
Docker.

## Installation

Pick one of three ways to get a running instance.

### Option A — Automatic (recommended)

The fastest path: one command runs migrations, seeds the database,
and registers/enables every module.

```bash
composer install
npm install
cp .env.example .env   # if .env doesn't already exist
```

Set your database credentials in `.env` (`DB_HOST`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`) and make sure `APP_URL` matches the port
you'll serve on (e.g. `http://localhost:8000`). Generate a key if
`.env` doesn't already have one: `php artisan key:generate`.

```bash
php artisan app:install --force
php artisan storage:link
```

⚠️ `app:install` runs `migrate:fresh`, which **drops all tables**.
Only run it on a fresh/disposable database.

Then skip to "Run the app" below.

### Option B — Manual

Same as Option A up through generating `APP_KEY`, then run the steps
`app:install` would otherwise do yourself — useful if you already have
data and don't want `migrate:fresh` to wipe it:

```bash
php artisan migrate
php artisan db:seed --force   # also registers every module into add_ons via PackageSeeder
php artisan storage:link
touch storage/installed       # marks the app as installed, skips the /install wizard
```

### Option C — Docker

No local PHP/Composer/Node/MySQL setup needed — everything runs in
containers. Still requires the sibling `ZerpPackages/` layout above,
since the image is built with the **parent** directory as build
context (so both `zerp/` and `ZerpPackages/` are visible to
`composer install` inside the container).

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan app:install --force
docker compose exec app php artisan storage:link
```

The app is served at `http://localhost:8000`. MySQL and Redis run as
their own containers (`db`, `redis`); `docker-compose.yml` wires
`.env`'s `DB_HOST`/`REDIS_HOST` to the container service names
automatically.

## Run the app

(Skip this if you used Option C — Docker already runs everything.)

Backend (Laravel):

```bash
php artisan serve --port=8000
```

Frontend (Vite, HMR dev server):

```bash
npm run dev
```

Visit `http://localhost:8000`.

## Log in

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
  a complete, installed state, including module registration).
- **Plans page shows no features for any plan** — `db:seed` (which
  `app:install` runs) registers every module found under
  `packages/local/*` and `vendor/zerp/*` into the `add_ons` table via
  `PackageSeeder`. If it's still empty, confirm the module packages
  are actually present under `vendor/zerp/` (see "Get the module
  packages" above — `composer install` symlinks them from the sibling
  `ZerpPackages/` directory; if that directory is missing, nothing
  gets installed) and re-run `php artisan db:seed --force` or
  `php artisan app:install --force`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) for how to report a vulnerability.

## License

[MIT](LICENSE).
