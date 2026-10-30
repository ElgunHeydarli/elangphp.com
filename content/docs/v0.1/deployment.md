# Deployment

Elang applications run on a standard PHP web server. This site uses PHP-FPM behind Apache and Nginx.

## Prepare the application

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php elang migrate --force
```

Review migrations and back up the database before running a production change. Do not install development tools into the public directory.

## Environment

Set `APP_ENV=production`, `APP_DEBUG=false`, an HTTPS `APP_URL`, and a generated `APP_KEY`. Configure database and mail credentials separately. Make only `storage/` and necessary database locations writable by the PHP process.

## Document root

Point the web server at `public/`. Route nonexistent paths to `index.php`. Apache can use the included `.htaccess`. A typical Nginx location is:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Add the PHP-FPM location appropriate to your server. Do not expose the project root, vendor files, Git metadata, secrets, or SQLite files.

## Existing shared hosting

When the hosting control panel fixes the document root, keep the application outside it and copy only the contents of `public/` into the public directory. Adjust the entry point to load the private application. This site's publish script follows that model.

## After deployment

Verify expected pages and API responses, check server-side logs, and monitor failures. HTTPS certificates and security headers for static resources should be configured at the hosting layer. This preview does not include automatic zero-downtime deployments or a managed hosting service.

## Account email and domain authentication

Account verification and password recovery need a working mail transport. The default `null://null` deliberately disables production account email. Use an authenticated SMTP service with a valid TLS certificate and an authorized sender; `smtps` on port 465 enables implicit TLS. URL-encode the username and password inside a DSN. Do not disable certificate verification.

A private secret file keeps the DSN out of the environment file itself:

```dotenv
MAILER_DSN=
MAILER_DSN_FILE=/run/secrets/elang-mailer-dsn
MAIL_FROM=noreply@example.com
```

The referenced file contains the complete DSN, belongs to the application operator, and must be readable only by the account that needs it. A nonempty `MAILER_DSN` takes precedence over `MAILER_DSN_FILE`. Never put either credential value in Git, chat, screenshots, or a public directory.

Create the sender mailbox and verify SMTP authentication. Publish SPF for the actual outgoing service, the mail server's public DKIM key, and a DMARC policy in the domain's authoritative DNS. Check the existing policy before adding a record: duplicate SPF policies can invalidate authentication. Begin DMARC monitoring before deciding on stricter enforcement. An accepted SMTP submission or a local mailbox test does not prove delivery to external providers.

For the official Elang site, a verified administrator can review the three prepared records in [integrations](/admin/integrations#mail-dns). The Fornex publisher adds missing records only for `elangphp.com`, stops on conflicting policies, and never replaces or deletes existing records. Its API key is used for one request and is not saved. HTTPS, CSRF, administrator access, current-password confirmation, throttling, and a publication lock protect the operation. A partial provider failure can leave some new records in place; retrying checks existing records first. Confirm public DNS propagation and real delivery separately.

## Back up and prove restoration

Back up the database, private object files, application key, referenced secret files, and encrypted integration settings together. Keep backups outside `public/` with restricted access; store a protected copy outside the application host. A database backup without the matching encryption key cannot recover encrypted credentials, notifications, or audit verification.

Use the database engine's consistent backup tool. For SQLite, install its command-line client and use its online backup command rather than copying a database while requests are writing. Substitute the actual database path from your deployment:

```bash
install -d -m 700 /srv/elang-backups
sqlite3 /srv/elang/storage/database.sqlite ".backup '/srv/elang-backups/elang.sqlite'"
chmod 600 /srv/elang-backups/elang.sqlite
sqlite3 /srv/elang-backups/elang.sqlite 'PRAGMA integrity_check;'
```

An integrity result of `ok` is only the first check. Restore into an isolated database and private storage directory. Compare all table counts against the completed backup, confirm migrations, administrator access, content, and the HMAC audit chain using the matching application key. Never point a restoration exercise at the live database or enable email/webhooks in the restored environment.

Rehearse an application release rollback separately. Keep a known working source release and its dependencies. A source rollback does not reverse schema changes, delivered messages, or object-storage writes. The official host uses an atomic private release pointer; other deployments must provide their own release and worker-restart procedure. See [releases and upgrades](/docs/v0.1/releases) and [operations](/docs/v0.1/observability).


## Next step

[Optional Doctrine ORM](/docs/v0.1/orm).
