# Database Schema

Migrations: `database/migrations/` (executed in timestamp order).
Dev database: SQLite (`database/database.sqlite`). Production: PostgreSQL or
MySQL — all queries are portable Eloquent; integer `*_cents` columns hold
money (never floats).

Conventions: big-int PKs, FKs with `constrained()`, indexes on every filter/
join column used by queries, `timestamps`, status enums as indexed strings.

## Identity & access

| Table | Purpose | Key columns |
|---|---|---|
| `users` | accounts | email (unique), phone, national_id, status, failed_login_attempts, locked_until, last_login_at |
| `roles` / `permissions` | RBAC vocabularies | slug, name, status (roles) |
| `role_user` / `permission_role` | many-to-many pivots | unique composite pairs |
| `devices` | push registration (mobile) | device_id (unique), platform, push_token, app_version, status, last_seen_at |
| `personal_access_tokens` | Sanctum API tokens | token hash, abilities, expires_at |

## Property & billing

| Table | Purpose | Key columns |
|---|---|---|
| `properties` | stands/units | reference (unique), address, suburb, ward, status |
| `property_users` | user↔property links + capability flags | relationship, status, can_view_bills/account/pay/submit_requests/view_statements — pending links grant nothing |
| `municipal_accounts` | billing accounts | account_number (unique), balance_cents, arrears_cents, currency, status |
| `water_meters` / `meter_readings` | consumption | meter_number, current/previous reading, reading_date, source |
| `bills` | invoices | invoice_number (unique), period, issue/due dates, previous_balance/charges/adjustments/payments/total/amount_paid (cents), status |
| `bill_items` | charge lines | charge_type (rates/water/refuse/sewer/other/adjustment), quantity, unit_price_cents, amount_cents, meter_reading_id |

`Bill::syncStatus()` recomputes paid/overdue state after every allocation.

## Payments

| Table | Purpose |
|---|---|
| `payments` | lifecycle records — uuid (public id), reference, idempotency_key (unique), provider, provider_reference, amount_cents, status, meta (JSON), paid_at |
| `payment_allocations` | payment→bill ledger lines |
| `payment_webhooks` | raw inbound events — signature validity, dedupe by event_id, replay-safe |
| `bank_payment_imports` | CSV rows awaiting finance allocation |

## City services

| Table | Purpose |
|---|---|
| `service_interruptions` | water/other outages — type (planned/emergency), status, area/suburb/ward, starts/expected/resolved |
| `water_schedules` | recurring shedding — area/suburb, day_of_week, window |
| `refuse_schedules` | collection day/frequency, next_collection_date |
| `announcements` / `announcement_targets` | published notices + suburb/ward targeting |
| `service_requests` / `service_request_comments` | tickets (ticket_number, category, status) + public/internal comments |
| `attachments` | polymorphic private-disk uploads (proofs, photos) |
| `disconnection_notices` | arrears workflow state machine |

## Notifications & system

| Table | Purpose |
|---|---|
| `notification_templates` | per event×channel subject/body |
| `notifications` | in-app feed records |
| `notification_deliveries` | per-channel send attempts/outbox |
| `notification_preferences` | user opt-in/out per event×channel |
| `audit_logs` | actor, action, subject, old/new values, IP, UA |
| `system_settings` | key/value config |
| `integration_logs` / `sync_jobs` | provider call log + municipal sync tracking |
| `faqs` | self-help content |

## ER spine

```
users ─┬─ role_user ── roles ── permission_role ── permissions
       ├─ property_users ── properties ── municipal_accounts ── bills ── bill_items
       │                                       └─ disconnection_notices
       ├─ payments ── payment_allocations ── bills
       ├─ devices · notifications · notification_preferences
       └─ service_requests ── service_request_comments
```
