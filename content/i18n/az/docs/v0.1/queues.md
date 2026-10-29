# Fon tapşırıqları və planlaşdırma

Ləng işləri HTTP sorğularından kənarda saxlayın. Elang verilənlər bazasına əsaslanan JSON tapşırıq növbəsi, gecikdirilmiş tapşırıqlar, məhdud təkrar cəhdlər, uğursuz tapşırıqların yoxlanması və UTC üzrə planlaşdırma təqdim edir. Eyni verilənlər bazası bağlantısı tətbiqin yazma əməliyyatını və onun növbəyə əlavə edilən tapşırığını birlikdə təsdiqləməyə imkan verir.

## İcra mühitinin cədvəllərini hazırlayın

Auth, bloq, idarəetmə paneli və API başlanğıc layihələrinə miqrasiya daxildir. Başqa tətbiqdə yerinə yetirin:

```bash
php elang queue:setup
```

İstehsal mühitində `--force` istifadə etməzdən əvvəl sxem dəyişikliklərini nəzərdən keçirin. SQLite lokal mühitdə faydalıdır. Bir neçə tətbiq serveri üçün ortaq MySQL və ya PostgreSQL verilənlər bazası istifadə edin.

## Emaledici qeydiyyata alın

Emalediciləri tətbiqin başlanğıcında və ya xidmət təminatçısında qeydiyyata alın. HTTP prosesləri və işçilər eyni qeydiyyatları yükləməlidir. Tapşırıq məzmunu JSON məlumatlarından ibarətdir; Elang heç vaxt PHP obyektlərini deserializasiya etmir və tapşırıq məzmunundan sinif adı qəbul etmir.

```php
use Elang\Queue\Jobs;
use Psr\Log\LoggerInterface;

$app->container->get(Jobs::class)->register(
    'reports.generate',
    function (array $payload, LoggerInterface $logger): void {
        // Load the record by ID and authorize the business operation here.
        $logger->info('Report requested', ['report_id' => $payload['report_id']]);
    }
);
```

## Növbəyə göndərin və icra edin

```php
use Elang\Queue\Queue;

$id = $app->container->get(Queue::class)->push(
    'reports.generate',
    ['report_id' => 42],
    delay: 10
);
```

```bash
php elang queue:work
php elang queue:work --once
php elang queue:work --stop-when-empty --max-jobs=100
php elang queue:failed
php elang queue:retry JOB_ID
```

Tapşırıqlar şərtli verilənlər bazası yeniləməsi və rezervasiya tokeni ilə götürülür. Çatdırılma **ən azı bir dəfə** baş verir: işçinin dayanması və ya rezervasiya müddətinin bitməsi başqa cəhdə səbəb ola bilər. Tətbiq səviyyəsində unikal məhdudiyyətlərdən və xarici təminatçının idempotentlik açarlarından istifadə edərək emalediciləri təkrar icra zamanı eyni nəticəni verən şəkildə qurun.

Standart rezervasiya müddəti 120 saniyə, maksimum cəhd sayı isə beşdir. `config/app.php` daxilində `queue.lease_seconds` və `queue.max_attempts` konfiqurasiya edin. Emaledicinin işləmə müddətini rezervasiya müddətindən qısa saxlayın, proses nəzarətçisinin vaxt limitlərini də bu müddətdən qısa təyin edin və uzun hesabatları məhdud hissələrə bölün. Elang işləyən PHP emaledicisini dayandırmır və ya onun rezervasiya müddətini avtomatik uzatmır. Müddəti bitmiş rezervasiyalar yenidən götürülə bilər; köhnə rezervasiya daha yeni işçinin tapşırığını tamamlanmış kimi təsdiqləyə bilməz.

Təkrar cəhdlər bir saatla məhdudlaşdırılmış eksponensial gecikmə istifadə edir. Uğursuz tapşırıq siyahıları tapşırıq məzmununu və ya istisna mesajlarını deyil, istisna sinfini və metadatanı göstərir. Tapşırıq məzmunu şəxsi verilənlər bazası yaddaşında saxlanılır; sirlər əvəzinə qeyd ID-ləri göndərin. Proses nəzarətçisi yerləşdirmələrdən və gözlənilməz çıxışlardan sonra işçiləri yenidən başlatmalıdır. `--max-jobs` uğurlu cəhdlərdən sonra işçini yeniləməyə imkan verir; növbəni təkrar-təkrar boş görən işçi işləməyə davam edir.

## Domen məlumatlarını və tapşırığı birlikdə təsdiqləyin

```php
$database->connection()->transactional(function ($connection) use ($queue): void {
    $connection->insert('reports', ['status' => 'pending']);
    $queue->push('reports.generate', ['report_id' => (int) $connection->lastInsertId()]);
});
```

Hər iki əməliyyat eyni yeridilmiş `Database` bağlantısından istifadə etməlidir. İstisna onları birlikdə geri qaytarır. Xarici HTTP çağırışları, e-poçt çatdırılması və ayrı verilənlər bazaları bu tranzaksiyadan kənardadır.

## Tapşırıqları planlaşdırın

```php
use Elang\Queue\Schedule;

$schedule = $app->container->get(Schedule::class);
$schedule->everyMinute('reports.minute', 'reports.generate', ['report_id' => 42]);
$schedule->dailyAt('reports.daily', '02:00', 'reports.generate', ['report_id' => 43]);
```

Cron vasitəsilə hər dəqiqədə bir dəfə `php elang schedule:run` yerinə yetirin. Vaxtlar UTC üzrədir. Ortaq unikal verilənlər bazası icra açarı iki planlaşdırıcı prosesin eyni dəqiqədə eyni cədvəl ID-si üçün tapşırıq növbəyə əlavə etməsinin qarşısını alır. Bu, uzun tapşırığın sonrakı planlaşdırılmış icra ilə üst-üstə düşməsinin qarşısını almır. Üst-üstə düşmə əhəmiyyətlidirsə, tətbiq kilidi əlavə edin. Buraxılmış dəqiqələr sonradan doldurulmur.

[Real vaxt hadisələri](/docs/v0.1/realtime) və [böyük tətbiq arxitekturası](/guides/large-applications) ilə davam edin.
