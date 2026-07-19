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
| real-estate | Real Estate | [zerp-pk/real-estate](https://github.com/zerp-pk/real-estate) | `zerp/real-estate` |
| recruitment | Recruitment | [zerp-pk/recruitment](https://github.com/zerp-pk/recruitment) | `zerp/recruitment` |
| restaurant | Restaurant | [zerp-pk/restaurant](https://github.com/zerp-pk/restaurant) | `zerp/restaurant` |
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
module packages" below) - `composer install` symlinks them into
`vendor/zerp/<module>/`.

## Prerequisites

- PHP 8.2+ (tested with 8.5) with the `pdo_mysql` extension enabled
- Composer
- Node.js + npm
- MySQL/MariaDB server running

Check `pdo_mysql` is enabled:

```bash
# Linux / macOS
php -m | grep pdo_mysql

# Windows (PowerShell or CMD)
php -m | findstr pdo_mysql
```

If missing, enable it in your `php.ini` by uncommenting
`extension=pdo_mysql`, then restart your terminal. The `php.ini` in use
is shown by `php --ini` - typically `/etc/php/php.ini` (or a distro
`conf.d/`) on Linux, `/usr/local/etc/php/` under Homebrew on macOS, and
the file next to `php.exe` (e.g. `C:\php\php.ini` or the XAMPP/Laragon
PHP folder) on Windows. Or pass it ad hoc:
`php -d extension=pdo_mysql artisan ...`.

> **Windows note:** the `php artisan`, `composer`, and `npm` commands
> below are identical on every OS. Only a few shell built-ins differ -
> those lines call out the Windows form inline. Run them from PowerShell
> or CMD (or use WSL, where the Linux commands apply verbatim).

The CLI `memory_limit` also needs to be reasonably high (this app's
class map is large) - 512M is safe:
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

### Option A - Automatic (recommended)

The fastest path: one command runs migrations, seeds the database, and
walks you through which modules to enable.

```bash
composer install
npm install
cp .env.example .env      # Windows: copy .env.example .env
```

`.env` is not in the repo: it holds your secrets, and every install needs
its own. `.env.example` is the template, and its defaults are ready to run.

Set your database credentials in `.env` (`DB_HOST`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`) and make sure `APP_URL` matches the port
you'll serve on (e.g. `http://localhost:8000`).

`APP_KEY` starts empty. `app:install` generates one, or run
`php artisan key:generate` yourself. It encrypts every session and cookie,
so keep it secret and never reuse one between installs.

```bash
php artisan app:install --force
php artisan storage:link
```

`app:install` prompts for the **super admin** name, email, and password,
then for a **module preset** (`Full Suite`, `HR Only`, `Sales & CRM`, or a
`Custom selection` picker) - see `config/module-presets.php` for the bundle
definitions. For scripted/non-interactive installs, pass the credentials
with `--admin-name`, `--admin-email`, `--admin-password`, and skip the
module prompt with `--preset=<name>` or `--modules=account,hrm,pos`
(comma-separated `package_name` slugs); with no module flag and no TTY it
defaults to installing everything, and with no admin flags it falls back to
the default super admin (see "Log in" below).

⚠️ `app:install` runs `migrate:fresh`, which **drops all tables**.
Only run it on a fresh/disposable database.

Then skip to "Run the app" below.

### Option B - Manual

Run the steps `app:install` would otherwise do yourself - useful if you
already have data and don't want `migrate:fresh` to wipe it. This path
never runs `app:install`, so nothing creates `.env` or the key for you:

```bash
cp .env.example .env          # Windows: copy .env.example .env  (then set DB_* and APP_URL in it)
php artisan key:generate      # APP_KEY starts empty; nothing works without it
php artisan migrate
php artisan db:seed --force   # also registers every module into add_ons via PackageSeeder
php artisan storage:link
touch storage/installed       # marks the app as installed, skips the /install wizard
                              # Windows (CMD): type nul > storage\installed
                              # Windows (PowerShell): New-Item storage/installed -ItemType File
```

### Option C - Docker

No local PHP/Composer/Node/MySQL setup needed - everything runs in
containers. Still requires the sibling `ZerpPackages/` layout above,
since the image is built with the **parent** directory as build
context (so both `zerp/` and `ZerpPackages/` are visible to
`composer install` inside the container).

```bash
cp .env.example .env          # Windows: copy .env.example .env
docker compose up -d --build
docker compose exec app php artisan app:install --force
docker compose exec app php artisan storage:link
```

The copy is required, not optional: Compose reads `.env` for the app and to
create the MySQL user, and won't start without it. The template's `DB_*`
defaults match what Compose creates, so it works unchanged - `DB_HOST` and
`REDIS_HOST` are pointed at the containers for you.

Add `-it` to the `exec` command above (`docker compose exec -it app ...`)
to get the interactive module picker; without it, `app:install` has no
TTY and installs every module by default. To choose modules without a
TTY, pass `--preset=<name>` or `--modules=account,hrm,pos` instead.

The app is served at `http://localhost:8000`. MySQL and Redis run as
their own containers (`db`, `redis`); `docker-compose.yml` wires
`.env`'s `DB_HOST`/`REDIS_HOST` to the container service names
automatically.

## Run the app

(Skip this if you used Option C - Docker already runs everything.)

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

**Super admin** - you set these during install. `app:install` prompts for
the super admin name, email, and password (the GUI installer collects them
on the database step). For scripted installs pass
`--admin-name`, `--admin-email`, and `--admin-password`. If you skip them
on a non-interactive install, the seeder falls back to:

- Email: `admin@zerp.pk`
- Password: `Admin@1234`

**Test company** - a ready-to-use company account is always seeded:

- Email: `testcompany@zerp.pk`
- Password: `Test@1234`

Change these passwords after your first login.

## API documentation (Swagger / OpenAPI)

The REST API is documented with [Scramble](https://scramble.dedoc.co/),
which generates OpenAPI specs from the controllers' type hints, form
requests, and API resources - no annotations to maintain. With the app
running, open the interactive Swagger UI in your browser:

| URL | Scope |
|---|---|
| `http://localhost:8000/docs/api` | Everything - core auth plus every module's API |
| `http://localhost:8000/docs/hrm` | HRM module only |
| `http://localhost:8000/docs/support-ticket` | Support Ticket module only |
| `http://localhost:8000/docs/taskly` | Project (Taskly) module only |

Each page has a **Download** link for the raw OpenAPI JSON (or fetch it
directly, e.g. `/docs/api.json`) to import into Postman, Insomnia, or a
client generator. Endpoints behind `auth:*` show an **Authorize** button -
paste a bearer token (Sanctum) to try them live.

The per-module pages exist only for modules that ship an API today (HRM,
Support Ticket, Taskly); more appear as other modules gain API routes.

> **Access is restricted to the `local` environment** by Scramble's
> `RestrictedDocsAccess` middleware, so `/docs/*` returns 403 in
> production. To expose it elsewhere, define a `viewApiDocs` gate or adjust
> `middleware` in `config/scramble.php`. Verify a spec builds with
> `php artisan scramble:analyze --api=hrm`.

## Troubleshooting

- **`could not find driver` on any DB command** - `pdo_mysql` isn't
  loaded; see Prerequisites above.
- **`Allowed memory size exhausted` running `artisan tinker` or other
  commands** - bump CLI memory limit: `php -d memory_limit=512M artisan ...`.
- **(Windows) `storage:link` fails or uploaded media 404s** - creating
  symlinks needs elevation. Run the terminal as Administrator (or enable
  Windows Developer Mode) and re-run `php artisan storage:link`.
- **Redirected to `/install` in the browser after already
  installing** - the `storage/installed` marker file is missing;
  either re-run `php artisan app:install --force` or, if the DB is
  already migrated/seeded and you just need to skip the wizard,
  `touch storage/installed` (only do this if you're sure the DB is in
  a complete, installed state, including module registration).
- **Plans page shows no features for any plan** - `db:seed` (which
  `app:install` runs) registers every module found under
  `packages/local/*` and `vendor/zerp/*` into the `add_ons` table via
  `PackageSeeder`. If it's still empty, confirm the module packages
  are actually present under `vendor/zerp/` (see "Get the module
  packages" above - `composer install` symlinks them from the sibling
  `ZerpPackages/` directory; if that directory is missing, nothing
  gets installed) and re-run `php artisan db:seed --force` or
  `php artisan app:install --force`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) for how to report a vulnerability.

## License

[MIT](LICENSE).
