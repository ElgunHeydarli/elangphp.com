# Your first application

Start with a generated application from the installation guide. You only need a route and a response to get moving.

## Return some JSON

In `routes/web.php`:

```php
use Elang\Http\Response;

$router->get('/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

Visit `/hello/Ada`. Elang passes the named route parameter into your handler.

## Add a controller

```bash
php elang make:controller HelloController
```

Register the generated invokable controller:

```php
use App\Controllers\HelloController;

$router->get('/hello', HelloController::class, 'hello.controller');
```

Constructor dependencies and typed handler dependencies are resolved by the container.

## Render HTML

```php
use Elang\View\View;

$router->get('/welcome', fn (View $view) =>
    $view->render('welcome', ['name' => 'Ada'])
);
```

Create `resources/views/welcome.twig`:

```twig
<!doctype html>
<html lang="en">
<title>Welcome</title>
<h1>Hello, {{ name }}.</h1>
</html>
```

Twig escapes `name` before placing it in HTML.


## Next step

[Collections and lazy streams](/docs/v0.1/collections).
