# Sentry package documentation

> Engineering documentation derived from the source in this package. The
> package's `includes/` directory must be denied to direct HTTP requests.

## Purpose

Optional Bitweaver package that reports PHP errors to a **Sentry-compatible**
server (sentry.io, self-hosted Sentry, or GlitchTip).

## Responsibility

- Register a Kernel error reporter when `sentry_dsn` is set.
- Map Kernel’s vendor-agnostic error hash to the Sentry Store API.
- Own DSN / environment / level preferences and admin UI.

## Dependencies

- **Kernel** — `bit_error_register_reporter()` / `bit_error_notify()` /
  `bit_error_build_report_hash()` (no vendor SDK in Kernel). PHP errors
  come from `bit_error_handler`. `bit_error_log()` notifies with channel
  `error_log` (ImageMagick CLI exits and other operational logs).
- Outbound HTTPS to the configured Sentry/GlitchTip host.

## Boundary

Does not replace Kernel email, compact admin error boxes, or Xdebug policy.
Does not embed the official `sentry/sentry` Composer SDK; uses a minimal HTTP
Store client (`SentryReporter`).

## Request / user context

GlitchTip/Sentry’s default “IP” on an event is often the **PHP host’s outbound
address** to the ingest host, not the browser client.

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

## Configuration

| Preference | Meaning |
|------------|---------|
| `sentry_dsn` | DSN URL (`https://<key>@host/<project_id>`). Empty = disabled. |
| `sentry_environment` | Optional environment tag; empty → `live` or `development`. |
| `sentry_report_levels` | Comma list: `notice`, `warning`, `deprecated`, `fatal`, `error`. Default `notice,fatal`. Filters **PHP** errors only. Channel `error_log` (`bit_error_log()`) is always sent when `sentry_dsn` is set. |

Admin: Kernel admin → package **Sentry** → Sentry Settings
(`kernel/admin/index.php?page=sentry`).

## Documentation map

- This README — purpose and configuration.
- [Security](security.md) — DSN handling and what is sent to the remote host.
