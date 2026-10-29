# Views & templates

Elang uses Twig to render HTML on the server. Your frontend can remain plain HTML, CSS, and JavaScript.

## Render a view

```php
use Elang\View\View;

$router->get('/', fn (View $view) =>
    $view->render('home', ['title' => 'My application'])
);
```

This loads `resources/views/home.twig`. Pass an optional third argument to set an HTTP status.

## Escape output

```twig
<h1>{{ title }}</h1>
```

HTML autoescaping and strict variable checks are enabled. Use `|raw` only for trusted, sanitized HTML. Do not mark user-supplied content as raw.

## Layouts

```twig
{% extends "base.twig" %}
{% block content %}
  <h1>{{ title }}</h1>
{% endblock %}
```

Twig templates are compiled into the writable storage cache. CSS and JavaScript files belong in `public/assets/`. External files also fit the default Content Security Policy, which disallows inline scripts and styles.

## Named route links

```twig
<a href="{{ route('posts.show', {id: 42}) }}">Read post</a>
```

Read the [Twig documentation](https://twig.symfony.com/doc/3.x/) for inheritance, includes, filters, and template syntax.
