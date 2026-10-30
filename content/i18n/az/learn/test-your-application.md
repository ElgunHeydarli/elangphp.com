# Yerləşdirmədən əvvəl davranışı test edin

Ön şərt: əvvəlki iki praktiki dərs. Məqsəd: real kernel ilə JSON-u, bazaya yazını, məxfi sahənin gizlənməsini və xarici API adapterini yoxlamaq.

Kataloqu işlədib TestClient yoxlamalarını oxuyun. JSON gözləntisini qəsdən səhv edib uğursuz nəticəni görün, sonra düzəldin. Tətbiqin test edilməsi bölməsinə uyğun ayrıca test yaradın. Yerləşdirmədən əvvəl icazəsiz giriş və CSRF uğursuzluğu ssenarisi əlavə edin.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```

Müsbət və mənfi ssenarilər ayrı bazada keçirsə, real məktub və xarici sorğu getmirsə tapşırıq hazırdır. Framework testləri sizin icazə və biznes qaydalarınızın testini əvəz etmir.

[Növbəti addım](/az/docs/v0.1/application-testing)
