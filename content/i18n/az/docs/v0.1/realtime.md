# Real vaxt hadisələri və WebSockets

Birtərəfli yeniləmələr üçün server tərəfindən göndərilən hadisələrdən, davamlı ikitərəfli bağlantı üçün isə WebSockets-dən istifadə edin. Elang daimi verilənlər bazası hadisə tarixçəsi, şəxsi kanal səlahiyyətləri, təkrar oxuma kursorları və Workerman əsaslı WebSocket icra mühiti ehtiva edir. API başlanğıc layihəsi istifadəçiyə məxsus qeydlərlə hər iki nəql üsulunu nümayiş etdirir.

## Quraşdırın və işə salın

```bash
composer create-project --repository=https://elangphp.com/packages elang/api my-api 0.1.0-dev
cd my-api
composer require elang/realtime:0.1.0-dev
php elang serve
```

Tətbiq qovluğunda ikinci terminal açın:

```bash
php elang realtime:serve --port=8081 --workers=1
```

WebSockets üçün Linux və ya macOS-da `pcntl` və `posix` olan CLI PHP tələb olunur. Bu proses idarəetmə funksiyaları ayrıca CLI PHP konfiqurasiyasında aktivləşdirilməlidir; bəzi hostinq panelləri onları standart olaraq söndürür. Veb proses hovuzunun məhdudiyyətlərini ayrıca saxlayın. İstehsal mühitində `wss://` üçün proses nəzarətçisi və TLS əks proksisi də lazımdır. SSE əlavə WebSocket paketi olmadan adi HTTP tətbiqi vasitəsilə işləyir.

## Qorunan əməliyyatdan sonra yayımlayın

```php
use Elang\Realtime\Events;

$events->publish('user:'.$user->id.':notes', 'note.created', [
    'note' => ['id' => 42, 'title' => 'A shared update'],
]);
```

Kanalı autentifikasiya edilmiş istifadəçidən və server tərəfindəki avtorizasiyadan müəyyən edin. Brauzerdən məhdudiyyətsiz kanal qəbul etməyin. Geri qaytarılmış dəyişikliklərin görünən hadisələrə çevrilməməsi üçün domen məlumatlarını və hadisəni eyni `Database` tranzaksiyasında yazın. Məzmun JSON formatındadır və 16 KB ilə məhdudlaşdırılır.

## SSE üzərindən abunə olun

API başlanğıc layihəsi `notes:read` bearer tokeni ilə `GET /api/notes/events` təqdim edir. Yenidən qoşularkən `Last-Event-ID` göndərin. Axınlar 20 saniyədən sonra bağlanır, bağlantının aktivliyini bildirən siqnallar göndərir və saxlanmış hadisələri ardıcıllıqla təkrar ötürür. Brauzerin standart `EventSource` vasitəsi Authorization başlığı təyin edə bilmir; əvəzində daxil edilmiş fetch müştərisindən istifadə edin.

```javascript
const controller = new AbortController();
await api.events('/api/notes/events', {
  signal: controller.signal,
  onEvent: ({ id, event, data }) => console.log(id, event, data),
});
```

`api` API başlanğıc layihəsinin `public/assets/elang-client.js` faylındakı `createClient` funksiyasından alınır. [React və Next.js inteqrasiyasına](/docs/v0.1/frontend) baxın. Axını `controller.abort()` ilə dayandırın. Köməkçi normal axın vaxtı bitdikdən sonra yenidən qoşulur; şəbəkə xətalarını və yenidən girişi tətbiqinizdə emal edin. Son nöqtə bağlantı zamanı bearer tokenini təkrar yoxlayır. Kursorsuz yeni abunə sıfırdan başlayır və saxlanmış tarixçəni təkrar ötürür.

## WebSockets ilə qoşulun

`notes:read` bearer tokeni ilə `POST /api/realtime-token` sorğusu göndərin. Cavab həmin istifadəçinin qeydlər kanalı üçün beş dəqiqəlik şifrələnmiş səlahiyyətdir. `notes:write` tokeni əlavə olaraq `note.create` əmrinə icazə verir. Səlahiyyət URL-də deyil, WebSocket alt protokolunda ötürülür.

```javascript
const socket = await api.socket({ url: 'ws://127.0.0.1:8081', after: 0 });
socket.onmessage = message => console.log(JSON.parse(message.data));
socket.onopen = () => {
  socket.send(JSON.stringify({ type: 'ping' }));
  // With a write-scoped capability:
  socket.send(JSON.stringify({
    type: 'note.create',
    payload: { title: 'From a socket', body: 'Stored by Elang' },
  }));
};
```

Əmrləri bağlantı açıldıqdan sonra göndərin. Lokal inkişaf üçün qaytarılan token və `['elang', 'token.' + token]` protokolları ilə birbaşa `ws://127.0.0.1:8081/realtime/socket` ünvanına qoşulun. Fetch müştərisinin `socket()` metodu `url` göstərilmədikdə API mənbəyini istifadə edir; istehsal mühitində `/realtime/socket` yolunu əks proksiniz vasitəsilə yönləndirin. Ayrıca lokal soket portu üçün nümunədəki kimi `url` istifadə edin. İlkin bağlantı razılaşması konfiqurasiya edilmiş `APP_URL` mənbəyinə və açıq şəkildə göstərilmiş `realtime.origins` qeydlərinə icazə verir. API başlanğıc layihəsi sonuncuları `FRONTEND_URL` dəyərindən oxuyur. CORS və WebSocket mənbə yoxlamaları ayrıdır; kanal və əmr icazələri yenə də şifrələnmiş səlahiyyətdən alınır.

`after` olmayan yeni bağlantılar ən son hadisədən başlayır. Son alınmış ID-ni saxlayın və yenidən qoşularkən yeni səlahiyyət istəyin. Səlahiyyət ən gec beş dəqiqə ərzində qüvvədən düşür. Parol dəyişiklikləri istifadəçinin autentifikasiya versiyası vasitəsilə onu ləğv edir; soket hər beş saniyədə və əmrlərdən əvvəl etibarlılığı yoxlayır. İlkin API tokeninin ləğv edilməsi artıq verilmiş soket səlahiyyətini ləğv etmir; onun qısa bitmə müddəti bu ömrü məhdudlaşdırır.

## İstehsal mühiti üçün əks proksi konfiqurasiya edin

HTTPS virtual hostunuz daxilində soket yolunu proses nəzarətçisi ilə idarə olunan lokal demona yönləndirin. Nginx üçün:

```nginx
location /realtime/socket {
    proxy_pass http://127.0.0.1:8081;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 75s;
    proxy_buffering off;
}
```

Nginx-i yenidən yükləməzdən əvvəl virtual host konfiqurasiyasını yoxlayın. Demonu loopback ünvanına bağlı saxlayın və HTTPS ön tərəfindən `wss://` istifadə edin. Bağlantı zamanı tətbiqin aktivlik siqnalını göndərin:

```javascript
const heartbeat = setInterval(() => {
  if (socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ type: 'ping' }));
  }
}, 20000);
socket.onclose = () => clearInterval(heartbeat);
```

Bağlantı kəsildikdən sonra yeni səlahiyyət və son alınmış hadisə ID-si ilə yenidən qoşulun. Server JSON ping mesajına pong ilə cavab verir; aktivlik siqnalı başqa halda fəaliyyətsiz qalacaq proksi bağlantısını aktiv saxlayır.

## Tətbiq əmrləri təyin edin

```php
use Elang\Realtime\Messages;
use Elang\Auth\User;

$app->container->get(Messages::class)->register(
    'chat.send',
    function (array $payload, User $user, string $channel): array {
        // Validate payload, authorize the resource, persist, then publish.
        return ['accepted' => true];
    }
);
```

Yalnız qeydiyyata alınmış və `SocketTokens::issue` səlahiyyəti verilərkən açıq şəkildə siyahıya daxil edilmiş əmrlər işləyə bilər. Təkcə qeydiyyat müştəriyə heç bir icazə vermir. Yuxarıdakı nümunə emaledici yalnız metodun formasını göstərir; onu çat üçün istifadə etməzdən əvvəl yoxlamanı və daimi saxlamanı reallaşdırın.

## İdarə edin və ölçün

Soket icra mühiti mesajları 32 KB, müştəri mesajlarını dəqiqədə 60, göndərmə buferini 256 KB və hər işçi üçün bağlantıları 1.000 ilə məhdudlaşdırır. Ləng müştərilərin bağlantısı kəsilir. Bunlar ölçülmüş tutum zəmanətləri deyil, qoruyucu limitlərdir. Cari hadisə arxa sistemi hər bağlantı üçün hər 500 ms-də ortaq SQL verilənlər bazasını sorğulayır. Miqyası artırmazdan əvvəl verilənlər bazasına tələbi və bağlantı sayını ölçün; Redis və ya xüsusi broker adapteri gələcək işdir. Bir SSE bağlantısı qısa axını müddətində HTTP işçisini tutur, buna görə axın trafiki üçün ayrıca işçi hovuzu ayırın.

`php elang realtime:prune` əmrini cədvəl üzrə işlədərək ən azı 24 saatlıq təkrar oxuma tarixçəsi saxlayın. Daha köhnə kursorlar yalnız saxlanmış hadisələri təkrar oxuyur. Müştərilər uzun fasilədən sonra resursun əsas vəziyyətinin surətini yeniləməlidir. Hadisə tarixçəsi mühasibat kitabı və ya daimi audit izi deyil.
