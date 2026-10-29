# Hadisələr

Hadisələr yayımlayanı hər bir dinləyiciyə bağlamadan xidmətlərin tətbiq davranışına reaksiya verməsinə imkan yaradır.

## Dinləyici qeydiyyata alın

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

$events = $app->container->get(EventDispatcherInterface::class);
$events->addListener(OrderPlaced::class, function (OrderPlaced $event) {
    // React to the event synchronously.
});
```

## Obyekt göndərin

```php
final readonly class OrderPlaced
{
    public function __construct(public int $orderId) {}
}

$events->dispatch(new OrderPlaced(42));
```

Dinləyiciləri xidmət təminatçısının `boot()` metodunda qeydiyyata alın. Elang müqavilə interfeysini Symfony EventDispatcher-ə singleton kimi bağlayır.

## İcra modeli

Dinləyicilər cari PHP prosesində sinxron işləyir. Ləng işləri /docs/v0.1/queues ünvanında təsvir edilmiş verilənlər bazasına əsaslanan JSON tapşırıq növbəsi vasitəsilə açıq şəkildə göndərin; dinləyicilər avtomatik olaraq fon tapşırıqlarına çevrilmir. İstisnaların emalını və tranzaksiya sərhədlərini açıq şəkildə layihələndirin.
