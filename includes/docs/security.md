# Sentry package security

## Direct HTTP

`includes/.htaccess` and `includes/web.config` deny direct web access to this
tree. Confirm with 403/404 on `includes/` PHP and docs URLs (not PHP 500).

## Secrets

`sentry_dsn` contains a project key. Treat it like a credential:

- Only `p_sentry_admin` / site admins may view or change it in Kernel admin.
- Do not commit real DSNs into git.
- Prefer HTTPS DSNs.

## Data sent to the remote server

Kernel’s report hash is already scrubbed (compact stack, no raw POST/SESSION).
This package forwards that hash plus host/script/URI tags. Channel `error_log`
may include CLI command lines and stderr (for example ImageMagick) in
`message` / `extra.detail`. Do not extend the reporter to attach payment
fields, cookies, or full session dumps.

## Trust

The configured Sentry/GlitchTip host receives application error text and paths.
Use an internal or trusted host (self-hosted Sentry, private GlitchTip, or
sentry.io under your org).
