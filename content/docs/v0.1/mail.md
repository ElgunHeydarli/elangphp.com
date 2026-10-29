# Mail

Elang binds Symfony Mailer to a configured transport. No messages are sent until a real transport is configured.

## Configure a transport

```dotenv
MAILER_DSN="smtp://user:password@smtp.example.com:587"
```

URL-encode credentials. Configure provider-specific encryption and options according to Symfony Mailer's documentation. The default `null://null` transport intentionally discards messages.

## Send a message

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

$message = (new Email())
    ->from('hello@example.com')
    ->to('reader@example.com')
    ->subject('Welcome')
    ->text('Your application is ready.');

$mailer->send($message);
```

Inject MailerInterface into your application service. Use validated recipient addresses and application-controlled sender addresses.

## Delivery model

Sending is synchronous. Delivery depends on your configured provider, DNS, credentials, and transport behavior. Queue-based delivery, templates for transactional mail, and retry orchestration are future work.
