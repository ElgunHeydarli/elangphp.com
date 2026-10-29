# Read from a database

The live website reads a demonstration table through Doctrine DBAL. Its migration seeds three example records.

## Query

```php
use Elang\Database\Database;
$rows = $database->table('examples')
    ->orderBy('id', 'ASC')
    ->setMaxResults(3)
    ->fetchAllAssociative();
```

## Try it

```bash
curl https://elangphp.com/api/playground/database
```

## Filter safely

```php
$row = $database->table('examples')
    ->where('id = :id')
    ->setParameter('id', 1)
    ->fetchAssociative();
```

Bind values. Keep identifiers and SQL expressions under application control. The query builder is not an ORM and does not make arbitrary SQL fragments safe.
