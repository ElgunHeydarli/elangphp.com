# Console commands

Run `php elang` to see available commands. Commands are implemented with Symfony Console.

## Everyday commands

```bash
php elang doctor
php elang about
php elang routes
php elang serve --port=8000
php elang make:controller WelcomeController
php elang make:migration create_posts
php elang migrate:status
php elang migrate
php elang cache:clear
```

The development server binds to 127.0.0.1 by default. Use a production web server and PHP-FPM for deployment.

## Application setup

```bash
php elang key:generate
php elang new ../my-app --local
```

Generators reject nonempty destinations or existing controller files. Controller names use PascalCase, and migration names use snake_case.

## Production migrations

Database-changing commands require `--force` when `APP_ENV=production`. Key replacement also requires `--force`; it invalidates old ciphertext. Keep production secrets and backups outside version control.

## Framework checks

```bash
composer test
composer analyse
composer audit
```

These commands run in the framework repository. GitHub Actions runs PHP compatibility checks, dependency validation, tests, and static analysis when the workflow is pushed.

## Create projects with the global installer

After the [one-time CLI setup](/docs/v0.1/installation), run `elang new my-app` or `elang new task-board --example=task-board`. The global command delegates dependency installation and project setup to Composer. Inside a project, use `php elang` to run its own application console.


## Next step

[Application console commands](/docs/v0.1/custom-console).
