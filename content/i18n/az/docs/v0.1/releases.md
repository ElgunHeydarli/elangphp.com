# Paketlər, yeniləmələr və buraxılışların hazırlanması

Elang hazırda `0.1.0-dev` istifadə edir. Öz serverində yerləşdirilən rəsmi Composer repozitoriyası terminaldan quraşdırmanı dəstəkləyir. Repozitoriya qeydi Packagist nəşri, sabit uyğunluq zəmanəti və ya uzunmüddətli dəstək elanı deyil. Versiyanı qəbul etməzdən əvvəl faktiki paket indeksini və buraxılış qeydlərini yoxlayın.

## Terminaldan quraşdırılan paketlər

```bash
composer create-project --repository=https://elangphp.com/packages elang/elang app 0.1.0-dev
composer create-project --repository=https://elangphp.com/packages elang/saas workspace 0.1.0-dev
```

Paylanmaya freymvork, tətbiq skeleti, quraşdırıcı, tapşırıq lövhəsi nümunəsi, autentifikasiya/bloq/idarəetmə paneli/API başlanğıc layihələri, React/Next tam stek başlanğıc layihələri, SaaS iş sahəsi və istəyə bağlı Workerman icra mühiti paketi daxildir. Composer daxildə arxivlər endirə bilər; istifadəçilər adi terminal əmrləri və asılılıqların həlli vasitəsilə quraşdırır.

Qlobal quraşdırıcı istəyə bağlıdır. Əvvəlcə öz serverində yerləşdirilən Composer mənbəyini qeydiyyata alın, sonra onu quraşdırın:

```bash
composer global config repositories.elang composer https://elangphp.com/packages
composer global require elang/installer:0.1.0-dev
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

Paket adları əlçatan olmalı və layihəni müşayiət edən şəxsin nəzarətində olmalıdır. Xarici ad məkanına sahibliyi və hesab səlahiyyətini GitHub repozitoriyasından çıxarmaq olmaz. Packagist həmin adı həqiqətən həll edənədək xüsusi repozitoriyasız `composer require elang/framework` reklam etməyin. Layihəni müşayiət edən şəxs xarici hesab/ad məkanı mərhələsini tamamlamalıdır; giriş məlumatları mənbədə və ya söhbətdə deyil, qorunan CI sirlərində saxlanmalıdır.

## Administrator paneli vasitəsilə dərc edin

Rəsmi sayt qorunan `/admin/integrations` səhifəsi təqdim edir. Təsdiqlənmiş administrator olaraq daxil olun, varsa müvəqqəti parolu dəyişin və göndərməzdən əvvəl hər hazırlanmış repozitoriyanı və mənbə kommitini nəzərdən keçirin. [Packagist profilinizi](https://packagist.org/profile/) açın və **əsas API tokenini** həmin HTTPS formasına kopyalayın, heç vaxt söhbətə və ya ictimai problem bildirişinə göndərməyin. Saxlayarkən cari hesab parolunuzu təsdiqləyin. Server bağlantını şəxsi yaddaşda şifrələyir və forma tokeni bir daha göstərmir.

Hər “Paketi göndərin” düyməsi yalnız siyahıdakı rəsmi repozitoriyanı göndərir. Nəşr vasitəsi mövcud mənbəsi başqa repozitoriyaya məxsus olan paketi əvəz etməyi rədd edir. Giriş məlumatları URL əvəzinə Authorization başlığında ötürülür. Uğurlu API cavabı **indekslənmə üçün göndərildiyini** bildirir; ictimai quraşdırma təlimatlarını dəyişməzdən əvvəl inkişaf versiyasının Packagist-də göründüyünü təsdiqləyin. İlkin göndərmədən sonra Packagist-də avtomatik GitHub yeniləmə hook-larını konfiqurasiya edin.

Layihəni müşayiət edənlər təmizlənmiş mənbə ağaclarını bu əmrlə hazırlaya bilər:

```bash
php bin/export-packages --output=/absolute/private/release-trees
```

Ayrı inkişaf repozitoriyaları `dev-main` təqdim edir və onun üçün `0.1.x-dev` budaq ləqəbindən istifadə edir. Bunlar sabit buraxılışlar deyil. Xarici indekslənmə gözlənilərkən öz serverində yerləşdirilən repozitoriya sənədləşdirilmiş `0.1.0-dev` əmrlərini həll etməyə davam edir. Şəxsi tokenlər, icra vaxtı məlumatları və tətbiq açarları ayrı repozitoriyaya daxil edilməməlidir.

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
