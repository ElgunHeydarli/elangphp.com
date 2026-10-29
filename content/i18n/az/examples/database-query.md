# Verilənlər bazasından oxuyun

Canlı veb-sayt nümayiş cədvəlini Doctrine DBAL vasitəsilə oxuyur. Onun miqrasiyası üç nümunə qeyd əlavə edir.

## Sorğu

```php
use Elang\Database\Database;
$rows = $database->table('examples')
    ->orderBy('id', 'ASC')
    ->setMaxResults(3)
    ->fetchAllAssociative();
```

## Sınayın

```bash
curl https://elangphp.com/api/playground/database
```

## Təhlükəsiz filtrləyin

```php
$row = $database->table('examples')
    ->where('id = :id')
    ->setParameter('id', 1)
    ->fetchAssociative();
```

Dəyərləri bağlayın. İdentifikatorları və SQL ifadələrini tətbiqin nəzarətində saxlayın. Sorğu qurucusu ORM deyil və ixtiyari SQL parçalarını təhlükəsiz etmir.
