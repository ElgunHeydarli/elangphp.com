# Installation

The development source is maintained at [ElgunHeydarli/elangphp.com](https://github.com/ElgunHeydarli/elangphp.com). There is no stable Packagist release yet. The commands below use the source repository.

## Download the source

You can also [download the development snapshot](/downloads/elang-0.1.0-dev.zip), extract it, and run the Composer and Elang commands below from the extracted directory. The archive includes source and tests; install dependencies with Composer.

## Run the documentation application

```bash
git clone https://github.com/ElgunHeydarli/elangphp.com.git elang
cd elang
composer install
cp .env.example .env
php elang key:generate
php elang migrate
php elang serve
```

Open `http://127.0.0.1:8000`. The migration creates the demonstration database used by this website. A blank application does not need that demonstration table.

## Create your own application

From the framework checkout, generate a separate application:

```bash
php elang new ../my-app --local
cd ../my-app
composer install
php elang key:generate
php elang serve
```

`--local` creates a sanitized framework source snapshot in `.elang/framework`. Composer installs that snapshot without symlinking. Source secrets, vendor files, and runtime data are excluded. To update a local snapshot, review and replace it from a newer framework checkout.

To reference the GitHub development branch instead, omit `--local`. This requires the framework source to have been pushed to the repository. The generator configures a VCS repository automatically.

## Public directory

Only `public/` should be exposed by the web server. Keep `.env`, `vendor/`, application code, storage, and database files outside the public document root.

## Verify your environment

```bash
php elang about
php elang routes
composer test
```

The test suite belongs to the framework checkout; a generated application starts with a minimal application skeleton.
