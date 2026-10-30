# Tətbiqin test edilməsi

TestClient real kernel və middleware-ləri işlədir, hər müştəriyə ayrıca yaddaş sessiyası verir. Mühit açıq şəkildə testing deyilsə işləməyəcək. Ayrı verilənlər bazası və storage qovluğu yaradın; müştəri sizin bazanızı özü kopyalamır və əvəz etmir.

## İşlək nümunə
phpunit/phpunit:^12 paketini development asılılığı kimi quraşdırın. Aşağıdakı tam nümunəni tests/ApiTest.php faylına yazın; vendor/bin/phpunit --bootstrap vendor/autoload.php tests ilə işlədin. Öz test factory-nizdə layihənin real route və provider-lərini təcrid edilmiş tətbiqə qoşun ki test məhz tətbiqin davranışını yoxlasın.

```bash
composer require --dev phpunit/phpunit:^12
```



```php
<?php
declare(strict_types=1);
use Elang\Application;
use Elang\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase
{
    public function testReadyEndpoint(): void
    {
        $directory = sys_get_temp_dir().'/my-elang-test-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        try {
            $app = new Application($directory, [
                'environment' => 'testing',
                'database' => ['url' => 'sqlite:///:memory:'],
                'mail' => ['dsn' => 'null://null'],
                'redis' => ['url' => ''],
                'telemetry' => ['enabled' => false],
                'operations' => ['enabled' => false],
            ]);
            $app->router->get('/api/ready', fn () => ['ready' => true]);
            $browser = new TestClient($app);
            $browser->get('/api/ready')->assertOk()->assertJsonPath('ready', true);
        } finally {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() && !$file->isLink()
                    ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($directory);
        }
    }
}
```

## Yoxlamalar və istifadəçilər

Status, header, redirect, görünən mətn, JSON yolu/sayı və parametrli baza yoxlamaları var. Sessiya üçün withSession, giriş etmiş istifadəçi üçün actingAs($persistedUser) istifadə edin. Eyni tətbiqi paylaşan iki müştərinin sessiyaları ayrıdır.

## CSRF aktiv qalır

CSRF aktiv qalır. Tokensiz qorunan yazı 419 qaytarmalıdır. Düzgün sessiya yazısı üçün withCsrf()->post istifadə edin; bu, middleware-i söndürmür, real token və eyni origin məlumatını verir. Scope və sahiblik yoxlamaları da qalır.

## HTTP və poçt üçün fake

Müştəri konteynerdəki Elang HTTP Client-i strict FakeTransport, Symfony MailerInterface-i yaddaş recorder-i ilə əvəz edir. http->when ilə cavab verin, HTTP sorğularını yoxlayın; mail->messages(), assertSentCount və assertSentTo mövcuddur. Eyni tətbiqdə fakes ortaqdır. Birbaşa cURL, ayrıca mail transport-u və başqa SDK bu əvəzləməyə daxil deyil; onların adapterini ayrıca dəyişin. Testi canlı bazaya qoşmayın.

## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/deployment) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| Qorunan yazı 419 qaytarır | Rəddi yoxlayın və ya düzgün sessiya yazısında withCsrf() istifadə edin. |
| Başqa SDK şəbəkəyə çıxır | Həmin adapteri ayrıca dəyişin; yalnız konteynerdəki Elang client və mailer fake olur. |
