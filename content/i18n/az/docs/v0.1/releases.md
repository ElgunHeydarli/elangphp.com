# Paketlər, yeniləmələr və buraxılışların hazırlanması

Elang 0.1 ilkin inkişaf versiyasıdır. On səkkiz inkişaf paketinin hamısı Packagist-də `dev-main` və `0.1.x-dev` budaq ləqəbi ilə indekslənib. Doqquz tətbiq paketinin və qlobal installer-in təmiz quraşdırılması yoxlanılıb. Bu, stabil buraxılış və ya uzunmüddətli dəstək vədi deyil.

On səkkiz rəsmi paylama repozitoriyasının hamısında Packagist-ə aktiv və imzalanmış GitHub push bağlantıları var. Real push-dan sonra yenilənmələr Composer inkişaf metadatası ilə yoxlanılıb; webhook URL-lərində giriş məlumatları yoxdur. Əvvəllər öz serverində yerləşdirilən mənbəni qlobal əlavə etmisinizsə, Packagist installer-ini yeniləməzdən əvvəl köhnə ayarı `composer global config --unset repositories.elang` ilə çıxarın.

## Terminaldan quraşdırılan paketlər

```bash
composer create-project elang/elang app dev-main
composer create-project elang/saas workspace dev-main
```

Paylanmaya freymvork, tətbiq skeleti, quraşdırıcı, tapşırıq lövhəsi nümunəsi, autentifikasiya/bloq/idarəetmə paneli/API başlanğıc layihələri, React/Next tam stek başlanğıc layihələri, SaaS iş sahəsi və istəyə bağlı Workerman icra mühiti paketi daxildir. Composer daxildə arxivlər endirə bilər; istifadəçilər adi terminal əmrləri və asılılıqların həlli vasitəsilə quraşdırır.

Könüllü qlobal `elang new` əmri üçün inkişaf versiyasındakı installer-i birbaşa Packagist-dən quraşdırın:

```bash
composer global require elang/installer:dev-main
elang new my-app --starter=saas
```

`elang` tapılmırsa, Composer-in qlobal icra faylları qovluğunu PATH-a əlavə edin. `composer global config bin-dir --absolute` onu göstərir. Əməliyyat sisteminin hazırlanması üçün [quraşdırma](/docs/v0.1/installation) bölməsinə baxın. Layihələr adi Composer kilid faylı istifadə edir; təkrarlana bilən tətbiq quraşdırmaları üçün onu repozitoriyaya daxil edin, `.env` və vendor/icra vaxtı fayllarını Git-dən kənarda saxlayın.

## Paylanma artefaktlarını yığın

Layihəni müşayiət edənlər bunu işlədir:

```bash
php bin/build-packages --output=/absolute/private/build-output --base-url=https://elangphp.com/packages
```

Yığıcı açıq şəkildə seçilmiş freymvork/mənbə resurslarını və təmizlənmiş başlanğıc layihəsi ağaclarını hazırlayır, vendor, sirlər, verilənlər bazaları, icra vaxtı məlumatları, ön tərəf asılılıqları və tərtib edilmiş yığım qovluqlarını çıxarır, sonra məzmuna görə ünvanlanan arxiv URL-ləri ilə Composer metadatasını yaradır. Mövcud kilid fayllarının istinad etdiyi köhnə dəyişməz arxivləri qoruyun. Yeni metadatanı yalnız hər paketi hazırlanmış indeksə qarşı təzə `composer create-project` və ya `composer require` vasitəsilə yoxladıqdan sonra dərc edin.

Rəsmi sayt şəxsi yaddaşdan paket metadatasını və icazə verilmiş arxiv adlarını təqdim edir. O, mənbənin işçi nüsxəsini və ya `.env` faylını heç vaxt açmır. Paket nəşrini davam edən kod işçi nüsxəsindən ayrı saxlayın ki, istifadəçilər sınaqdan keçirilməmiş aralıq yığımı quraşdıra bilməsin.

## Packagist-də dərc etmə

Packagist hər mənbə repozitoriyası üçün bir kök Composer paketi siyahılayır. Buna görə çoxpaketli mənbə ağacı nəzərdən keçirilmiş paylanma repozitoriyaları və ya sənədləşdirilmiş bölmə prosesi tələb edir. Təmizlənmiş paket ağaclarını hazırlayın, Composer manifestlərini yoxlayın, nəzərdən keçirilmiş Git teqlərini dərc edin, sonra nəzərdə tutulan hər repozitoriyanı səlahiyyətli Packagist hesabı vasitəsilə təqdim edin. Bundan sonra GitHub webhook/yeniləmə inteqrasiyasını konfiqurasiya edin.

Rəsmi `elang/*` ad məkanı və on səkkiz paylama repozitoriyası layihənin sahibinin nəzarətindədir. Onların `dev-main` versiyaları Composer metadatası və real quraşdırmalarla yoxlanılıb. Yeni adlar və gələcək buraxılışlar yenə də layihə sahibinin açıq icazəsini tələb edir. Hesab məlumatlarını mənbəyə, URL-lərə və jurnallara yazmayın.

## Administrator paneli vasitəsilə dərc edin

Rəsmi sayt qorunan `/admin/integrations` səhifəsi təqdim edir. Təsdiqlənmiş administrator olaraq daxil olun, varsa müvəqqəti parolu dəyişin və göndərməzdən əvvəl hər hazırlanmış repozitoriyanı və mənbə kommitini nəzərdən keçirin. [Packagist profilinizi](https://packagist.org/profile/) açın və **əsas API tokenini** həmin HTTPS formasına kopyalayın, heç vaxt söhbətə və ya ictimai problem bildirişinə göndərməyin. Saxlayarkən cari hesab parolunuzu təsdiqləyin. Server bağlantını şəxsi yaddaşda şifrələyir və forma tokeni bir daha göstərmir.

Hər “Paketi göndərin” düyməsi yalnız siyahıdakı rəsmi repozitoriyanı göndərir. Nəşr vasitəsi mövcud mənbəsi başqa repozitoriyaya məxsus olan paketi əvəz etməyi rədd edir. Giriş məlumatları URL əvəzinə Authorization başlığında ötürülür. Uğurlu API cavabı **indekslənmə üçün göndərildiyini** bildirir; ictimai quraşdırma təlimatlarını dəyişməzdən əvvəl inkişaf versiyasının Packagist-də göründüyünü təsdiqləyin. İlkin göndərmədən sonra Packagist-də avtomatik GitHub yeniləmə hook-larını konfiqurasiya edin.

Layihəni müşayiət edənlər təmizlənmiş mənbə ağaclarını bu əmrlə hazırlaya bilər:

```bash
php bin/export-packages --output=/absolute/private/release-trees
```

Packagist `dev-main` və onun `0.1.x-dev` ləqəbini həll edir. `https://elangphp.com/packages` öz serverində yerləşdirilən repozitoriya `0.1.0-dev` nüsxələri üçün açıq şəkildə seçilən alternativ olaraq qalır; bu etiketi Packagist budaq versiyaları ilə qarışdırmayın. Packagist üçün ixrac olunan manifestlərdə şəxsi qurma repozitoriyası yoxdur. Tokenləri, icra vaxtı məlumatlarını və tətbiq açarlarını ayrıca repozitoriyaya daxil etməyin.

## Uyğunluq siyasəti

`0.x` inkişafı zamanı hər dəyişikliyi potensial uyğunluq pozuntusu kimi nəzərdən keçirin. LTS/təhlükəsizlik dəstəyinin müddəti elan edilməyib. Sabit buraxılış sənədləşdirilmiş ictimai API, yeniləmə təlimatı, dəstəklənən PHP/verilənlər bazası/icra mühiti matrisi, davamlı CI, asılılıq/təhlükəsizlik yoxlaması, təkrarlana bilən artefaktlar, geri qaytarma məşqləri və aydın zəiflik bildirmə siyasəti tələb edir.

CI PHP 8.3/8.4, birdəfəlik SQL verilənlər bazaları, Redis davranışı, statik analiz, asılılıq təhlükəsizlik bildirişləri və ön tərəf müqavilələri/yığımlarını yoxlayır. Minimum və cari asılılıq həlli təcrid olunmuş işlərdə yoxlanmalıdır; yalnız kilid faylına əsaslanan icra icazə verilən bütün Composer məhdudiyyətlərini əhatə etmir. Ön tərəf kilid faylları və icra mühiti tələbləri ayrıca yoxlanılır.

## Tətbiqi təhlükəsiz yeniləyin

1. Versiyalı sənədləri, buraxılış qeydlərini, dəyişmiş sxemləri və ictimai API dəyişikliklərini oxuyun.
2. SQL-in, tələb olunduqda obyekt yaddaşının və şifrələmə açarlarının ehtiyat nüsxəsini çıxarın; təcrid olunmuş mühitdə bərpanı sübut edin.
3. Ayrı budaqda yeniləyin, Composer/npm kilid faylı dəyişikliklərini nəzərdən keçirin və layihənin öz testlərini işlədin.
4. Bərpa olunmuş nüsxə üzərində miqrasiyaları məşq edin; `platform:setup` əlavə qurmadır, nəzərdən keçirilmiş yerləşdirmə miqrasiyası planını əvəz etmir.
5. Dəyişməz buraxılış yerləşdirin, növbə/soket işçilərini yenidən başladın və xəta tezliyini, növbə gecikməsini və çıxış qutusu yığılmasını izləyin.
6. Geri qaytarma buraxılışını və verilənlər bazasını geri qaytarma və ya irəli düzəltmə qərarını saxlayın. Mənbəni geri qaytarmaq sxemi və ya xarici yan təsiri ləğv etmir.

Adi yeniləmə zamanı `APP_KEY` dəyərini heç vaxt dəyişməyin: MFA sirləri, şəxsi inkişaf poçtu, növbəyə alınmış şifrəli mesajlar, imzalanmış fayl URL-ləri və audit doğrulaması ondan asılıdır. Açar dəyişikliyini açıq bərpa strategiyası olan ayrıca miqrasiya kimi planlaşdırın.

## İddiaları sübutlarla yoxlayın

Performansı qiymətləndirərkən autentifikasiya olunmuş, verilənlər bazasına əsaslanan API iş yükləri, realistik sorğu ölçüləri, rəqabət aparan təşkilatlar, növbəyə alınmış yan təsirlər və real vaxt yayımından istifadə edin. Aparat təminatını, PHP/opcache parametrlərini, verilənlər bazası versiyasını, paralelliyi, isinmə mərhələsini, müddəti, xətaları və gecikmə paylanmasını qeyd edin. Boş “hello world” müqayisəsi bank işi üçün hazırlığı və ya hər freymvork üzərində üstünlüyü müəyyən edə bilməz.

Buraxılışda yerinə yetirilmiş dəqiq yoxlamalar üçün [testlər](/docs/v0.1/testing), [yerləşdirmə](/docs/v0.1/deployment) bölmələrinə və repozitoriyanın yoxlama hesabatına baxın.
