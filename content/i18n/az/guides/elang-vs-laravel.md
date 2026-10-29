# Elang və Laravel: imkanlar, öyrənmə və yetkinlik

Elang və Laravel çox fərqli mərhələlərdə olan PHP freymvorklarıdır. Elang açıq tətbiq API-si və modul xidmətlər hədəfləyən 0.1 ilkin inkişaf versiyasıdır. Laravel daha geniş tətbiq funksiyaları və dəstəkləyici alətləri olan oturuşmuş freymvorkdur.

Bu səhifə təsəvvür edilən gələcək buraxılışı deyil, cari davranışı müqayisə edir. O, Elang-ın ümumilikdə daha sürətli, daha təhlükəsiz və ya daha yaxşı olduğunu müəyyən etmir.

## Mövcud funksiyaları müqayisə edin

| Sahə | Elang 0.1 ilkin versiyası | Laravel |
| --- | --- | --- |
| Marşrutlaşdırma | Adlandırılmış marşrutlar, parametrlər, qruplar, aralıq proqram təminatı | Yetkin marşrutlaşdırma sistemi və geniş inteqrasiyalar |
| Asılılıqların yeridilməsi | PSR-11 konteyneri və konstruktor vasitəsilə yeridilmə | Xidmət konteyneri və xidmət təminatçıları |
| Verilənlər bazası | Doctrine DBAL, açıq model repozitoriyaları, səhifələmə və sorğu qurucusu | Sorğu qurucusu və Eloquent ORM |
| Görünüşlər | Avtomatik HTML ekranlaşdırması olan Twig | Blade və çoxsaylı ön tərəf seçimləri |
| Yoxlama | Symfony Constraint obyektləri | Laravel yoxlama qaydaları və forma sorğuları |
| Autentifikasiya | Təsdiqləmə, bərpa, rollar, sessiyalar və səlahiyyətləri məhdudlaşdırılmış tokenlər olan Auth Starter | Autentifikasiya xidmətləri və tətbiq başlanğıc dəstləri |
| Tapşırıqlar və planlaşdırma | Verilənlər bazası növbəsi, rezervasiya müddətləri, təkrar cəhdlər, uğursuz tapşırıqlar, UTC üzrə planlaşdırma | Növbə və planlaşdırma imkanları |
| Süni intellekt alətləri | Versiyalandırılmış Markdown, yalnız oxumağa imkan verən MCP sənədləşmə serveri və layihə göstərişləri | Laravel Boost və əlaqəli süni intellekt alətləri |
| Real vaxt | SQL hadisələrinin təkrar oxunması ilə şəxsi SSE və Workerman WebSockets | Yayım və ekosistem inteqrasiyaları |
| Ön tərəflər | HTML, React, Next.js, mobil müştərilər, CORS, OpenAPI başlanğıc layihəsi | Çoxsaylı rəsmi ön tərəf inteqrasiyaları |
| Buraxılış yetkinliyi | Erkən ilkin versiya; API-lər dəyişə bilər | Oturuşmuş buraxılış prosesi və ekosistem |

Cari imkanları üçün [Laravel-in rəsmi sənədləşməsini](https://laravel.com/framework/docs) yoxlayın. Planlaşdırılmış Elang funksiyasına güvənməzdən əvvəl [Elang-ın yol xəritəsini](/roadmap) yoxlayın.

## Elang harada əlçatan olmağı hədəfləyir

Elang marşrut faylı açıq marşrutlaşdırıcı obyekti qəbul edir. Xidmətlər tipləndirilmiş konstruktor və emaledici parametrləri vasitəsilə görünür. Yoxlama konkret məhdudiyyət obyektlərindən istifadə edir və verilənlər bazası API-si ilkin versiyada yeni ORM model qatı təqdim etmək əvəzinə DBAL-ı açıq edir.

Bu, açıq obyektlərə və kiçik API səthinə üstünlük verənlər üçün sorğunun həyat dövrünü yoxlamağı asanlaşdıra bilər. Bu, hər tətbiqin daha az kod sətri tələb etdiyini və ya hər öyrənənin eyni qaydalara üstünlük verəcəyini bildirmir.

## Öyrənmə hələ nə tələb edir

Hər iki freymvork PHP əsaslarını və HTTP, məlumatlar, tətbiqə giriş qaydaları barədə anlayış tələb edir. Daha kiçik freymvork bu anlayışları aradan qaldırmır. Elang-ın öyrənmə yolu yeni başlayanın artıq ad məkanlarını, Composer-i, sessiyaları və ya CSRF-ni bildiyini fərz etmək əvəzinə bunları ardıcıllıqla izah edir.

[İlk marşrut dərsini](/learn/first-route), sonra isə [tam tapşırıq lövhəsini](/learn/build-a-task-board) sınayın. Öyrənmə təcrübəsini onu izlədikdən sonra kodu izah edə və uğursuzluğu diaqnoz edə bilib-bilmədiyinizə görə qiymətləndirin.

## Performans üçün təkrar yaradıla bilən müqayisə lazımdır

Ədalətli performans testi PHP versiyasını, SAPI və ya serveri, OPcache konfiqurasiyasını, asılılıqları, avadanlığı, sorğu məzmununu, paralelliyi, isinmə mərhələsini və iş yükünü göstərir. Minimal JSON marşrutu sorğunun əlavə yükünün dar hissəsini ölçür; verilənlər bazası ilə intensiv işləyən tətbiqi, növbə işçisini və ya əməliyyat etibarlılığını təmsil etmir.

Elang hazırda Laravel üzərində ümumi üstünlüyü sübut edən yoxlanılmış nəticə yayımlamır. Performans ölçmə alətləri bir əlverişli mikrotesti ümumi iddiaya çevirmək əvəzinə xam nəticələri saxlamalı və dəqiq iş yükünü təsvir etməlidir.

## Layihəni necə qiymətləndirməli

Həqiqətən ehtiyac duyduğunuz kiçik funksiyanı seçin. Hər iki namizəddə eyni girişləri, cavabları, yoxlama davranışını, məlumat əməliyyatlarını və testləri reallaşdırın. Hazırlıq addımlarını, uğursuzluq hallarını, texniki xidmət zəhmətini və ölçülmüş resurs istifadəsini qeydə alın.

Elang-da tələb olunan funksiya yoxdursa, onu qurmağın və ya inteqrasiya etməyin xərcini də daxil edin. Çatışmayan işi nəzərə almadan minimal ilkin versiyanı funksiyalarla zəngin texnologiyalar toplusu ilə müqayisə etmək yanıltıcı nəticə verər.

## Hazırkı layihələrə uyğunluq

Elang dizaynını öyrənmək, ilkin versiyanı sınamaq və nəzarət olunan təcrübələrlə rəy vermək üçün uyğundur. İstehsal tələbləri olan komandalar qərar verməzdən əvvəl dəyişən API-ləri, tamamlanmamış funksiya sahələrini və müstəqil təhlükəsizlik yoxlamasının yoxluğunu qiymətləndirməlidir.

Daha yaxşı öyrənmə təcrübəsi və ölçülə bilən təkmilləşmələr üzərində işləyə biləcəyimiz məqsədlərdir. Növbəti faydalı addım ana səhifədə daha güclü sifət deyil, real nümunə və sübutdur.
