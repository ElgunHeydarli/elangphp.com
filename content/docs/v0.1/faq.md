# Frequently asked questions about Elang

## Is Elang a programming language?

No. PHP is the language. Elang is a PHP framework that provides application structure and reusable backend tools. You write ordinary PHP code and install dependencies with Composer.

## Do I need to know PHP first?

A little PHP makes the framework much easier to understand. Start with [the PHP you need](/learn/php-basics), which introduces exactly the variables, arrays, functions, classes, and namespaces used in the examples. You can then follow the lessons in order.

## Where should a complete beginner start?

Start with [environment setup](/learn/environment). Verify PHP and Composer before copying application code. Then follow the PHP basics lesson and your first route. Every stage includes a concrete result to check before continuing.

## What should I install on my computer?

You need PHP 8.3+ within PHP 8.x, Composer 2, the required PHP extensions, a terminal, and an editor. SQLite is the default database for learning. Node.js, npm, a JavaScript framework, and Docker are not required to run the website or the basic examples.

## Is there a stable release on Packagist?

Not yet. The current version is a 0.1 development preview. Install from the terminal using Elang’s Composer repository. The installation guide explains both the website application and a separate generated application. Do not assume `composer require elang/framework` works from Packagist today.

## Can I use existing Composer packages?

Yes. Install a suitable PHP package with Composer, check its PHP requirements, and register its services when necessary. Elang does not replace Composer or require a separate package manager. The [ecosystem page](/ecosystem) lists the actual libraries used by this site.

## Do I need React, Vue, or a build system?

No. Twig renders HTML on the server. CSS and JavaScript can be ordinary files in `public/assets/`. You can choose a frontend stack for your own application, but Elang does not require one for these lessons.

## Is Elang better than Laravel?

Elang aims for a small, approachable application API. Laravel has a much broader mature feature set and ecosystem. This early preview does not establish overall superiority. Read the [Elang and Laravel comparison](/guides/elang-vs-laravel) for specific differences and current limitations.

## Can I deploy to shared hosting?

The host must meet the PHP and extension requirements and support routing requests to the front controller. Keep only public files in the document root. Some hosts have fixed roots or restrictive PHP configurations; inspect their capabilities before choosing a deployment plan. The [deployment guide](/docs/v0.1/deployment) explains the fixed-root layout used by this site.

## Where do I put my own code?

Controllers and services go in `app/`. Routes go in `routes/web.php`. Templates go in `resources/views/`. CSS and JavaScript go in `public/assets/`. Configuration and secrets stay outside `public/`. See the [directory structure](/docs/v0.1/structure).

## How are dependencies installed?

Composer create-project installs the application and dependencies, creates composer.lock, and runs initial setup. After cloning an existing application, run `composer install` to reproduce its locked dependencies. The global Elang installer delegates project creation to Composer.

## Why does a route work locally but fail on hosting?

Usually the document root, rewrite rule, PHP version, extension set, or file permissions differ. Run doctor on the host, inspect the route list, and check the server's private log. The troubleshooting guide separates 404, 405, 419, 422, and 500 errors so you can fix the right layer.

## Does Elang already include login, queues, or an ORM?

Authentication, verified accounts, password recovery, scoped API tokens, model repositories, and complete starters are available. Database jobs, UTC scheduling, SSE, WebSockets, frontend CORS, and retry-safe JSON writes are also available. A full ORM and distributed broker adapters are not included. Check the [roadmap](/roadmap) before depending on a feature.

## Are the examples production applications?

They are learning examples with clearly stated boundaries. The task board stores temporary state in the current browser session; it does not offer accounts, durable shared data, or a complete task-management product. Use application-specific access control and testing when building real services.

## How do I know whether my installation is ready?

Run `php elang doctor`. Then start the local server and open the exact URL shown in the lesson. A green requirements report and the expected HTTP response are better evidence than a command that merely produced no visible output.

## How can I contribute or get help?

Check the [troubleshooting guide](/docs/v0.1/troubleshooting), then prepare a small reproduction if needed. GitHub issues are suitable for ordinary bugs and questions. For vulnerabilities, follow the repository's security policy and keep sensitive details private.
