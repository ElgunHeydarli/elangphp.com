# Ресурсы API и пагинация

Ресурс явно определяет публичные поля. Сохраните класс в app/Resources/ProductResource.php. Модель, ORM-сущность, пароль и внутренние связи автоматически не сериализуются.

## Рабочий пример


```php
namespace App\Resources;

use Elang\Http\Resource;
use Symfony\Component\HttpFoundation\Request;

final class ProductResource extends Resource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'quantity' => $this->resource->quantity,
        ];
    }
}
```

Верните new ProductResource($product), чтобы получить {"data":{...}}, или ProductResource::collection($items) для списка. Elang Data Paginator сохраняет meta рядом с преобразованными data. Список Doctrine repository — обычный iterable; автоматической пагинации у него нет.

Ресурс форматирует уже разрешённые данные. Проверяйте доступ и ограничивайте запрос владельцем/tenant до создания ресурса. Загрузите необходимые связи fetch join, чтобы избежать отдельного запроса для каждой строки. Серверным контрактом запроса/ответа остаётся Symfony HttpFoundation.

```php
use App\Resources\ProductResource;

$router->get('/api/products', fn () => ProductResource::collection($products));
```



## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| Дополнительный SQL на каждую строку | Используйте fetch join нужных ресурсу связей. |
| Видна запись другого пользователя | Ограничьте запрос владельцем/tenant до создания ресурса. |
