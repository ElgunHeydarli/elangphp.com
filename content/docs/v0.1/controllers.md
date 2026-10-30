# Controllers & responses

Controllers keep route files focused. Elang resolves constructor dependencies and handler parameters from the service container.

## An invokable controller

```php
namespace App\Controllers;

use Elang\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HelloController
{
    public function __invoke(): JsonResponse
    {
        return Response::json(['message' => 'Hello from Elang']);
    }
}
```

Register it with `$router->get('/hello', HelloController::class)`.

## Read a request

```php
use Symfony\Component\HttpFoundation\Request;

$router->get('/search', function (Request $request) {
    return ['query' => $request->query->get('q', '')];
});
```

For JSON, decode `$request->getContent()` using `JSON_THROW_ON_ERROR`, verify the top-level structure, and validate fields. Form data is available through `$request->request`.

## Response helpers

```php
Response::json(['created' => true], 201);
Response::html('<h1>Hello</h1>');
Response::redirect('/dashboard');
```

The redirect helper accepts local absolute paths and rejects external or protocol-relative destinations. Returning raw HTML requires you to escape interpolated values; Twig templates handle escaping automatically.

## Errors

Throw `Elang\Http\HttpException` with a status and safe public message. API errors use a JSON envelope with `message` and `request_id`. Internal errors are logged through PSR-3; stack traces are not sent to visitors.


## Next step

[API resources and pagination](/docs/v0.1/api-resources).
