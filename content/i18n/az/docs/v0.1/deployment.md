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
