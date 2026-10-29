# The PHP you need to learn Elang

This lesson explains the syntax used in the framework examples. It is a focused introduction, not a replacement for the complete PHP manual. You can run the small snippets in a `practice.php` file with `php practice.php`.

## Variables and strings

PHP variables begin with `$`. A semicolon ends a statement. The dot operator joins strings:

```php
<?php
declare(strict_types=1);
$name = 'Ada';
echo 'Hello, '.$name.'!';
```

Expected output: `Hello, Ada!`. Quoted text is a string. `42` is an integer, and `true` is a boolean. Types matter when you read JSON or declare a typed function parameter.

## Arrays

An array can hold a list of values or named fields:

```php
$tags = ['php', 'elang'];
$post = ['title' => 'A small beginning', 'published' => true];
echo $post['title'];
```

The `=>` operator connects a key to a value. Elang's JSON responses often begin as PHP arrays. JSON is a text format sent over HTTP; it is not the same thing as the in-memory PHP array.

## Functions and return values

A function groups reusable behavior. Parameter and return types describe what it accepts and returns:

```php
function greeting(string $name): string
{
    return 'Hello, '.$name.'!';
}
echo greeting('Ada');
```

`return` gives a value back to the caller. `echo` writes output directly. In an Elang handler, return a response instead of printing output yourself, so the kernel can set status codes and headers correctly.

## Anonymous and arrow functions

A route can receive an anonymous function:

```php
$router->get('/hello', function () {
    return ['message' => 'Hello'];
});
```

An arrow function is a concise expression:

```php
$router->get('/hello', fn () => ['message' => 'Hello']);
```

These examples have the same intended response. The function runs when a matching request reaches the handler, not merely because the route was registered.

## Classes and objects

A class describes an object. `new` creates an instance; `->` accesses an instance method or property:

```php
final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello, '.$name.'!';
    }
}
$greeter = new Greeter();
echo $greeter->greet('Ada');
```

A controller is a class used to handle requests. A service is a class with a reusable responsibility. Naming the responsibility makes code easier to understand as a project grows.

## Constructors and dependency injection

A constructor receives the dependencies an object needs:

```php
final class WelcomeController
{
    public function __construct(private Greeter $greeter) {}
}
```

The short `private Greeter $greeter` syntax defines and stores the property. Elang's container can construct the Greeter and pass it into the controller. That is dependency injection; it does not require a global facade or a hidden variable.

## Namespaces and use statements

A qualified class name includes its namespace:

```php
namespace App\Controllers;
use Elang\Http\Response;
```

`use` gives the file a shorter way to refer to an existing class. It does not download or install that class. Composer's autoloader locates the class file using the project's PSR-4 mapping.

In a generated application, `App\Controllers\HelloController` belongs in `app/Controllers/HelloController.php`. Match the spelling and capitalization, especially on Linux.

## Static calls and class names

`Response::json(...)` calls a static helper. `HelloController::class` produces the qualified class name as a string so the router can ask the container for it later. It does not immediately execute the controller.

## Exceptions

An exception interrupts normal execution. Elang converts supported HTTP and validation exceptions into responses. Unexpected exceptions are logged and become a safe 500 response in production.

Do not catch every exception and pretend the request succeeded. Use an expected status, inspect errors, and keep private details out of public output.

## A quick self-check

Can you explain why a route returns an array, why `$name` begins with a dollar sign, what `->` and `::` mean, and why a use statement does not install a package? If yes, continue with [your first route](/learn/first-route). If a term is unfamiliar, keep the [glossary](/docs/v0.1/glossary) open beside your editor.

For a broader language reference, read the [official PHP tutorial](https://www.php.net/manual/en/tutorial.php).
