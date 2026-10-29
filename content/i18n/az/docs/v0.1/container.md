# Xidmət konteyneri

Elang konteyneri PSR-11 standartını reallaşdırır. Konkret sinifləri konstruktor vasitəsilə asılılıq yeridilməsi ilə həll edir və interfeyslər və skalyar konfiqurasiya üçün açıq bağlamaları dəstəkləyir.

## İnterfeys bağlayın

```php
$app->container->bind(Clock::class, SystemClock::class);
```

Bağlama hər həll olunduqda yeni xidmət yaradır. Xidmət konfiqurasiya tələb etdikdə anonim funksiyadan istifadə edin.

## Paylaşılan xidmətlər

```php
use Elang\Container\Container;

$app->container->singleton(Client::class, fn (Container $container) =>
    new Client('https://api.example.com')
);
```

`singleton()` bir nüsxəni təkrar istifadə edir. `instance()` mövcud dəyəri qeydiyyata alır. Bağlanmamış konkret siniflər hər dəfə yenidən yaradılır. Dairəvi asılılıqlar həll zənciri ilə birlikdə konteyner istisnası yaradır.

## Asılılıqların yeridilməsi

```php
final class ReportController
{
    public function __construct(private ReportService $reports) {}
}
```

Skalyar arqumentlər üçün açıq şəkildə təyin edilmiş fabrik və ya standart dəyər lazımdır. Birləşmə tipləri avtomatik həll edilmir. Adlandırılmış marşrut dəyərləri yeridilə bilən emaledici arqumentlərindən üstün tutulur.

## Xidmət təminatçıları

`register(Container $container)` və `boot(Application $app)` metodları ilə `Elang\Contracts\ServiceProvider` interfeysini reallaşdırın, sonra `$app->provider(new MyProvider())` çağırın. Onlardan istifadə edən marşrut və ya dinləyiciləri əlavə etməzdən əvvəl asılılıqları qeydiyyata alın.

Sorğudan asılı xidmətlər hər dəfə yenidən yaradılmalıdır. Elang hazırda adi PHP sorğularının həyat dövrünə yönəlib; uzun müddət işləyən HTTP işçiləri ilə inteqrasiya daxil deyil.
