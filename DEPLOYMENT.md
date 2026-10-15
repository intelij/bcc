# Deployment

## Topology (recommended production)

```
                 HTTPS/TLS
                    |
              Reverse proxy / LB
                    |
        +-----------+-----------+
        |                       |
   App (php-fpm)          Queue worker(s)
        |                       |
        +-----------+-----------+
                    |
     +--------------+--------------+
     |              |              |
 PostgreSQL       Redis         Object storage
 (primary DB)   (queue/cache)   (attachments/PDFs)
                    |
                cron → scheduler
```

Dev/demo runs monolith on SQLite + database queue (`php artisan serve`).

## Container build

`Dockerfile` reference (no file committed — generate per infra):

```dockerfile
FROM php:8.3-fpm
RUN apt-get update && apt-get install -y libpq-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo_pgsql zip gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . .
RUN composer install --no-dev --optimize-autoloader \
 && npm ci && npm run build \
 && php artisan config:cache && php artisan route:cache && php artisan view:cache
CMD ["php-fpm"]
```

Compose services: `app` (php-fpm), `web` (nginx), `worker` (`php artisan queue:work`), `scheduler` (`php artisan schedule:work` or cron), `db` (postgres), `redis`.

## Environment

See `.env.example`. Required for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=              # php artisan key:generate
APP_URL=https://city.example.gov.zw

DB_CONNECTION=pgsql
DB_HOST=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

FILESYSTEM_DISK=s3    # AWS_* keys

MAIL_MAILER=smtp / ses / mailgun
MAIL_HOST=...

PAYMENT_PROVIDER=mock           # → real provider + credentials
MOCK_PAYMENT_SECRET=            # rotate; real provider secrets per driver
SMS_PROVIDER=mock               # → real + keys
WHATSAPP_PROVIDER=mock          # → real + keys
MUNICIPAL_BILLING_PROVIDER=mock # → real + BILLING_API_URL/KEY
```

## Checklist

- [ ] HTTPS enforced (`APP_URL` https, `SESSION_SECURE_COOKIE`, HSTS at proxy)
- [ ] `config:cache`, `route:cache`, `view:cache`, `composer install --no-dev`
- [ ] `php artisan migrate --force` in release step
- [ ] `queue:work` (payments/notifications/sync) supervised
- [ ] cron `* * * * * php artisan schedule:run` → `billing:mark-overdue` hourly, `billing:sync-bills` as required
- [ ] Webhook URL reachable: `POST /webhooks/payments/{provider}` (allowlist provider IPs if supported)
- [ ] Backups: DB daily + WAL, `s3` versioning
- [ ] Logs: `storage/logs` + centralized (stderr → platform logging)
- [ ] Monitoring: health endpoint `/up`, queue depth, webhook failure rate, sync job age
- [ ] Secrets via secret manager (not `.env` on disk in prod)
- [ ] DB indexes: present on FKs/statuses — add composite indexes per production query profile (see `PROJECT_AUDIT.md`)

## Migrations

`php artisan migrate` — 7 grouped domain migrations, FK constraints, soft deletes on sensitive tables. Rollback safe for dev only.
