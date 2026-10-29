# Elang tələbləri və mühitin hazırlanması

Elang-ı quraşdırmazdan əvvəl terminalınızdakı PHP icra faylının freymvorkun tələblərinə uyğun olduğuna əmin olun. Terminaldakı PHP və veb-serverinizdəki PHP ayrı quraşdırmalar ola bilər.

## Sizə nə lazımdır

- PHP 8.x daxilində PHP 8.3 və ya daha yeni versiya. Repozitoriya PHP 8.3 və 8.4 üzərində sınaqdan keçirilir.
- PHP asılılıqlarını quraşdırmaq üçün Composer 2.
- PHP genişlənmələri: ctype, JSON, mbstring, PDO və Sodium.
- Parol xidməti üçün Argon2id dəstəyi olan PHP yığımı.
- Verilənlər bazanız üçün PDO drayveri. Daxil edilmiş nümunələr `pdo_sqlite` istifadə edir.
- Terminal və mətn redaktoru. Git freymvorka töhfə vermək üçün faydalıdır; adi tətbiqlər Composer vasitəsilə quraşdırılır.

Freymvorkun test dəstinə əlavə olaraq PHPUnit-in tələb etdiyi, o cümlədən DOM və XML genişlənmələri lazımdır. `composer install` çatışmayan genişlənmələri səssizcə buraxmaq əvəzinə onlar barədə məlumat verir.

## Terminalınızı yoxlayın

Bu əmrləri bir-bir yerinə yetirin:

```bash
php -v
php --ini
php -m
composer --version
```

Birinci əmr PHP 8.3 və ya daha yeni versiyanı göstərməlidir. İkinci əmr yüklənmiş CLI `php.ini` faylını göstərir. Üçüncü əmr aktiv modulların siyahısını verir. Dördüncü əmr Composer 2-ni göstərməlidir.

`php` və ya `composer` tanınmırsa, quraşdırmadan sonra terminalı yenidən açın və icra faylının PATH qeydini yoxlayın. Windows-da `where.exe php` hansı icra faylının istifadə edildiyini göstərir. macOS və Linux-da `command -v php` istifadə edin.

## Elang doctor-u işə salın

Asılılıqları quraşdırıb tətbiq açarınızı yaratdıqdan sonra:

```bash
php elang doctor
```

Hər tələb olunan yoxlama `OK` göstərməlidir. Doctor PHP versiyasını, tələb olunan genişlənmələri, parol dəstəyini, tətbiq açarını, yazıla bilən saxlama sahəsini, istehsal mühitinin sazlama parametrlərini və sadə verilənlər bazası bağlantısını yoxlayır. Açarınızı və ya verilənlər bazasına giriş məlumatlarınızı çap etmir.

Maşın tərəfindən oxuna bilən çıxış üçün:

```bash
php elang doctor --json
```

Tələb olunan yoxlama uğursuz olduqda sıfırdan fərqli çıxış kodu və konkret növbəti addım qaytarılır. Avtomatik yükləyicinin özü yoxdursa, doctor başlamazdan əvvəl `composer install` yerinə yetirin.

## Verilənlər bazası seçin

SQLite lokal öyrənmə üçün ən qısa yoldur. Məlumatı faylda saxlayır və ayrıca verilənlər bazası serveri tələb etmir. Bu faylı `public/` qovluğundan kənarda saxlayın.

MySQL üçün işləyən server, giriş məlumatları, tətbiqiniz üçün yaradılmış verilənlər bazası və `pdo_mysql` tələb olunur. PostgreSQL üçün də server və `pdo_pgsql` lazımdır. DBAL bu drayverləri dəstəkləyir; cari repozitoriyanın inteqrasiya testləri bütün verilənlər bazası platformalarını deyil, SQLite-ı yoxlayır.

## Başlanğıc səviyyənizə uyğun davam edin

Lokal PHP alətləri ilə yenicə tanış olursunuz? Windows, macOS və Ubuntu üçün [mühitin hazırlanması](/learn/environment) təlimatını izləyin. Artıq PHP və Composer ilə rahat işləyirsiniz? [Quraşdırma](/docs/v0.1/installation), sonra isə [ilk marşrutunuz](/learn/first-route) ilə davam edin.
