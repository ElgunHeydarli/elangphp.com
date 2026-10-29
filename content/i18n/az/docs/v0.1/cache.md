# Keş

Konteyner PSR-16 CacheInterface interfeysini fayl sisteminə əsaslanan Symfony keşinə bağlayır. Konkret reallaşdırma əvəzinə interfeysi yeridin.

## Saxlayın və əldə edin

```php
use Psr\SimpleCache\CacheInterface;

$cache->set('homepage.summary', ['posts' => 12], 300);
$summary = $cache->get('homepage.summary', []);
$cache->delete('homepage.summary');
```

TTL dəyərləri saniyə, DateInterval obyektləri və ya null ola bilər. Tətbiqin idarə etdiyi açarlardan istifadə edin; PSR-16 bəzi simvolları rezerv edir.

## Emaledicidə

```php
$router->get('/summary', function (CacheInterface $cache) {
    return $cache->get('homepage.summary', ['posts' => 0]);
});
```

## Tətbiq keşini təmizləyin

```bash
php elang cache:clear
```

Əmr tətbiqin keş hovuzunu təmizləyir. Twig-in kompilyasiya edilmiş şablonları ayrıdır və mənbə faylları dəyişdikdə avtomatik yenidən yüklənir. Çoxserverli tətbiqlər uyğun ortaq keş adapteri bağlamalıdır.
