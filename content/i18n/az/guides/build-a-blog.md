# Elang ilə verilənlər bazasına əsaslanan bloq qurun

Bloq başlanğıc layihəsi autentifikasiyanı, təsdiqlənmiş istifadəçi ilə yayımlamanı, Post modelini, səhifələməni, sahiblik yoxlamalarını və ekranlaşdırılmış, serverdə yaradılan HTML-i birləşdirir. Bu, əlaqəsiz kod parçaları toplusu əvəzinə tam işlək nümunədir.

## Layihəni yaradın

```bash
elang new journal --starter=blog
cd journal
php elang serve
```

Composer asılılıqları quraşdırır, tətbiq açarını yaradır, autentifikasiya və yazı miqrasiyalarını tətbiq edir və doctor-u işə salır. Serverin göstərdiyi lokal URL-i açın.

## Hesabınızı yaradın və təsdiqləyin

`/register` ünvanında qeydiyyatdan keçin. Lokal rejimdə `/_elang/studio/mail` açın və təsdiqləmə keçidindən istifadə edin. Poçt qutusu şifrələnmiş lokal mesajları saxlayır və lokal mühitdə yalnız loopback ünvanından əlçatandır.

Təsdiqləmədən sonra `/posts/new` açın. Başlıq və məqalə mətni yazın. Əvvəlcə qaralama kimi saxlayın, sonra ictimai siyahıda görünməsini istəyəndə yayımlama seçimini edin.

## Faylları anlayın

Post cədvəli, yazıla bilən sahələri və tip çevirmələrini elan edir. BlogController girişi yoxlayır, author_id dəyərini cari istifadəçidən təyin edir və yeniləmə ilə silməni eyni identifikatorla məhdudlaşdırır. Yazı miqrasiyası sxemi və müəllif xarici açarını yaradır. Twig görünüşü məqalə mətnini ixtiyari HTML kimi qəbul etmək əvəzinə ekranlaşdırır.

İctimai oxucular yalnız yayımlanmış yazıları görür. İkinci hesab birinci hesabın məqaləsini redaktə edə və ya silə bilməz. Rəqəmsal ID-ni bilmək avtorizasiya deyil.

## Nəticəni yoxlayın

Məqalə yaradın, redaktə edin, yayımlayın və ictimai URL-ini açın. İkinci hesab yaradın və birinci məqalənin redaktə URL-inin 404 qaytardığını təsdiqləyin. Səhifələməni görmək üçün bir neçə məqalə əlavə edin. Səhifənin arxasındakı sorğuları anlamaq üçün Studio-nu yoxlayın.

Yerləşdirmədən əvvəl HTTPS, real poçt nəqli, verilənlər bazası giriş məlumatları və istehsal mühiti parametrləri konfiqurasiya edin. İctimai tətbiqin qorumalı olduğu sərhədlər üçün yerləşdirmə və autentifikasiya təlimatlarını nəzərdən keçirin.
