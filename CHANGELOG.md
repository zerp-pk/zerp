# Changelog

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
