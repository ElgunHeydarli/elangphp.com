# Verilənlər bazasına qoşulun

Verilənlər bazasına əsaslanan ilk funksiyanız üçün SQLite istifadə edin. `pdo_sqlite` quraşdırıldığına əmin olun.

## Sxemi yaradın

`php elang make:migration create_posts` yerinə yetirin. Doctrine Table API-si ilə `up()` daxilinə tam ədəd əsas açarı və sətir başlığı olan `posts` cədvəli əlavə edin. Miqrasiyalar sənədləşməsindəki tam sxem nümunəsinə baxın.

`php elang migrate` yerinə yetirin, sonra tətbiq kodunuzda və ya lokal hazırlıq skriptində DBAL vasitəsilə qeyd əlavə edin:

```php
$database->connection()->insert('posts', ['id' => 1, 'title' => 'Hello from SQLite']);
```

## Qeydləri oxuyun

```php
use Elang\Database\Database;
$router->get('/api/posts', function (Database $database) {
    return ['data' => $database->table('posts')
        ->orderBy('id', 'DESC')
        ->setMaxResults(20)
        ->fetchAllAssociative()];
});
```

## Dəyərləri bağlayın

Filtrləyərkən `->where('id = :id')->setParameter('id', $id)` yazın. Sorğudan gələn dəyərləri SQL parçalarına və ya identifikatorlara birbaşa daxil etməyin.

## Nəticəni yoxlayın

`/api/posts` açın və qeydinizin qaytarıldığını təsdiqləyin. SQLite fayllarını ictimai veb kök qovluğundan və versiya nəzarətindən kənarda saxlayın.

## Tam ilk miqrasiya

Bu, ilk sxem dəyişikliyinizdirsə, yaradılmış miqrasiyanın gövdəsini aşağıdakı tam reallaşdırma ilə əvəz edin. Yaradılmış fayl adını qoruyun.

```php
<?php
declare(strict_types=1);
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
        $database->insert('posts', ['id' => 1, 'title' => 'Hello from SQLite']);
    }
    public function down(Connection $database): void
    {
        $database->createSchemaManager()->dropTable('posts');
    }
};
```

`php elang migrate` yerinə yetirin, sonra yuxarıdakı GET marşrutundan istifadə edin. Gözlənilən cavab:

```json
{"data":[{"id":1,"title":"Hello from SQLite"}]}
```

Miqrasiyada ilkin qeyd var, buna görə bu tam miqrasiyanı tətbiq etdikdən sonra əvvəlki əlavəetmə parçasını da işlətməyin. Əvvəlki parça ayrıca yazma əməliyyatını göstərir; eyni əsas açarın təkrarlanması haqlı olaraq uğursuz olar.

Server “no such table” (“belə cədvəl yoxdur”) bildirirsə, tətbiqin DATABASE_URL dəyərini miqrasiya əmrinin istifadə etdiyi dəyərlə müqayisə edin. “could not find driver” (“drayver tapılmadı”) bildirirsə, `php -m` çıxışını yoxlayın və pdo_sqlite aktivləşdirin. [Verilənlər bazası problemlərinin aradan qaldırılmasına](/docs/v0.1/troubleshooting) baxın.
