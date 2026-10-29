# Şəxsi fayllar, S3 və şəkillər

Elang fayl metadatasını SQL-də, obyekt baytlarını isə Flysystem arxasında saxlayır. Lokal fayllar şəxsi saxlama sahəsində yerləşir; S3 obyektləri şəxsi qalır. İstifadəçi fayl adları heç vaxt fayl sistemi yollarına çevrilmir. Hər endirmə cari kimliyi və sahibliyi və ya cari komanda üzvlüyünü yoxlayır.

## Diski hazırlayın və seçin

```bash
php elang platform:setup
```

```dotenv
FILES_DISK=local
```

Lokal saxlama standart olaraq şəxsi fayl/qovluq icazələri ilə `storage/files` istifadə edir. Yalnız tətbiqin `public/` qovluğunu açıq edin. Şəxsi saxlama sahəsinə ictimai simvolik keçid yaratmayın. S3 üçün:

```dotenv
FILES_DISK=s3
S3_BUCKET=your-private-bucket
S3_REGION=eu-central-1
# Prefer a workload role. Set S3_KEY and S3_SECRET only when required.
```

Açar açıq şəkildə konfiqurasiya edilmədikdə AWS SDK öz adi giriş məlumatları təminatçıları zəncirindən istifadə edir. Yalnız tətbiqin bucket/prefiksinə giriş verin. İctimai girişi bloklayın və uyğun şifrələmə/versiyalama/həyat dövrü siyasətini aktivləşdirin. S3 ilə uyğun inkişaf xidmətləri `S3_ENDPOINT` və `S3_PATH_STYLE=true` istifadə edə bilər. Xüsusi istehsal son nöqtələri HTTPS istifadə etməlidir. Loopback və verilmiş inkişaf `seaweed` hostu istehsaldan kənarda HTTP istifadə edə bilər.

## Yükləyin və siyahılayın

```php
$files = $app->container->get(Elang\Storage\Files::class);
$record = $files->put($user, 'report.txt', 'Report contents');
$personal = $files->list($user);
$shared = $files->put($user, 'team-report.txt', $contents, $teamId);
```

Standart maksimum 10 MiB-dir; başqa məhdud limit üçün tətbiq konfiqurasiyasında `files.max_bytes` təyin edin. Boş yükləmələr rədd edilir. Adlar ən çox 180 simvol qəbul edir, idarəetmə simvollarını rədd edir və yol ayırıcılarını əvəz edir. Obyektlər sahib/komanda prefiksləri altında yaradılmış ID-lərdən istifadə edir. Metadataya bayt sayı, MIME tipi, SHA-256, yaradıcı və istəyə bağlı komanda ID-si daxildir. Disk adları/obyekt açarları ictimai metadata nəticəsinə daxil edilmir.

HTTP yükləmələri üçün əvvəlcə Symfony-nin `UploadedFile::isValid()` nəticəsini və yükləmə ölçüsünü yoxlayın, sonra ən çox konfiqurasiya edilmiş limitiniz qədər oxuyun. PHP `upload_max_filesize`, `post_max_size` və əks proksi limitlərini bir-birinə uyğun təyin edin. SaaS nümunəsinə bu əlaqələndirmə daxildir. Sətir əsaslı xidmət API-si baytları yaddaşda saxlayır; çox böyük obyektlər üçün diqqətlə nəzərdən keçirilmiş axınla yükləmə yanaşması istifadə edin.

Komanda yükləməsi `write` tələb edir; komanda üzrə siyahılama/endirmə `read` tələb edir. Şəxsi fayllar yalnız sahibinə əlçatandır. Siyahılama ən son 100 metadata sətrini qaytarır; böyük kitabxana üçün tətbiqə xas səhifələmə əlavə edin.

## Şəkillərin təhlükəsiz emalı

```php
$image = $files->put($user, 'avatar.png', $uploadedBytes, null, true);
```

Şəkil rejimi PHP GD tələb edir. Elang JPEG, PNG və ya WebP-ni dekodlaşdırır və yenidən kodlaşdırır, əlavə metadatanı və sona əlavə edilmiş məzmunu silir. SVG və dəstəklənməyən formatlar rədd edilir. Bayt limitinə əlavə olaraq hər tərəf üçün 4096 piksel və ümumilikdə 12 meqapiksel limiti var. Çıxışın MIME tipi və uzantısı kodlaşdırıcıdan alınır. Dekodlaşdırılmış rastr üçün kifayət qədər PHP yaddaşı ayırın; təkcə sıxılmış bayt ölçüsü şəklin yaddaş istifadəsini məhdudlaşdırmır.

İstiqamət metadatası silinir; avtomatik EXIF döndərilməsinə ehtiyacı olan tətbiqlər həmin siyasəti açıq şəkildə əlavə edib sınamalıdır. Antivirus yoxlaması, məzmun moderasiyası, kvotalar, təkrarlananların aradan qaldırılması, kiçik şəkil emal xətləri və şifrələnmiş sənəd emalı tətbiq əlavələridir, şəklin yenidən kodlaşdırılması onları özlüyündə təmin etmir.

## Endirmələr və müvəqqəti URL-lər

```php
$download = $files->download($user, $record['id']);
try {
    // Stream $download['stream'] in an authenticated response.
} finally {
    fclose($download['stream']);
}
$url = $files->temporaryUrl($user, $record['id'], 300);
```

Müvəqqəti URL-lər 1–3600 saniyə qüvvədə olur və fayl ID-sini, bitmə vaxtını və sorğu edən istifadəçini tətbiq açarlı HMAC ilə bağlayır. Onlar `/files/{id}/download` ünvanına yönəlir; tətbiqiniz həmin marşrutu qeydiyyata almalıdır. SaaS başlanğıc layihəsinə bu marşrut daxildir. İmzanı yoxlayın və sonra daxil olmuş istifadəçi ilə `download()` çağırın. Etibarlı imza ləğv edilmiş üzvlüyü keçmir. URL başqa hesab tərəfindən istifadə edilə bilməz. Bunlar ictimai AWS əvvəlcədən imzalanmış URL-ləri deyil, autentifikasiya tələb edən tətbiq URL-ləridir.

Endirmələri həmişə əlavə fayl kimi göndərin, `X-Content-Type-Options: nosniff` təyin edin və şəxsi/saxlanmayan keşləmə istifadə edin. İxtiyari yüklənmiş HTML-i əsas mənbəyinizdə birbaşa göstərməyin. `$files->delete($user, $id)` ilə silin; komanda faylının silinməsi yazma icazəsi tələb edir.

## Uğursuzluqların emalı

SQL və obyekt yaddaşları ortaq tranzaksiya paylaşmır. Yazmadan sonra metadata əlavə edilməsi uğursuz olarsa, Elang obyekti silməyə çalışır. Xidmətdən kənarda verilənlər bazasının geri qaytarılması və ya təmizləmənin uğursuzluğu sahibsiz obyekt qoya bilər. Silmə obyektin silinməsi ilə metadatanın silinməsi arasında uğursuz ola bilər. Biznes üçün kritik iş axınlarında yükləmələri açıq gözləyən vəziyyətlə hazırlayın, təsdiqləmədən sonra tamamlayın və sahibsiz/çatışmayan obyektləri tutuşdurun. Sistemlərarası əməliyyatları atomik kimi təsvir etməyin.

Növbəti: [komandalar](/docs/v0.1/tenancy) və [yerləşdirmə](/docs/v0.1/deployment).
