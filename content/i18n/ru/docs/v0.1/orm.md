# Необязательная Doctrine ORM

Основные DBAL repositories остаются доступными. Подключите Doctrine ORM для attribute mapping, связей, unit of work, identity map, DQL/query builder, lifecycle listeners и optimistic locking. Elang интегрирует Doctrine; это не совместимый с Eloquent API.

## Рабочий пример
В глобальном installer выполните elang new my-app --orm. Из исходников framework используйте php elang new ../my-app --local --orm, затем установите зависимости и создайте ключ. Для существующего приложения выполните две команды ниже. Enable создаёт app/Entities, меняет только ORM_ENABLED и сохраняет ключи и остальные настройки. После этого перезапустите долгоживущие процессы.

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

## Mapping сущности

Сохраните сущность в app/Entities/Product.php. Для lazy loading Doctrine используйте non-final класс. make:entity Product генерирует ID и mapping таблицы; бизнес-поля добавьте самостоятельно. EntityManagerInterface разрешается контейнером и использует то же DBAL-соединение, что основной Database.

## Миграции и proxy

Для постоянной базы используйте проверенные миграции. orm:validate проверяет только mapping. orm:schema:sql выводит начальный CREATE SQL, не исполняет его и не вычисляет diff существующей схемы. Проверьте SQL и подготовьте миграцию. orm:proxies создаёт приватные proxy на PHP 8.3; PHP 8.4 использует native lazy objects. В production на PHP 8.3 подготовьте proxy до обработки запросов.

## Даты и мягкое удаление

Для дат UTC добавьте Timestamped и Timestamps. Для мягкого удаления — SoftDeletable и SoftDeletes, затем softDelete($entity) и flush. EntityManager::remove удаляет физически. withDeleted временно включает удалённые записи, restore снимает отметку. Фильтр не заменяет авторизацию и tenant isolation: объект из identity map может вернуться без нового SQL.

## Транзакции и восстановление

Orm::transaction откатывает ORM и основные DBAL-записи вместе. После неудачного flush/транзакции manager может закрыться: завершите транзакцию, вызовите reset, получите новый manager и перечитайте сущности. Не продолжайте с detached-объектами. Свои каталоги задаются orm.paths в config/app.php. Проверяемые базы — SQLite, MySQL и PostgreSQL; поддержка SQL Server не подтверждена.

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



## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/migrations) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Кеш при развёртывании

После изменения mapping выполните `php elang orm:cache:clear`, пересоберите proxy на PHP 8.3 и перезапустите долгоживущие процессы. ORM metadata/query cache очищается отдельно от основного кеша; строки базы не удаляются. Не очищайте mapping внутри транзакции.


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| ORM-команд нет | Установите Doctrine ORM, включите интеграцию и перезапустите CLI/worker. |
| Каталог entities отсутствует | Выполните orm:enable или задайте существующие orm.paths. |
| Manager закрыт после ошибки | Завершите транзакцию, сбросьте ORM и перечитайте сущности. |
| Столбцы отличаются от сущности | Подготовьте миграцию; не запускайте SchemaTool в production-запросе. |
