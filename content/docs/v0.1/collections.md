# Collections and lazy streams

Transform records without changing the original collection. Callbacks receive value and key. `filter()` preserves keys; use `values()` before returning a JSON list. Dot paths work with arrays, public properties and Elang models.

## Working example


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

## Collection operations

Available operations: `map`, `filter`, `reject`, `each`, `first`, `reduce`, `pluck`, `keyBy`, `groupBy`, `where`, `sortBy`, `take`, `chunk`, `sum`, `keys`, `values`, `get`, `all` and `toArray`. `where()` uses strict equality. Objects inside a collection are not deep-cloned.

## Bounded lazy traversal

A lazy collection takes a factory that returns a fresh iterable on every traversal. It supports map, filter, take, chunk, each and collect. `collect()` materializes the result: take a bounded subset first when processing large input. Chunk sizes must be positive; take counts cannot be negative. This is an in-memory transformation API, not an ORM query builder.

## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/api-resources) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Common mistakes

| Symptom | Fix |
| --- | --- |
| JSON list becomes an object | Call values() after filtering keyed records. |
| sum rejects a decimal string | sum accepts int/float only. Use explicit decimal arithmetic for money. |
