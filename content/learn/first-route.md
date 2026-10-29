# Your first route

This lesson turns an empty application into a small HTTP service.

## Prepare a project

Follow the installation guide to create a standalone application. Start it with `php elang serve`.

## Define a route

Replace the contents of `routes/web.php` with:

```php
<?php
declare(strict_types=1);
use Elang\Http\Response;
$router->get('/', fn () => Response::html('<h1>Hello, Elang.</h1>'), 'home');
$router->get('/hello/{name}', fn (string $name) => ['message' => 'Hello, '.$name.'!'], 'hello');
```

Visit `/hello/Ada`. You should receive `{"message":"Hello, Ada!"}`.

## Check the behavior

Open an unknown URL and confirm a 404 response. Send POST to `/hello/Ada` and confirm 405. Run `php elang routes` to inspect the registered methods.

## Try it yourself

Add a `/status` route that returns an array containing `status` and your application name. Continue to the JSON API lesson when both routes work.

## Where each line belongs

The route definitions belong in your application's `routes/web.php`, not in the framework's source directory. The bootstrap file creates `$router` before loading that file. The development server sends requests through `public/index.php`.

You do not need to include vendor/autoload.php again inside the route file; the public entry point already loaded Composer. A use statement makes a class name shorter, while Composer locates the installed class.

## Expected responses

Opening `/` renders the HTML heading. Opening `/hello/Ada` returns JSON with `Content-Type: application/json`. The PHP source is executed by the server; the browser should never receive a directory listing or the raw route file.

If the browser downloads PHP source or displays it as text, stop and fix the web server's PHP handler. If the terminal server is not running, the browser cannot reach your local application.
