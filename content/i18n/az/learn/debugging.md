# Elang-da problemi tapın və düzəldin

Yaxşı sazlama qaydası kodu dəyişməzdən əvvəl problemin əhatəsini daraldır. Bu dərs həm lokal hazırlıq, həm də HTTP uğursuzluqları üçün təkrarlana bilən ardıcıllıq verir.

## Addım 1: Nəyin uğursuz olduğunu qeydə alın

Əmri və ya URL-i, metodu, statusu və nə gözlədiyinizi yazın. Brauzerin ünvan sətri GET göndərir. JSON POST nümunəsi üçün düzgün gövdə və Content-Type olan HTTP müştərisi və ya fetch sorğusu lazımdır.

Hər dəfə bir şeyi dəyişin. PHP-ni yeniləyir, verilənlər bazası URL-ini dəyişir və marşrutu eyni vaxtda yenidən yazırsınızsa, hansı dəyişikliyin əhəmiyyətli olduğunu göstərən sübutu itirirsiniz.

## Addım 2: Mühiti yoxlayın

```bash
php -v
php elang doctor
php elang routes
```

Doctor giriş məlumatlarını göstərmədən tələbləri, şəxsi saxlama sahəsini, açar konfiqurasiyasını və verilənlər bazası bağlantısını yoxlayır. Marşrut siyahısı tətbiqin istifadə etdiyiniz yolu və metodu həqiqətən qeydiyyata alıb-almadığını göstərir.

## Addım 3: Status kodunu oxuyun

- 404: yolu, parametr tələblərini və veb-serverin yenidənyazma qaydasını yoxlayın.
- 405: HTTP metodunu və Allow başlığını yoxlayın.
- 419: formanı yeniləyin və onun sessiyaya bağlı CSRF tokenini yoxlayın.
- 422: JSON cavabındakı sahə xətalarını yoxlayın.
- 429: Retry-After müddətini gözləyin və sorğu tezliyinizi nəzərdən keçirin.
- 500: cavabın sorğu ID-si ilə şəxsi server jurnalını yoxlayın.

Hər uğursuzluğu verilənlər bazası problemi hesab etməyin. Hər status fərqli qata işarə edir.

## Addım 4: Təhlükəsiz təkrar yaratma nümunəsini yoxlayın

Xarici API çağırışını və ya mürəkkəb kontrolleri məlum massiv qaytaran kiçik marşrutla əvəz edin. Bu işləyirsə, növbəti asılılığı geri əlavə edin. Sorğuları və ya formaları sınayarkən müvəqqəti məlumatlardan istifadə edin.

Sirləri ekran görüntülərindən, ictimai problem qeydlərindən və yapışdırılmış çağırış izlərindən kənarda saxlayın. Uğursuz sorğunu həqiqi giriş məlumatlarını paylaşmadan təsvir etmək olar.

## Addım 5: Düzəlişi yoxlayın

İlkin sorğunu təkrarlayın. Uğurlu halı və emal etmək istədiyiniz uğursuz halı yoxlayın. Yoxlama qaydalarını dəyişmisinizsə, həm etibarsız, həm də etibarlı girişi sınayın. Marşrutu dəyişmisinizsə, nəzərdə tutulan metodu və dəstəklənməyən metodu sınayın.

Təkrarlanan xəta üçün məqsədyönlü avtomatlaşdırılmış test əlavə edin. Sonra dəyişikliyi yayımlamazdan əvvəl uyğun testi və freymvork yoxlamalarını icra edin.

## Praktik çalışma

Yalnız GET marşrutu olduqda müvəqqəti olaraq `/hello` ünvanına POST ilə sorğu göndərin. 405 cavabını təsdiqləyin, marşrut siyahısını oxuyun və düzgün metodu müəyyən edin. Marşrutu dəyişmədən sorğunu düzəldin. Tətbiqin davranışını zəiflətmək əvəzinə çağıran tərəfi düzəltdiniz.

[Yerləşdirməyə hazırlıq](/learn/ship) ilə davam edin və konkret xəta mesajları üçün [problemlərin aradan qaldırılması istinadını](/docs/v0.1/troubleshooting) saxlayın.
