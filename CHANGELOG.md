# Changelog

## v1.2.5 - 2026-07-19

### Added
- **The installer now asks who the super admin is.** Both install paths
  collect the super admin name, email, and password instead of shipping a
  hardcoded account. `app:install` prompts interactively, or accepts
  `--admin-name`, `--admin-email`, and `--admin-password` for scripted
  installs; the GUI installer collects them on the database step and shows
  the chosen email on the final screen. With no input supplied on a
  non-interactive install, it falls back to `admin@zerp.pk` / `Admin@1234`.

### Changed
- **Default seeded accounts.** The test company is now
  `testcompany@zerp.pk` / `Test@1234`, and the default super admin (when
  not supplied at install) is `admin@zerp.pk` / `Admin@1234`. Demo seeders
  that referenced the super admin by its old fixed email now resolve it by
  account type, so a user-supplied email no longer breaks seeding.
- **Docs and README.** Added the Real Estate and Restaurant modules to the
  module table, and the setup instructions now cover Windows alongside
  Linux and macOS.

## v1.2.4 - 2026-07-19

### Security
- **Passwords only needed six characters.** Every account, staff and admins
  included, could be secured with a weak password: the user create and change
  password requests required `min:6` and nothing else, and the auth controllers
  that call `Password::defaults()` had no policy registered, so it fell back to a
  bare eight-character minimum. One policy now lives in `AppServiceProvider`
  (at least 8 characters, mixed case, a number, and a compromised-password check
  in production), and every password-setting path shares it.
- **Index page size was unbounded.** Listing endpoints paginated with a
  client-controlled `?per_page` and no upper limit, so `?per_page=1000000` forced
  the app to load and serialise an entire table in one response, a resource
  exhaustion any authenticated user could trigger. A new `perPage()` helper clamps
  the value to a maximum of 100 (non-numeric input falls back to the default) and
  every index now uses it.

## v1.2.3 - 2026-07-18

### Security
- **Sort parameters could inject SQL identifiers.** The `account` module's listing
  screens passed `?sort=` and `?direction=` straight into `orderBy()`. Eloquent binds
  values but not identifiers, so a crafted sort or direction reached the SQL text
  unescaped. A new `sortSafe` query macro now accepts a column only if it exists on the
  table (`Schema::hasColumn`) and a direction only if it is `asc` or `desc`, falling
  back to safe defaults otherwise. Ships as `zerp/account` v1.0.3.
- **Search terms were not escaped for LIKE.** Every listing search built a
  `LIKE '%term%'` clause without escaping, so a `%` or `_` in the input was read as a
  wildcard: `%` matched every row and `_` matched any single character. A new
  `likeEscape()` helper neutralises those metacharacters, and the seventeen controllers
  that search now wrap the term with it. Values stay bound; only the wildcards are
  escaped, so a literal `%` or `_` matches itself.

### Fixed
- **Email templates rendered a blank app name and URL in production.**
  `EmailTemplate::replaceVariable()` read `env('APP_NAME')` and `env('APP_URL')`, but
  `env()` returns null once `php artisan config:cache` has run, which every production
  install does. `{app_name}` and `{app_url}` came through empty in outgoing mail. Both
  now read from `config()`, which stays populated after caching. The same fix ships for
  the recruitment offer letter and its seeded template `from` as `zerp/recruitment` v1.0.4.
- **The installer database step never submitted.** Its submit handler only logged on
  error and never posted, so the step could not advance. It now posts to
  `installer.database.store`. Stray debug `console.log` calls were also removed from the
  installer environment step and the plan subscribe flow.
- **The Settings page became unreachable from the sidebar.** The `product-service`
  module attached its menu under `settings`, and the sidebar drops a parent item's own
  link once it has children, so the Settings entry turned into a collapse-only group and
  `/settings` had no way in. Settings is a single navigable page and the product screens
  are not sections of it, so Product & Service is now its own top-level menu item.
  Ships as `zerp/product-service` v1.0.4.

## v1.2.2 - 2026-07-17

### Security
- **`.env` is no longer in the repo.** It carried a real `APP_KEY` and
  `APP_DEBUG=true`. `APP_KEY` encrypts every session and cookie, so a shared one
  means anyone holding it can forge a login, and `APP_DEBUG` is also what enables
  the debug bar. Because the file shipped, the `cp .env.example .env` step in every
  install guide never ran, so a fresh install silently adopted both. `.env` is now
  ignored, and `app:install` creates it from the template.

### Changed
- **Defaults are now Asia/Karachi and PKR.** A fresh install gets the Pakistani
  timezone and rupee rather than UTC and the US dollar: `APP_TIMEZONE`, the
  `config/app.php` fallback, the settings seeder, the currency list's default entry,
  and every `'USD'` / `'$'` fallback in the app and frontend. Companies still pick
  their own in `Settings > Currency`; this only decides what they start with, and no
  existing setting is rewritten.
- **`.env.example` is Zerp's, not Laravel's stock file.** It defaulted to
  `DB_CONNECTION=sqlite` with every `DB_*` commented out, so copying it produced an
  install that could not reach a database. It now carries MySQL defaults matching
  what Docker Compose creates, `APP_DEBUG=false`, `LOG_LEVEL=error`, an empty
  `APP_KEY`, and a comment on each group explaining what to change.
- Dropped six variables nothing reads: `MAIL_DRIVER`, `CACHE_DRIVER`,
  `QUEUE_DRIVER`, `BROADCAST_DRIVER`, `APP_LOG_LEVEL` (Laravel 8 names that Laravel
  12 ignores) and `VITE_DEV_SERVER`. Added `PUSHER_APP_CLUSTER`, which the config
  reads but the template never offered.

## v1.2.1 - 2026-07-16

### Fixed
- **Module translations never loaded.** The translations endpoint merged a module's
  language file from `packages/local/<Module>/`, the path modules used before they
  became Composer packages. They live under `vendor/zerp/<package>/` now, so the file
  was never found and every module string fell back to its English key in every
  language. It failed silently: the check is a `File::exists()` that is simply false.
  French returned 1706 strings where it should return 6485, so 4779 translations per
  language were being dropped. Same class as the module image path fixed in v1.1.0.

## v1.2.0 - 2026-07-16

### Changed
- **Settings is one page.** The company's modules and the sidebar arranger were their
  own screens; both are sections of `Settings` now. The old URLs redirect, so existing
  links still work. The `Settings` sidebar entry no longer carries a permission and can
  no longer be hidden: the arranger lives inside it, so hiding it would leave no way
  back, and staff hold no settings permission at all yet still arrange their own
  sidebar. Sections are shown per permission, so staff see only the sidebar, and
  nothing they may not manage is sent to the browser.

### Fixed
- **A company saw modules its plan does not include.** Entitlement was the plan's
  modules merged with `user_active_modules`, so any row there widened it past the plan.
  `PackageSeeder` writes a row per installed module, so a seeded company saw everything
  regardless of its plan, and editing a plan to drop a module left subscribers holding
  it. The plan is the boundary now; `user_active_modules` records what was picked and
  paid for and can no longer widen the entitlement.
- The sidebar and route access had a **second, independent answer** to the same
  question: `ActivatedModule()` read `user_active_modules` directly and never consulted
  the plan, so a module outside the plan stayed in the menu and its routes kept
  resolving. Both now derive from one place.

## v1.1.2 - 2026-07-16

### Fixed
- **Creating a user returned a 500** when the mobile number was left blank. `mobile_no`
  is optional, so the form omits it, but `validated()` only returns keys the request
  actually sent. Reading the absent key raised "Undefined array key" and the request
  died. Editing a user had the same fault.
- The same pattern, an optional field read from `validated()` without a default, also
  affected warehouse phone/email and the helpdesk category and plan descriptions. All
  of them would have failed the same way once the field was left empty.
- **Creating a user returned a 500 when the company had no SMTP configured**, on a user
  that had in fact been created: the welcome and verification emails are sent after the
  user is saved, and `SetConfigEmail()` throws when no mail host is set. Both sends are
  now guarded. The user is created, the admin gets a warning that the email did not go
  out, and the reason is logged. Converting a lead to a deal had the same fault, where a
  failure left the client created but the deal never made (fixed in zerp/lead v1.0.5).
- Warning flash messages never reached the screen: the frontend has handled
  `flash.warning` all along, but the middleware only shared `success` and `error`.

## v1.1.1 - 2026-07-16

### Fixed
- **`Settings → Modules` returned a 500.** The screen read the module catalogue as
  arrays, but `Module::find()` returns the module object itself, so rendering the page
  raised a fatal `Error`. Module images now come from the catalogue too, which resolves
  an add-on's own uploaded image and the `vendor/zerp/` path - the old hardcoded
  `packages/local/` path no longer exists for modules that moved to Composer packages.
- **Blank page at `/index.php`.** The root front controller was Laravel's dev-server
  shim, whose `return false` only means "serve the static file yourself" to `php -S`;
  Apache and LiteSpeed ended the request with an empty `200` instead. It now always
  hands off to `public/index.php`. `.htaccess` serves the static assets and never
  routed them through here, so nothing else changes.

## v1.1.0 - 2026-07-16

### Security
- **Cross-tenant isolation.** Module controllers authorised mutations with a
  capability check alone (`can('edit-leads')`), which every company's staff passes,
  so another company's record id resolved and could be read, edited or deleted. The
  boundary now lives on the models as a global scope, so a foreign id resolves to
  null and route-model binding 404s before a controller runs. Applied across the
  Lead, HRM, Accounting, Recruitment and Support Ticket modules.
- Public per-company portals (job board, help centre) stand the scope down for the
  request, since serving another company's postings by URL slug is the point of a
  portal. The public ticket form validates against the portal's company rather than
  the submitter's.
- The backend tree sits under the document root on shared hosting; `storage/` is now
  denied by URI except the `/storage/media/` upload prefix.

### Added
- **Modules screen** (`Settings → Modules`): a company can switch off modules it does
  not use. A disabled module leaves the sidebar *and* stops resolving its routes.
  Switching a module off never destroys a paid entitlement, and enabling is bounded
  by what the plan or purchased add-ons allow.
- **Sidebar arranger** (`Settings → Menu`): drag to reorder, hide what you do not
  want. Owners can set a company default; anyone can override it with their own
  layout. Permissions still decide what exists - hiding only affects the sidebar.

### Fixed
- Dropped the HRM `system-setup` route, which pointed at a controller that was never
  written: it threw from `artisan route:list` and would have 500'd in production.
- Release publishing: nested `public/build/build/` path, missing `composer.lock`,
  absolute module asset symlinks, and `.env` no longer publishable.

## v1.0.0 - 2026-07-10

First stable release of Zerp - an open, modular ERP platform.

### Core
- Multi-company ERP core: authentication, roles/permissions, plan & module gating, media library.
- Module system: packages auto-discovered from `zerp/*`, enabled per company, wired together through domain events (no direct cross-module calls).

### Modules (33)
Accounting & double-entry, products & inventory, POS, quotations, contracts,
CRM (leads/deals with scoring, forecasting, activities), HRM, recruitment,
performance, training, timesheets, goals, budget planner, project & task
management, support tickets, form builder, calendar, landing page, and
industry verticals (real estate, restaurant), plus integrations (Stripe,
PayPal, Twilio, Slack, Telegram, Zoom, Google Meet, Jitsi, webhooks, AI
assistant).

### Developer docs
- Restructured developer documentation with an auto-generated per-package
  class reference.
