# Elang, Laravel, CodeIgniter və Symfony: faktlarla seçim

Müqayisə 30 oktyabr 2026 tarixindəki development preview vəziyyətini göstərir. Elang Symfony komponentləri və Doctrine istifadə edən müstəqil PHP framework-üdür; Laravel distributivi deyil və Laravel API-sini təkrarlamır.

Laravel Eloquent modelləri, əlaqələri və kolleksiyaları sənədləşdirir. CodeIgniter Model CRUD, validasiya, səhifələmə, tarixlər və yumşaq silmə imkanlarını təqdim edir. Symfony HttpFoundation, Console, Cache və Clock kimi təkrar istifadə edilən komponentlər verir. Hər layihənin ayrıca qaydaları və ekosistemi var; funksiyanın adının oxşarlığı kod uyğunluğu demək deyil.

[Laravel Eloquent](https://laravel.com/framework/docs/13.x/eloquent), [CodeIgniter Model](https://codeigniter.com/user_guide/models/model.html), [Symfony components](https://symfony.com/doc/current/components/index.html), [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/getting-started.html).

## Hazırda Elang-da mövcud imkanlar

| Sahə | Reallaşdırılmış davranış | Öyrənin |
| --- | --- | --- |
| HTTP və xidmətlər | Route, middleware, PSR-11 DI, Symfony validasiyası | [routing](/az/docs/v0.1/routing) |
| Hesablar | Təsdiq, rol/policy, məhdud token, MFA/passkey | [account-security](/az/docs/v0.1/account-security) |
| Baza | DBAL repository və seçimə bağlı Doctrine ORM | [orm](/az/docs/v0.1/orm) |
| Məlumat çevirmə | Dəyişməz kolleksiya və tənbəl axın | [collections](/az/docs/v0.1/collections) |
| JSON cavabı | Açıq sahələr və əsas paginator meta məlumatı | [api-resources](/az/docs/v0.1/api-resources) |
| Partnyor inteqrasiyası | TLS, cavab həddi, ayrıca token, strict fake | [http-client](/az/docs/v0.1/http-client) |
| Test | Kernel sorğusu, sessiya, CSRF, yaddaş mail-i və HTTP yoxlaması | [application-testing](/az/docs/v0.1/application-testing) |
| Tətbiq əmri | Symfony Console genişlənməsi və ad toqquşması yoxlaması | [custom-console](/az/docs/v0.1/custom-console) |
| Fon və canlı yeniləmə | Baza növbəsi/cədvəli, SSE, qorunan WebSocket | [queues](/az/docs/v0.1/queues) |
| Tətbiq bazası | Redis, komanda, məxfi storage, bildiriş, telemetry, starter | [fullstack-starters](/az/docs/v0.1/fullstack-starters) |

## Məlumat qatını seçin

Sadə və birbaşa əməliyyatlar üçün DBAL repository-lərini saxlayın. Əlaqələr, unit of work və identity map domenə uyğundursa Doctrine ORM seçin. İkisi eyni DBAL bağlantısını paylaşır, amma əsas Model və Doctrine entity ayrı modellərdir; factory və səhifələmə adapterlərinin eyni işlədiyini fərz etməyin.

## Qalan işlər

Əlavə növbə brokerləri, semantik sənəd axtarışı, xüsusi redaktor inteqrasiyaları və avtomatik yeniləmə alətləri plandadır. Stabil buraxılışdan əvvəl API sabitləşməsi, dəstək siyasəti, müstəqil təhlükəsizlik yoxlaması və daha geniş real tətbiq/yük sübutları lazımdır. Ödəniş provayderi, axtarış mühərriki və OAuth kimi ekosistem inteqrasiyalarını ayrıca qiymətləndirin; başqa framework-ün bütün paketlərinin mövcudluğu iddia edilmir.

## Real layihədə qiymətləndirin

Hər variantda eyni girişləri, icazə qaydalarını, məlumat əməliyyatlarını və testləri qurun. Quraşdırma vaxtını, saxlanma işini və xətaları qeyd edin. Performans müqayisəsində PHP/SAPI, OPcache, avadanlıq, asılılıq, payload, isinmə və paralellik göstərilməli, xam nəticə təkrarlana bilməlidir. Burada ümumi sürət, təhlükəsizlik və production tutumu üstünlüyü sübut olunmur.

Task-board dərsindən başlayın, sonra partnyor API, entity/resurs və tətbiq testi dərslərinə keçin. Üç dildə icra edilən kod eynidir. Kataloq nümunəsi müvəqqəti bazada və fake API ilə test dəstində yoxlanır.

[Learn](/az/learn) · [ORM](/az/docs/v0.1/orm) · [Application testing](/az/docs/v0.1/application-testing)
