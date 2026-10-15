# Payments

## Design principles

1. **The server is authoritative.** A payment is final only when a *signed
   provider webhook* or a finance officer's reconciliation marks it so. A
   browser redirect or an app foreground NEVER changes payment state.
2. **Idempotent initiation.** `payments.idempotency_key` is unique. Web forms
   and the mobile app submit a stable key per attempt; retries reuse the same
   `Payment` row (`PaymentService::initiate` returns `reused: true`).
3. **Provider logic lives behind an interface.** `PaymentProviderInterface`
   adapters are registered in `PaymentManager`; controllers/screens never
   contain gateway-specific code.

## Lifecycle

```
initiate → pending → processing → successful
                     ↓          → failed → (new attempt allowed)
                     → cancelled
        successful → refunded   (finance action)
```

Terminal states never transition again — webhook replays are ignored
(`200` returned but no-op).

## Flow

1. `POST /api/v1/payments` (or web `POST /payments`) validates account access
   via `AccountPolicy::pay` — users can only pay into accounts on their
   approved property links with `can_pay`.
2. `PaymentService::initiate` creates (or reuses) the payment, records the
   provider's `initiation` payload in `meta`, returns it to the client:
   - `mock` → `{type: redirect, redirect_url}` → hosted demo checkout page →
     DemoPay sends a signed webhook.
   - `bank_transfer` → `{type: instructions, instructions, reference}` →
     resident pays externally, uploads proof, finance reviews.
3. Webhook `POST /webhooks/payments/{provider}` verifies the signature, dedupes
   by `event_id`, validates the amount matches, transitions the payment,
   auto-allocates to the oldest open bills, and notifies the payer.
4. Mobile polls `GET /payments/{uuid}` until a terminal state.

## Bank-transfer workflow

- Resident receives instructions + a generated reference.
- Proof upload (web form or `POST /payments/{uuid}/proof`, jpg/png/pdf ≤4 MB)
  stores on the **private** disk as an `Attachment` — served only through an
  authorized controller route, never a public URL.
- Finance sees it in `/admin/payments/{uuid}` → approve (allocates + marks
  successful) or reject with reason. Fully audited.

## Reconciliation

`/admin/reconciliation` — CSV bank-statement import matches rows to accounts
by account number or reference text; unmatched rows land in the unallocated
register for manual allocation. Every allocation is an auditable action.

## Providers

| Key | Type | Status |
|---|---|---|
| `mock` | DemoPay hosted checkout + signed webhook | MOCK — demo only |
| `bank_transfer` | instructions + proof + manual review | REAL (manual ops) |
| EcoCash/OneMoney/cards | — | NOT IMPLEMENTED (add adapter, no other changes) |

Webhook security: `X-Provider-Signature: HMAC-SHA256(raw_body, secret)` for the
mock; a real driver implements its own `verifyWebhook()`. Secrets come from
`config/services.php` env keys — never hardcoded.

## Failure handling

- Signature failure → `403`, logged.
- Amount mismatch → webhook recorded `failed`, payment stays `pending`,
  alert logged.
- Provider unreachable at initiation → validation error, no orphan payment.
- Duplicate webhook events → deduped (`payment_webhooks.event_id` unique).
