# Коллекции и ленивые потоки

Преобразуйте записи, сохраняя исходную коллекцию. Callback получает значение и ключ. filter сохраняет ключи; для JSON-списка вызовите values. Пути с точкой работают с массивами, публичными свойствами и моделями Elang.

## Рабочий пример


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

Доступны map, filter, reject, each, first, reduce, pluck, keyBy, groupBy, where, sortBy, take, chunk, sum, keys, values, get, all и toArray. where использует строгое сравнение. Вложенные объекты не клонируются глубоко.

LazyCollection принимает фабрику нового iterable при каждом обходе. Поддерживаются map, filter, take, chunk, each и collect. collect загружает результат в память: сначала ограничьте объём большого потока. Размер chunk должен быть положительным, take — неотрицательным. Это обработка данных в памяти, а не построитель ORM-запросов.

## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/api-resources) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| JSON-список стал объектом | После фильтрации записей с ключами вызовите values(). |
| sum не принимает decimal string | sum принимает только int/float. Для денег используйте точную десятичную арифметику. |
