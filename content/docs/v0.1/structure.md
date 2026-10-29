# Directory structure

An Elang application has a small number of explicit places for code and content.

## Application files

```text
app/                 Your controllers and services
bootstrap/app.php    Application creation and route loading
config/app.php       Configuration array
routes/web.php       Route definitions
resources/views/     Twig HTML templates
public/              Web document root; CSS and JavaScript
database/migrations/ Versioned database changes
storage/             Writable cache, logs, sessions, and locks
elang                Console entry point
```

## Framework source

In the framework repository, `src/` contains the `Elang` namespace. The public website application lives in `app/`, while documentation, lessons, and examples live in `content/`.

## Request lifecycle

The public entry point loads Composer, creates the application, and calls `run()`. Global middleware executes first, followed by route matching, route middleware, and the handler. Responses are prepared for the request; errors receive request IDs and safe default headers.

Controllers return an HTTP response, an array for JSON, a string for HTML, or `null` for a 204 response.
