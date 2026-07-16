# Changelog

## v1.1.0 — 2026-07-16

### Security
- **Cross-tenant isolation.** Module controllers authorised mutations with a
  capability check alone (`can('edit-leads')`), which every company's staff passes —
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
  layout. Permissions still decide what exists — hiding only affects the sidebar.

### Fixed
- Dropped the HRM `system-setup` route, which pointed at a controller that was never
  written: it threw from `artisan route:list` and would have 500'd in production.
- Release publishing: nested `public/build/build/` path, missing `composer.lock`,
  absolute module asset symlinks, and `.env` no longer publishable.

## v1.0.0 — 2026-07-10

First stable release of Zerp — an open, modular ERP platform.

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
