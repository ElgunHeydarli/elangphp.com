# Konsol əmrləri

Mövcud əmrləri görmək üçün `php elang` yerinə yetirin. Əmrlər Symfony Console ilə reallaşdırılıb.

## Gündəlik əmrlər

```bash
php elang doctor
php elang about
php elang routes
php elang serve --port=8000
php elang make:controller WelcomeController
php elang make:migration create_posts
php elang migrate:status
php elang migrate
php elang cache:clear
```

İnkişaf serveri standart olaraq 127.0.0.1 ünvanına bağlanır. Yerləşdirmə üçün istehsal veb-serveri və PHP-FPM istifadə edin.

## Tətbiqin hazırlanması

```bash
php elang key:generate
php elang new ../my-app --local
```

Generatorlar boş olmayan hədəf qovluqlarını və ya mövcud kontroller fayllarını rədd edir. Kontroller adları PascalCase, miqrasiya adları isə snake_case istifadə edir.

## İstehsal mühitində miqrasiyalar

`APP_ENV=production` olduqda verilənlər bazasını dəyişən əmrlər `--force` tələb edir. Açarın dəyişdirilməsi də `--force` tələb edir; bu, köhnə şifrəli mətnləri etibarsız edir. İstehsal mühitinin sirlərini və ehtiyat nüsxələrini versiya nəzarətindən kənarda saxlayın.

## Freymvork yoxlamaları

```bash
composer test
composer analyse
composer audit
```

Bu əmrlər freymvork repozitoriyasında işləyir. İş axını repozitoriyaya göndərildikdə GitHub Actions PHP uyğunluğu yoxlamalarını, asılılıq yoxlamasını, testləri və statik təhlili icra edir.

## Qlobal quraşdırıcı ilə layihələr yaradın

[CLI-nin birdəfəlik hazırlanmasından](/docs/v0.1/installation) sonra `elang new my-app` və ya `elang new task-board --example=task-board` yerinə yetirin. Qlobal əmr asılılıqların quraşdırılmasını və layihənin hazırlanmasını Composer-ə həvalə edir. Layihə daxilində onun öz tətbiq konsolunu işə salmaq üçün `php elang` istifadə edin.
