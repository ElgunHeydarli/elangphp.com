# Verilənlər bazası

Elang verilənlər bazası bağlantıları və sorğu qurucusu üçün Doctrine DBAL istifadə edir. Bu, ORM deyil, verilənlər bazası abstraksiya qatıdır.

## Bağlantını konfiqurasiya edin

SQLite standart seçimdir və `database/database.sqlite` istifadə edir. MySQL üçün:

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_app?charset=utf8mb4"
```

PostgreSQL üçün `postgresql://` URL-i istifadə edin və `pdo_pgsql` quraşdırın. Giriş məlumatlarındakı xüsusi simvolları URL kodlaşdırması ilə yazın. Bağlantı sirlərini heç vaxt repozitoriyaya daxil etməyin.

## Qeydləri sorğulayın

```php
use Elang\Database\Database;

$rows = $database->table('posts')
    ->where('published = :published')
    ->setParameter('published', 1)
    ->orderBy('id', 'DESC')
    ->setMaxResults(10)
    ->fetchAllAssociative();
```

`Database::table()` cədvəl identifikatorunu yoxlayır və Doctrine sorğu qurucusunu qaytarır. İstifadəçi dəyərlərini parametrlərlə bağlayın. Sütun adları, SQL ifadələri və sıralama istiqamətləri avtomatik təhlükəsiz olmur; onları tətbiqin idarə etdiyi icazə siyahılarından seçin.

## Yazma əməliyyatları

```php
$database->connection()->insert('posts', [
    'title' => $validated['title'],
    'published' => 0,
]);
```

DBAL əlavəetmə, yeniləmə, silmə, hazırlanmış ifadələr və sxem API-ləri təqdim edir.

## Tranzaksiyalar

```php
$database->transaction(function ($connection) {
    $connection->insert('posts', ['title' => 'Hello', 'published' => 0]);
});
```

İstisna tranzaksiyanı geri qaytarır. DDL davranışı verilənlər bazasından asılı olaraq fərqlənir; miqrasiya əməliyyatlarını yerləşdirməzdən əvvəl nəzərdən keçirin.
