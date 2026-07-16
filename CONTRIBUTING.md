# Contributing to Zerp

Thanks for considering contributing! A few things are worth knowing before
you dive in.

## Repository layout

Zerp isn't a single repo - it's this core app (auth, settings, billing, the
Media Library, and other cross-cutting infrastructure) plus **30 separate
module repositories** under the [zerp-pk](https://github.com/zerp-pk)
organization (HRM, CRM, Accounting, POS, etc. - see the full table in
[README.md](README.md)). Each module is its own Composer package, pulled in
as a local path repository and symlinked into `vendor/zerp/<module>/`.

**Where to open your PR depends on what you're changing:**

- Core app behavior (auth, settings, the installer, the Media Library,
  billing/plans, cross-module infrastructure) → this repo.
- A specific feature module's behavior (e.g. an HRM screen, a CRM field, a
  payment gateway integration) → that module's own repository under
  `zerp-pk`.

If you're not sure which repo something belongs in, open an issue here and
we'll help point you in the right direction.

## Development setup

Follow the README's [Get the module packages](README.md#get-the-module-packages)
and [Installation](README.md#installation) sections to get a working local
environment (native, or via Docker). You'll need every module repo cloned as
a sibling directory even if you're only working on the core app, since
`composer install` won't succeed otherwise.

## Making a change

1. Fork the repo (or the specific module repo you're changing) and create a
   branch off `main`.
2. Make your change. Keep commits focused - one logical change per commit is
   easier to review than a single commit doing five unrelated things.
3. Match the existing code style in the file(s) you're touching (PSR-12 for
   PHP, the existing TypeScript/React conventions for frontend code). There's
   no automated formatter configured yet, so consistency with surrounding
   code is the bar.
4. If you're fixing a bug, include a short note in the PR description on how
   you verified the fix (steps to reproduce before/after, or a test).
5. Open a pull request using the PR template. Reference any related issue.

## Reporting bugs / requesting features

Use the issue templates - they ask for the info that's actually needed to
act on a report (repro steps, expected vs. actual behavior, which
module/repo it affects).

## Security issues

Please don't open a public issue for security vulnerabilities - see
[SECURITY.md](SECURITY.md) for the private reporting process.

## Code of Conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
Participation in this project means agreeing to abide by it.
