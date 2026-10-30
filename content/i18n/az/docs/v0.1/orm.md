# Seçimə bağlı Doctrine ORM

Əsas DBAL repository-ləri qalır. Atributla entity xəritələmə, əlaqələr, unit of work, identity map, DQL/sorğu qurucusu, lifecycle listener və optimistic locking üçün Doctrine ORM quraşdırın. Elang Doctrine-i inteqrasiya edir; bu API Eloquent ilə eyni deyil.

## İşlək nümunə
Global installer ilə `elang new my-app --orm` istifadə edin. Framework mənbəyində `php elang new ../my-app --local --orm` yazın, sonra asılılıqları quraşdırıb açarı yaradın. Mövcud tətbiqdə aşağıdakı iki əmri yerinə yetirin. Enable əmri app/Entities yaradır, yalnız ORM_ENABLED dəyərini dəyişir, açar və digər ayarları saxlayır. Sonra uzunömürlü prosesləri yenidən başladın.

```bash
composer require doctrine/orm:^3.7
php elang orm:enable
php elang make:entity Product
```



```php
<?php
declare(strict_types=1);
namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 160)] public string $title,
        #[ORM\Column(type: 'integer')] public int $quantity,
    ) {}
}
```

## Entity xəritələmə

Entity-ni app/Entities/Product.php faylında saxlayın. Doctrine-in lazy loading mexanizmi üçün sinif non-final olsun. `make:entity Product` ID və cədvəl xəritəsini yaradır; biznes sahələrini siz əlavə edirsiniz. EntityManagerInterface konteynerdən verilir və əsas Database xidməti ilə eyni DBAL bağlantısını paylaşır.

## Miqrasiyalar və proxy-lər

Daimi bazada yoxlanmış miqrasiyalar istifadə edin. orm:validate yalnız mapping-i yoxlayır. orm:schema:sql ilkin CREATE SQL-i göstərir, işlətmir və mövcud sxemin fərqini hesablamır. SQL-i nəzərdən keçirib uyğun miqrasiya yazın. PHP 8.3-də orm:proxies məxfi proxy faylları yaradır; PHP 8.4 native lazy objects istifadə edir. PHP 8.3 production yerləşdirməsində proxy-ləri əvvəlcədən hazırlayın.

## Tarixlər və yumşaq silmə

UTC tarixləri üçün Timestamped interfeysi və Timestamps trait-i əlavə edin. Yumşaq silmə üçün SoftDeletable və SoftDeletes istifadə edib $orm->softDelete($entity) və flush çağırın. EntityManager::remove fiziki silir. withDeleted silinmiş sətirləri müvəqqəti göstərir; restore nişanı təmizləyir. Filtr icazə və tenant sərhədi deyil: Doctrine identity map-də olan obyekt yeni SQL olmadan qaytarıla bilər.

## Tranzaksiyalar və bərpa

Orm::transaction ORM və əsas DBAL yazılarını birlikdə geri qaytarır. Uğursuz flush/tranzaksiyadan sonra manager bağlana bilər: tranzaksiyanı bitirin, reset çağırın, yeni manager alın və entity-ləri yenidən oxuyun. Köhnə detached obyektlə davam etməyin. Xüsusi qovluqları config/app.php-də orm.paths ilə göstərin. Yoxlanılan bazalar SQLite, MySQL və PostgreSQL-dir; SQL Server üçün uyğunluq iddiası yoxdur.

```php
use Doctrine\ORM\EntityManagerInterface;
use App\Entities\Product;

$manager = $app->container->get(EntityManagerInterface::class);
$manager->persist(new Product('Keyboard', 5));
$manager->flush();
$items = $manager->getRepository(Product::class)->findBy([], ['id' => 'ASC']);
```



```bash
php elang orm:validate
php elang orm:schema:sql
php elang orm:proxies
```



## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/migrations) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Yerləşdirmədə keşin yenilənməsi

Mapping dəyişdikdən sonra `php elang orm:cache:clear` işlədin, PHP 8.3 proxy-lərini yenidən qurun və uzunömürlü prosesləri başladın. ORM metadata/query keşi əsas tətbiq keşindən ayrıdır; əməliyyat baza sətirlərini silmir. Tranzaksiya zamanı mapping keşini təmizləməyin.


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| ORM əmrləri görünmür | Doctrine ORM quraşdırın, aktivləşdirin, CLI/worker-i yenidən başladın. |
| Entity qovluğu yoxdur | orm:enable işlədin və ya mövcud orm.paths qovluqlarını göstərin. |
| Xətadan sonra manager bağlıdır | Tranzaksiyanı bitirin, ORM-i reset edib entity-ləri yenidən oxuyun. |
| Sütunlar entity ilə fərqlənir | Yeni miqrasiya yazıb yoxlayın; production sorğusunda SchemaTool işlətməyin. |
