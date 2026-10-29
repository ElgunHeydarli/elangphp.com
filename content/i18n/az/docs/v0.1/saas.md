# Elang ilə SaaS iş sahəsi qurun

`elang/saas` istinad tətbiqi təsdiqlənmiş hesabları, təşkilatları, rol yoxlamalarını, layihələri, şəxsi faylları, dəvətləri, gələn qutusu bildirişlərini, məhdud səlahiyyətli API-ləri, autentifikasiya olunmuş hadisə axınlarını, hesab təhlükəsizliyini və administrator istismarını birləşdirir. O, komanda məhsulu üçün işə salına bilən başlanğıc nöqtəsidir. Hesablaşma, ödəniş reyestrləri və sahə sertifikatlaşdırması ayrıca domen işi olaraq qalır.

## Quraşdırın və işə salın

```bash
composer create-project --repository=https://elangphp.com/packages elang/saas workspace 0.1.0-dev
cd workspace
php elang doctor
php elang serve
```

Composer-in layihə yaratma emalçıları tətbiq açarı yaradır və miqrasiyaları/platforma cədvəllərini hazırlayır. Mənbədən yaradılmış layihə üçün əvvəlcə `composer install`, `php elang key:generate`, `php elang migrate` və `php elang platform:setup` işlədin. Tətbiqin kökünü şəxsi saxlayın və yalnız `public/` qovluğunu açın.

`/register` açın, istifadəçi yaradın və lokal hesabı `/_elang/studio/mail` vasitəsilə təsdiqləyin. `/workspace` açın. Dəvət və təcrid davranışını yoxlamaq üçün ayrıca brauzer kontekstində ikinci hesab qeydiyyatdan keçirin. Çoxhostlu yerləşdirmədə PostgreSQL və ya MySQL ilə ortaq Redis istifadə edin; SQLite və lokal saxlama inkişaf üçün faydalıdır.

## İlk komanda ssenarisini izləyin

1. Qısa adlı təşkilat yaradın. Onun yaradıcısı sahib olur.
2. Layihə əlavə edin. Layihələr `planned` vəziyyətində başlayır; onları `active` və ya `done` vəziyyətinə keçirin.
3. Başqa təsdiqlənmiş e-poçtu üzv və ya baxışçı kimi dəvət edin. Yaradılmış şəxsi keçidi həmin şəxslə paylaşın.
4. Uyğun e-poçtun sahibi kimi daxil olarkən dəvəti qəbul edin.
5. Baxışçının oxuya bildiyini, lakin layihələri dəyişdirə və ya fayl yükləyə bilmədiyini təsdiqləyin.
6. Şəxsi sənəd yükləyin və ya JPEG/PNG/WebP üçün şəkil emalını seçin.
7. Yaradılmış autentifikasiya tələb edən endirməni açın. Üzvü silin və girişinin rədd edildiyini təsdiqləyin.
8. Gələn qutusu yeniləmələrini, hesab cihazlarını və audit davranışını nəzərdən keçirin.

Dəvət keçidləri yeddi gündən sonra bitir və bir dəfə istifadə edilir. Yenidən verilməsi eyni komanda/e-poçt üçün əvvəlki dəvəti əvəz edir. Nümunə məqsədli paylaşım üçün şəxsi keçid göstərir; xarici dəvət e-poçtunu avtomatik göndərmir.

## Layihə yazmalarını anlayın

`app/Projects.php` siyahılama, yeniləmə və silməyə eyni açıq təşkilat məhdudiyyətini tətbiq edir. `Teams::within()` üzvlüyü yenidən yükləyir və hər SQL tranzaksiyası boyunca təşkilat kilidini saxlayır. Layihə versiyaları optimist paralellik nəzarətini həyata keçirir: köhnə forma daha yeni redaktənin üzərinə səssizcə yaza bilməz. Köhnəlmiş versiya 409 qaytarır və istifadəçidən yenidən yükləməyi istəyir.

Layihə dəyişiklikləri və onların real vaxt hadisəsi eyni SQL bağlantısında birlikdə təsdiqlənir. Hadisə məzmunu layihə identifikatorunu ehtiva edir; istehlakçılar hazırda icazə verilən vəziyyət görüntüsünü yenidən yükləyir. Audit qeydləri layihə mətni əvəzinə təhlükəsiz ID-lər/əməl adları ehtiva edir. Layihələr, fayl metadatası, bildirişlər və üzvlüklər açıq sahibliyi olan ayrı cədvəllərdən istifadə edir.

Nümunə son 100 layihəni/faylı/gələn qutusu elementini sadalayır. Daha böyük məhsul üçün səhifələmə, axtarış, kvotalar, domen yoxlamaları və xüsusi indekslər əlavə edin. Freymvorkun model repozitoriyası artıq səhifələməni dəstəkləyir; məhdud nümunə ekranını limitsiz qeyd əldə edilməsi hesab etməyin.

## API və real vaxt müştəriləri

SaaS tətbiqi Notes API-ni saxlayır və bunları əlavə edir:

| Metod | Yol | Tələb olunan səlahiyyət |
| --- | --- | --- |
| GET / POST | `/api/teams` | `teams:read` / `teams:write` |
| GET / POST | `/api/teams/{team}/projects` | `teams:read` / `teams:write` |
| PUT / DELETE | `/api/teams/{team}/projects/{project}` | `teams:write` |

Kuki ilə autentifikasiya olunmuş yazmalar CSRF tələb edir; məhdud səlahiyyətli bearer tokenləri server/mobil inteqrasiyalar üçün işləyir. Token səlahiyyəti üzvlük/rol yoxlamalarını əvəz etmir. Kənar şəxs 404 alır və yalnız oxuma icazəli üzv yazma səlahiyyətli tokenlə belə yaza bilməz. PUT title/status və tamədədli version göndərir; DELETE cari tamədədli version dəyərini JSON kimi göndərir. Etibarlı Idempotency-Key dəstəklənən son nöqtələrdə eyni JSON yazmasının təkrar cəhdlərini qoruyur.

Brauzer yeniləmələri üçün `/workspace/team/{team}/events` ünvanında autentifikasiya olunmuş SSE istifadə edin. Axın sessiya kilidini buraxır və cari hesab versiyasını və üzvlüyü vaxtaşırı yenidən yoxlayır. Məhdud cavab müddətindən sonra bağlanır və yenidən qoşulmalıdır. Hadisə kursorunu saxlayın və dəyişiklikdən sonra layihə siyahısını yeniləyin. Saxlanmış tarixçədən kənarda yeni vəziyyət görüntüsü yükləyin. Bu nümunə komanda soketi səlahiyyətlərini təqdim etmir; ayrıca Notes API şəxsi kanallar üçün autentifikasiya olunmuş WebSocket-ləri nümayiş etdirir.

## Tətbiqi istismar edin

Etibarlı terminal vasitəsilə administrator yaradın; parol daxil edilməsi gizlədilir:

```bash
php elang user:create owner@example.com --admin --verified
php elang queue:work
php elang outbox:work
```

Autentifikatoru aktivləşdirmək və keçid açarlarını qeydiyyata almaq üçün `/security` istifadə edin. Növbə vəziyyətinə, uğursuz tapşırıq metadatasına, işçi aktivlik siqnallarına, çıxış qutusu yığılmasına və HTTP ümumiləşdirmələrinə baxmaq üçün təsdiqlənmiş administrator olaraq `/operations` istifadə edin. Növbəyə alınmış poçtu emal etməzdən əvvəl real poçt nəqli konfiqurasiya edin. İşçiləri proses nəzarətçisi altında işlədin və veb ilə işçi buraxılışları arasında tapşırıq qeydiyyatlarını eyni saxlayın.

İstehsaldan əvvəl ehtiyat nüsxəsinin bərpasını, etimadnamə bərpasını, təşkilatlararası təcridi, paralel redaktələri, üzvlüyün ləğvini, növbə qəzası/təkrar cəhd davranışını, saxlama uzlaşdırılmasını, sorğu tezliyi limitlərini və realistik autentifikasiya olunmuş trafiki sınaqdan keçirin. Xidmət səviyyəsi hədəfləri müəyyənləşdirin və bütün steki ölçün. Elang inkişaf buraxılışı olaraq qalır; ölçülməmiş ötürmə qabiliyyəti və ya bütün freymvorklar üzərində ümumi üstünlük vəd edilmir.

Əlaqəli bölmələr: [komandalar](/docs/v0.1/tenancy), [fayllar](/docs/v0.1/files), [bildirişlər](/docs/v0.1/notifications), [istismar](/docs/v0.1/observability) və [buraxılışların hazırlanması](/docs/v0.1/releases).
