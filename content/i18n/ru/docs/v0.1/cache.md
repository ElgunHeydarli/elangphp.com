# Кеш

Контейнер связывает PSR-16 CacheInterface с файловым кешем Symfony. Внедряйте интерфейс вместо конкретной реализации.

## Сохранение и получение

```php
use Psr\SimpleCache\CacheInterface;

$cache->set('homepage.summary', ['posts' => 12], 300);
$summary = $cache->get('homepage.summary', []);
$cache->delete('homepage.summary');
```

Значения TTL задаются в секундах, объектами DateInterval или null. Используйте ключи, контролируемые приложением; PSR-16 резервирует некоторые символы.

## В обработчике

```php
$router->get('/summary', function (CacheInterface $cache) {
    return $cache->get('homepage.summary', ['posts' => 0]);
});
```

## Очистка кеша приложения

```bash
php elang cache:clear
```

Команда очищает пул кеша приложения. Скомпилированные шаблоны Twig хранятся отдельно и автоматически обновляются при изменении исходных файлов. Для приложения на нескольких серверах следует привязать подходящий адаптер общего кеша.
