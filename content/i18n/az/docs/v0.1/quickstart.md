# İlk tətbiqiniz

Quraşdırma təlimatında yaradılmış tətbiqlə başlayın. Başlamaq üçün yalnız marşrut və cavab lazımdır.

## JSON qaytarın

`routes/web.php` faylında:

```php
use Elang\Http\Response;

$router->get('/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

`/hello/Ada` ünvanına keçin. Elang adlandırılmış marşrut parametrini emaledicinizə ötürür.

## Kontroller əlavə edin

```bash
php elang make:controller HelloController
```

Yaradılmış çağırıla bilən kontrolleri qeydiyyata alın:

```php
use App\Controllers\HelloController;

$router->get('/hello', HelloController::class, 'hello.controller');
```

Konstruktor asılılıqları və tipləndirilmiş emaledici asılılıqları konteyner tərəfindən həll olunur.

## HTML yaradın

```php
use Elang\View\View;

$router->get('/welcome', fn (View $view) =>
    $view->render('welcome', ['name' => 'Ada'])
);
```

`resources/views/welcome.twig` faylını yaradın:

```twig
<!doctype html>
<html lang="en">
<title>Welcome</title>
<h1>Hello, {{ name }}.</h1>
</html>
```

Twig `name` dəyərini HTML-ə yerləşdirməzdən əvvəl ekranlaşdırır.


## Növbəti addım

[Kolleksiyalar və tənbəl axınlar](/az/docs/v0.1/collections).
