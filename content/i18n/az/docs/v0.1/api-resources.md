# API resursları və səhifələmə

Resurs cavabda görünəcək sahələri açıq göstərir. Sinfi `app/Resources/ProductResource.php` faylında saxlayın. Model, ORM entity-si, parol və daxili əlaqələr avtomatik JSON-a çevrilmir.

## İşlək nümunə


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

Kontrollerdən `new ProductResource($product)` qaytaranda `{"data":{...}}` alınır. Siyahı üçün ProductResource::collection($items) istifadə edin. Elang Data Paginator verilərsə meta məlumatı çevrilmiş data ilə saxlanır. Doctrine repository siyahısı adi iterable-dır; avtomatik səhifələnmir.

Resurs artıq icazəsi yoxlanmış məlumatı formatlayır. İcazə və sahib/tenant şərtlərini resursdan əvvəl tətbiq edin. Hər sətir üçün ayrıca sorğu yaranmasın deyə yalnız lazım olan əlaqələri seçin və ya fetch join edin. Serverin request/response müqaviləsi Symfony HttpFoundation olaraq qalır.

```php
use App\Resources\ProductResource;

$router->get('/api/products', fn () => ProductResource::collection($products));
```



## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/application-testing) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| Hər sətirə əlavə SQL gedir | Resursun istifadə etdiyi əlaqəni fetch join edin. |
| Başqa istifadəçinin sətri görünür | Resursdan əvvəl sorğunu sahib/tenant ilə məhdudlaşdırın. |
