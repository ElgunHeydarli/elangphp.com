# Hello, JSON

A complete route with a named parameter and a JSON response.

## Route

```php
use Elang\Http\Response;
$router->get('/api/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

## Request

```bash
curl http://127.0.0.1:8000/api/hello/Ada
```

## Response

```json
{"message":"Hello, Ada!"}
```

Route parameters are injected by name. For constrained values, add a route requirement and use the appropriate PHP type.
