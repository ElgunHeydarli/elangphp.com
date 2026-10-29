# Yerləşdirmə

Elang tətbiqləri standart PHP veb-serverində işləyir. Bu sayt Apache və Nginx arxasında PHP-FPM istifadə edir.

## Tətbiqi hazırlayın

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php elang migrate --force
```

İstehsal mühitində dəyişiklik etməzdən əvvəl miqrasiyaları nəzərdən keçirin və verilənlər bazasının ehtiyat nüsxəsini çıxarın. İnkişaf alətlərini ictimai qovluğa quraşdırmayın.

## Mühit

`APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL` və yaradılmış `APP_KEY` təyin edin. Verilənlər bazası və poçt giriş məlumatlarını ayrıca konfiqurasiya edin. PHP prosesi üçün yalnız `storage/` və zəruri verilənlər bazası yerlərini yazıla bilən edin.

## Veb kök qovluğu

Veb-serveri `public/` qovluğuna yönəldin. Mövcud olmayan yolları `index.php` faylına ötürün. Apache daxil edilmiş `.htaccess` faylından istifadə edə bilər. Tipik Nginx location bloku belədir:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Serverinizə uyğun PHP-FPM location blokunu əlavə edin. Layihənin kökünü, vendor fayllarını, Git metadatasını, sirləri və ya SQLite fayllarını açıq etməyin.

## Mövcud paylaşılan hostinq

Hostinq idarəetmə paneli veb kök qovluğunu sabit təyin etdikdə tətbiqi ondan kənarda saxlayın və ictimai qovluğa yalnız `public/` məzmununu köçürün. Giriş nöqtəsini şəxsi tətbiqi yükləyəcək şəkildə tənzimləyin. Bu saytın yayımlama skripti həmin modeli izləyir.

## Yerləşdirmədən sonra

Gözlənilən səhifələri və API cavablarını yoxlayın, server jurnallarını nəzərdən keçirin və uğursuzluqları izləyin. HTTPS sertifikatları və statik resurslar üçün təhlükəsizlik başlıqları hostinq qatında konfiqurasiya edilməlidir. Bu ilkin versiyaya fasiləsiz avtomatik yerləşdirmə və ya idarə olunan hostinq xidməti daxil deyil.

## Hesab məktubları və domenin təsdiqlənməsi

Email təsdiqi və şifrə bərpası işlək poçt nəqliyyatı tələb edir. Standart `null://null` istehsal mühitində hesab məktublarını qəsdən deaktiv edir. Etibarlı TLS sertifikatı və icazəli göndəricisi olan autentifikasiyalı SMTP xidməti istifadə edin; 465 portunda `smtps` bağlantını əvvəldən TLS ilə qoruyur. DSN daxilində istifadəçi adı və şifrəni URL kodlaşdırması ilə yazın. Sertifikat yoxlamasını söndürməyin.

Şəxsi sirr faylı DSN-i mühit faylının özündən kənarda saxlayır:

```dotenv
MAILER_DSN=
MAILER_DSN_FILE=/run/secrets/elang-mailer-dsn
MAIL_FROM=noreply@example.com
```

Göstərilən fayl tam DSN-i saxlayır, tətbiqi idarə edən hesaba məxsus olur və yalnız ehtiyacı olan hesab tərəfindən oxunmalıdır. Boş olmayan `MAILER_DSN`, `MAILER_DSN_FILE` dəyərindən üstündür. Giriş məlumatlarını Git-ə, çata, ekran görüntülərinə və ya açıq qovluğa yerləşdirməyin.

Göndərici poçt qutusunu yaradın və SMTP girişini yoxlayın. Domenin səlahiyyətli DNS xidmətində faktiki göndəriş xidməti üçün SPF, poçt serverinin açıq DKIM açarı və DMARC siyasəti yayımlayın. Qeyd əlavə etməzdən əvvəl mövcud siyasəti yoxlayın: təkrarlanan SPF siyasətləri təsdiqləməni poza bilər. Daha sərt rejimə keçməzdən əvvəl DMARC monitorinqi ilə başlayın. SMTP-nin məktubu qəbul etməsi və ya yerli poçt qutusu sınağı xarici provayderlərə çatdırılmanı sübut etmir.

Elang-ın rəsmi saytında təsdiqlənmiş administrator hazırlanmış üç qeydi [inteqrasiyalar](/admin/integrations#mail-dns) bölməsində görə bilər. Fornex yayımlayıcısı yalnız `elangphp.com` üçün çatışmayan qeydləri əlavə edir, siyasət ziddiyyəti olduqda dayanır və mövcud qeydləri əvəz etmir və silmir. API açarı bir sorğu üçün istifadə edilir və saxlanmır. Əməliyyat HTTPS, CSRF, administrator icazəsi, cari şifrənin təsdiqi, sorğu məhdudiyyəti və yayım kilidi ilə qorunur. Provayderdə qismən xəta olduqda bəzi yeni qeydlər yarana bilər; təkrar cəhd əvvəlcə mövcud qeydləri yoxlayır. Açıq DNS yayılmasını və real çatdırılmanı ayrıca təsdiqləyin.

## Ehtiyat nüsxə yaradın və bərpanı sübut edin

Verilənlər bazasını, şəxsi obyekt fayllarını, tətbiq açarını, istinad edilən sirr fayllarını və şifrələnmiş inteqrasiya ayarlarını birlikdə ehtiyatlayın. Nüsxələri `public/` xaricində məhdud icazələrlə saxlayın; qorunan bir nüsxə tətbiq serverindən kənarda da olsun. Uyğun şifrələmə açarı olmadan verilənlər bazası nüsxəsi şifrələnmiş giriş məlumatlarını, bildirişləri və audit təsdiqini bərpa etmir.

Verilənlər bazasının tutarlı ehtiyat nüsxə alətindən istifadə edin. SQLite üçün komanda sətri proqramını quraşdırın və sorğular yazı əməliyyatı apararkən bazanı kopyalamaq əvəzinə onlayn ehtiyat nüsxə əmrini işlədin. Yerləşdirmənizdəki faktiki baza yolunu göstərin:

```bash
install -d -m 700 /srv/elang-backups
sqlite3 /srv/elang/storage/database.sqlite ".backup '/srv/elang-backups/elang.sqlite'"
chmod 600 /srv/elang-backups/elang.sqlite
sqlite3 /srv/elang-backups/elang.sqlite 'PRAGMA integrity_check;'
```

Bütövlük yoxlamasının `ok` nəticəsi yalnız ilk yoxlamadır. Nüsxəni ayrıca baza və şəxsi yaddaş qovluğuna bərpa edin. Bütün cədvəllərin sətir saylarını tamamlanmış nüsxə ilə müqayisə edin; miqrasiyaları, administrator girişini, məzmunu və uyğun tətbiq açarı ilə HMAC audit zəncirini təsdiqləyin. Bərpa sınağını heç vaxt canlı bazaya yönəltməyin və bərpa mühitində email və webhook göndərişini aktiv etməyin.

Tətbiq versiyasının geri qaytarılmasını ayrıca məşq edin. İşlədiyi məlum olan mənbə versiyasını və asılılıqlarını saxlayın. Mənbəni geri qaytarmaq sxem dəyişikliklərini, çatdırılmış məktubları və obyekt yaddaşına yazıları geri almır. Rəsmi hostinq atomik şəxsi versiya göstəricisindən istifadə edir; digər yerləşdirmələr versiya dəyişmə və işçiləri yenidən başlatma prosedurunu özləri təmin etməlidir. [Versiyalar və yeniləmələr](/docs/v0.1/releases) və [əməliyyatlar](/docs/v0.1/observability) bölmələrinə baxın.
