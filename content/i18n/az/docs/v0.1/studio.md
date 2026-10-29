# Elang Studio

Studio sorğuları, marşrutları, sorğu əməliyyatlarını və hesab e-poçtunu anlamaq üçün lokal inkişaf panelidir. İstehsal mühitində söndürülür və hətta lokal mühitdə də loopback olmayan müştəriləri rədd edir.

## Studio-nu açın

Tətbiqi `php elang serve` ilə başladın, sonra `http://127.0.0.1:8000/_elang/studio` açın. `php elang studio` əmri konfiqurasiya edilmiş ünvanı göstərir.

APP_ENV=local istifadə edin. İstehsal mühiti inkişaf marşrutlarını qeydiyyata almır. HTTP parametrinin dəyişdirilməsi onları aktivləşdirə bilməz.

## Sorğular və verilənlər bazası sorğuları

Panel son 100 lokal sorğunu saxlayır. Sorğu təfərrüatlarına HTTP statusu, müddət, marşrut, məhdudlaşdırılmış sorğu şablonları və sorğu vaxtları daxildir. SQL-ə bağlanan dəyərlər daxil edilmir; dırnaq içindəki literallar gizlədilir.

Sorğu gövdələri, kukilər, avtorizasiya başlıqları, URL sorğu parametrləri və istisna arqumentləri qeydə alınmır. Tokenə bənzər yol seqmentləri gizlədilir. Bu məhdudiyyətlər faydalı inkişaf kontekstini saxlayaraq giriş məlumatlarının təsadüfən qeydə alınmasını azaldır.

## Xətaların yerləri

İstisna qeydi onun tipini, mənbə yerini və qısa çağırış zəncirini göstərir. Göstərilən faylı və sətri yoxlayın, sonra əlavə kontekst üçün tətbiqin şəxsi jurnalından istifadə edin. Təkcə status kodu hansı asılılığın uğursuz olduğunu bildirmir.

Studio hostinqdə yerləşdirilmiş xəta monitorinqi məhsulu deyil. Lokal şəxsi saxlama istifadə edir və bir inkişaf mühiti üçün nəzərdə tutulub.

## İnkişaf poçt qutusu

Lokal hesab e-poçtu null nəql istifadə etdikdə mesajlar APP_KEY ilə şifrələnir və public/ xaricində saxlanılır. Lokal poçt qutusu təsdiqləmə və bərpa mesajlarını göstərir ki, poçt təminatçısı olmadan bütün prosesi sınaya biləsiniz.

İstehsal mühitində çatdırılma konfiqurasiya edilmiş SMTP və ya digər dəstəklənən poçt nəqli tələb edir. Lokal poçt qutusu istehsal saytından əlçatan deyil.
