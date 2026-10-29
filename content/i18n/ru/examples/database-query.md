# Чтение из базы данных

Работающий сайт читает демонстрационную таблицу через Doctrine DBAL. Её миграция добавляет три примерные записи.

## Запрос

```php
use Elang\Database\Database;
$rows = $database->table('examples')
    ->orderBy('id', 'ASC')
    ->setMaxResults(3)
    ->fetchAllAssociative();
```

## Попробуйте

```bash
curl https://elangphp.com/api/playground/database
```

## Безопасная фильтрация

```php
$row = $database->table('examples')
    ->where('id = :id')
    ->setParameter('id', 1)
    ->fetchAssociative();
```

Привязывайте значения. Идентификаторы и SQL-выражения должны оставаться под контролем приложения. Построитель запросов не является ORM и не делает произвольные SQL-фрагменты безопасными.
