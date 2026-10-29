# Install Elang from your terminal

Elang applications are installed with Composer. The current development preview is distributed through Elang's Composer repository, so the repository option is part of the working command. No manual source download or extraction is needed.

## Before you start

Install PHP 8.3+ within PHP 8.x and Composer 2. Run `php -v` and `composer --version` to verify them. If either command is unavailable, follow [environment setup](/learn/environment). Check the [requirements](/docs/v0.1/requirements) for extensions and database drivers.

## Create your first application

Run this in the parent directory where you want the new project:

```bash
composer create-project --repository=https://elangphp.com/packages elang/elang my-app 0.1.0-dev
cd my-app
php elang serve
```

Composer creates `my-app`, installs Elang and its dependencies, generates an application key, and runs doctor. When the server starts, open `http://127.0.0.1:8000`.

Expected result: a welcome page saying “Your next idea starts here.” The generated application also includes `/api/hello/Ada`, which returns `{"message":"Hello, Ada!"}`.

Use a new or empty directory. If `my-app` already has files, choose another name instead of overwriting your work. Press Ctrl+C to stop the local server.

## Install the Elang CLI once

If you prefer an `elang new` command, register the Composer repository and install the CLI globally:

```bash
composer global config repositories.elang composer https://elangphp.com/packages
composer global require elang/installer:0.1.0-dev
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
composer create-project --repository=https://elangphp.com/packages elang/task-board task-board 0.1.0-dev
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

The four current packages are `elang/framework`, `elang/elang`, `elang/task-board`, and `elang/installer`. They are Composer packages hosted at `https://elangphp.com/packages`.

A public Packagist release has not been published yet, so omitting the repository option from an ordinary Composer installation is not supported today. Do not assume a short Packagist-only command works before that publication. The current version remains a changing development preview.

## If installation stops

Read the first Composer error. Missing extensions, an unsupported PHP version, an occupied destination directory, or inability to reach the HTTPS repository need different fixes. Do not hide platform requirements with `--ignore-platform-reqs`.

If dependency installation succeeded but a post-create check failed, enter the new directory, fix the reported requirement, and run `php elang doctor` again. Preserve the generated `.env` key. See [troubleshooting](/docs/v0.1/troubleshooting).

## For framework contributors

Clone the source repository, run `composer install`, and use `php elang new ../my-app --local` to test a sanitized local framework snapshot. This contributor workflow is separate from installing an application through Composer. See CONTRIBUTING.md for repository checks and `bin/build-packages` for distribution builds.
