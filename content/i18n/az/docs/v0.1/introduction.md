# Elang ilə tanış olun

Elang açıq şəkildə müəyyən edilmiş tətbiq API-si və modul xidmətlər üzərində qurulmuş PHP freymvorkudur. Marşrutlar yazın, xidmətləri birləşdirin, cavablar qaytarın və tətbiqinizi anlaşılan saxlayın.

Bu veb-sayt Elang üzərində işləyir və ingilis, Azərbaycan və rus dillərində məzmun təqdim edir. Onun ön tərəfi HTML, CSS və sadə JavaScript ilə hazırlanıb; arxa tərəfi isə burada sənədləşdirilən eyni marşrutlaşdırıcıdan, konteynerdən və xidmətlərdən istifadə edir.

## Hazırkı vəziyyət

**0.1 ilkin inkişaf versiyası.** Bu, stabil istehsal buraxılışı deyil, işlək əsasın erkən versiyasıdır. İctimai API-lər dəyişə bilər. Laravel-dən daha sürətli olduğuna dair iddia yoxdur və yalnız inkişaf serveri üzrə məhdud müqayisə mövcuddur; ümumi performans üstünlüyü müəyyən edilməyib.

## Nələr daxildir

- HTTP nüvəsi, adlandırılmış marşrutlar, marşrut qrupları və aralıq proqram təminatı.
- Konstruktor vasitəsilə asılılıq yeridilməsi və açıq bağlamalar olan PSR-11 konteyneri.
- HTML ekranlaşdırılması aktiv olan Twig şablonları.
- Strukturlaşdırılmış xətalarla Symfony yoxlama məhdudiyyətləri.
- Doctrine DBAL bağlantıları, sorğu qurucusu və miqrasiya əmrləri.
- Sessiyalar, CSRF aralıq proqram təminatı, Argon2id ilə parol heşlənməsi və Sodium şifrələməsi.
- PSR-16 keşləmə, PSR-3 jurnal qeydləri, hadisələr, poçt nəqli və konsol.

## Hələ nələr daxil deyil

Autentifikasiya, rollar, səlahiyyətləri məhdudlaşdırılmış API tokenləri, model/repozitoriya qatı, lokal Studio və yalnız oxumağa imkan verən sənədləşmə MCP serveri bu ilkin versiyaya daxildir. Fon tapşırıqları, UTC üzrə planlaşdırma, SSE, autentifikasiyalı WebSockets, ön tərəf üçün CORS və JSON yazma əməliyyatlarının idempotentliyi də daxildir. Redis-ə əsaslanan infrastruktur, tranzaksiyalı çıxış qutusu bildirişləri, MFA/keçid açarları/cihaz idarəetməsi, təşkilatlar, şəxsi fayllar, gələn qutusu bildirişləri, OpenTelemetry və React/Next/SaaS başlanğıc layihələri reallaşdırılıb. Tam ORM, əlavə broker inteqrasiyaları və avtomatik yeniləmə alətləri gələcək işlər olaraq qalır.

## Tələblər

PHP 8.x daxilində PHP 8.3 və ya daha yeni versiya, Composer 2 və `composer.json` faylında göstərilən genişlənmələr. SQLite nümunələri üçün `pdo_sqlite` də lazımdır. MySQL üçün `pdo_mysql`, PostgreSQL üçün isə `pdo_pgsql` tələb olunur.

[Quraşdırma](/docs/v0.1/installation) ilə davam edin və ya [öyrənmə yolunu](/learn) izləyin.
