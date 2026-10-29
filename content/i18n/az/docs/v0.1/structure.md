# Qovluq quruluşu

Elang tətbiqində kod və məzmun üçün az sayda, aydın təyin edilmiş yer var.

## Tətbiq faylları

```text
app/                 Your controllers and services
bootstrap/app.php    Application creation and route loading
config/app.php       Configuration array
routes/web.php       Route definitions
resources/views/     Twig HTML templates
public/              Web document root; CSS and JavaScript
database/migrations/ Versioned database changes
storage/             Writable cache, logs, sessions, and locks
elang                Console entry point
```

## Freymvorkun mənbə kodu

Freymvork repozitoriyasında `src/` qovluğu `Elang` ad məkanını ehtiva edir. İctimai veb-sayt tətbiqi `app/` qovluğunda, sənədlər, dərslər və nümunələr isə `content/` qovluğunda yerləşir.

## Sorğunun həyat dövrü

İctimai giriş nöqtəsi Composer-i yükləyir, tətbiqi yaradır və `run()` metodunu çağırır. Əvvəlcə qlobal aralıq proqram təminatı icra olunur, ardınca marşrut uyğunlaşdırılması, marşrutun aralıq proqram təminatı və emaledici gəlir. Cavablar sorğuya uyğun hazırlanır; xətalara sorğu identifikatorları və təhlükəsiz standart başlıqlar əlavə edilir.

Kontrollerlər HTTP cavabı, JSON üçün massiv, HTML üçün sətir və ya 204 cavabı üçün `null` qaytarır.
