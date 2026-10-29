# Elang-ı terminaldan quraşdırın

Elang tətbiqləri Composer ilə quraşdırılır. Cari ilkin inkişaf versiyası Elang-ın Composer repozitoriyası vasitəsilə paylanır, buna görə repozitoriya seçimi işlək əmrin bir hissəsidir. Mənbəni əl ilə endirmək və ya arxivdən çıxarmaq lazım deyil.

## Başlamazdan əvvəl

PHP 8.x daxilində PHP 8.3+ və Composer 2 quraşdırın. Onları yoxlamaq üçün `php -v` və `composer --version` yerinə yetirin. Əmrlərdən biri əlçatan deyilsə, [mühitin hazırlanması](/learn/environment) təlimatını izləyin. Genişlənmələr və verilənlər bazası drayverləri üçün [tələbləri](/docs/v0.1/requirements) yoxlayın.

## İlk tətbiqinizi yaradın

Yeni layihənin yerləşməsini istədiyiniz ana qovluqda bunu yerinə yetirin:

```bash
composer create-project --repository=https://elangphp.com/packages elang/elang my-app 0.1.0-dev
cd my-app
php elang serve
```

Composer `my-app` qovluğunu yaradır, Elang-ı və onun asılılıqlarını quraşdırır, tətbiq açarı yaradır və doctor-u işə salır. Server başlayanda `http://127.0.0.1:8000` ünvanını açın.

Gözlənilən nəticə: “Your next idea starts here.” (“Növbəti ideyanız burada başlayır.”) yazılmış qarşılama səhifəsi. Yaradılmış tətbiqə həmçinin `{"message":"Hello, Ada!"}` qaytaran `/api/hello/Ada` daxildir.

Yeni və ya boş qovluq istifadə edin. `my-app` artıq fayllar ehtiva edirsə, işinizin üzərinə yazmaq əvəzinə başqa ad seçin. Lokal serveri dayandırmaq üçün Ctrl+C basın.

## Elang CLI-ni bir dəfə quraşdırın

`elang new` əmrinə üstünlük verirsinizsə, Composer repozitoriyasını qeydiyyata alın və CLI-ni qlobal quraşdırın:

```bash
composer global config repositories.elang composer https://elangphp.com/packages
composer global require elang/installer:0.1.0-dev
```

İcra fayllarının qovluğunu bu əmrlə tapın:

```bash
composer global config bin-dir --absolute
```

Göstərilən qovluğu istifadəçi PATH dəyişəninizə əlavə edin və terminalı yenidən açın. Windows-da istifadəçi mühit dəyişənləri redaktorundan istifadə edərək həmin qovluğu Path dəyişəninə əlavə edin. macOS və ya Linux-da qovluğu əmr örtüyünüzün profilində PATH dəyişəninə əlavə edin. Sonra `elang --version` ilə yoxlayın.

İndi layihələri belə yaradın:

```bash
elang new my-app
cd my-app
php elang serve
```

Quraşdırıcı tətbiqi yaratmaq üçün Composer-i çağırır. Freymvork mənbəsinin işçi nüsxəsinin kompüterinizdə qalmasını tələb etmir.

## Tam öyrənmə layihəsi ilə başlayın

Tapşırıq lövhəsini birbaşa Composer vasitəsilə yaradın:

```bash
composer create-project --repository=https://elangphp.com/packages elang/task-board task-board 0.1.0-dev
cd task-board
php elang serve
```

Və ya qlobal CLI-ni quraşdırdıqdan sonra:

```bash
elang new task-board --example=task-board
```

Mənbə faylları, sorğunun həyat dövrü, gözlənilən nəticələr və çalışmalar üçün [tam tapşırıq lövhəsi təlimatını](/learn/build-a-task-board) izləyin. Bu nümunə müvəqqəti tapşırıqları cari brauzer sessiyasında saxlayır.

## İki konsol əmrini anlayın

`elang new` qlobal quraşdırılmış layihə yaratma alətidir. `php elang ...` isə hazırda olduğunuz tətbiq qovluğunun konsolunu işə salır. Doctor, marşrutlar, miqrasiyalar, generatorlar və lokal server üçün tətbiq konsolundan istifadə edin.

```bash
php elang doctor
php elang routes
php elang make:controller HelloController
```

## İlkin versiyanın paylanması və Packagist

Hazırda doqquz paket var: `elang/framework`, `elang/elang`, `elang/task-board`, `elang/installer`, `elang/auth-starter`, `elang/blog`, `elang/dashboard`, `elang/api` və `elang/realtime`. Bunlar `https://elangphp.com/packages` ünvanında yerləşdirilmiş Composer paketləridir.

Hələ ictimai Packagist buraxılışı dərc edilməyib, buna görə adi Composer quraşdırmasında repozitoriya seçimini buraxmaq hazırda dəstəklənmir. Bu dərc baş verməmiş yalnız Packagist-ə əsaslanan qısa əmrin işləyəcəyini fərz etməyin. Cari versiya dəyişməkdə olan ilkin inkişaf versiyası olaraq qalır.

## Quraşdırma dayanarsa

İlk Composer xətasını oxuyun. Çatışmayan genişlənmələr, dəstəklənməyən PHP versiyası, dolu hədəf qovluğu və ya HTTPS repozitoriyasına qoşula bilməmək fərqli həllər tələb edir. `--ignore-platform-reqs` ilə platforma tələblərini gizlətməyin.

Asılılıqların quraşdırılması uğurlu olub, lakin yaratmadan sonrakı yoxlama uğursuz olubsa, yeni qovluğa daxil olun, göstərilən tələbi düzəldin və `php elang doctor` əmrini yenidən yerinə yetirin. Yaradılmış `.env` açarını qoruyun. [Problemlərin aradan qaldırılması](/docs/v0.1/troubleshooting) bölməsinə baxın.

## Freymvorka töhfə verənlər üçün

Mənbə repozitoriyasını klonlayın, `composer install` yerinə yetirin və təmizlənmiş lokal freymvork nüsxəsini sınamaq üçün `php elang new ../my-app --local` istifadə edin. Bu töhfəvermə prosesi tətbiqin Composer vasitəsilə quraşdırılmasından ayrıdır. Repozitoriya yoxlamaları üçün CONTRIBUTING.md, paylama yığımları üçün isə `bin/build-packages` faylına baxın.

## Tam başlanğıc layihəsi seçin

```bash
elang new accounts --starter=auth
elang new journal --starter=blog
elang new workspace --starter=dashboard
elang new notes-api --starter=api
```

Hər başlanğıc layihəsinə öz miqrasiyaları və tam mənbə kodu daxildir. Composer layihənin qurulması zamanı başlanğıc layihəsinin sxemini tətbiq edir. İctimai tətbiqi yayımlamazdan əvvəl autentifikasiya, modellər, Studio, bloq, idarəetmə paneli və qorunan API təlimatlarını oxuyun.
