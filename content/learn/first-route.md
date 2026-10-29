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
