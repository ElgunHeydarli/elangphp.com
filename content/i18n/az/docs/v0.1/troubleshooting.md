# Elang problemlərinin aradan qaldırılması

Uğursuz olmuş dəqiq əmr və ya URL ilə başlayın. HTTP statusunu, PHP versiyanızı və etdiyiniz son əməli qeyd edin. Kiçik, təkrar yaradıla bilən problemi həll etmək bir neçə parametri eyni vaxtda dəyişməkdən daha asandır.

## PHP və ya Composer tanınmır

Alətlərdən hər hansı birini quraşdırdıqdan sonra terminalı yenidən açın. Windows-da `where.exe php`, macOS və Linux-da isə `command -v php` yerinə yetirin. Nəticə köhnə quraşdırmaya işarə edirsə, Elang-ın tələblərini redaktə etmək əvəzinə PATH dəyişənini düzəldin.

`php -v` və `composer --version` yerinə yetirin. Elang PHP 8.x daxilində PHP 8.3+ və Composer 2 tələb edir. Əmrlərdən biri əlçatan deyilsə, [mühit dərsini](/learn/environment) izləyin.

## Composer çatışmayan genişlənmə bildirir

CLI konfiqurasiyasını tapmaq üçün `php --ini`, yüklənmiş modulları yoxlamaq üçün isə `php -m` yerinə yetirin. Göstərilən genişlənməni Composer-in həqiqətən istifadə etdiyi PHP versiyası üçün aktivləşdirin və ya quraşdırın.

Problemi gizlətmək üçün `--ignore-platform-reqs` istifadə etməyin. Bu, icra zamanı uğursuz olan quraşdırma yarada bilər. PHP-FPM üçün genişlənmənin quraşdırılması onu mütləq CLI PHP üçün aktivləşdirmir və əksinə.

## vendor/autoload.php yoxdur

Tətbiqinizin `composer.json` faylının yerləşdiyi qovluğa keçin, sonra yerinə yetirin:

```bash
composer install
```

Əmrin uğurla tamamlanmasını gözləyin. `vendor/` qovluğu Composer tərəfindən yaradılır və endirilə bilən mənbədən və Git repozitoriyasından məqsədli şəkildə çıxarılıb.

## Tətbiq açarı yoxdur və ya etibarsızdır

Tətbiq qovluğundan `php elang key:generate` yerinə yetirin. Bundan sonra doctor etibarlı şifrələmə açarı bildirməlidir.

Açar base64 ilə kodlaşdırılmış 32 baytlıq dəyər olmalıdır. `.env` faylını şəxsi saxlayın. Əlaqəsiz xətanı araşdırmaq üçün etibarlı istehsal açarını dəyişdirməyin: köhnə şifrəli mətn həmin açardan asılıdır. Generator `--force` olmadan boş olmayan açarı dəyişdirməkdən imtina edir.

## Verilənlər bazası bağlantısı uğursuz olur

`php elang doctor` yerinə yetirin. SQLite üçün `pdo_sqlite` modulunun aktiv olduğunu və verilənlər bazasının ana qovluğuna yazmaq mümkün olduğunu yoxlayın. MySQL və ya PostgreSQL üçün uyğun PDO drayverini, serverin əlçatanlığını, verilənlər bazasının adını və giriş məlumatlarını yoxlayın.

Bağlantı giriş məlumatlarındakı xüsusi simvolları URL kodlaşdırması ilə yazın. Həqiqi bağlantı URL-ini heç vaxt ictimai problem qeydinə yapışdırmayın. Doctor çıxışında giriş məlumatlarını gizlədir.

## Belə cədvəl yoxdur və ya cədvəl mövcud deyil

Sxeminiz yaradılmadıqda belə bağlantı işləyə bilər. Yerinə yetirin:

```bash
php elang migrate:status
php elang migrate
```

İstehsal mühitində gözləyən miqrasiya fayllarını nəzərdən keçirin və `--force` seçimini yalnız ehtiyat nüsxə hazırladıqdan sonra istifadə edin. DATABASE_URL dəyişdirmisinizsə, indi miqrasiya etdiyinizdən fərqli verilənlər bazasına baxa bilərsiniz.

## Marşrut 404 qaytarır

`php elang routes` yerinə yetirin. Metodu və yolu sorğunuzla müqayisə edin. `[0-9]+` kimi parametr tələbi `/posts/hello` yolunu məqsədli şəkildə rədd edir.

Veb-serverdə `/` işləyir, lakin digər səhifələr uğursuz olursa, veb kök qovluğunu və yenidənyazma qaydasını yoxlayın. Apache daxil edilmiş `.htaccess` faylının tətbiq edilməsini tələb edir; Nginx üçün `index.php` faylına çatan `try_files` qaydası lazımdır.

404 tələb olunan resursun tapılmadığını bildirir. Hər çatışmayan səhifəni 200 cavabına çevirməklə bunu düzəltmək olmaz.

## Sorğu 405 qaytarır

Yol mövcuddur, lakin HTTP metodu uyğun gəlmir. POST forması üçün POST marşrutu lazımdır. `Allow` cavab başlığını və marşrut siyahısını yoxlayın. Brauzerin ünvan sətri GET göndərir, buna görə yalnız POST qəbul edən son nöqtəni birbaşa sınaqdan keçirə bilməz.

## Forma 419 qaytarır

CSRF tokeni yoxdur, vaxtı bitib və ya başqa sessiyadandır. Formanı yenidən açın və yeni yaradılmış tokeni göndərin. SessionMiddleware-in CsrfMiddleware-dən əvvəl işlədiyini, formaya `_token` daxil olduğunu və brauzerin sessiya kukisini qəbul etdiyini təsdiqləyin.

Fərqli mənbələr arasında kukilərlə fetch istifadə etdikdə mənbə və kuki qaydaları əhəmiyyətlidir. Öyrənərkən eyni mənbəli forma ilə başlayın. CSRF giriş və ya avtorizasiyanı əvəz etmir.

## API 400, 415 və ya 422 qaytarır

- **400:** JSON təhlil edilə bilmədi, parametrin forması yanlışdır və ya sorğu strukturu düzgün deyil.
- **415:** Son nöqtə `Content-Type: application/json` gözləyir.
- **422:** Təhlil uğurlu oldu, lakin sahə yoxlamadan keçmədi. Hansı sahənin uğursuz olduğunu təxmin etmək əvəzinə `errors` obyektini oxuyun.

Fərqi konkret sorğularla görmək üçün [yoxlama nümunəsindən](/examples/validated-api) istifadə edin.

## Sorğu 429 qaytarır

API sorğu tezliyi limitinə çatılıb. `Retry-After` müddətini gözləyin. Test zamanı eyni müəyyən edilmiş IP-dən təkrar çağırışlar ortaq sayğacdan istifadə edir. Əks proksidə müştəri IP-sinin emalı düşünülmüş şəkildə konfiqurasiya edilməlidir; ixtiyari ötürülmüş başlıqlara kor-koranə etibar etməyin.

## Səhifə 500 qaytarır

Cavabda sorğu ID-sini tapın və serverdə `storage/logs/app.log` faylını yoxlayın. İstehsal cavabları daxili istisna mesajlarını gizlədir. Həmin sorğu ID-sinin yaxınlığında çatışmayan bağlama, şablon dəyişəni, bağlantı xətası və ya fayl icazəsi problemini axtarın.

Problemi lokal olaraq təkrar yaradın. Giriş məlumatlarını, şəxsi istifadəçi məlumatlarını və ya istehsal mühitinin bütün faylını deyil, minimal nümunə və həssas məlumatlardan təmizlənmiş jurnal kontekstini paylaşın.

## Dəyişikliklər görünmür

Başqa işçi nüsxəni deyil, təqdim edilən tətbiqi redaktə etdiyinizi yoxlayın. Twig-in mənbədən yenidən yüklənməsi aktivdir. Rəsmi veb-sayt CSS və JavaScript URL-lərini faylların dəyişmə vaxtına əsasən versiyalandırır.

Öz resurslarınız üçün yeniləmələri yerləşdirərkən onların versiyasını və ya fayl adını dəyişin. Vaxt nişanı yoxlamaları söndürülmüş PHP OPcache istifadə edirsinizsə, yerləşdirmə uyğun PHP işçilərini yenidən yükləməlidir. Əlaqəsiz xidmətləri kor-koranə sıfırlamayın.

## Hələ də çətinlik çəkirsiniz

[Sazlama dərsini](/learn/debugging) oxuyun. Faydalı problem bildirişində freymvork reviziyası, PHP versiyası, əmr və ya sorğu, gözlənilən nəticə, faktiki nəticə və kiçik təkrar yaratma nümunəsi olur. [Terminlər lüğəti](/docs/v0.1/glossary) xəta müzakirələrində istifadə edilən anlayışları izah edir.
