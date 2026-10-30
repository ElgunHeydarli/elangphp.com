# PHP and framework glossary

These are the terms used throughout the Elang learning path. You do not need to memorize them before building your first page; come back when a term becomes useful.

## PHP

PHP is the programming language that executes the backend code. Elang is a framework written in PHP. Installing Elang does not install a new programming language or a separate interpreter.

## Framework

A framework provides conventions and reusable tools for building applications. Elang coordinates requests, routes, services, and responses. Your own application still defines its features, data model, and access rules.

## Backend and frontend

The backend runs on the server. It reads input, applies rules, accesses data, and creates responses. The frontend is what the browser displays and interacts with: HTML structure, CSS presentation, and JavaScript behavior. This site uses Elang on the backend and plain HTML, CSS, and JavaScript on the frontend.

## HTTP request and response

A request asks the server to do something. It includes a method, URL, headers, and sometimes a body. A response includes a status, headers, and usually a body. A JSON API and an HTML page use the same underlying exchange.

## HTTP method and status code

GET reads a resource; POST commonly submits data or creates something. PUT, PATCH, and DELETE express other changes when your API defines them. Status codes communicate the result: 200 is success, 201 is creation, 404 means not found, and 422 can identify validation failures.

## Route

A route maps a method and path to a handler. `/posts/{id}` contains a named parameter. A named route also has an internal label used to generate a URL without repeating its path throughout the application. See [routing](/docs/v0.1/routing).

## Controller

A controller is a class containing request-handling behavior. It can accept injected services, read a request, validate input, and return a response. It helps keep the route file readable as an application grows.

## Middleware

Middleware wraps the next step of a request. It can inspect input, reject a request, or change a response. Order matters: session middleware must establish the session before CSRF middleware checks a token stored in it.

## Service and dependency injection

A service is an object that performs a useful job, such as rendering a template or connecting to a database. Dependency injection supplies that object through a constructor or handler parameter, rather than asking application code to create every dependency itself.

## Container and singleton

The container resolves services. A normal binding or unbound concrete class produces a fresh object. A singleton binding reuses one object within that application instance. A request-dependent object should not accidentally become a long-lived shared service.

## Composer and package

Composer installs a project's PHP libraries and creates its autoloader. A package is a reusable library described by composer.json. Composer resolves versions and records the chosen versions in composer.lock. It does not make every package globally available to every PHP project.

## Namespace and autoloading

A namespace gives a class a qualified name such as `App\Controllers\HelloController`. Composer's PSR-4 mapping connects that name to a file. A `use` statement imports a name for readability; it does not install a missing package.

## Environment variable and configuration

Environment-specific values include database credentials, the canonical site URL, and an application key. Elang reads these through configuration. The `.env` file must remain outside the public directory and out of version control.

## Validation and escaping

Validation decides whether input is acceptable for a particular purpose. Escaping encodes output safely for a specific context. A title can be valid text while still containing `<` or `&`; Twig escapes those characters when rendering HTML. Validation and escaping solve different problems.

## Database, query builder, and ORM

A database stores records. A query builder constructs SQL through an API, but user values must still be bound as parameters. An ORM maps records to domain objects and adds a different set of abstractions. Elang provides core Doctrine DBAL repositories and an optional Doctrine ORM integration; choose the data model that fits the application.

## Migration and transaction

A migration changes database schema in a versioned file. A transaction groups compatible operations so they can commit together or roll back after a failure. Database engines differ in how schema changes interact with transactions; MySQL DDL can implicitly commit.

## Session and cookie

A session stores server-side data associated with a browser. A cookie carries the session identifier. A session is useful for temporary state, but it does not automatically establish a user's identity. A login system needs additional authentication and authorization behavior.

## CSRF

Cross-site request forgery tricks a browser into making a state-changing request with its existing cookies. A CSRF token connects a form submission to the intended session. It does not protect an API that has no authorization rules or make unsafe data valid.

## Authentication and authorization

Authentication establishes who the caller is. Authorization decides what that caller may do. Hashing a password is only one primitive used by an authentication system. Auth Starter provides account flows, while each application must still define ownership checks and access policies.

## Cache and log

A cache temporarily stores reusable results. A log records server-side events and errors for diagnosis. Neither should be placed in the public directory. Avoid logging secrets or assuming a cached value stays valid forever.

## CLI and document root

CLI means command-line interface: commands run in a terminal, such as `php elang doctor`. The document root is the directory a web server exposes. For Elang it should be `public/`, while source, vendor files, configuration, and storage remain private.

## Next step

Apply the vocabulary in [your first route](/learn/first-route), or follow the [complete task board project](/learn/build-a-task-board) to see several concepts working together.
