# API & Integration Contracts

## Client REST API — `/api/v1`

First-class JSON API consumed by the Flutter app (`mobile/`) and available to
future clients. Bearer-token auth via Laravel Sanctum; authorization is the
same policy layer as the web portal — **identical scoping, enforced per
object** (see `RBAC.md`).

### Authentication

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/auth/register` | name/email/phone/user_type/password(+optional `account_number` → pending link) → `{token, user}` |
| POST | `/api/v1/auth/login` | `{email, password, device_name}` → `{token, user}` — same lockout rules as web |
| POST | `/api/v1/auth/forgot-password` | sends reset link, never reveals existence |
| POST | `/api/v1/auth/logout` | revokes the current token |
| GET | `/api/v1/me` | profile + roles + effective permissions |

Suspended accounts: `403 {"error": "account_suspended"}` and the token is revoked.

### Endpoints (all auth'd unless noted)

| Method | Path | Purpose |
|---|---|---|
| GET | `/meta` | **public** — city config, request/announcement categories |
| GET | `/dashboard?property_id=` | balance summary, open bills, water alerts, requests |
| GET | `/properties` · `/properties/{id}` | linked properties w/ capabilities |
| POST | `/properties/link` | request a link (pending staff verification) |
| GET | `/bills` · `/bills/{id}` | paginated, `?status=&account_id=` |
| GET | `/statements/{account}` · `/statements/{account}/pdf` | `?from=&to=`; PDF download |
| GET | `/payments/providers` | server-driven provider list |
| GET | `/payments` · `/payments/{uuid}` | history + status polling |
| POST | `/payments` | `{municipal_account_id, amount, provider, idempotency_key}` → `{data, initiation, reused}` — throttled, idempotent |
| POST | `/payments/{uuid}/proof` | multipart `proof` upload (bank transfers) |
| GET | `/requests` · `/requests/{id}` | ticket list/detail + public comments |
| POST | `/requests` · `/requests/{id}/comments` | multipart `photos[]` supported |
| GET | `/water` · `/refuse` · `/announcements` · `/announcements/{slug}` | suburb-scoped city info |
| GET | `/notifications?unread=` · POST `/notifications/{id}/read` · `/notifications/read-all` | |
| GET/PUT | `/preferences` | per-event×channel notification prefs |
| GET/POST/DELETE | `/devices` · `/devices/{deviceId}` | push registration (token refresh = POST same device_id) |
| GET | `/attachments/{id}` | authorized private download |

Conventions: paginated lists return Laravel's `{data, links, meta}` envelope;
single resources return `{data: …}`; validation errors are `422` with
`{message, errors}`; all amounts are integer **cents**.

Rate limits: `auth/*` 6–10/min, `payments` & `requests` POST 10/min.

---

## External-facing interfaces

1. **Payment provider webhooks** (inbound)
2. **Provider contracts** (outbound adapters, swappable)
3. **Municipal billing sync contract**

## Payment webhook — `POST /webhooks/payments/{provider}`

Inbound payment confirmation. **Never trusts the browser redirect** — only a verified webhook or finance reconciliation finalizes a payment.

### MockPay contract (`provider = mock`)

Headers: `X-Provider-Signature: hex(HMAC-SHA256(raw_body, MOCK_PAYMENT_SECRET))`

Payload:

```json
{
  "event_id": "uuid — deduplication key",
  "event_type": "payment.completed | payment.failed | payment.cancelled",
  "payment_uuid": "platform payment UUID",
  "provider_reference": "MOCK-XXXX",
  "amount_cents": 5000,
  "currency": "USD",
  "paid_at": "ISO-8601"
}
```

Behavior:
- `200` on valid signature; `403` on invalid/missing signature.
- `event_id` deduplicated in `payment_webhooks` — replays return `200` but are ignored.
- `amount_cents` mismatch → webhook recorded `failed`, payment left `pending`, alert logged.
- Unknown `payment_uuid` → webhook recorded `failed`, ignored.
- Terminal payments (`successful`/`failed`/etc.) never re-transition — safe for retries.

## Provider contracts

### `PaymentProviderInterface`

```php
initiate(User $user, MunicipalAccount $account, int $amountCents, string $reference, string $idempotencyKey): array
verifyWebhook(Request $request): bool                    // signature check
parseWebhook(Request $request): array                    // normalized event payload
refund(Payment $payment): bool
label(): string
```

Implementations: `MockPaymentProvider` (redirect + webhook simulation), `BankTransferProvider` (instructions + manual review). Register new drivers in `PaymentManager::registerProvider()` — no other code changes needed.

### `MunicipalBillingProviderInterface`

```php
fetchAccounts(array $params): Collection
fetchBills(?string $from, ?string $to): Collection
pushBillStatus(Bill $bill): bool
syncPayments(): Collection
```

`MockMunicipalBillingProvider` generates deterministic demo bills. `MunicipalSyncService::syncBills()` wraps sync in transactions, upserts by `external_invoice`, and writes `sync_jobs` + `integration_logs`.

### `NotificationChannelInterface`

```php
send(string $to, ?string $subject, string $body, array $context): array // returns ['reference', 'status']
channel(): string // in_app|email|sms|whatsapp
```

Mock SMS/WhatsApp providers write deliveries and append to `storage/app/mock-outbox-*.log` (inspectable on the admin Integrations page). Real SMS/WhatsApp gateways slot in via config `integrations.notifications.channels.*.driver`.

## Municipal sync (scheduled)

`php artisan billing:sync-bills [--from= --to=]` — runs `MunicipalSyncService::syncBills`. Add to `routes/console.php` scheduler for production cadence. Overdue sweep: `php artisan billing:mark-overdue`.

## Internal routes

See `routes/web.php` — resident portal routes are session-authenticated with per-object policies; admin routes sit under `/admin` behind `permission:*` middleware.
