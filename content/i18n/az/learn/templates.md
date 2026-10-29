# İlk səhifənizi yaradın

HTML-in yaradılması arxa tərəfin vəzifəsidir; kiçik qarşılıqlı əlaqələr sadə JavaScript ilə qala bilər.

## Şablon yaradın

`resources/views/welcome.twig` faylını yaradın:

```twig
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ title }}</title></head>
<body>
  <h1>{{ title }}</h1>
  <p>Hello, {{ name }}.</p>
</body>
</html>
```

## Marşrutdan məlumat ötürün

```php
use Elang\View\View;
$router->get('/welcome', fn (View $view) =>
    $view->render('welcome', ['title' => 'My first Elang page', 'name' => 'Ada'])
);
```

## Ekranlaşdırmanı yoxlayın

Adı HTML teqi ehtiva edən sətirlə dəyişin. Twig teqi mətn kimi göstərməlidir. Etibarsız məzmun üçün `|raw` istifadə etməyin.

## Ön tərəf faylları əlavə edin

CSS və JavaScript-i `public/assets/` daxilinə yerləşdirin və adi link və script teqləri ilə onlara istinad edin. Standart CSP eyni mənbədən olan ayrı faylları dəstəkləyir, daxili skript və stillər isə bloklanır.

## Gözlənilən nəticə və yayılmış səhvlər

Şablonu və marşrutu saxladıqdan sonra `/welcome` açın. Səhifə “My first Elang page” (“İlk Elang səhifəm”) və “Hello, Ada.” (“Salam, Ada.”) göstərməlidir. Serverdə yaradılmış HTML-i görmək üçün brauzerdə səhifənin mənbəyinə baxın.

`welcome.twig` adlı şablon uzantı təkrarlanmadan `render('welcome', ...)` ilə yaradılır. Ciddi dəyişən yoxlamaları aktiv olduqda çatışmayan title və ya name səhvi səssizcə gizlətmək əvəzinə şablon xətası yaradır. Səhifə 500 qaytardıqda şəxsi jurnalı oxuyun.

## Ümumi tərtibatı sınayın

`resources/views/base.twig` faylını yaradın:

```twig
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ title }}</title></head>
<body><main>{% block content %}{% endblock %}</main></body>
</html>
```

Sonra welcome.twig faylını bununla əvəz edin:

```twig
{% extends 'base.twig' %}
{% block content %}
<h1>{{ title }}</h1>
<p>Hello, {{ name }}.</p>
{% endblock %}
```

Kontroller kodu eyni qalır. Avtomatik ekranlaşdırılmış məlumatları qoruyaraq təkrarlanan səhifə quruluşunu bir ümumi tərtibata köçürdünüz.
