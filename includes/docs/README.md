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

## Request / user context

GlitchTip/Sentry’s default “IP” on an event is often the **app server’s outbound
address** to the ingest host (e.g. `72.15.192.x`), not the browser client.

This package maps Kernel report fields (same sources as `bit_error_string()` /
`bit_error_email()` headers) into the Store payload:

| Email-style header | Sentry field |
|--------------------|--------------|
| `#### IP` (`REMOTE_ADDR`) | `user.ip_address`, `request.env.REMOTE_ADDR`, tag `ip` |
| `#### ACCT` | `user.id` / `username` / `email`, `extra.acct` |
| `#### USER AGENT` | `request.headers.User-Agent` |
| `#### URL` | `request.url` |
| `#### REFERRER` | `request.headers.Referer` |
| `#### HOST` | `server_name` / `request.env.HTTP_HOST` |
| `#### DB` | `extra.db` (no password) |

Developer-local GlitchTip API tokens for Grok (never commit) live under
`$DEV_ROOT/.secrets` as `name: token` lines, e.g.
`grok_glitchtip_sentry.bitweaver.org: <token>`.

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
