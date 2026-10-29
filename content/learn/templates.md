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

## Expected result and common mistakes

Open `/welcome` after saving the template and route. The page should show “My first Elang page” and “Hello, Ada.” View Source in the browser to see the server-rendered HTML.

A template named `welcome.twig` is rendered with `render('welcome', ...)`, without repeating the extension. With strict variables enabled, a missing title or name produces a template error instead of silently hiding the mistake. Read the private log when a page returns 500.

## Try a layout

Create `resources/views/base.twig`:

```twig
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ title }}</title></head>
<body><main>{% block content %}{% endblock %}</main></body>
</html>
```

Then replace welcome.twig with:

```twig
{% extends 'base.twig' %}
{% block content %}
<h1>{{ title }}</h1>
<p>Hello, {{ name }}.</p>
{% endblock %}
```

The controller code stays the same. You have moved repeated page structure into one layout while preserving automatically escaped data.
