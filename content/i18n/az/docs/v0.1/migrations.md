# Miqrasiyalar

Miqrasiyalar verilənlər bazası sxeminizdəki dəyişiklikləri qeydə alır. Hər fayl Migration interfeysini reallaşdıran obyekt qaytarır.

## Miqrasiya yaradın

```bash
php elang make:migration create_posts
```

`database/migrations/` daxilində yaradılmış faylı redaktə edin:

```php
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Elang\Database\Migration;

return new class implements Migration {
    public function up(Connection $database): void
    {
        $table = new Table('posts');
        $table->addColumn('id', 'integer');
        $table->addColumn('title', 'string', ['length' => 160]);
        $table->setPrimaryKey(['id']);
        $database->createSchemaManager()->createTable($table);
    }

    public function down(Connection $database): void
    {
        $database->createSchemaManager()->dropTable('posts');
    }
};
```

## Tətbiq edin və yoxlayın

```bash
php elang migrate:status
php elang migrate
```

Miqrasiyalar fayl adlarının ardıcıllığı ilə icra olunur və artıq tətbiq edilmiş fayllar ötürülür. Miqrasiya cədvəli versiyaları və paketləri izləyir. Tətbiq edilmiş miqrasiyanı redaktə etməyin; yenisini yazın.

## Geri qaytarın

```bash
php elang migrate:rollback
```

Bu əmr son paket üçün `down()` metodunu əks ardıcıllıqla çağırır. Geri qaytarma məlumatları silə bilər. Əvvəlcə vacib məlumatların ehtiyat nüsxəsini çıxarın və kodu nəzərdən keçirin. İstehsal mühitindəki dəyişikliklər `--force` tələb edir.

SQLite və PostgreSQL miqrasiyaları tranzaksiyalıdır. MySQL DDL əməliyyatları gizli commit edə bilər, buna görə oradakı sxem dəyişiklikləri uğursuzluqdan bərpanın diqqətlə planlaşdırılmasını tələb edir. Bir host üzərində paralel miqrasiya icraları fayl kilidi istifadə edir; çoxhostlu yerləşdirmələrdə miqrasiyalar bir yerləşdirmə tapşırığından icra edilməlidir.
