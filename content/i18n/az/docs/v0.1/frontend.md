# React, Next.js və digər ön tərəflər

Elang HTML təqdim edə və ya React, Next.js, Vue, platformaya məxsus mobil tətbiqlər və server müştəriləri üçün API arxa tərəfi kimi işləyə bilər. Rəsmi veb-sayt HTML, CSS və sadə JavaScript istifadə edir. Tətbiqiniz öz ön tərəfini seçə bilər.

## API tətbiqi ilə başlayın

```bash
composer create-project --repository=https://elangphp.com/packages elang/api my-api 0.1.0-dev
cd my-api
php elang serve
```

Hesab qeydiyyatdan keçirin, lokal [Studio poçt qutusu](/docs/v0.1/studio) ilə onu təsdiqləyin və `/dashboard` daxilində token yaradın. Yalnız müştərinizə lazım olan səlahiyyətləri verin. İşlək API-yə şəxsi qeydlər, yoxlama, sahiblik yoxlamaları, səhifələmə, SSE, WebSocket səlahiyyətləri və `/openapi.json` daxildir.

## Ön tərəf mənbəyinizə icazə verin

`.env` daxilində dəqiq ön tərəf mənbəyini təyin edin:

```dotenv
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000
```

API başlanğıc layihəsinin CORS aralıq proqram təminatı siyahıya daxil edilmiş mənbəyə icazə verir və ilkin yoxlama sorğularını emal edir. Bir neçə mənbəni vergüllə ayırmaq olar. Ümumi uyğunluq işarələrindən çəkinin. O, Authorization, Content-Type, Idempotency-Key, Last-Event-ID və Accept başlıqlarını dəstəkləyir. Tokenlər Authorization daxilində göndərilir; fərqli mənbələr arasında sessiya kukiləri aktivləşdirilməyib. CORS brauzer girişini idarə edir və autentifikasiyanı əvəz etmir.

## İstənilən JavaScript freymvorkundan fetch istifadə edin

```javascript
const response = await fetch('http://127.0.0.1:8000/api/notes', {
  headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const { data, meta } = await response.json();
```

API-nin qaytardığı səhifələmə sahələrindən istifadə edin. Vaxtı bitmiş və ya ləğv edilmiş tokenlər üçün 401, qeyri-kafi səlahiyyət üçün 403, çatışmayan və ya başqa istifadəçiyə məxsus qeydlər üçün 404, sahə xətaları üçün 422 və sorğu tezliyi limitləri üçün 429 cavablarını emal edin.

## Daxil edilmiş müştərini React ilə istifadə edin

`my-api/public/assets/elang-client.js` faylını ön tərəfinizin `src/lib/` qovluğuna köçürün. O, standart fetch istifadə edən, asılılıqsız ES moduludur. Composer başlanğıc layihəsinə daxildir; dərc edilməmiş npm paketi tələb olunmur.

```jsx
import { useEffect, useState } from 'react';
import { createClient } from './lib/elang-client.js';

export function Notes({ token }) {
  const [notes, setNotes] = useState([]);
  const [error, setError] = useState('');
  useEffect(() => {
    const controller = new AbortController();
    const api = createClient({
      baseUrl: 'http://127.0.0.1:8000',
      getToken: () => token,
    });
    api.request('GET', '/api/notes', { signal: controller.signal })
      .then(result => setNotes(result.data))
      .catch(error => { if (error.name !== 'AbortError') setError(error.message); });
    return () => controller.abort();
  }, [token]);
  if (error) return <p role="alert">{error}</p>;
  return <ul>{notes.map(note => <li key={note.id}>{note.title}</li>)}</ul>;
}
```

Nümunə tokeni tətbiqinizin giriş inteqrasiyasından alır. Həqiqi tokeni mənbə kodunda sabit yazıb versiya nəzarətinə daxil etməyin, yığımla yayımlamayın və ya URL-ə qoymayın. Başlanğıc layihəsinin hesab interfeysi şəxsi API tokenləri yaradır; o, OAuth təminatçısı və ya tam SPA giriş protokolu deyil.

## Serverdə Next.js-dən Elang-ı çağırın

Xidmət giriş məlumatlarını serverdə saxlayın. İstifadəçiyə xas tətbiqlər üçün bir xidmət tokenini istifadəçilər arasında paylaşmaq əvəzinə hər istifadəçinin giriş məlumatını server sessiyanızdan əldə edin.

```jsx
import 'server-only';

export default async function Page() {
  const response = await fetch(`${process.env.ELANG_API_URL}/api/notes`, {
    headers: { Authorization: `Bearer ${process.env.ELANG_API_TOKEN}` },
    cache: 'no-store',
  });
  if (!response.ok) throw new Error(`Elang returned HTTP ${response.status}`);
  const { data } = await response.json();
  return <ul>{data.map(note => <li key={note.id}>{note.title}</li>)}</ul>;
}
```

`ELANG_API_URL` və `ELANG_API_TOKEN` dəyərlərini server yerləşdirmənizin sirlərində saxlayın. Token dəyişəninin əvvəlinə `NEXT_PUBLIC_` əlavə etməyin. Serverlərarası fetch CORS tələb etmir. API tokeni yalnız öz sahibinin qeydlərinə giriş verir.

## Yazma əməliyyatlarını təhlükəsiz təkrarlayın

```javascript
const key = crypto.randomUUID();
const created = await api.request('POST', '/api/notes', {
  json: { title: 'A durable note', body: 'Created from React' },
  idempotencyKey: key,
});
```

Eyni açarı yalnız eyni əməliyyatı eyni girişlə təkrar sınayarkən istifadə edin. API başlanğıc layihəsi buferlənmiş JSON cavabını, domen yazmasını və hadisəni eyni verilənlər bazası bağlantısında təsdiqləyir. Təkrar uyğun sorğu saxlanmış cavabı qaytarır; eyni açarla fərqli giriş 409 qaytarır. Xarici xidmətlər öz idempotentlik emalını tələb edir. Başlıq verilmədikdə bu aralıq proqram təminatının istifadəsi seçimə bağlıdır və o, sistemlər arasında məhz bir dəfə çatdırılma iddiası irəli sürmür.

[Real vaxt bağlantıları](/docs/v0.1/realtime) və [böyük tətbiq arxitekturası](/guides/large-applications) ilə davam edin.
