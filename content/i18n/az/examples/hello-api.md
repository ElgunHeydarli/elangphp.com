# Salam, JSON

Adlandırılmış parametr və JSON cavabı olan tam marşrut.

## Marşrut

```php
use Elang\Http\Response;
$router->get('/api/hello/{name}', function (string $name) {
    return Response::json(['message' => 'Hello, '.$name.'!']);
}, 'hello');
```

## Sorğu

```bash
curl http://127.0.0.1:8000/api/hello/Ada
```

## Cavab

```json
{"message":"Hello, Ada!"}
```

Marşrut parametrləri adlarına görə yeridilir. Məhdudiyyətli dəyərlər üçün marşrut tələbi əlavə edin və uyğun PHP tipindən istifadə edin.
