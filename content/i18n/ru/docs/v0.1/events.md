# События

События позволяют сервисам реагировать на действия приложения без связывания отправителя с каждым слушателем.

## Регистрация слушателя

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

$events = $app->container->get(EventDispatcherInterface::class);
$events->addListener(OrderPlaced::class, function (OrderPlaced $event) {
    // React to the event synchronously.
});
```

## Отправка объекта

```php
final readonly class OrderPlaced
{
    public function __construct(public int $orderId) {}
}

$events->dispatch(new OrderPlaced(42));
```

Регистрируйте слушателей в методе `boot()` провайдера сервисов. Elang связывает интерфейс контрактов с Symfony EventDispatcher как singleton.

## Модель выполнения

Слушатели выполняются синхронно в текущем процессе PHP. Явно отправляйте медленные операции через очередь заданий с JSON-данными в базе данных, описанную в /docs/v0.1/queues; слушатели не превращаются в фоновые задания автоматически. Явно проектируйте обработку исключений и границы транзакций.
