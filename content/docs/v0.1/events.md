# Events

Events let services react to application behavior without coupling the publisher to every listener.

## Register a listener

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

$events = $app->container->get(EventDispatcherInterface::class);
$events->addListener(OrderPlaced::class, function (OrderPlaced $event) {
    // React to the event synchronously.
});
```

## Dispatch an object

```php
final readonly class OrderPlaced
{
    public function __construct(public int $orderId) {}
}

$events->dispatch(new OrderPlaced(42));
```

Register listeners in a service provider's `boot()` method. Elang binds the contracts interface to Symfony EventDispatcher as a singleton.

## Execution model

Listeners run synchronously in the current PHP process. A queue worker and background job system are not part of this preview. Design exception handling and transaction boundaries explicitly.
