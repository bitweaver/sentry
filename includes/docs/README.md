# Sentry package documentation

> Engineering documentation derived from the source in this package. The
> package's `includes/` directory must be denied to direct HTTP requests.

## Purpose

Optional Bitweaver package that reports PHP errors to a **Sentry-compatible**
server (sentry.io, self-hosted Sentry, or GlitchTip such as
`sentry.bitweaver.org`).

## Responsibility

- Register a Kernel error reporter when `sentry_dsn` is set.
- Map Kernel’s vendor-agnostic error hash to the Sentry Store API.
- Own DSN / environment / level preferences and admin UI.

## Dependencies

- **Kernel** — `bit_error_register_reporter()` / `bit_error_notify()` /
  `bit_error_build_report_hash()` (no vendor SDK in Kernel).
- Outbound HTTPS to the configured Sentry/GlitchTip host.

## Boundary

Does not replace Kernel email, compact admin error boxes, or Xdebug policy.
Does not embed the official `sentry/sentry` Composer SDK; uses a minimal HTTP
Store client (`SentryReporter`).

## Configuration

| Preference | Meaning |
|------------|---------|
| `sentry_dsn` | DSN URL (`https://<key>@host/<project_id>`). Empty = disabled. |
| `sentry_environment` | Optional environment tag; empty → `live` or `development`. |
| `sentry_report_levels` | Comma list: `notice`, `warning`, `deprecated`, `fatal`, `error`. Default `notice,fatal`. |

Admin: Kernel admin → package **Sentry** → Sentry Settings
(`kernel/admin/index.php?page=sentry`).

## Documentation map

- This README — purpose and configuration.
- Kernel plan (developer-local): `$DEV_ROOT/kernel/plans/local-sentry-notice-fatal.md`
  — agnostic hook design and rollout notes.
