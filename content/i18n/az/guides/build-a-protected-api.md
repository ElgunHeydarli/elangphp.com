# Qorunan qeydlər API-si qurun

API başlanğıc layihəsi işlək hesab interfeysini vəziyyət saxlamayan bearer-token son nöqtələri ilə birləşdirir. Tokenlərin açıq səlahiyyət dairələri, bitmə vaxtı, ləğv imkanı və sahibi var. Qeydlər həmişə həmin sahiblə məhdudlaşdırılır.

## Layihəni yaradın

```bash
elang new notes-api --starter=api
cd notes-api
php elang serve
```

Lokal inkişaf poçt qutusundan istifadə edərək qeydiyyatdan keçin və hesabınızı təsdiqləyin. Hesab səhifəsini açın və parolunuzu təsdiqlədikdən sonra token yaradın. Birdəfəlik dəyəri köçürün və şəxsi saxlayın.

## Oxuma və yazma səlahiyyətləri

GET kolleksiya və element son nöqtələri notes:read tələb edir. POST, PUT və DELETE notes:write tələb edir. Yalnız oxuma tokeni qeydləri yarada və ya silə bilməz. Server göndərilmiş sahib ID-lərini nəzərə almır və sahibliyi autentifikasiya edilmiş tokendən müəyyən edir.

```bash
curl http://127.0.0.1:8000/api/notes \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

Öz terminalınızda YOUR_TOKEN dəyərini əvəz edin. Həqiqi tokenləri nümunələrdə və ya jurnallarda yayımlamayın.

## Gözlənilən cavablar

Uğurlu yaratma 201 və Location qaytarır. Uğurlu silmə 204 qaytarır. Etibarsız token 401, qeyri-kafi səlahiyyət dairəsi 403, başqa istifadəçinin qeydi isə 404 qaytarır. Etibarsız JSON 400, etibarsız sahələr 422, dəstəklənməyən məzmun tipləri isə 415 qaytarır.

API autentifikasiya üçün brauzer kukilərindən istifadə etmir. Brauzer hesab formaları ayrıca sessiya və CSRF qorunmasından istifadə edir.

## Sərhədləri sınaqdan keçirin

Yalnız oxuma tokeni yaradın və yazmanın rədd edildiyini təsdiqləyin. İkinci hesab üçün token yaradın və onun birinci hesabın qeydinə daxil ola bilmədiyini təsdiqləyin. Tokeni ləğv edin və növbəti sorğunun icazəsiz olduğunu təsdiqləyin. Hesab parolunu dəyişin və əvvəl verilmiş tokenlərin işləməyi dayandırdığını təsdiqləyin.

Yerləşdirmədən əvvəl HTTPS, istehsal mühiti parametrləri, poçt çatdırılması, ehtiyat nüsxələri və monitorinq konfiqurasiya edin. İşlək başlanğıc layihəsinə yenə də öz məhsulunuzun biznes qaydaları və məlumat saxlama tələbləri lazımdır.
