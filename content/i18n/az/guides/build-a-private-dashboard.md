# Şəxsi istifadəçi iş sahəsi qurun

İdarəetmə paneli başlanğıc layihəsi qeydiyyat, hesab parametrləri, daimi şəxsi qeydlər, tamamlanma vəziyyəti, səhifələmə və faydalı say göstəriciləri təqdim edir. Hər qeyd verilənlər bazasında öz sahibinə məxsusdur.

## Layihəni yaradın

```bash
elang new workspace --starter=dashboard
cd workspace
php elang serve
```

Şəxsi hesab qeydiyyatdan keçirin, sonra `/workspace` açın. Başlıq və təfərrüatları olan qeyd əlavə edin. Verilmiş formalarla onu tamamlanmış kimi işarələyin və ya silin.

## Bir yazma əməliyyatını izləyin

Sorğu sessiya, CSRF və autentifikasiya aralıq proqram təminatından keçir. Kontroller məlum giriş sahələrini yoxlayır və owner_id dəyərini autentifikasiya edilmiş istifadəçidən təyin edir. Repozitoriya yalnız modelin yazıla bilən sahələrini yazır.

Oxuma, vəziyyət dəyişdirmə və silmə əməliyyatlarının hamısı owner_id ilə məhdudlaşdırılır. Göndərilmiş gövdə sahəsi bu sahibliyi dəyişə bilməz və başqa hesabın qeydi tapılmamış kimi qaytarılır.

## Məxfiliyi yoxlayın

Ayrı brauzer sessiyalarında iki hesab yaradın. Birincidə qeyd əlavə edin, sonra ikincidən onun rəqəmsal ID-sini sınayın. İkinci hesab onu oxuya və ya dəyişə bilməməlidir. Parol dəyişiklikləri digər sessiyaları etibarsız edir və API tokenlərini ləğv edir.

## Quruluşdan öyrənin

Note modeli tip çevirmələrini və yazıla bilən sahələri nümayiş etdirir. WorkspaceController məhdudlaşdırılmış repozitoriyaları göstərir. Miqrasiya xarici açarı, şablon isə CSRF tokenləri olan adi HTML formalarını nümayiş etdirir. Studio sorğu gövdələri və ya SQL-ə bağlanan dəyərlər olmadan sorğu şablonlarını göstərir.

Başlanğıc layihəsini həqiqi ortaq xidmət üçün istifadə etməzdən əvvəl istehsal poçtunu, HTTPS-i, şəxsi saxlama icazələrini və ehtiyat nüsxələrini konfiqurasiya edin.
