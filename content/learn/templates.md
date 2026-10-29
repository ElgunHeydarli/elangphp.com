# Render your first page

HTML rendering belongs on the backend; small interactions can remain plain JavaScript.

## Create a template

Create `resources/views/welcome.twig`:

```twig
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ title }}</title></head>
<body>
  <h1>{{ title }}</h1>
  <p>Hello, {{ name }}.</p>
</body>
</html>
```

## Pass data from a route

```php
use Elang\View\View;
$router->get('/welcome', fn (View $view) =>
    $view->render('welcome', ['title' => 'My first Elang page', 'name' => 'Ada'])
);
```

## Check escaping

Change the name to a string containing an HTML tag. Twig should display the tag as text. Avoid `|raw` for untrusted content.

## Add frontend files

Put CSS and JavaScript in `public/assets/` and reference them with ordinary link and script tags. The default CSP supports same-origin external files, while inline scripts and styles are blocked.
