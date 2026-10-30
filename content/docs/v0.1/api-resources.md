# API resources and pagination

Resources define the public response explicitly. Put this class in `app/Resources/ProductResource.php`. Models, ORM entities, passwords and internal relationships are not automatically serialized.

## Working example


```php
<?php
declare(strict_types=1);
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

## Response shapes

Return `new ProductResource($product)` from a controller to receive `{"data":{...}}`. Return `ProductResource::collection($items)` for a list. A core `Elang\Data\Paginator` retains its `meta` alongside the transformed `data`. A Doctrine repository list is an ordinary iterable; it does not become paginated automatically.

## Authorization and relationships

A resource formats an already-authorized record; it does not decide who may access it. Apply authorization and owner/tenant query constraints before constructing the resource. Select or fetch-join only the relationships needed by the response to avoid one query per row. Symfony HttpFoundation remains the server request/response contract.

```php
use App\Resources\ProductResource;

$router->get('/api/products', fn () => ProductResource::collection($products));
```



## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Common mistakes

| Symptom | Fix |
| --- | --- |
| One extra SQL query per item | Fetch-join the relationships used by the resource. |
| Another user’s record is visible | Constrain the query by the owner/tenant before creating the resource. |
