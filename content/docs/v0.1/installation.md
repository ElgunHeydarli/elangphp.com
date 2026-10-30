# Install Elang from your terminal

Install the development preview directly from Packagist using Composer. The commands below were verified in a clean Composer environment without a custom repository. No manual source download or extraction is needed.

## Before you start

Install PHP 8.3+ within PHP 8.x and Composer 2. Run `php -v` and `composer --version` to verify them. If either command is unavailable, follow [environment setup](/learn/environment). Check the [requirements](/docs/v0.1/requirements) for extensions and database drivers.

## Create your first application

Run this in the parent directory where you want the new project:

```bash
composer create-project elang/elang my-app dev-main
cd my-app
php elang serve
```

Composer creates `my-app`, installs Elang and its dependencies, generates an application key, and runs doctor. When the server starts, open `http://127.0.0.1:8000`.

Expected result: a welcome page saying “Your next idea starts here.” The generated application also includes `/api/hello/Ada`, which returns `{"message":"Hello, Ada!"}`.

Use a new or empty directory. If `my-app` already has files, choose another name instead of overwriting your work. Press Ctrl+C to stop the local server.

## Install the Elang CLI once

For the optional global `elang new` command, install the development installer directly from Packagist:

```bash
composer global require elang/installer:dev-main
```

Find the executable directory with:

```bash
composer global config bin-dir --absolute
```

Add the printed directory to your user PATH and reopen the terminal. On Windows, use the user environment-variable editor and add that directory to Path. On macOS or Linux, add the directory to PATH in your shell profile. Then verify `elang --version`.

Now create projects with:

```bash
elang new my-app
cd my-app
php elang serve
```

The installer calls Composer to create the application. It does not require the framework source checkout to remain on your machine.

## Start with a complete learning project

Create the task board directly through Composer:

```bash
composer create-project elang/task-board task-board dev-main
cd task-board
php elang serve
```

Or, after installing the global CLI:

```bash
elang new task-board --example=task-board
```

Follow the [complete task board walkthrough](/learn/build-a-task-board) for the source files, request lifecycle, expected results, and exercises. This example stores temporary tasks in the current browser session.

## Understand the two console commands

`elang new` is the globally installed project creator. `php elang ...` runs the console belonging to the application directory you are currently in. Use the application console for doctor, routes, migrations, generators, and the local server.

```bash
php elang doctor
php elang routes
php elang make:controller HelloController
```

## Preview distribution and Packagist

Eighteen development packages are indexed in [Packagist](https://packagist.org/packages/elang/): `elang/framework`, `elang/elang`, `elang/task-board`, `elang/auth-starter`, `elang/blog`, `elang/dashboard`, `elang/api`, `elang/react`, `elang/next`, `elang/saas`, `elang/realtime`, `elang/installer`, `elang/redis`, `elang/security`, `elang/tenancy`, `elang/storage`, `elang/notifications`, `elang/observability`. Install them from the default Composer repository using `dev-main`.

Packagist provides `dev-main` with the `0.1.x-dev` branch alias. These are changing development versions, not a stable release. Commit your application lockfile and review changes before upgrading.

## If installation stops

Read the first Composer error. Missing extensions, an unsupported PHP version, an occupied destination directory, or inability to reach the HTTPS repository need different fixes. Do not hide platform requirements with `--ignore-platform-reqs`.

If dependency installation succeeded but a post-create check failed, enter the new directory, fix the reported requirement, and run `php elang doctor` again. Preserve the generated `.env` key. See [troubleshooting](/docs/v0.1/troubleshooting).

## For framework contributors

Clone the source repository, run `composer install`, and use `php elang new ../my-app --local` to test a sanitized local framework snapshot. This contributor workflow is separate from installing an application through Composer. See CONTRIBUTING.md for repository checks and `bin/build-packages` for distribution builds.

## Choose a complete starter

```bash
elang new accounts --starter=auth
elang new journal --starter=blog
elang new workspace --starter=dashboard
elang new notes-api --starter=api
```

Each starter includes its own migrations and complete source. Composer applies the starter schema during project setup. Read the authentication, models, Studio, blog, dashboard, and protected API guides before deploying a public application.


## Optional ORM project

```bash
elang new my-app --orm
```

[ORM setup, migrations and tested examples](/docs/v0.1/orm).
