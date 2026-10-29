# In-app notifications and queued email

Use the notification service for a private inbox and optional background mail. SQL inbox insertion and queue dispatch use the same database transaction. Email delivery runs in a separately supervised worker. This is separate from the existing synchronous account-verification/password-recovery transport.

## Prepare and register

```bash
php elang platform:setup
```

Register the provider in the bootstrap used by both HTTP requests and workers:

```php
$app->provider(new Elang\Notification\Provider());
```

The provider registers the fixed `notification.mail` handler. JSON payloads cannot choose arbitrary PHP classes or code. Mail payloads are encrypted under `APP_KEY` before entering the queue. Keep that key stable and backed up. Inbox text is private through authorization but is not automatically encrypted at rest; apply database/disk encryption appropriate to your data.

## Send and read

```php
$notifications = $app->container->get(Elang\Notification\Notifications::class);
$id = $notifications->send(
    $user,
    'Export ready',
    'Your requested report is ready to review.',
    '/reports/42',
    mail: true,
);
$inbox = $notifications->inbox($user);
$notifications->markRead($user, $id);
```

Subjects are limited to 160 characters and bodies to 16,000 bytes. Optional links must be local paths of at most 500 bytes; protocol-relative links, backslashes, controls, and spaces are rejected. Render text with template escaping. Inbox queries and read updates always constrain `user_id`; another user cannot mark your message as read. The service returns the latest 100 inbox records.

A notification link is navigation, not authorization. The destination must still check record ownership/team membership. For tenant data, put only the minimum useful information in inbox/email text: removing a member cannot erase an email already delivered to them.

## Configure real email

```dotenv
MAILER_DSN=smtp://username:encoded-password@smtp.example.com:587
MAIL_FROM=notifications@example.com
```

Store real values outside Git and URL-encode credentials inside DSNs. Prefer a secret manager or a protected environment file. Use the transport provider's documented TLS settings. Configure SPF, DKIM, and DMARC for the sending domain, confirm the authorized sender, and test bounce/complaint handling. A configured DSN alone does not prove delivery or domain reputation.

```bash
php elang queue:work
php elang queue:failed
```

`null://null` is intentionally rejected by queued notification delivery so a worker does not silently discard mail. A development SMTP capture server such as Mailpit accepts messages without sending to real recipients. Account verification/recovery already offers an encrypted local mailbox in `APP_ENV=local` when the transport is null; that mailbox is a development convenience, not a production mail service.

## Retry and delivery semantics

The worker reserves a job with a fenced lease, retries with bounded backoff, and records the exception class without message bodies or credentials. Inspect failed job metadata, fix the cause, then retry a reviewed job:

```bash
php elang queue:retry JOB_ID_FROM_FAILED_LIST
```

Delivery is at least once. If an SMTP server accepts a message but the worker loses its acknowledgment or dies before completing the job, a retry may send it again. For providers that support idempotency, add a stable delivery key and provider-specific adapter. Never promise exactly-once mail.

Keep jobs shorter than the configured lease or implement a reviewed renewal policy. Do not put raw verification links, tokens, passwords, or personal data into unencrypted queue payloads. Monitor queue lag, failures, worker heartbeats, and outbox backlog. See [operations](/docs/v0.1/observability), [mail](/docs/v0.1/mail), and [queues](/docs/v0.1/queues).
