# İstismar və OpenTelemetry

Elang şəxsi istismar icmalları və istəyə bağlı əl ilə yaradılan OpenTelemetry izləri təqdim edir. İstismar ekranı növbə vəziyyətini, uğursuz tapşırıq metadatasını, SQL çıxış qutusunun yığılmasını, işçi aktivlik siqnallarını və bir saatlıq HTTP ümumiləşdirmələrini göstərir. O, tapşırıq gövdələrini, kukiləri, bearer tokenlərini, SQL dəyərlərini və ya istifadəçi e-poçtlarını açıqlamır.

## İstismarı hazırlayın və aktivləşdirin

```bash
php elang platform:setup
```

```dotenv
OPERATIONS_ENABLED=true
```

```php
$app->provider(new Elang\Observability\Provider());
```

Daimi parolunu dəyişmiş təsdiqlənmiş administrator olaraq `/operations` açın. `/operations/metrics` eyni hesab/rol yoxlamaları altında eyni şəxsi JSON icmalını qaytarır. Uğursuz tapşırıq təkrar cəhdləri CSRF ilə qorunan POST formalarıdır. Brauzerin təqdim etdiyi rollar heç vaxt giriş vermir. Bu son nöqtəni ictimai etməyin və onun ümumiləşdirilmiş JSON-unu ictimai sağlamlıq yoxlaması hesab etməyin.

HTTP metrikaları xam URL yolları/sorğu sətirləri əvəzinə marşrut adlarından istifadə edir. Onlar sorğu sayını, server xətası sayını, ümumi müddəti və maksimum müddəti dəqiqəlik qruplarda saxlayır. Ekran orta gecikməni hesablayır. Bu dəyərlər persentillər deyil və p95/p99 kimi təqdim edilməməlidir. Monitorinq saxlama sahəsi uğursuz olarsa, sorğunun emalı davam edir; çatışmayan telemetriya barədə ayrıca xəbərdarlıq edin.

Növbə/çıxış qutusu işçiləri təsadüfi proses identifikatorunu, növü, son aktivlik siqnalını və emal edilmiş cəhdləri bildirir. 120 saniyədən köhnə aktivlik siqnalı köhnəlmiş kimi işarələnir. Uzun işləyən tapşırıq əsaslı olaraq bu intervalı aşa bilər, buna görə iş yükünə xas aktivlik siqnalı/rezervasiya siyasətləri istifadə edin. Boş işçi siyahısı prosesin dayanmasına sübut deyil; proses nəzarətçisini və monitorinq konfiqurasiyasını yoxlayın.

```bash
php elang queue:work --max-jobs=1000
php elang outbox:work
php elang operations:prune
```

Təmizləməni nəzarət edilən planlaşdırıcıdan işlədin. O, HTTP ümumiləşdirmələrini 24 saat, işçi metadatasını yeddi gün və yayımlanmış çıxış qutusu sətirlərini yeddi gün saxlayır. Yayımlanmamış sətirlər saxlanılır. Biznes audit qeydləri və uğursuz tapşırıqlar üçün saxlanma qərarları ayrıdır.

## İzləri aktivləşdirin

```dotenv
OTEL_ENABLED=true
OTEL_SERVICE_NAME=orders-api
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://127.0.0.1:4318/v1/traces
OTEL_SAMPLE_RATE=0.1
```

Freymvork aktiv dəstəklənən OpenTelemetry PHP SDK-dan və OTLP HTTP/protobuf ixracçısından istifadə edir. Əl ilə yaradılan bu spanlar üçün yerli OpenTelemetry genişlənməsi tələb olunmur. Tövsiyə edilən ixrac sərhədi lokal Collector-dur; uzaq toplama üçün HTTPS istifadə edin. Xüsusi HTTP ixracı loopback və ya inkişaf kollektorunun host adı ilə məhdudlaşdırılır. Kollektoru, onun sonrakı sistemlərə giriş məlumatlarını və iz yaddaşınıza girişi qoruyun.

HTTP server spanları metod, marşrut adı və cavab statusunu ehtiva edir. Növbə istehlakçısı spanları qeydiyyata alınmış tapşırıq adını ehtiva edir və göndərilmədən W3C iz kontekstini ötürür. Sorğu gövdəsi/sorğu sətri, avtorizasiya başlığı, xam yol parametri, e-poçt, SQL parametri və ya istisna mesajı məqsədli şəkildə əlavə edilmir. Bu əsas səviyyə üçün xəta sinifləri və status kifayətdir. Həssas dəyərlər və ya çoxsaylı unikal identifikatorlar əlavə etməzdən əvvəl hər xüsusi span atributunu nəzərdən keçirin.

Kök izlərin seçilmə nisbəti standart olaraq 10%-dir; etibarlı valideyn iz konteksti valideynə əsaslanan seçiciyə təsir edir. İctimai müştərilər iz başlıqları göndərə bilər, buna görə onları avtorizasiya üçün istifadə etməyin və lazım olduqda sərhədinizdə etibarsız yuxarı axın kontekstini atmağı nəzərdən keçirin. Tapşırıqlar biznes məzmunundan ayrı yalnız iz kontekstini saxlayır.

İxrac təkrar cəhdlər olmadan 250 ms-lik məhdud nəql vaxt limitindən istifadə edir. Paket emalı yenə də sorğu, buferin boşaldılması və ya dayanma zamanı ixrac edə bilər; o, ayrıca fon axını deyil və sıfır gecikmə zəmanəti vermir. Kollektoru yaxın saxlayın. İxrac uğursuzluğu sirləri açıqlamamalı və ya biznes davranışını pozmamalıdır. Uzunömürlü proseslər nəzarət edilən həyat dövrü sərhədlərində buferi boşaltmalı və proses nəzarətçisi altında yenidən başladılmalıdır.

## İstismar zamanı tədbirlər

- Növbədəki say artır: işçi prosesini, qeydiyyatları, mövcud verilənlər bazası tutumunu və rezervasiya müddətini yoxlayın.
- Uğursuzluq sayı artır: təkrar cəhddən əvvəl istisna siniflərini və tətbiq tərəfində idempotentliyi yoxlayın.
- Çıxış qutusu sayı artır: Redis/ötürücü bağlantısını bərpa edin; yayımlanmamış sətirləri qoruyun.
- HTTP metrikaları yoxdur: `OPERATIONS_ENABLED`, cədvəllərin qurulması və tətbiq marşrutlarının trafikini yoxlayın.
- İzlər yoxdur: son nöqtəni, kollektor protokolunu, seçmə nisbətini, şəbəkə siyasətini və ixracçı asılılıqlarını yoxlayın.
- İşçilər köhnəlib: prosesin çıxış/yenidən başlama tarixçəsini və tapşırığın gözlənilən aktivlik siqnalı intervalından daha uzun bloklayıb-bloklamadığını yoxlayın.

Ehtiyat nüsxələri, müstəqil işlək qalma yoxlamaları, xəbərdarlıq yönləndirilməsi, fəlakətdən bərpa və verilənlər bazası/Redis/S3 host metrikaları yerləşdirmənizin məsuliyyətidir. Sağlam freymvork paneli ehtiyat nüsxələrinin bərpa olunduğunu və ya xarici təminatçıların işlədiyini sübut edə bilməz. [Yerləşdirmə](/docs/v0.1/deployment) və [testlər](/docs/v0.1/testing) bölmələrinə baxın.
