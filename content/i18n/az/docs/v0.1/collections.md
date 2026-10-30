# Kolleksiyalar və tənbəl axınlar

İlkin kolleksiyanı dəyişmədən məlumatı çevirin. Callback dəyər və açar qəbul edir. `filter()` açarları saxlayır; JSON siyahısı üçün sonra `values()` çağırın. Nöqtəli yollar massivlər, açıq obyekt xassələri və Elang modelləri ilə işləyir.

## İşlək nümunə


```php
use Elang\Support\{Collection, LazyCollection};

$stock = new Collection([
    ['sku' => 'A', 'quantity' => 5],
    ['sku' => 'B', 'quantity' => 0],
    ['sku' => 'C', 'quantity' => 3],
]);
$available = $stock->filter(fn ($row) => $row['quantity'] > 0)->values();
$total = $available->sum('quantity'); // 8
$codes = $available->pluck('sku')->all(); // ['A', 'C']

$stream = new LazyCollection(function () {
    for ($id = 1; $id <= 1000000; $id++) yield $id;
});
$first = $stream->filter(fn ($id) => $id % 2 === 1)->take(3)->collect();
// [1, 3, 5]; the source is read only through 5.
```

Mövcud metodlar: `map`, `filter`, `reject`, `each`, `first`, `reduce`, `pluck`, `keyBy`, `groupBy`, `where`, `sortBy`, `take`, `chunk`, `sum`, `keys`, `values`, `get`, `all`, `toArray`. `where()` ciddi bərabərlik yoxlayır. Kolleksiyadakı obyektlər dərin surətdə kopyalanmır.

LazyCollection hər keçiddə yeni iterable yaradan factory qəbul edir. map, filter, take, chunk, each və collect dəstəklənir. `collect()` nəticəni yaddaşa yığır: böyük fayllarda əvvəl lazımi hissəni məhdudlaşdırın. Chunk ölçüsü müsbət, take sayı isə sıfır və ya müsbət olmalıdır. Bu, yaddaşdakı məlumatı çevirmək üçündür; ORM sorğu qurucusu deyil.

## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/api-resources) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| JSON siyahısı obyekt kimi görünür | Açarlı sətirləri filter etdikdən sonra values() çağırın. |
| sum onluq mətn qəbul etmir | sum yalnız int/float qəbul edir. Pul üçün dəqiq onluq hesab istifadə edin. |
