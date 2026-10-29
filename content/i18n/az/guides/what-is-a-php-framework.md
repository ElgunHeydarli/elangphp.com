# PHP freymvorku nədir?

PHP freymvorku veb-tətbiqə təkrar istifadə edilə bilən quruluş verir: sorğunu qəbul etmək, düzgün davranışı seçmək, məlumatla işləmək və cavab qaytarmaq üsulu. Proqramlaşdırma dili PHP olaraq qalır. Freymvork həmin dildə yazdığınız tətbiqi təşkil etməyə kömək edir.

Elang belə freymvorklardan biridir. Onun cari ilkin inkişaf versiyası tanınmış PHP komponentlərini kiçik tətbiq API-si vasitəsilə birləşdirir. Əsas sorğu həyat dövrünü anlamaq həm Elang-ı, həm də digər freymvorkları öyrənməyi asanlaşdırır.

## Brauzer və serverdən başlayın

Kimsə URL açanda brauzer HTTP sorğusu göndərir. Server PHP kodunu işlədir və HTTP cavabı qaytarır. Həmin cavab səhifə üçün HTML, API üçün JSON, yönləndirmə və ya xəta statusu ehtiva edə bilər.

Ön tərəf brauzerin nə göstərdiyini və etdiyini təsvir edir: HTML, CSS və JavaScript. Arxa tərəf serverdə sorğuları və məlumatları emal edir. PHP freymvorku brauzerə göndərilən HTML-i yaratdıqda belə arxa tərəfə aiddir.

## Marşrut nə edir

Marşrut sorğunun metodunu və yolunu emalediciyə bağlayır. Elang-da:

```php
$router->get('/hello/{name}', fn (string $name) => [
    'message' => 'Hello, '.$name.'!',
]);
```

`GET` metoddur. `/hello/{name}` yol nümunəsidir. Anonim funksiya emaledicidir. Adlandırılmış dəyər funksiyaya ötürülür və qaytarılan PHP massivi JSON cavabına çevrilir.

Freymvork hər PHP faylının öz marşrutlaşdırma davranışını yaratmasını tələb etmək əvəzinə bu qaydaları çoxsaylı səhifələr boyunca vahid edir.

## Kontrollerlər və xidmətlər niyə mövcuddur

Marşrut kiçik funksiya kimi başlaya bilər. Davranış böyüdükcə kontroller onun üçün aydın yer təmin edir. Xidmətlər şablon yaratmaq, girişi yoxlamaq və ya verilənlər bazasına müraciət etmək kimi təkrar istifadə olunan məsuliyyətləri yerinə yetirir.

Asılılıqların yeridilməsi xidməti ona ehtiyacı olan konstruktor və ya metoda ötürür. Konteyner asılılığı həll edir. Bu, əl ilə yaratmanı və ya qlobal axtarışları hər emalediciyə səpələmək əvəzinə asılılığı kodda görünən saxlayır.

## Freymvork sizin üçün nəyi qərarlaşdırmır

Freymvork qeydin kimə məxsus olduğunu, hansı istifadəçinin onu redaktə edə biləcəyini, məhsulunuzun nə etməli olduğunu və ya yerləşdirmənizin düzgün konfiqurasiya edilib-edilmədiyini bilmir. Bunlar tətbiqin məsuliyyətləridir.

Eyni şəkildə, yoxlayıcı çıxışın ekranlaşdırılmasını əvəz etmir və sessiya avtomatik olaraq istifadəçini autentifikasiya etmir. Bu sərhədləri öyrənmək uzun köməkçi metodlar siyahısını əzbərləməkdən daha faydalıdır.

## Freymvork, kitabxana və CMS

| Alət | Tipik rol | Hələ də sizin qurduğunuz hissə |
| --- | --- | --- |
| Kitabxana | Poçt göndərmək kimi konkret problemi həll edir | Ətrafındakı tətbiq və onun qaydaları |
| Freymvork | Tətbiq quruluşunu və təkrar istifadə olunan alətləri əlaqələndirir | Məhsul funksiyaları, məlumat qaydaları və giriş nəzarəti |
| CMS | Məzmun idarəetmə məhsulu və redaktə iş axını təqdim edir | Saytın konfiqurasiyası, məzmunu və xüsusi əlavələri |

Freymvork daxilində kitabxanalardan istifadə edə bilər. Elang Symfony komponentləri, Doctrine DBAL, Twig və digər paketlərdən istifadə edir; PHP ekosistemini ayrıca paket meneceri ilə əvəz etmir.

## Elang-ı nə vaxt öyrənməli

Kiçik və açıq PHP tətbiq API-sini anlamaq və şəffaf inkişaf layihəsini izləmək istəyəndə Elang-ı araşdırmaq faydalıdır. İlkin versiyanın API-ləri dəyişir və yetkin freymvorklarla müqayisədə funksiyalar toplusu daha kiçikdir.

Hazırda sizə oturuşmuş istehsal ekosistemi, uzunmüddətli uyğunluq vədləri və ya hazır autentifikasiya və növbə texnologiyaları lazımdırsa, həmin ehtiyacları ilkin versiyanın faktiki imkanları ilə müqayisə edin. [Elang və Laravel təlimatı](/guides/elang-vs-laravel) bu fərqləri açıq göstərir.

## Praktik öyrənmə ardıcıllığı

1. [Mühit dərsi](/learn/environment) ilə PHP və Composer-i yoxlayın.
2. [Nümunələrdə istifadə olunan PHP sintaksisinin](/learn/php-basics) kiçik hissəsini öyrənin.
3. [İlk marşrutunuzu](/learn/first-route) qurun və cavabını yoxlayın.
4. Yoxlama və HTML şablonları əlavə edin.
5. [Tam tapşırıq lövhəsi layihəsini](/learn/build-a-task-board) izləyin.
6. Verilənlər bazasına girişi, testləri və yerləşdirməni ayrıca addımlar kimi öyrənin.

Məqsəd hər hissənin niyə mövcud olduğunu anlamaqdır. Bu aydınlaşdıqdan sonra API istinadındakı adları yadda saxlamaq xeyli asanlaşır.
