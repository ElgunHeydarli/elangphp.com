# Görünüşlər və şablonlar

Elang serverdə HTML yaratmaq üçün Twig istifadə edir. Ön tərəfiniz sadə HTML, CSS və JavaScript ilə qala bilər.

## Görünüş yaradın

```php
use Elang\View\View;

$router->get('/', fn (View $view) =>
    $view->render('home', ['title' => 'My application'])
);
```

Bu, `resources/views/home.twig` faylını yükləyir. HTTP statusu təyin etmək üçün istəyə bağlı üçüncü arqumenti ötürün.

## Çıxışı ekranlaşdırın

```twig
<h1>{{ title }}</h1>
```

HTML-in avtomatik ekranlaşdırılması və dəyişənlərin ciddi yoxlanması aktivdir. `|raw` filtrini yalnız etibarlı, təmizlənmiş HTML üçün istifadə edin. İstifadəçinin təqdim etdiyi məzmunu xam olaraq işarələməyin.

## Ümumi tərtibatlar

```twig
{% extends "base.twig" %}
{% block content %}
  <h1>{{ title }}</h1>
{% endblock %}
```

Twig şablonları yazıla bilən saxlama keşində kompilyasiya olunur. CSS və JavaScript faylları `public/assets/` qovluğunda yerləşməlidir. Ayrı fayllar daxili skript və stillərə icazə verməyən standart Məzmun Təhlükəsizliyi Siyasətinə də uyğundur.

## Adlandırılmış marşrut keçidləri

```twig
<a href="{{ route('posts.show', {id: 42}) }}">Read post</a>
```

İrsilik, daxiletmələr, filtrlər və şablon sintaksisi üçün [Twig sənədləşməsini](https://twig.symfony.com/doc/3.x/) oxuyun.
