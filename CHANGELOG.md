# Changelog

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
