# Elang Studio

Studio is a local development panel for understanding requests, routes, queries, and account email. It is disabled in production and rejects non-loopback clients even in a local environment.

## Open Studio

Start the application with `php elang serve`, then open `http://127.0.0.1:8000/_elang/studio`. The `php elang studio` command shows the configured address.

Use APP_ENV=local. A production environment does not register the development routes. Changing an HTTP parameter cannot enable them.

## Requests and queries

The panel keeps the last 100 local requests. A request detail includes HTTP status, duration, route, bounded query templates, and query timing. SQL bind values are excluded; quoted literals are redacted.

Request bodies, cookies, authorization headers, URL query parameters, and exception arguments are not captured. Token-like path segments are redacted. These limits reduce accidental credential capture while preserving useful development context.

## Error locations

An exception record shows its type, source location, and a short call chain. Inspect the named file and line, then use the private application log for additional context. A status code alone does not tell you which dependency failed.

Studio is not a hosted error-monitoring product. It uses local private storage and is intended for one development environment.

## Development mailbox

When local account email uses the null transport, messages are encrypted with APP_KEY and stored outside public/. The local mailbox shows verification and recovery messages so you can test the complete flow without a mail provider.

Production delivery requires a configured SMTP or other supported mail transport. The local mailbox is inaccessible from the production site.
