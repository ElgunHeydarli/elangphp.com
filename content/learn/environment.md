# Set up PHP and Composer for Elang

By the end of this lesson, your terminal will run PHP and Composer, and Elang doctor will explain whether your project is ready. You do not need to install a frontend framework.

## Before you begin

Use a computer where you can install development tools, a text editor, and a terminal. A terminal runs commands; it is different from typing PHP into a browser address bar. Run each command from the directory indicated in the lesson.

Elang requires PHP 8.3 or newer within PHP 8.x. If you already have PHP, verify the version before installing another copy. On a machine with multiple PHP installations, Composer and the web server may use different ones.

## Windows setup

1. Follow the [official PHP Windows installation instructions](https://www.php.net/manual/en/install.windows.php) and install a supported PHP 8.x build for your system.
2. Add the directory containing `php.exe` to your user PATH. Create the recommended php.ini from the supplied development template if your installation does not already configure one.
3. Enable the extensions reported by Composer. The local examples need mbstring, Sodium, PDO, and pdo_sqlite, plus the framework and test requirements. `php --ini` identifies the configuration your terminal actually reads.
4. Install Composer using the [official Windows installer](https://getcomposer.org/doc/00-intro.md#installation-windows), selecting the intended PHP executable.
5. Close and reopen PowerShell, then run the verification commands below.

If `php` is not recognized, run `where.exe php` and check PATH. If a command finds PHP 8.2, changing Elang code will not fix the version mismatch.

## macOS setup

Use an installation method described in the [PHP macOS installation guidance](https://www.php.net/manual/en/install.macosx.php). A maintained package manager is usually the shortest route to a supported CLI installation. Install Composer using the [official Unix/macOS instructions](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos).

When following Composer's installer instructions, use the checksum shown on its official download page. Do not rely on a checksum copied from an old tutorial. Open a new terminal after changing PATH and verify both executables.

## Ubuntu 24.04 setup

On Ubuntu 24.04, the standard PHP packages provide a suitable starting point:

```bash
sudo apt update
sudo apt install php-cli php-mbstring php-xml php-sqlite3 php-curl php-zip unzip
```

Then follow the official Composer installation instructions. On another distribution or release, package names and default PHP versions can differ. Verify `php -v`; Elang needs 8.3+, even if your package manager successfully installed an older PHP version.

The default SQLite connection needs no separate database server. You can add MySQL or PostgreSQL later when you understand the basic request lifecycle.

## Verify the tools

```bash
php -v
php --ini
php -m
composer --version
```

Expected result: PHP 8.3+ within PHP 8.x, a readable CLI configuration path, the required extensions, and Composer 2. A missing-extension error names the item you need to enable or install.

## Create the application with Composer

Open a terminal in the parent directory where you want your project. Run:

```bash
composer create-project elang/elang my-app dev-main
cd my-app
php elang doctor
php elang serve
```

Composer creates the project, installs dependencies, generates its key, and checks the environment. Open `http://127.0.0.1:8000` when the server prints that address. Keep the terminal running while using the local site; press Ctrl+C to stop it.

The development packages are available from Packagist. This verified command uses the default Composer repository and the `dev-main` branch. You do not need a Git checkout or a manually extracted archive.

## A shorter command after one-time setup

The [installation guide](/docs/v0.1/installation) explains the global CLI setup. After that setup, `elang new my-app` creates another project. Inside the application directory, `php elang doctor` and `php elang serve` run that application's console.

## Check your understanding

You should now know which PHP executable your terminal uses, where your project's composer.json lives, how to start and stop the local server, and how to read the doctor report. Continue with [the PHP you need](/learn/php-basics). If a command failed, use the [troubleshooting guide](/docs/v0.1/troubleshooting) before skipping ahead.
