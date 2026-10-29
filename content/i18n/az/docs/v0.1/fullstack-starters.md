# React, Next.js və avtomatik yaradılan TypeScript müştəriləri

Elang PHP arxa tərəfidir. Onun rəsmi sənədləşdirmə saytı serverdə yaradılan HTML, CSS və sadə JavaScript istifadə etməyə davam edir. Tətbiqinizin ön tərəfi React, Next.js, başqa JavaScript freymvorku, mobil kod və ya server tərəfli müştəridən istifadə edə bilər.

## Tam ön tərəf/arxa tərəf layihəsi yaradın

Öz serverində yerləşdirilən Composer repozitoriyası terminaldan quraşdırmanı təmin edir. Bunlar inkişaf paketləridir; sabit buraxılış olduqları və ya Packagist-də dərc edildikləri iddia olunmur.

```bash
composer create-project --repository=https://elangphp.com/packages elang/react my-react-app 0.1.0-dev
# Alternatively:
composer create-project --repository=https://elangphp.com/packages elang/next my-next-app 0.1.0-dev
```

Konfiqurasiya edilmiş qlobal quraşdırıcı həmçinin `elang new my-app --starter=react` və ya `--starter=next` qəbul edir. Composer PHP tətbiqini hazırlayır. Ön tərəfin mənbələri `frontend/` altında yerləşir; ZIP-i əl ilə endirmək tələb olunmur. Tələblər: bu ön tərəf başlanğıc layihələri üçün PHP 8.3+, Composer 2 və Node 22.12+.

```bash
cd my-react-app
php elang serve
```

İkinci terminalda:

```bash
cd my-react-app/frontend
npm ci
npm run dev
```

Vite və ya Next-in göstərdiyi ön tərəf URL-ini açın. Arxa tərəfi onun göstərdiyi ünvanda işlək saxlayın. Arxa tərəfin ünvanını dəyişdirərkən `frontend/.env.example` faylını ön tərəfin sənədləşdirilmiş lokal mühit faylına köçürün. `ELANG_BACKEND_URL` server tərəfli proksi konfiqurasiyasıdır və heç vaxt brauzerə açıq sirlər ehtiva etməməlidir.

## Autentifikasiya necə işləyir

Hər iki başlanğıc layihəsi eyni mənbəli `/api` proksilərindən istifadə edir. Brauzer HttpOnly sessiya kukisi saxlayır; bearer tokenləri localStorage-da saxlanılmır. `GET /api/session` cari istifadəçini, CSRF tokenini, qeydiyyatın əlçatanlığını, poçtun əlçatanlığını və ikinci amilin gözlənilib-gözlənilmədiyini qaytarır. Təhlükəsiz olmayan sorğulara `X-CSRF-Token` daxildir.

1. Formanı göndərməzdən əvvəl sessiyanı yükləyin.
2. `/api/session/register` və ya `/api/session/login` vasitəsilə qeydiyyatdan keçin və ya daxil olun.
3. `mfaRequired` true olarsa, `/api/session/mfa` ünvanına kod göndərin; bu uğurla tamamlananadək hesaba giriş gözləmədə qalır.
4. Yazma əməliyyatından sonra sessiya vəziyyətini yeniləyin, çünki giriş/çıxış sessiya və CSRF tokenini dəyişir.
5. Şəxsi qeydlərinizi yaradın, redaktə edin, siyahılayın və silin.

Yeni hesablar qorunan API yazmalarından əvvəl e-poçtlarını təsdiqləməlidir. Lokal inkişafda şifrələnmiş inkişaf poçt qutusuna baxmaq üçün arxa tərəfin `/_elang/studio/mail` ünvanını açın. Kukilərin əhatə dairəsi portla deyil, host/domen və yolla müəyyən edilir; ön və arxa tərəf üçün fərqli host adlarından istifadə ayrıca arxa tərəf girişini tələb edə bilər. Başlanğıc layihəsinin `/backend-account` səhifəsi bu keçidi izah edir.

## Proksi sərhədləri

Next proksisi yalnız açıq şəkildə icazə verilmiş API yollarını/metodlarını ötürür, JSON sorğularını 32 KiB ilə məhdudlaşdırır, məhdud vaxt limiti tətbiq edir, yuxarı axın yönləndirmələrini rədd edir və ayrı-ayrı Set-Cookie başlıqlarını ötürür. Təhlükəsiz olmayan brauzer sorğuları ön tərəfin öz mənbəyindən gəlməlidir. Proksi serverdən serverə sorğudan əvvəl brauzerin Origin başlığını silir ki, arxa tərəf proksi trafikini birbaşa CORS sorğusu ilə qarışdırmasın.

Vite-in inkişaf proksisi təhlükəsiz olmayan çağırışlara eyni mənbə yoxlaması tətbiq edir. İstehsalda nəzərdən keçirilmiş əks proksi və ya tətbiq proksisi yerləşdirin; Vite inkişaf serveri istehsal yerləşdirməsi deyil. Proksini işlətmək üçün heç vaxt ümumi təyinatlı yuxarı axın URL parametri açmayın, brauzerin ixtiyari avtorizasiyasını əlaqəsiz hostlara ötürməyin və ya CSRF-ni söndürməyin.

Birbaşa fərqli API mənbəyi ilə danışan brauzer üçün dəqiq CORS mənbələrini və nəzərdən keçirilmiş etimadnamə/kuki siyasətini konfiqurasiya edin. CORS autentifikasiya deyil. Təqdim olunan başlanğıc layihələri eyni mənbəli proksi istifadə etməklə saytlararası kukilərdən yayınır. Server/mobil müştərilər əvəzində məhdud səlahiyyətli bearer tokenlərindən istifadə edə bilər.

## TypeScript müqaviləsini yaradın

Notes başlanğıc layihəsi `/openapi.json` sənədini `resources/openapi.php` əsasında dərc edir. Bu etibarlı lokal sxemdən müstəqil tipləşdirilmiş müştəri yaradın:

```bash
php elang api:types --schema=resources/openapi.php --output=frontend/src/lib/generated.ts
# Next.js starter:
php elang api:types --schema=resources/openapi.php --output=frontend/lib/generated.ts
```

Yaradılan modul sxem tiplərini, əməliyyat giriş/çıxışlarını, marşrutları və `createApiClient()` ehtiva edir. Başlanğıc layihəsi bu tipləri idxal edir və qeyd yazmaları üçün yaradılan əməliyyatlardan istifadə edir. Arxa tərəf müqaviləsini dəyişdirərkən OpenAPI sənədini redaktə edin, yenidən yaradın, tipləri yoxlayın və alınan fərqləri nəzərdən keçirin.

```ts
import { createApiClient } from './generated.ts';

const api = createApiClient();
const result = await api.request('createNote', {
  body: { title: 'First idea', body: 'A useful note' },
  headers: { 'X-CSRF-Token': csrfToken },
});
```

Generator məqsədli şəkildə OpenAPI 3.1-in məhdud alt dəstini dəstəkləyir: lokal komponent istinadları, obyektlər, massivlər, skalyar/null tipləri, sadalamalar, birləşmə tipləri, JSON sorğu gövdələri və JSON/SSE cavab sxemləri. Dəstəklənməyən sxem konstruksiyaları açıq xəta verir. TypeScript tipləri icra vaxtında etibarsız JSON-u yoxlamır. SSE əməliyyatları müqaviləni təsvir edir; canlı axın üçün JSON yönümlü köməkçi çağırış əvəzinə EventSource/axın emalından istifadə edin.

## Yoxlayın və yerləşdirin

```bash
cd frontend
npm run typecheck
npm test
npm run build
```

Next həmçinin `npm run test:proxy` təqdim edir. PHP test dəstini və real arxa tərəfə qarşı brauzer ssenarisini işlədin: daxil olun, təsdiqləyin, aktivdirsə MFA-nı tamamlayın, yaradın/redaktə edin/silin, səhifəni yeniləyin, çıxın və başqa istifadəçinin məlumatını oxumağa cəhd edin. Öz yerləşdirmənizdə CSP, HTTPS, etibarlı proksi başlıqları, arxa tərəf URL konfiqurasiyası, kuki domeni, vaxt limitləri və yuxarı axının əlçatanlığını nəzərdən keçirin.

Ön tərəfin kilid faylları sınaqdan keçirilmiş asılılıq ağacını sabitləyir. Təhlükəsizlik yeniləmələri yenə də nəzərdən keçirmə və təkrar yoxlama tələb edir. [API autentifikasiyası](/docs/v0.1/authentication), [hesab təhlükəsizliyi](/docs/v0.1/account-security) və [testlər](/docs/v0.1/testing) bölmələrinə baxın.
