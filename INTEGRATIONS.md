# Integrations — Status

Legend: **REAL** = tested against the live provider · **MOCK** = simulated adapter with realistic behavior · **NOT IMPLEMENTED** = contract only / stub.

| System | Driver | Status | Notes |
|---|---|---|---|
| Payments — DemoPay gateway | `mock` | **MOCK** | Full loop: initiate → hosted checkout → signed webhook → finalize. Webhook signature + replay protection real. |
| Payments — bank transfer | `bank_transfer` | **MOCK** | Instructions + proof upload + finance review/allocate. Approval is a human workflow, not a bank API. |
| Payments — EcoCash / OneMoney / card | — | **NOT IMPLEMENTED** | Contract-ready (`PaymentProviderInterface`); need provider API specs + credentials. |
| Municipal billing system | `mock` | **MOCK** | `MockMunicipalBillingProvider` upserts bills via `sync_jobs`. Replace driver when council API spec exists. |
| Email | `log` mailer | **MOCK** | Laravel `Mail` facade — point `MAIL_*` at SES/Mailgun/etc. to go live. |
| SMS | `mock` | **MOCK** | Writes to `storage/app/mock-outbox-sms.log`, visible on Admin → Integrations. |
| WhatsApp | `mock` | **MOCK** | Same outbox pattern; channel + preferences + delivery tracking implemented. |
| Push notifications | — | **NOT IMPLEMENTED** | PWA manifest + service worker present; web-push needs VAPID + provider. |
| Bank CSV import | internal | **REAL (file-based)** | Finance uploads bank CSV → matched/unmatched rows → allocate. Format documented in-app. |
| Billing provider inbound webhooks | `MunicipalBillingProvider` | **NOT IMPLEMENTED** | Contract designed; no endpoint until spec exists. |
| PDF statements | dompdf | **REAL** | Generates and emails real PDFs locally. |

## Switching to real providers

Set drivers in `.env`:

```env
PAYMENT_PROVIDER=mock            # mock | bank_transfer | <new>
MOCK_PAYMENT_SECRET=...          # HMAC secret for the mock provider
SMS_PROVIDER=mock                # mock | <twilio|africastalking|...>
WHATSAPP_PROVIDER=mock           # mock | <meta|twilio|...>
MUNICIPAL_BILLING_PROVIDER=mock  # mock | <municipal api adapter>
BILLING_API_URL=                 # base URL for real adapter (future)
BILLING_API_KEY=
MAIL_MAILER=log                  # ses|smtp|mailgun|...
```

New provider = implement contract in `app/Providers/*` (or `app/Services/*`) and register in `PaymentManager`/`config/integrations.php`. No controllers change.

Webhook endpoint exposed: `POST /webhooks/payments/{provider}` — signature header `X-Provider-Signature` (mock scheme; each real adapter may define its own).

## What is NOT claimed

- No live EcoCash/OneMoney/bank/payment-gateway integration — credentials and API specs were not supplied.
- No real SMS/WhatsApp sending — mock outbox only.
- Municipal sync adapter is deterministic mock; real endpoint mappings TBD.
