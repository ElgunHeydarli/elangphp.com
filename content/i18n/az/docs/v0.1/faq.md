# Elang haqqında tez-tez verilən suallar

## Elang proqramlaşdırma dilidir?

Xeyr. Dil PHP-dir. Elang tətbiq quruluşu və təkrar istifadə edilə bilən arxa tərəf alətləri təqdim edən PHP freymvorkudur. Siz adi PHP kodu yazır və asılılıqları Composer ilə quraşdırırsınız.

## Əvvəlcə PHP bilməliyəm?

Bir qədər PHP bilmək freymvorku anlamağı xeyli asanlaşdırır. Nümunələrdə istifadə olunan dəyişənləri, massivləri, funksiyaları, sinifləri və ad məkanlarını təqdim edən [sizə lazım olan PHP](/learn/php-basics) dərsi ilə başlayın. Sonra dərsləri ardıcıllıqla izləyə bilərsiniz.

## Tam yeni başlayan haradan başlamalıdır?

[Mühitin hazırlanması](/learn/environment) ilə başlayın. Tətbiq kodunu köçürməzdən əvvəl PHP və Composer-i yoxlayın. Sonra PHP əsasları və ilk marşrutunuz dərslərini izləyin. Hər mərhələdə davam etməzdən əvvəl yoxlanmalı konkret nəticə var.

## Kompüterimə nə quraşdırmalıyam?

Sizə PHP 8.x daxilində PHP 8.3+, Composer 2, tələb olunan PHP genişlənmələri, terminal və redaktor lazımdır. Öyrənmə üçün standart verilənlər bazası SQLite-dır. Veb-saytı və ya əsas nümunələri işə salmaq üçün Node.js, npm, JavaScript freymvorku və Docker tələb olunmur.

## Packagist-də stabil buraxılış varmı?

Hələ stabil buraxılış yoxdur. On səkkiz inkişaf paketi Packagist-də `dev-main` (`0.1.x-dev` ləqəbi) ilə mövcuddur. [Quraşdırma](/docs/v0.1/installation) əmrlərindən istifadə edin; xüsusi repozitoriya lazım deyil. Stabil buraxılışa qədər API-lər dəyişə bilər.

## Mövcud Composer paketlərindən istifadə edə bilərəm?

Bəli. Uyğun PHP paketini Composer ilə quraşdırın, PHP tələblərini yoxlayın və lazım olduqda xidmətlərini qeydiyyata alın. Elang Composer-i əvəz etmir və ayrıca paket meneceri tələb etmir. [Ekosistem səhifəsi](/ecosystem) bu saytın həqiqətən istifadə etdiyi kitabxanaları sadalayır.

## React, Vue və ya yığım sisteminə ehtiyacım varmı?

Xeyr. Twig serverdə HTML yaradır. CSS və JavaScript `public/assets/` daxilində adi fayllar ola bilər. Öz tətbiqiniz üçün ön tərəf texnologiyalarını seçə bilərsiniz, lakin Elang bu dərslər üçün bunu tələb etmir.

## Elang Laravel-dən yaxşıdır?

Elang kiçik və asan mənimsənilən tətbiq API-si təqdim etməyi hədəfləyir. Laravel daha geniş, yetkin funksiyalar toplusuna və ekosistemə malikdir. Bu erkən ilkin versiya ümumi üstünlük müəyyən etmir. Konkret fərqlər və cari məhdudiyyətlər üçün [Elang və Laravel müqayisəsini](/guides/elang-vs-laravel) oxuyun.

## Paylaşılan hostinqə yerləşdirə bilərəm?

Host PHP və genişlənmə tələblərinə uyğun olmalı və sorğuların ön kontrollerə yönləndirilməsini dəstəkləməlidir. Veb kök qovluğunda yalnız ictimai faylları saxlayın. Bəzi hostlarda sabit kök qovluqlar və ya məhdudlaşdırıcı PHP konfiqurasiyaları olur; yerləşdirmə planı seçməzdən əvvəl onların imkanlarını yoxlayın. [Yerləşdirmə təlimatı](/docs/v0.1/deployment) bu saytın istifadə etdiyi sabit kök qovluqlu quruluşu izah edir.

## Öz kodumu harada yerləşdirməliyəm?

Kontrollerlər və xidmətlər `app/` daxilində yerləşir. Marşrutlar `routes/web.php` faylına yazılır. Şablonlar `resources/views/` daxilində yerləşir. CSS və JavaScript `public/assets/` qovluğuna qoyulur. Konfiqurasiya və sirlər `public/` xaricində saxlanılır. [Qovluq quruluşuna](/docs/v0.1/structure) baxın.

## Asılılıqlar necə quraşdırılır?

Composer create-project tətbiqi və asılılıqları quraşdırır, composer.lock yaradır və ilkin hazırlığı icra edir. Mövcud tətbiqi klonladıqdan sonra onun kilidlənmiş asılılıqlarını eynilə bərpa etmək üçün `composer install` yerinə yetirin. Qlobal Elang quraşdırıcısı layihə yaradılmasını Composer-ə həvalə edir.

## Niyə marşrut lokal işləyir, amma hostinqdə uğursuz olur?

Adətən veb kök qovluğu, yenidənyazma qaydası, PHP versiyası, genişlənmələr dəsti və ya fayl icazələri fərqlənir. Hostda doctor-u işə salın, marşrut siyahısını yoxlayın və serverin şəxsi jurnalına baxın. Problemlərin aradan qaldırılması təlimatı düzgün qatı düzəldə bilməyiniz üçün 404, 405, 419, 422 və 500 xətalarını ayırır.

## Elang artıq giriş sistemi, növbələr və ya ORM ehtiva edirmi?

Autentifikasiya, təsdiqlənmiş hesablar, parol bərpası, səlahiyyətləri məhdudlaşdırılmış API tokenləri, model repozitoriyaları və tam başlanğıc layihələri mövcuddur. Verilənlər bazasına əsaslanan tapşırıqlar, UTC üzrə planlaşdırma, SSE, WebSockets, ön tərəf üçün CORS və təhlükəsiz təkrar cəhd edilə bilən JSON yazma əməliyyatları da mövcuddur. Redis-ə əsaslanan keş, sessiyalar, ciddi sorğu tezliyi limitləri, kilidlər və tranzaksiyalı bildirişlər də mövcuddur. Tam ORM və əlavə broker inteqrasiyaları cari reallaşdırmaya daxil deyil. Funksiyadan asılı olmamışdan əvvəl [yol xəritəsini](/roadmap) yoxlayın.

## Nümunələr istehsal tətbiqləridirmi?

Onlar sərhədləri aydın göstərilmiş öyrənmə nümunələridir. Tapşırıq lövhəsi müvəqqəti vəziyyəti cari brauzer sessiyasında saxlayır; hesablar, daimi ortaq məlumatlar və ya tam tapşırıq idarəetmə məhsulu təqdim etmir. Həqiqi xidmətlər qurarkən tətbiqə xas giriş nəzarəti və testlərdən istifadə edin.

## Quraşdırmamın hazır olduğunu necə bilim?

`php elang doctor` yerinə yetirin. Sonra lokal serveri başladın və dərsdə göstərilmiş dəqiq URL-i açın. Yaşıl tələblər hesabatı və gözlənilən HTTP cavabı sadəcə görünən çıxış verməyən əmrdən daha yaxşı sübutdur.

## Necə töhfə verə və ya kömək ala bilərəm?

[Problemlərin aradan qaldırılması təlimatını](/docs/v0.1/troubleshooting) yoxlayın, sonra lazım gələrsə kiçik təkrar yaratma nümunəsi hazırlayın. GitHub problem qeydləri adi xətalar və suallar üçün uyğundur. Təhlükəsizlik boşluqları üçün repozitoriyanın təhlükəsizlik siyasətini izləyin və həssas təfərrüatları şəxsi saxlayın.
