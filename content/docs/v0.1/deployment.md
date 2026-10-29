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
