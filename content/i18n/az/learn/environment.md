# Elang üçün PHP və Composer hazırlayın

Bu dərsin sonunda terminalınız PHP və Composer-i işə sala biləcək və Elang doctor layihənizin hazır olub-olmadığını izah edəcək. Ön tərəf freymvorku quraşdırmağa ehtiyac yoxdur.

## Başlamazdan əvvəl

İnkişaf alətləri quraşdıra bildiyiniz kompüter, mətn redaktoru və terminal istifadə edin. Terminal əmrləri icra edir; bu, PHP-ni brauzerin ünvan sətrinə yazmaqdan fərqlidir. Hər əmri dərsdə göstərilən qovluqdan yerinə yetirin.

Elang PHP 8.x daxilində PHP 8.3 və ya daha yeni versiya tələb edir. Artıq PHP varsa, başqa nüsxə quraşdırmazdan əvvəl versiyanı yoxlayın. Bir neçə PHP quraşdırması olan kompüterdə Composer və veb-server fərqli nüsxələrdən istifadə edə bilər.

## Windows-da hazırlıq

1. [Rəsmi PHP Windows quraşdırma təlimatını](https://www.php.net/manual/en/install.windows.php) izləyin və sisteminiz üçün dəstəklənən PHP 8.x yığımını quraşdırın.
2. `php.exe` olan qovluğu istifadəçi PATH dəyişəninizə əlavə edin. Quraşdırmanız bunu artıq konfiqurasiya etməyibsə, verilmiş inkişaf şablonundan tövsiyə edilən php.ini faylını yaradın.
3. Composer-in bildirdiyi genişlənmələri aktivləşdirin. Lokal nümunələrə freymvork və test tələblərindən əlavə mbstring, Sodium, PDO və pdo_sqlite lazımdır. `php --ini` terminalınızın həqiqətən oxuduğu konfiqurasiyanı müəyyən edir.
4. Nəzərdə tutulan PHP icra faylını seçərək [rəsmi Windows quraşdırıcısı](https://getcomposer.org/doc/00-intro.md#installation-windows) ilə Composer quraşdırın.
5. PowerShell-i bağlayıb yenidən açın, sonra aşağıdakı yoxlama əmrlərini yerinə yetirin.

`php` tanınmırsa, `where.exe php` yerinə yetirin və PATH dəyişənini yoxlayın. Əmr PHP 8.2 tapırsa, Elang kodunun dəyişdirilməsi versiya uyğunsuzluğunu düzəltməyəcək.

## macOS-da hazırlıq

[PHP macOS quraşdırma təlimatında](https://www.php.net/manual/en/install.macosx.php) təsvir edilən quraşdırma üsulundan istifadə edin. Aktiv dəstəklənən paket meneceri adətən dəstəklənən CLI quraşdırmasına ən qısa yoldur. Composer-i [rəsmi Unix/macOS təlimatı](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) ilə quraşdırın.

Composer quraşdırıcısının təlimatını izləyərkən onun rəsmi endirmə səhifəsində göstərilən yoxlama cəmindən istifadə edin. Köhnə dərslikdən köçürülmüş yoxlama cəminə etibar etməyin. PATH dəyişdikdən sonra yeni terminal açın və hər iki icra faylını yoxlayın.

## Ubuntu 24.04-də hazırlıq

Ubuntu 24.04-də standart PHP paketləri uyğun başlanğıc nöqtəsi verir:

```bash
sudo apt update
sudo apt install php-cli php-mbstring php-xml php-sqlite3 php-curl php-zip unzip
```

Sonra rəsmi Composer quraşdırma təlimatını izləyin. Başqa distributiv və ya buraxılışda paket adları və standart PHP versiyaları fərqlənə bilər. `php -v` ilə yoxlayın; paket meneceriniz köhnə PHP versiyasını uğurla quraşdırsa belə, Elang-a 8.3+ lazımdır.

Standart SQLite bağlantısı üçün ayrıca verilənlər bazası serveri lazım deyil. Sorğunun əsas həyat dövrünü anladıqdan sonra MySQL və ya PostgreSQL əlavə edə bilərsiniz.

## Alətləri yoxlayın

```bash
php -v
php --ini
php -m
composer --version
```

Gözlənilən nəticə: PHP 8.x daxilində PHP 8.3+, oxuna bilən CLI konfiqurasiya yolu, tələb olunan genişlənmələr və Composer 2. Çatışmayan genişlənmə xətası aktivləşdirməli və ya quraşdırmalı olduğunuz elementi göstərir.

## Composer ilə tətbiq yaradın

Layihənizi istədiyiniz ana qovluqda terminal açın. Yerinə yetirin:

```bash
composer create-project elang/elang my-app dev-main
cd my-app
php elang doctor
php elang serve
```

Composer layihəni yaradır, asılılıqları quraşdırır, onun açarını yaradır və mühiti yoxlayır. Server bu ünvanı göstərdikdə `http://127.0.0.1:8000` açın. Lokal saytdan istifadə edərkən terminalı işlək saxlayın; dayandırmaq üçün Ctrl+C basın.

İnkişaf paketləri Packagist-də mövcuddur. Yoxlanılmış bu əmr standart Composer repozitoriyasını və `dev-main` budağını istifadə edir. Git işçi nüsxəsi və ya əl ilə çıxarılmış arxiv lazım deyil.

## Birdəfəlik hazırlıqdan sonra daha qısa əmr

[Quraşdırma təlimatı](/docs/v0.1/installation) qlobal CLI hazırlığını izah edir. Bu hazırlıqdan sonra `elang new my-app` başqa layihə yaradır. Tətbiq qovluğunda `php elang doctor` və `php elang serve` həmin tətbiqin konsolunu işə salır.

## Anladığınızı yoxlayın

İndi terminalınızın hansı PHP icra faylından istifadə etdiyini, layihənizin composer.json faylının harada olduğunu, lokal serveri necə başladıb dayandırmağı və doctor hesabatını necə oxumağı bilməlisiniz. [Sizə lazım olan PHP](/learn/php-basics) ilə davam edin. Əmr uğursuz olubsa, irəli keçməzdən əvvəl [problemlərin aradan qaldırılması təlimatından](/docs/v0.1/troubleshooting) istifadə edin.
