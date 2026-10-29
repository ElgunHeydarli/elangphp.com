# Sənədləşmə veb-saytını idarə edin

Rəsmi veb-sayt Elang üzərində işləyir. Onun Content Studio aləti etibarlı administratora tətbiqin mənbə fayllarını dəyişmədən sənədləri, dərsləri, nümunələri, təlimatları və yeniləmə yazılarını redaktə etməyə imkan verir.

## Redaksiya iş sahəsinə daxil olun

`/login` açın, əvvəlcədən yaradılmış administrator hesabı ilə daxil olun və tələb olunan müvəqqəti parol dəyişikliyini tamamlayın. Sonra `/admin` açın. Adi istifadəçilər iş sahəsinə daxil ola bilmir və rəsmi saytda ictimai qeydiyyat söndürülüb.

Administrator hesabının yaradılması ictimai forma deyil, etibarlı terminal vasitəsilə həyata keçirilir. Hesab təsdiqlənmiş olmalı və administrator roluna malik olmalıdır.

## Qaralama hazırlayın, önbaxış edin və yayımlayın

Mövcud səhifə seçin və ya dəstəklənən bölmədə səhifə yaradın. Səhifənin seçilmiş dilində başlıq, aydın axtarış təsviri və bir əsas başlıqla başlayan Markdown yazın. İlkin şərtlər, tam əmrlər, gözlənilən nəticələr, uğursuzluq izahları və növbəti addım əlavə edin.

Qaralamanı saxlamaq mətni şəxsi saxlayır. Önbaxış yayımlamadan təmizlənmiş Markdown-u göstərir. Yayımlamaq eyni yayımlanmış məzmundan ictimai səhifəni, naviqasiyanı, axtarış nəticələrini, Markdown təsvirini və sayt xəritəsini yeniləyir.

Markdown çevrilməsi zamanı xam HTML silinir. İstifadəçinin yaratdığı skriptlər icra oluna bilən önbaxış məzmununa çevrilmir.

## Reviziyalar və paralel redaktələr

Hər saxlama reviziyanı və əməli edən şəxsi qeydə alır. Redaktor dəyişikliyi qəbul etməzdən əvvəl reviziya nömrəsini yoxlayır. Başqa sessiya səhifəni yeniləyibsə, konflikt cavabı həmin işin üzərinə səssizcə yazmaq əvəzinə yenidən yükləməyinizi istəyir.

Əvvəlki reviziyanın bərpası yeni qaralama yaradır. Onu nəzərdən keçirin və açıq şəkildə yayımlayın. Qaralama dəyişiklikləri ictimai məqalənin dəyişmə tarixini dəyişmir.

## Saxlama sərhədləri

Redaksiya məzmunu və reviziyalar şəxsi verilənlər bazasında saxlanılır. PHP tətbiqinin mənbə kodu və konfiqurasiyası veb kök qovluğundan kənarda qalır. Redaktor əmr örtüyü əmrlərini icra edə, .env redaktə edə və ya ixtiyari fayl sistemi yollarına yaza bilmir.

Verilənlər bazasının ehtiyat nüsxələrini saxlayın və bərpa prosedurlarını sınaqdan keçirin. Reviziya tarixçəsi redaksiya bərpası üçün faydalıdır, lakin əməliyyat ehtiyat nüsxəsini əvəz etmir.
