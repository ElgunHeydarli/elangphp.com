# Elang requirements and environment setup

Before installing Elang, make sure the PHP executable in your terminal meets the framework's requirements. Your terminal PHP and your web server PHP can be different installations.

## What you need

- PHP 8.3 or newer within PHP 8.x. The repository is tested on PHP 8.3 and 8.4.
- Composer 2 to install the PHP dependencies.
- PHP extensions: ctype, JSON, mbstring, PDO, and Sodium.
- A PHP build with Argon2id support for the password service.
- A PDO driver for your database. The included examples use `pdo_sqlite`.
- A terminal and a text editor. Git is useful for framework contributions; ordinary applications install through Composer.

The framework test suite additionally needs the extensions required by PHPUnit, including DOM and XML. `composer install` reports missing extensions rather than silently omitting them.

## Check your terminal

Run these commands one at a time:

```bash
php -v
php --ini
php -m
composer --version
```

The first command should show PHP 8.3 or newer. The second shows the loaded CLI `php.ini`. The third lists enabled modules. The fourth should identify Composer 2.

If `php` or `composer` is not recognized, reopen the terminal after installation and check the executable's PATH entry. On Windows, `where.exe php` shows which executable is used. On macOS and Linux, use `command -v php`.

## Run Elang doctor

After installing dependencies and generating your application key:

```bash
php elang doctor
```

Each required check should show `OK`. Doctor verifies the PHP version, required extensions, password support, application key, writable storage, production debug settings, and a simple database connection. It does not print your key or database credentials.

For machine-readable output:

```bash
php elang doctor --json
```

A failed required check returns a nonzero exit code and a concrete next step. If the autoloader itself is missing, run `composer install` before doctor can start.

## Choose a database

SQLite is the shortest path for local learning. It stores data in a file and does not require a separate database server. Keep that file outside `public/`.

MySQL requires a running server, credentials, a database created for your application, and `pdo_mysql`. PostgreSQL similarly needs a server and `pdo_pgsql`. DBAL supports these drivers; the current repository's integration tests exercise SQLite, not every database platform.

## Continue from your starting point

New to local PHP tooling? Follow [environment setup](/learn/environment) for Windows, macOS, and Ubuntu. Already comfortable with PHP and Composer? Continue to [installation](/docs/v0.1/installation), then [your first route](/learn/first-route).
