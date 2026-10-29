# What is a PHP framework?

A PHP framework gives a web application reusable structure: a way to receive a request, choose the right behavior, work with data, and return a response. PHP remains the programming language. The framework helps organize the application you write in that language.

Elang is one such framework. Its current development preview connects established PHP components through a small application API. Understanding the underlying request lifecycle makes it easier to learn both Elang and other frameworks.

## Start with the browser and the server

When someone opens a URL, the browser sends an HTTP request. The server runs PHP code and returns an HTTP response. That response might contain HTML for a page, JSON for an API, a redirect, or an error status.

A frontend describes what the browser displays and does: HTML, CSS, and JavaScript. A backend processes requests and data on the server. A PHP framework belongs on the backend, even when it renders the HTML sent to the browser.

## What a route does

A route connects a request's method and path to a handler. In Elang:

```php
$router->get('/hello/{name}', fn (string $name) => [
    'message' => 'Hello, '.$name.'!',
]);
```

`GET` is the method. `/hello/{name}` is the path pattern. The anonymous function is the handler. The named value is passed into the function, and the returned PHP array becomes a JSON response.

A framework makes these rules consistent across many pages instead of requiring each PHP file to invent its own routing behavior.

## Why controllers and services exist

A route can start as a small function. As behavior grows, a controller gives it a clear place to live. Services handle reusable responsibilities such as rendering templates, checking input, or accessing a database.

Dependency injection passes a service into the constructor or method that needs it. The container resolves the dependency. This keeps the dependency visible in the code instead of scattering manual construction or global lookups across every handler.

## What a framework does not decide for you

A framework does not know who owns a record, which user may edit it, what your product should do, or whether your deployment is configured correctly. Those are application responsibilities.

Likewise, a validator does not replace output escaping, and a session does not automatically authenticate a user. Learning these boundaries is more useful than memorizing a long list of helper methods.

## Framework, library, and CMS

| Tool | Typical role | What you still build |
| --- | --- | --- |
| Library | Solves a focused problem, such as sending mail | The surrounding application and its conventions |
| Framework | Coordinates application structure and reusable tools | Product features, data rules, and access control |
| CMS | Provides a content-management product and editing workflow | The site's configuration, content, and custom extensions |

A framework can use libraries internally. Elang uses Symfony components, Doctrine DBAL, Twig, and other packages; it does not replace the PHP ecosystem with a separate package manager.

## When to learn Elang

Elang is useful to explore when you want to understand a small, explicit PHP application API and follow a transparent development project. The preview has changing APIs and a smaller feature set than mature frameworks.

If you need an established production ecosystem, long-term compatibility promises, or a ready-made authentication and queue stack today, compare those needs against the preview's actual capabilities. The [Elang vs Laravel guide](/guides/elang-vs-laravel) makes those differences explicit.

## A practical learning order

1. Verify PHP and Composer with the [environment lesson](/learn/environment).
2. Learn the small amount of [PHP syntax used in the examples](/learn/php-basics).
3. Build [your first route](/learn/first-route) and inspect its response.
4. Add validation and HTML templates.
5. Follow the [complete task board project](/learn/build-a-task-board).
6. Study database access, testing, and deployment as separate steps.

The goal is to understand why each piece exists. Once that is clear, the names in the API reference become much easier to remember.
