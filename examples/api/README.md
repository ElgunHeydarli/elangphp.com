# Elang Api Starter

Composer installs dependencies, generates your application key, applies this starter’s migrations, and runs doctor. Start with `php elang serve` and open http://127.0.0.1:8000.

Create your own account. Local verification and recovery messages are available in Elang Studio at /_elang/studio/mail. Production account email requires MAILER_DSN and a valid sender address. No shared administrator password is bundled.

Before deployment: set APP_ENV=production, APP_DEBUG=false, and an HTTPS APP_URL; configure mail and database credentials; keep only public/ exposed. Studio and the development mailbox are unavailable in production.
