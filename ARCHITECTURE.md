# Digital City Platform — Architecture

## 1. Purpose

A centralized digital channel between a Zimbabwean city council and its residents,
property owners, tenants, and municipal staff. The platform sits **beside** the
existing municipal billing system — it does not replace it. All billing data is
synchronized through an integration layer so the platform remains a channel, not
the system of record.

```
                CITY / MUNICIPAL SYSTEMS
                          |
                          v
                   INTEGRATION LAYER        (MunicipalBillingProvider)
                          |
           +--------------+--------------+
           |              |              |
           v              v              v
       Billing         Payments      Notifications
       sync            adapters      adapters
           |              |              |
           +--------------+--------------+
                          |
                          v
               DIGITAL CITY PLATFORM      (this application)
                          |
         +----------------+----------------+
         |                                 |
         v                                 v
   RESIDENT PORTAL                  MUNICIPAL PORTAL
   Web / PWA                        Administration
   /                                /admin
```

## 2. Architectural style

A **modular monolith**. One deployable Laravel application, internally organized
by domain. This is a deliberate choice: a municipality needs operational
simplicity, transactional consistency across billing/payment boundaries, and a
small hosting footprint far more than it needs distributed-systems complexity.
Modules communicate through Laravel's container and domain events, so extraction
into services later is possible where genuinely needed.

### Layering inside the monolith

```
HTTP layer        routes → middleware (auth, role, throttle) → controllers
                    thin; validate via Form Requests; delegate to services
Domain layer      app/Services/* — business logic, state machines, orchestration
Integration layer app/Providers/{Payments,Notifications,Municipal}/ —
                    interface + adapters (mock now, real later)
Persistence       Eloquent models + migrations (SQLite dev / MySQL·PgSQL prod)
Presentation      Blade + Tailwind + Alpine (mobile-first, PWA manifest)
Async             Laravel queues (database driver dev / Redis prod), scheduler
```

### Domain modules

| Module | Path | Responsibility |
|---|---|---|
| Identity & RBAC | `User`, `Role`, policies | auth, roles, permissions |
| Properties | `Property`, `PropertyUser` | linking, verification, tenancy |
| Billing | `MunicipalAccount`, `Bill`, `BillItem`, `WaterMeter`, `MeterReading` | bills, meters, statements |
| Payments | `Payment`, `PaymentAllocation`, `PaymentWebhook`, `BankPaymentImport` | state machine, reconciliation |
| Collections control | `DisconnectionNotice` | arrears → disconnect → restore |
| City services | `Announcement`, `ServiceInterruption`, `RefuseSchedule` | content & schedules |
| Service requests | `ServiceRequest`, `ServiceRequestComment`, `Attachment` | ticketing |
| Notifications | `Notification`, `NotificationTemplate`, `NotificationPreference`, `NotificationDelivery` | multi-channel delivery |
| Integration | `SyncJob`, `IntegrationLog` | municipal system sync |
| Platform | `AuditLog`, `SystemSetting`, `Faq` | audit, config |

## 3. Provider abstraction (anti-lock-in)

Every external dependency is behind an interface bound in a service provider,
selected by config. Only mocks ship in this codebase; real adapters plug in
without touching domain code.

| Interface | Implementations shipped | Real candidates |
|---|---|---|
| `PaymentProviderInterface` | `MockPaymentProvider`, `BankTransferProvider` | EcoCash, OneMoney, Paynow, card gateway |
| `MunicipalBillingProvider` | `MockMunicipalBillingProvider` | vendor REST/CSV adapters |
| `EmailProvider` | Laravel mail (log driver dev) | SMTP / SES / Postmark |
| `SmsProvider` | `MockSmsProvider` | Twilio, local aggregator |
| `WhatsAppProvider` | `MockWhatsAppProvider` | Meta Cloud API |

`config/integrations.php` selects drivers. Mocks are namespaced under
`app/Services/Integrations/Mock` and are clearly labelled non-production.

## 4. Payment architecture (trust boundaries)

```
select acct → amount → provider → initiate → provider page / instructions
                                            |
                              webhook/callback (signed)
                                            |
                              verify → record → allocate → balance → receipt → notify
```

- **A payment is NEVER final because a browser returned.** Only a verified
  webhook/callback or a finance officer's reconciliation can move a payment to
  `successful`.
- `PaymentService` owns the state machine: `pending → processing → successful |
  failed | cancelled`, plus `refunded` and `unallocated`.
- **Idempotency**: client-supplied `idempotency_key` unique-constrained;
  provider `provider_reference` unique-constrained; webhook dedupe table.
- **Webhook security**: HMAC signature verification where the provider supports
  it (mock signs payloads), replay protection via stored webhook IDs, raw-body
  hashing, timestamp tolerance.
- **Bank transfers**: resident uploads proof → `unallocated`/`pending_review`
  → finance approves/rejects/allocates. CSV import + manual entry for
  reconciliation; ambiguous matches are never auto-allocated.

## 5. Security model

- Session auth (cookies, CSRF) for the portals; token-ability retained for a
  future mobile API (Sanctum-ready).
- **Authorization server-side via Policies/Gates on every query** — scoping, not
  trust of URLs/IDs. IDOR is the #1 threat: `bills/{id}` resolves through
  ownership scope, never raw find.
- Roles: `resident`, `property_owner`, `tenant`, `customer_service`,
  `finance`, `content_admin`, `admin`. Permissions via role matrix +
  per-resource policies (tenant access explicitly granted per property).
- Rate limiting on auth, payments initiation, webhooks, uploads.
- Uploads: private disk, MIME+extension whitelist, size cap, randomized stored
  names, authorization-checked download route. No public execution path.
- Audit log for every sensitive mutation (old/new values, IP, UA).
- OWASP: parameterised queries (Eloquent), `{{ }}` escaped output, CSRF tokens,
  SSRF avoided (no outbound fetch of user URLs), security headers.

## 6. Data design

Normalized relational schema (see `database/migrations`). Money stored as
integer cents (`*_cents`) — no float rounding in a financial system. Soft
deletes where audit requires history. Foreign keys + indexes on every
lookup path (account, property, status, dates). Unique constraints on
natural keys (account number, invoice number, idempotency key, provider ref).

## 7. Async & scheduled work

Database queue for the prototype (zero infra); swap `QUEUE_CONNECTION=redis`
in production. Jobs: notification fan-out, municipal sync, statement/email
delivery, report generation, webhook processing. Scheduler (`schedule:run`)
drives periodic sync, bill overdue sweeps, reminder notifications.

## 8. Observability

Structured logs (Monolog JSON channel option), dedicated logging of payment /
webhook / integration / audit events, `integration_logs` + `sync_jobs` tables,
`audit_logs` for sensitive mutations. Sentry-compatible hooks documented. Never
log secrets, full card data, or credentials.

## 9. Environments & deployment

`.env` per environment (dev/test/staging/prod); `.env.example` committed, no
secrets in VCS. Container-first deployment documented (app + worker + scheduler
+ nginx + DB + Redis + S3-compatible storage). See `DEPLOYMENT.md`.

## 10. Extensibility

Future modules (parking, licences, permits, smart meters, IoT) plug in as new
domain modules with their own migrations, policies and providers — the provider
abstraction and role system already accommodate them.
