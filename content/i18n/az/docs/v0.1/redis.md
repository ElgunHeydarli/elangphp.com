# Redis və paylanmış infrastruktur

Elang keş, sessiyalar, sorğu tezliyi limitləri və kilidləri Redis vasitəsilə paylaşa bilər. SQL tapşırıqlar və real vaxtda təkrar oxuma üçün daimi əsas mənbə olaraq qalır. Redis Streams işçiləri və soket proseslərini oyadan, təsdiqlənmiş bildirişləri daşıyır. Bu quruluş tətbiq yazmalarını, növbəyə tapşırıq göndərilməsini və hadisə yayımlanmasını bir SQL tranzaksiyası daxilində saxlayır.

## Tətbiqi qoşun

Tam freymvorku quraşdırın, icra mühiti cədvəllərini yaradın və bütün tətbiq proseslərinin paylaşdığı bir Redis son nöqtəsini konfiqurasiya edin:

```dotenv
REDIS_URL=redis://127.0.0.1:6379/0
```

```bash
php elang platform:setup
php elang outbox:work
php elang queue:work
```

Redis-i şəxsi şəbəkədə yerləşdirin, autentifikasiya/ACL tələb edin və trafik etibar sərhədlərini keçdikdə TLS istifadə edin. DSN-i sirr menecerində və ya versiya nəzarətində izlənməyən `.env` daxilində saxlayın; giriş məlumatlarını heç vaxt əmrlərə, jurnallara, ekran görüntülərinə və ya Git-ə daxil etməyin. Açarları və əmrləri tətbiqin ehtiyacları ilə məhdudlaşdırın. Redis verilənlər bazası nömrəsi Pub/Sub-u təcrid etmir, buna görə Elang hər tətbiq kanalına və açar ad məkanına da prefiks əlavə edir.

Standart prefiks `APP_URL` dəyərindən törədilir. Eyni URL-i paylaşan tətbiqlər konfiqurasiyada unikal yerləşdirmə prefiksini açıq şəkildə təyin etməlidir:

```php
'redis' => [
    'url' => Elang\Support\Config::env('REDIS_URL', ''),
    'prefix' => 'acme:production',
],
```

Sınaq mühiti üçün fərqli prefiks istifadə edin. Prefiks dəyişiklikləri istifadəçiləri Redis əsaslı sessiyalardan çıxarır və limit/keş ad məkanlarını yenidən başladır. Əlaqəsiz tətbiqin ad məkanında keş təmizləmə əmri işlətməyin.

## Keş və kilidlər

```php
$cache = $app->container->get(Psr\SimpleCache\CacheInterface::class);
$cache->set('catalog.version', 4, 300);

$runtime = $app->container->get(Elang\Infrastructure\Runtime::class);
$lock = $runtime->lock('report:42', 30);
if ($lock->acquire()) {
    try {
        // Perform bounded, idempotent work. Refresh before the lease expires.
    } finally {
        $lock->release();
    }
}
```

`REDIS_URL` olmadan keş və kilidlər şəxsi lokal fayl sistemindən istifadə edir. Bu ehtiyat üsul bir hostda faydalıdır. Bir neçə hostu əlaqələndirmir. Tətbiq prefiksli resurslar üçün `Runtime::lock()` istifadə edin; birbaşa yeridilmiş Symfony `LockFactory` resurslar üçün düzgün ad məkanlı adlar seçməyinizi tələb edir.

Paylanmış sessiya oxumaları hər sessiya üçün kilid alır. Sessiya məlumatlarının vaxtı iki saatdan sonra bitir. Kilidin rezervasiya müddəti 30 saniyədir; köhnəlmiş yazan proseslər daha yeni sessiyanın üzərinə yazmamalıdır. Sessiyanı saxlayan sorğuları qısa saxlayın və uzun SSE cavablarından və ya xarici çağırışlardan əvvəl sessiyanı sərbəst buraxın. Sonrakı sorğularda autentifikasiyanın ləğvi yenə də SQL əsasında yoxlanılır.

## Daimi SQL çıxış qutusu

Redis aktiv olduqda `Queue::push()` və `Events::publish()` əsas yazmaları ilə eyni SQL bağlantısında çıxış qutusu sətri əlavə edir. Geri qaytarma bütün əlaqəli yazmaları silir. Ötürücü yalnız təsdiqlənmiş sətirləri oxuyur, məhdud Redis Stream-ə yayımlayır və Redis qəbul etdikdən sonra sətirləri çatdırılmış kimi işarələyir. Təkrarlanmanın qarşısını alan açar ötürücünün adi təkrar cəhdlərini yeddi gün qoruyur. İstehlakçılar yenə də idempotent emal tələb edir: bu, məhz bir dəfə vədi deyil, ən azı bir dəfə çatdırılmadır.

```bash
php elang outbox:dispatch
php elang outbox:work
php elang operations:prune
```

Davamlı ötürücünü növbə işçilərindən ayrı nəzarətdə saxlayın. Növbə işçisi gözləməzdən əvvəl bildirişləri də ötürə bilər, lakin ayrıca ötürücü növbə davamlı məşğul olduqda hadisə oyanışlarının vaxtında çatmasını təmin edir. Açıq tətbiq tranzaksiyası daxilindən ötürmə aparmayın. Yığılmanı təmizləmək üçün yayımlanmamış çıxış qutusu sətirlərini silməyin.

Redis itkisi SQL tapşırıqlarını və ya təkrar oxuma tarixçəsini silmir. Redis-i bərpa edin, ötürücünü yenidən başladın və gözləyən çıxış qutusu sayını izləyin. Keş/sessiya/limitləyici əlçatanlığının öz təsiri var: tətbiq keşindən oxumalar keşdə nəticə tapılmaması kimi davam edə bilər, sessiya, limitləyici və kilid əməliyyatları isə Redis uğursuzluqlarını bildirir. Onlar müstəqil lokal avtorizasiya vəziyyətinə səssizcə keçmir. Tətbiqlər nəzarətli xidmət xətası qaytarmalı və operatoru xəbərdar etməlidir.

## Real vaxt ardıcıllığı və bərpa

Hadisə ID-ləri kanal daxilində monoton artır. SQL sətir kilidi kanalın ardıcıllıq nömrələrinin ayrılmasını xarici biznes tranzaksiyası boyunca ardıcıllaşdırır. Bu, avtomatik artım üzrə ayrılma sırasını təsdiqlənmə sırası kimi qəbul etməyin qarşısını alır. Geri qaytarmalar mövcud olmayan hadisələri yayımlamır. Ardıcıllıq nömrələri kanallar arasında qlobal müqayisə edilə bilməz.

Soket prosesləri məhdud Redis bildiriş paketlərini oxuyur və müştəriləri kanal/kursor üzrə qruplaşdırır, sonra seçilmiş hər qrup üçün SQL-i bir dəfə sorğulayır. Buraxılmış Redis bildirişlərini bərpa etmək üçün məhdud SQL tutuşdurma yoxlaması da işləyir. Reallaşdırma sinxron və məhdud SQL/Redis çağırışlarından istifadə edir; bu, ölçülmüş bloklamayan tutum zəmanəti deyil. WebSocket işçilərini təcrid edin, verilənlər bazası indekslərini və hovuzlarını tənzimləyin və öz real yük testinizi aparın.

Təkrar oxuma saxlanma müddətini gözlənilən oflayn müddətə uyğun saxlayın. Saxlanmış tarixçədən daha uzun fasilədən sonra yenidən qoşulan müştəri tətbiq vəziyyətinin surətini yeniləməlidir. Qısamüddətli soket səlahiyyətləri yeni təşkilat yazmaları üçün cari biznes avtorizasiyasını əvəz etmir. [Real vaxt](/docs/v0.1/realtime), [növbələr](/docs/v0.1/queues) və [yerləşdirmə](/docs/v0.1/deployment) bölmələrinə baxın.
