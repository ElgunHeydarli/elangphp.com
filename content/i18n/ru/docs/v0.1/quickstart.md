# Ваше первое приложение

Начните с приложения, созданного по руководству по установке. Для первых шагов нужны только маршрут и ответ.

## Верните JSON

В `routes/web.php`:

```php
use Elang\Http\Response;

$router->get('/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

Откройте `/hello/Ada`. Elang передаст именованный параметр маршрута вашему обработчику.

## Добавьте контроллер

```bash
php elang make:controller HelloController
```

Зарегистрируйте созданный вызываемый контроллер:

```php
use App\Controllers\HelloController;

$router->get('/hello', HelloController::class, 'hello.controller');
```

Зависимости конструктора и типизированные зависимости обработчика разрешаются контейнером.

## Отобразите HTML

```php
use Elang\View\View;

$router->get('/welcome', fn (View $view) =>
    $view->render('welcome', ['name' => 'Ada'])
);
```

Создайте `resources/views/welcome.twig`:

```twig
<!doctype html>
<html lang="en">
<title>Welcome</title>
<h1>Hello, {{ name }}.</h1>
</html>
```

Twig экранирует `name` перед вставкой в HTML.
