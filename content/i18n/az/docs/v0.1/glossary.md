# PHP və freymvork terminləri lüğəti

Bunlar Elang öyrənmə yolu boyunca istifadə olunan terminlərdir. İlk səhifənizi qurmamışdan əvvəl onları əzbərləməyə ehtiyac yoxdur; termin lazım olduqda geri qayıdın.

## PHP

PHP arxa tərəf kodunu icra edən proqramlaşdırma dilidir. Elang PHP-də yazılmış freymvorkdur. Elang-ın quraşdırılması yeni proqramlaşdırma dili və ya ayrıca interpretator quraşdırmır.

## Freymvork

Freymvork tətbiq qurmaq üçün qaydalar və təkrar istifadə edilə bilən alətlər təqdim edir. Elang sorğuları, marşrutları, xidmətləri və cavabları əlaqələndirir. Öz tətbiqiniz yenə də funksiyalarını, məlumat modelini və giriş qaydalarını təyin edir.

## Arxa və ön tərəf

Arxa tərəf serverdə işləyir. Girişi oxuyur, qaydaları tətbiq edir, məlumatlara müraciət edir və cavablar yaradır. Ön tərəf brauzerin göstərdiyi və qarşılıqlı əlaqə qurduğu hissədir: HTML quruluşu, CSS təqdimatı və JavaScript davranışı. Bu sayt arxa tərəfdə Elang, ön tərəfdə isə sadə HTML, CSS və JavaScript istifadə edir.

## HTTP sorğusu və cavabı

Sorğu serverdən nəsə etməyi istəyir. Ona metod, URL, başlıqlar və bəzən gövdə daxildir. Cavaba status, başlıqlar və adətən gövdə daxildir. JSON API və HTML səhifəsi eyni əsas mübadilədən istifadə edir.

## HTTP metodu və status kodu

GET resursu oxuyur; POST adətən məlumat göndərir və ya nəsə yaradır. API-niz onları təyin etdikdə PUT, PATCH və DELETE digər dəyişiklikləri ifadə edir. Status kodları nəticəni bildirir: 200 uğur, 201 yaradılma, 404 tapılmama deməkdir, 422 isə yoxlama uğursuzluqlarını göstərə bilər.

## Marşrut

Marşrut metodu və yolu emalediciyə uyğunlaşdırır. `/posts/{id}` adlandırılmış parametr ehtiva edir. Adlandırılmış marşrutun yolu tətbiq boyunca təkrarlamadan URL yaratmaq üçün istifadə edilən daxili etiketi də var. [Marşrutlaşdırmaya](/docs/v0.1/routing) baxın.

## Kontroller

Kontroller sorğu emalı davranışını ehtiva edən sinifdir. Yeridilmiş xidmətləri qəbul edə, sorğunu oxuya, girişi yoxlaya və cavab qaytara bilər. Tətbiq böyüdükcə marşrut faylını oxunaqlı saxlamağa kömək edir.

## Aralıq proqram təminatı

Aralıq proqram təminatı sorğunun növbəti addımını əhatə edir. Girişi yoxlaya, sorğunu rədd edə və ya cavabı dəyişə bilər. Ardıcıllıq əhəmiyyətlidir: CSRF aralıq proqram təminatı sessiyada saxlanan tokeni yoxlamazdan əvvəl sessiya aralıq proqram təminatı sessiyanı yaratmalıdır.

## Xidmət və asılılıqların yeridilməsi

Xidmət şablon yaratmaq və ya verilənlər bazasına qoşulmaq kimi faydalı işi yerinə yetirən obyektdir. Asılılıqların yeridilməsi tətbiq kodundan hər asılılığı özünün yaratmasını istəmək əvəzinə həmin obyekti konstruktor və ya emaledici parametri vasitəsilə verir.

## Konteyner və singleton

Konteyner xidmətləri həll edir. Adi bağlama və ya bağlanmamış konkret sinif yeni obyekt yaradır. Singleton bağlaması həmin tətbiq nüsxəsi daxilində bir obyekti təkrar istifadə edir. Sorğudan asılı obyekt təsadüfən uzunömürlü ortaq xidmətə çevrilməməlidir.

## Composer və paket

Composer layihənin PHP kitabxanalarını quraşdırır və avtomatik yükləyicisini yaradır. Paket composer.json ilə təsvir edilən təkrar istifadə oluna bilən kitabxanadır. Composer versiyaları həll edir və seçilmiş versiyaları composer.lock faylında qeydə alır. O, hər paketi bütün PHP layihələri üçün qlobal əlçatan etmir.

## Ad məkanı və avtomatik yükləmə

Ad məkanı sinfə `App\Controllers\HelloController` kimi tam ad verir. Composer-in PSR-4 uyğunlaşdırılması həmin adı fayla bağlayır. `use` ifadəsi oxunaqlılıq üçün adı idxal edir; çatışmayan paketi quraşdırmır.

## Mühit dəyişəni və konfiqurasiya

Mühitə xas dəyərlərə verilənlər bazası giriş məlumatları, kanonik sayt URL-i və tətbiq açarı daxildir. Elang bunları konfiqurasiya vasitəsilə oxuyur. `.env` faylı ictimai qovluqdan və versiya nəzarətindən kənarda qalmalıdır.

## Yoxlama və ekranlaşdırma

Yoxlama girişin müəyyən məqsəd üçün məqbul olub-olmadığını müəyyən edir. Ekranlaşdırma çıxışı müəyyən kontekst üçün təhlükəsiz kodlaşdırır. Başlıq etibarlı mətn olmaqla yanaşı `<` və ya `&` ehtiva edə bilər; Twig HTML yaradarkən həmin simvolları ekranlaşdırır. Yoxlama və ekranlaşdırma fərqli problemləri həll edir.

## Verilənlər bazası, sorğu qurucusu və ORM

Verilənlər bazası qeydləri saxlayır. Sorğu qurucusu API vasitəsilə SQL qurur, lakin istifadəçi dəyərləri yenə də parametr kimi bağlanmalıdır. ORM qeydləri domen obyektlərinə uyğunlaşdırır və fərqli abstraksiyalar dəsti əlavə edir. Elang hazırda daxili ORM deyil, Doctrine DBAL və sorğu qurucusu ehtiva edir.

## Miqrasiya və tranzaksiya

Miqrasiya versiyalandırılmış faylda verilənlər bazası sxemini dəyişir. Tranzaksiya uyğun əməliyyatları qruplaşdırır ki, onlar birlikdə təsdiqlənsin və ya uğursuzluqdan sonra geri qaytarılsın. Verilənlər bazası mühərrikləri sxem dəyişikliklərinin tranzaksiyalarla qarşılıqlı əlaqəsində fərqlənir; MySQL DDL gizli commit edə bilər.

## Sessiya və kuki

Sessiya brauzerlə əlaqəli server məlumatlarını saxlayır. Kuki sessiya identifikatorunu daşıyır. Sessiya müvəqqəti vəziyyət üçün faydalıdır, lakin avtomatik olaraq istifadəçinin kimliyini müəyyən etmir. Giriş sisteminə əlavə autentifikasiya və avtorizasiya davranışı lazımdır.

## CSRF

Saytlararası sorğu saxtalaşdırması brauzeri mövcud kukiləri ilə vəziyyəti dəyişən sorğu etməyə aldadır. CSRF tokeni forma göndərişini nəzərdə tutulan sessiyaya bağlayır. O, avtorizasiya qaydaları olmayan API-ni qorumur və ya təhlükəli məlumatı etibarlı etmir.

## Autentifikasiya və avtorizasiya

Autentifikasiya çağıran tərəfin kim olduğunu müəyyən edir. Avtorizasiya həmin tərəfin nə edə biləcəyinə qərar verir. Parolun heşlənməsi autentifikasiya sisteminin istifadə etdiyi əsas mexanizmlərdən yalnız biridir. Auth Starter hesab proseslərini təmin edir, lakin hər tətbiq yenə də sahiblik yoxlamalarını və giriş siyasətlərini özü təyin etməlidir.

## Keş və jurnal

Keş təkrar istifadə edilə bilən nəticələri müvəqqəti saxlayır. Jurnal diaqnostika üçün server hadisələrini və xətalarını qeydə alır. Heç biri ictimai qovluğa yerləşdirilməməlidir. Sirləri jurnala yazmaqdan və ya keşlənmiş dəyərin həmişə etibarlı qalacağını fərz etməkdən çəkinin.

## CLI və veb kök qovluğu

CLI əmr sətri interfeysi deməkdir: `php elang doctor` kimi əmrlər terminalda icra olunur. Veb kök qovluğu veb-serverin açıq etdiyi qovluqdur. Elang üçün bu, `public/` olmalıdır, mənbə, vendor faylları, konfiqurasiya və saxlama sahəsi isə şəxsi qalmalıdır.

## Növbəti addım

Terminləri [ilk marşrutunuzda](/learn/first-route) tətbiq edin və ya bir neçə anlayışın birlikdə necə işlədiyini görmək üçün [tam tapşırıq lövhəsi layihəsini](/learn/build-a-task-board) izləyin.
