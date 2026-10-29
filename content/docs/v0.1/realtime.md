# Real-time events and WebSockets

Use server-sent events for one-way updates and WebSockets for a persistent two-way connection. Elang includes durable database event history, private channel capabilities, replay cursors, and a Workerman-based WebSocket runtime. The API starter demonstrates both transports with user-owned notes.

## Install and run

```bash
composer create-project --repository=https://elangphp.com/packages elang/api my-api 0.1.0-dev
cd my-api
composer require elang/realtime:0.1.0-dev
php elang serve
```

Open a second terminal in the application:

```bash
php elang realtime:serve --port=8081 --workers=1
```

WebSockets require CLI PHP on Linux or macOS with `pcntl` and `posix`. These process-control functions must be enabled in the dedicated CLI PHP configuration; some hosting panels disable them by default. Keep web-pool restrictions separate. Production also needs a process supervisor and a TLS reverse proxy for `wss://`. SSE runs through the regular HTTP application without the optional WebSocket package.

## Publish after a protected operation

```php
use Elang\Realtime\Events;

$events->publish('user:'.$user->id.':notes', 'note.created', [
    'note' => ['id' => 42, 'title' => 'A shared update'],
]);
```

Derive the channel from the authenticated user and server-side authorization. Do not accept an unrestricted channel from the browser. Write domain data and its event inside the same `Database` transaction so rolled-back changes do not become visible events. Payloads are JSON, bounded to 16 KB.

## Subscribe over SSE

The API starter exposes `GET /api/notes/events` with a `notes:read` bearer token. Send `Last-Event-ID` when reconnecting. Streams close after 20 seconds, send heartbeats, and replay ordered retained events. Native `EventSource` cannot set an Authorization header; use the included fetch client instead.

```javascript
const controller = new AbortController();
await api.events('/api/notes/events', {
  signal: controller.signal,
  onEvent: ({ id, event, data }) => console.log(id, event, data),
});
```

`api` comes from `createClient` in the API starter's `public/assets/elang-client.js`. See [React and Next.js integration](/docs/v0.1/frontend). Stop the stream with `controller.abort()`. The helper reconnects after a normal stream timeout; handle network errors and renewed login in your application. The endpoint rechecks the bearer token while connected. A new subscription without a cursor starts at zero and replays retained history.

## Connect with WebSockets

Request `POST /api/realtime-token` using a `notes:read` bearer token. The response is a five-minute encrypted capability for that user's notes channel. A `notes:write` token additionally grants the `note.create` command. The capability travels in the WebSocket subprotocol, not the URL.

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

Send commands after the connection opens. For local development, connect directly to `ws://127.0.0.1:8081/realtime/socket` using the returned token and `['elang', 'token.' + token]` protocols. The fetch client's `socket()` uses the API origin when `url` is omitted; route `/realtime/socket` through your reverse proxy in production. Use `url` as shown for the separate local socket port. The handshake allows the configured `APP_URL` origin and explicit `realtime.origins` entries. The API starter reads the latter from `FRONTEND_URL`. CORS and WebSocket origin checks are separate; channel and command permissions still come from the encrypted capability.

New connections without `after` start at the latest event. Keep the last received ID and request a fresh capability when reconnecting. A capability expires within five minutes. Password changes revoke it through the user's authentication version; the socket checks validity every five seconds and before commands. Revoking its original API token does not revoke an already issued socket capability; its short expiry bounds that lifetime.

## Configure a production reverse proxy

Route the socket path to the supervised local daemon inside your HTTPS virtual host. For Nginx:

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

Validate the virtual-host configuration before reloading Nginx. Keep the daemon bound to loopback and use `wss://` from an HTTPS frontend. Send an application heartbeat while connected:

```javascript
const heartbeat = setInterval(() => {
  if (socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ type: 'ping' }));
  }
}, 20000);
socket.onclose = () => clearInterval(heartbeat);
```

Reconnect with a fresh capability and the last received event ID after a disconnect. The server replies to the JSON ping with a pong; the heartbeat keeps an otherwise idle proxy connection active.

## Define application commands

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

Only registered commands explicitly listed when issuing the `SocketTokens::issue` capability can run. Registration alone grants no client permission. The example handler above only illustrates the signature; implement validation and persistence before using it for chat.

## Operate and measure

The socket runtime caps messages at 32 KB, 60 client messages per minute, 256 KB of send buffering, and 1,000 connections per worker. Slow clients are disconnected. These are protective limits, not measured capacity guarantees. The current event backend polls a shared SQL database every 500 ms per connection. Measure database demand and connection count before scaling; a Redis or dedicated broker adapter is future work. One SSE connection occupies an HTTP worker during its short stream, so reserve a separate worker pool for streaming traffic.

Keep at least 24 hours of replay history with `php elang realtime:prune` on a schedule. Older cursors only replay retained events. Clients must refresh their canonical resource snapshot after a long absence. Event history is not an accounting ledger or permanent audit trail.
