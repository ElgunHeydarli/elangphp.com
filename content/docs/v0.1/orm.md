# Optional Doctrine ORM

The core DBAL repositories remain available. Install Doctrine ORM when a project needs attribute-mapped entities, relationships, a unit of work and identity map, DQL/query builders, lifecycle listeners and optimistic locking. Elang integrates Doctrine; these are not a separate Eloquent-compatible API.

## Working example
Create an application with `elang new my-app --orm` using the global installer. From a framework checkout use `php elang new ../my-app --local --orm`, then install its dependencies and generate its key. In an existing application, run the following two commands. The enable command creates `app/Entities`, updates only ORM_ENABLED, and preserves keys and other configuration. Restart long-running processes afterwards.

```bash
composer require doctrine/orm:^3.7
php elang orm:enable
php elang make:entity Product
```



```php
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

Save the entity below in `app/Entities/Product.php`. Entity classes must support Doctrine lazy loading; use a non-final class. `make:entity Product` generates the ID and table mapping; add the business fields yourself. EntityManagerInterface resolves through the container and shares the exact DBAL connection used by the core Database service.

Use reviewed migrations for persistent databases. `orm:validate` validates mappings; `orm:schema:sql` prints initial CREATE SQL only and never applies or diffs a live schema. Review that SQL and implement the appropriate migration. `orm:proxies` generates private proxy classes on PHP 8.3; PHP 8.4 uses native lazy objects. Production deployments using PHP 8.3 must prepare proxies before serving traffic.

Opt in to UTC timestamps with the Timestamped interface and Timestamps trait. Opt in to soft deletion with SoftDeletable and SoftDeletes, then call `$orm->softDelete($entity)` and flush. Native EntityManager::remove is a hard delete. `$orm->withDeleted()` temporarily includes deleted rows; `$orm->restore()` clears their marker. The filter is not an authorization or tenant boundary, and an entity already present in Doctrine’s identity map may be returned without a new filtered query.

`$orm->transaction(fn ($em) => ...)` rolls ORM and core DBAL writes back together. After a failed flush/transaction the manager may be closed: finish the transaction, call `$orm->reset()`, resolve a new manager and reload entities. Do not reuse detached entities or continue a failed unit of work. Custom mapping directories use `orm.paths` in config/app.php. SQL Server is not a verified platform; SQLite, MySQL and PostgreSQL are the tested targets.

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



## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/migrations) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


After changing mappings, run `php elang orm:cache:clear`, rebuild proxies on PHP 8.3 and restart long-running workers. This clears the ORM metadata/query cache separately from the default application cache; it does not delete database rows. Do not clear mappings in the middle of a transaction.


## Common mistakes

| Symptom | Fix |
| --- | --- |
| ORM commands are missing | Install doctrine/orm, enable ORM and restart the CLI/worker. |
| Entity directory does not exist | Run orm:enable or configure existing orm.paths directories. |
| Manager is closed after failure | Finish the transaction, reset the ORM and reload entities. |
| Columns differ from the entity | Review and apply a new migration; do not run SchemaTool in a production request. |
