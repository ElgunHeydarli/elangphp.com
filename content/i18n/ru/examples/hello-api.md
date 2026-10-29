# Привет, JSON

Полный маршрут с именованным параметром и JSON-ответом.

## Маршрут

```php
use Elang\Http\Response;
$router->get('/api/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

## Запрос

```bash
curl http://127.0.0.1:8000/api/hello/Ada
```

## Ответ

```json
{"message":"Hello, Ada!"}
```

Параметры маршрута внедряются по имени. Для значений с ограничениями добавьте требование маршрута и используйте подходящий тип PHP.
