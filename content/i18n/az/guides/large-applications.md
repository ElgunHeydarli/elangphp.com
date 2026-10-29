# Elang ilə böyük tətbiq layihələndirin

Bankın, ticarət platformasının, startap platformasının və ictimai API-nin düzgünlük və istismar tələbləri fərqlidir. Elang HTTP, məlumatlara giriş, tranzaksiyalar, hesab autentifikasiyası, API tokenləri, siyasətlər, fon tapşırıqları, planlaşdırma və real vaxt nəql üsulları təqdim edir. Tətbiqin arxitekturası və istismar sübutları konkret sistemin öz iş yükünə hazır olub-olmadığını müəyyən edir.

## Biznesi modullara bölün

Accounts, Orders, Payments və Reporting kimi modulları `app/` daxilində təşkil edin. Kontrollerləri yığcam saxlayın. Biznes əməliyyatlarını yeridilən xidmətlərə yerləşdirin və inteqrasiyaları xidmət təminatçıları vasitəsilə qeydiyyata alın. Avtorizasiyanı əməliyyata yaxın saxlayın ki, HTTP, tapşırıqlar və soket əmrləri eyni qaydanı tətbiq etsin.

Tətbiq siyasətləri üçün `Gate`, istifadəçi məlumatları üçün sahibə görə məhdudlaşdırılmış repozitoriyalar və dəyişməz şərtlər üçün açıq SQL məhdudiyyətləri istifadə edin. Təkcə göstərilən rol tam icazə modeli deyil. Müştəri təşkilatının ID-ləri etibarlı autentifikasiyadan alınmalı və hər uyğun sorğuya, unikal açara, tapşırığa və hadisə kanalına tətbiq edilməlidir.

## Dəyişiklikləri atomik edin

Birlikdə təsdiqlənməli əməliyyatları `Database::connection()->transactional()` daxilində yerinə yetirin. Elang-ın növbəsi və hadisə yaddaşı eyni bağlantıdan istifadə etdikdə iştirak edə bilər. Tranzaksiyaları qısa saxlayın. Paralel yazmaların toqquşa biləcəyi yerlərdə verilənlər bazası məhdudiyyətləri və kilid və ya versiya yoxlamaları əlavə edin. Rəqabətli müraciətləri yerləşdirmədə istifadə edilən verilənlər bazasında sınayın.

Təkrar cəhd edilə bilən JSON yazmaları üçün API başlanğıc layihəsinin `Idempotency-Key` dəstəyindən istifadə edin. Verilənlər bazası tranzaksiyası uzaq ödənişi, e-poçtu və ya mesaj brokerini atomik şəkildə təsdiqləmir. Bu sərhədlərdə daimi çıxış qutusu, təminatçının idempotentlik açarları, tutuşdurma və kompensasiya əməliyyatlarından istifadə edin.

## Pul və audit məlumatlarına düşünülmüş yanaşın

Maliyyə tətbiqlərində məbləğləri açıq valyuta və yuvarlaqlaşdırma qaydaları ilə valyutanın ən kiçik vahidlərini göstərən yoxlanmış tam ədədlər və ya verilənlər bazasının onluq dəyərləri kimi saxlayın. Rəsmi qalıqlar üçün heç vaxt üzən nöqtəli ədədlər istifadə etməyin. Tətbiqə xas ikili mühasibat uçotu kitabından, dəyişməz jurnal qeydlərindən, tutuşdurmadan, minimum səlahiyyətli verilənlər bazası girişindən və müstəqil nəzərdən keçirilmiş biznes nəzarətlərindən istifadə edin.

Hadisələrin təkrar oxuma cədvəli, icra jurnalları və növbə qeydləri istismar alətləridir. Onlar maliyyə uçotu kitabı, müdaxiləni aşkar edən audit sistemi, uyğunluq sertifikatı və ya müstəqil təhlükəsizlik yoxlamasının əvəzi deyil. Bu ilkin inkişaf versiyasının bank sertifikatı və ya müstəqil təhlükəsizlik auditi yoxdur.

## İnteraktiv işləri qısa saxlayın

Məhdudlaşdırılmış, səhifələnmiş API cavabları qaytarın. Hesabatları, idxalları, bildirişləri və kütləvi tapşırıqları növbəyə əlavə edin. Hər tapşırığın təkrar icrasını təhlükəsiz edin. Birtərəfli brauzer yeniləmələri üçün SSE, müştəri davamlı bağlantı üzərindən əmr göndərməli olduqda isə WebSockets istifadə edin. Hər kanalı və əmri avtorizasiya edin və təkrar oxumada boşluq yarandıqdan sonra resurs vəziyyətinin surətlərini yeniləyin.

Cari real vaxt nəqli SQL hadisə tarixçəsini oxuyur. Onun dövri sorğu tələbini sınaqdan keçirin; bağlantı limitindən limitsiz bağlantı tutumu nəticəsi çıxarmayın. Çox böyük miqyasda yayım tələb edən yerləşdirmə üçün işə başlamazdan əvvəl xüsusi broker və nəql adapterini qiymətləndirin.

## Bir neçə nüsxəni idarə edin

Ortaq istehsal verilənlər bazası, mərkəzləşdirilmiş sirlər, sinxronlaşdırılmış saatlar və HTTPS istifadə edin. Keş, sessiyalar, ciddi sorğu tezliyi limitləri və paylanmış kilidlər üçün REDIS_URL vasitəsilə ortaq Redis konfiqurasiya edin. Redis olmadan bu xidmətlər bir hostun fayl sistemi yaddaşından istifadə edir. Təsdiqlənmiş Redis bildirişləri üçün SQL çıxış qutusu ötürücüsünü işlədin və sessiya, limitləyici, kilid və ötürücü nasazlıqlarını izləyin.

Növbə və WebSocket işçilərini proses nəzarətçisi altında, imtiyazsız istifadəçi ilə işlədin. Kodun və qeydiyyatların aktual qalması üçün yerləşdirmə zamanı işçiləri yenidən başladın. Miqrasiyaları həm köhnə, həm də yeni tətbiq versiyaları ilə uyğun şəkildə yerləşdirin. Şəxsi məlumatların ehtiyat nüsxəsini çıxarın və bərpanı yoxlayın; saxlanmış məlumatların şifrəsini açmaq üçün tətbiq açarlarını əlçatan saxlayın.

## İş yükünü müəyyən edin və ölçün

Gözlənilən sorğu tezliyini, eyni vaxtlı soket bağlantılarını, verilənlər bazası ölçüsünü, tapşırıq gecikməsini və məqbul uğursuzluq tezliyini yazın. p50/p95/p99 gecikməsini, ötürmə qabiliyyətini, yaddaşı, verilənlər bazası kilidlərini, işçilərin doyma səviyyəsini və asılılıq uğursuzluğundan sonra bərpanı ölçün. Testə real avtorizasiya, məlumatlar və yazma əməliyyatları daxil edin.

Salamlama marşrutunun mikrotesti bir freymvorkun hər layihə üçün daha yaxşı olduğunu müəyyən edə bilməz. Repozitoriyanın məhdud performans testi istehsal tutumu vədi deyil, təkrar yaradıla bilən inkişaf ölçməsidir. Elang-ı kritik sistem üçün seçməzdən əvvəl öz iş yükünüzü yoxlayın və müstəqil kod və təhlükəsizlik yoxlaması aparın.

[API və ön tərəf inteqrasiyası](/docs/v0.1/frontend), [növbələr](/docs/v0.1/queues), [real vaxt nəql üsulları](/docs/v0.1/realtime) və [yerləşdirmə](/docs/v0.1/deployment) ilə başlayın.
