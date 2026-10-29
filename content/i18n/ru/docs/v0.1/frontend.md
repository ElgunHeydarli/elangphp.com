# React, Next.js и другие интерфейсы

Elang может отдавать HTML или выступать серверной частью API для React, Next.js, Vue, нативных мобильных приложений и серверных клиентов. Официальный сайт использует HTML, CSS и обычный JavaScript. Ваше приложение может выбрать собственный интерфейс.

## Начните с приложения API

```bash
composer create-project elang/api my-api dev-main
cd my-api
php elang serve
```

Зарегистрируйте учётную запись, подтвердите её через локальный [почтовый ящик Studio](/docs/v0.1/studio) и создайте токен в `/dashboard`. Предоставьте только те области доступа, которые нужны клиенту. Работающий API включает закрытые заметки, валидацию, проверки принадлежности, постраничную навигацию, SSE, разрешения WebSocket и `/openapi.json`.

## Разрешите источник интерфейса

Задайте точный источник интерфейса в `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000
```

Middleware CORS стартового проекта API разрешает указанный источник и обрабатывает предварительные запросы. Несколько источников можно разделить запятыми. Избегайте подстановочных знаков. Поддерживаются заголовки Authorization, Content-Type, Idempotency-Key, Last-Event-ID и Accept. Токены передаются в Authorization; межсайтовые cookie сессий не включены. CORS управляет доступом из браузера и не заменяет аутентификацию.

## Использование fetch из любого JavaScript-фреймворка

```javascript
const response = await fetch('http://127.0.0.1:8000/api/notes', {
  headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
});
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const { data, meta } = await response.json();
```

Используйте поля постраничной навигации, возвращаемые API. Обрабатывайте 401 для просроченных или отозванных токенов, 403 для недостаточной области доступа, 404 для отсутствующих записей или записей другого пользователя, 422 для ошибок полей и 429 для ограничения частоты запросов.

## Использование включённого клиента с React

Скопируйте `my-api/public/assets/elang-client.js` в каталог `src/lib/` интерфейса. Это ES-модуль без зависимостей, использующий стандартный fetch. Он включён в стартовый проект Composer; неопубликованный пакет npm не требуется.

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

Пример получает токен из интеграции входа вашего приложения. Не записывайте реальный токен в исходный код под контролем версий, не публикуйте его в сборке интерфейса и не помещайте в URL. Интерфейс учётной записи создаёт персональные API-токены для этого примера с bearer-токеном. Для полных сценариев браузерного входа, регистрации, CSRF и MFA используйте стартовые проекты полного стека React/Next и API сессий. Elang не является OAuth-провайдером.

## Вызов Elang из Next.js на сервере

Храните служебные учётные данные на сервере. Для приложений с данными конкретных пользователей получайте индивидуальные учётные данные из серверной сессии вместо использования одного служебного токена для всех пользователей.

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

Поместите `ELANG_API_URL` и `ELANG_API_TOKEN` в секреты серверного развёртывания. Не добавляйте префикс `NEXT_PUBLIC_` к переменной токена. Межсерверный fetch не требует CORS. API-токен получает доступ только к записям своего владельца.

## Безопасное повторение операций записи

```javascript
const key = crypto.randomUUID();
const created = await api.request('POST', '/api/notes', {
  json: { title: 'A durable note', body: 'Created from React' },
  idempotencyKey: key,
});
```

Повторно используйте один и тот же ключ только при повторении той же операции с идентичными входными данными. Стартовый проект API фиксирует буферизованный JSON-ответ, запись данных предметной области и событие через одно подключение к базе данных. Повторный совпадающий запрос возвращает сохранённый ответ; другие входные данные с тем же ключом возвращают 409. Для внешних сервисов нужна собственная обработка идемпотентности. При отсутствии заголовка этот middleware не применяется и не заявляет гарантию доставки ровно один раз между системами.

Продолжите изучение с [соединений в реальном времени](/docs/v0.1/realtime) и [архитектуры крупных приложений](/guides/large-applications).
