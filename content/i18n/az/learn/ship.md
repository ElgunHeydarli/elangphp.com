# Yerləşdirməyə hazırlaşın

Lokal işləyən tətbiq onlayn olmamışdan əvvəl açıq konfiqurasiya tələb edir.

## Tətbiqi nəzərdən keçirin

Yoxlama qaydalarınızı, avtorizasiya qərarlarınızı, verilənlər bazası sorğularınızı və xəta davranışını yoxlayın. Test dəstinizi işə salın. Freymvork testləri tətbiqə xas testləri əvəz etmir.

## Asılılıqları hazırlayın

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

`APP_ENV=production`, `APP_DEBUG=false`, kanonik APP_URL və tətbiq açarı təyin edin. Sirləri və yazıla bilən faylları şəxsi saxlayın.

## Veb kök qovluğunu təyin edin

Yalnız `public/` qovluğunu açıq edin. PHP-FPM konfiqurasiya edin və mövcud olmayan yolları `index.php` faylına yönəldin. Yerləşdirmə təlimatında Apache və Nginx üzrə göstərişlər var.

## Sxem dəyişikliklərini tətbiq edin

`php elang migrate --force` yerinə yetirməzdən əvvəl gözləyən miqrasiyaları nəzərdən keçirin və vacib məlumatların ehtiyat nüsxəsini çıxarın. Yerləşdirmədən sonra səciyyəvi səhifələri, JSON xətalarını və jurnalları yoxlayın.

## Buraxılışın vəziyyətini bilin

Elang 0.1 dəyişən API-ləri olan ilkin inkişaf versiyasıdır. Uyğunluq və təhlükəsizlik testləri davam edərkən ondan qiymətləndirmə və nəzarət olunan layihələr üçün istifadə edin.
