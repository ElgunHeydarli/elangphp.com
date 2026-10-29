# Build a PHP REST API with Elang

This guide builds a small read-only catalog API and a separate input-validation endpoint. You will return JSON, accept a route parameter, use HTTP status codes, and reject malformed input. The catalog is deliberately in memory so you can understand the HTTP behavior before adding database persistence.

## Create the application

Run this from the parent directory for the new application:

```bash
composer create-project --repository=https://elangphp.com/packages elang/elang catalog-api 0.1.0-dev
cd catalog-api
php elang doctor
```

Keep this terminal in the new application directory. Replace `routes/web.php` with the complete file below.

## Register the endpoints

```php
<?php
declare(strict_types=1);

use Elang\Http\HttpException;
use Elang\Http\Middleware\RateLimitMiddleware;
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

$catalog = [
    1 => ['id' => 1, 'title' => 'Learn routing'],
    2 => ['id' => 2, 'title' => 'Validate input'],
];

$router->group('/api', [RateLimitMiddleware::class], function ($router) use ($catalog) {
    $router->get('/lessons', fn () => ['data' => array_values($catalog)]);

    $router->get('/lessons/{id}', function (int $id) use ($catalog) {
        if (!isset($catalog[$id])) {
            throw new HttpException(404, 'This lesson does not exist.');
        }
        return ['data' => $catalog[$id]];
    })->where('id', '[0-9]+');

    $router->post('/validate-contact', function (Request $request, Validator $validator) {
        if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
            throw new HttpException(415, 'Send an application/json request.');
        }
        try {
            $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'Invalid JSON body.');
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new HttpException(400, 'Expected a JSON object.');
        }
        $valid = $validator->validate($data, [
            'name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 80)],
            'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()],
        ]);
        return ['message' => 'Input accepted; nothing was stored.', 'data' => $valid];
    });
});
```

All values and routes are defined in the file. There are no missing controller or database steps. The validation endpoint does not create an account, send email, or persist a record.

## Start the server

```bash
php elang serve
```

Open a second terminal for requests, leaving the server running in the first. On Windows PowerShell, use `curl.exe` for the cURL executable, or an HTTP client that lets you set the method, headers, and JSON body.

## Read a collection and an item

```bash
curl http://127.0.0.1:8000/api/lessons
curl http://127.0.0.1:8000/api/lessons/1
```

The first response contains both lesson objects in `data`. The second contains the lesson with ID 1. Requesting `/api/lessons/999` returns 404 with a message and request ID. A nonnumeric identifier fails the route requirement.

## Send valid JSON

In a POSIX shell, run:

```bash
curl -X POST http://127.0.0.1:8000/api/validate-contact \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@example.com"}'
```

Alternatively, use an HTTP client: choose POST, enter the URL, add `Content-Type: application/json`, and paste the JSON object into its raw request body. Expected status: 200. The response repeats only the validated fields and explains that nothing was stored.

## Exercise the failure cases

| Request | Expected result |
| --- | --- |
| Malformed JSON | 400 with a safe message |
| Missing JSON Content-Type | 415 |
| Invalid email | 422 with an email field error |
| POST to the GET collection route | 405 with Allow |
| Unknown lesson ID | 404 |
| Requests beyond the configured limit | 429 with Retry-After |

Use these cases as the beginning of an automated API test suite. A successful request alone does not show whether the input boundary behaves correctly.

## Add persistence deliberately

The next step is [database access](/docs/v0.1/database), followed by [migrations](/docs/v0.1/migrations). Bind all user values as query parameters. Decide who may create or change a record before adding write endpoints to a public application.

This sample has no identity or authorization system because it only exposes an application-controlled catalog and validates a nonpersistent payload. Adding authentication, ownership checks, and durable writes is a separate design task.

## Continue learning

Follow the [complete task board](/learn/build-a-task-board) for an HTML application with state-changing forms, session storage, CSRF protection, and validation feedback. Use the [HTTP response reference](/docs/v0.1/controllers) when you need custom statuses or headers.
