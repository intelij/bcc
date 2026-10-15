# Digital City Rates, Water & Service Delivery Platform

A municipal digital-service platform for a Zimbabwean city council (demo: **Bulawayo City Council**, all data fictional). Residents pay rates and water bills, view statements, get service alerts and report issues; municipal staff administer customers, billing, reconciliation, disconnections, water/refuse services, announcements and audit.

## Stack

- **Backend:** Laravel 13 (PHP 8.3), Blade + Tailwind CSS + Alpine.js
- **Database:** SQLite (dev) — PostgreSQL/MySQL for production
- **Queue/Cache/Session:** database driver (dev) — Redis for production
- **PDF statements:** barryvdh/laravel-dompdf
- **Payments:** `PaymentProviderInterface` adapters — MockPay (demo gateway with signed webhooks) and bank-transfer (proof upload + finance review)
- **Notifications:** contract-driven channels — in-app, email, SMS, WhatsApp (all mocks write deliveries + an inspectable outbox)
- **Municipal billing sync:** `MunicipalBillingProviderInterface` — mock adapter with sync-job tracking

## Quick start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # roles, templates + full demo dataset
npm run build                      # or: npm run dev
php artisan serve
```

Open http://localhost:8000

## Demo credentials (all fictional)

| Email | Password | Role |
|---|---|---|
| admin@example.com | Password123 | System Administrator |
| finance@example.com | Password123 | Finance Officer |
| support@example.com | Password123 | Customer Service Officer |
| content@example.com | Password123 | Content Administrator |
| owner@example.com | Password123 | Property Owner (3 properties) |
| resident@example.com | Password123 | Resident |
| tenant@example.com | Password123 | Tenant (authorized on owner's property) |

## Demo walkthrough

1. **Resident**: sign in as `resident@example.com` → dashboard shows outstanding/current/overdue → open a bill → **Pay now** → choose DemoPay → approve on the mock checkout → payment confirmed via signed webhook → receipt page → notification delivered.
2. **Bank transfer**: make a payment choosing "Bank transfer" → instructions shown → upload proof → `finance@example.com` approves under Payments/Reconciliation.
3. **Admin**: sign in as `admin@example.com` → `/admin` → search, billing, reconciliation CSV import, disconnections, water/refuse, announcements, reports (CSV export), integration monitor, audit log.
4. **Staff boundaries**: `support@example.com` sees customers/requests but not finance screens; `finance@example.com` sees money but not user admin; `content@example.com` manages announcements/water/refuse.

## Tests

```bash
php artisan test          # 91 backend tests
cd mobile && flutter test # mobile tests
```

Backend tests cover auth, lockout/suspension, IDOR/authorization, billing, payment state machine, webhook security (signature, replay, amount-mismatch), CSV reconciliation, API v1 (token auth, idempotent payments, device registration), and portal flows.

## Mobile app

A Flutter app for Android/iOS lives in `mobile/` — a first-class client of the REST API (`/api/v1`), not a web wrapper. Dashboard, bills, payments with provider-agnostic flow + proof upload, service requests with photos, water/refuse/announcements, notifications, statement PDFs, offline read cache, secure token storage. See `MOBILE.md`.

```bash
cd mobile
flutter pub get
flutter run   # defaults API to http://10.0.2.2:8899 (Android emulator → local dev server)
```

## Docs

- `ARCHITECTURE.md` — system architecture and module boundaries
- `TECHNOLOGY_DECISION.md` — stack selection rationale
- `IMPLEMENTATION_PLAN.md` — phased build plan
- `RBAC.md` — roles, permissions, scoping, enforcement, administration
- `API.md` — REST API v1 + webhook contracts and integration interfaces
- `DATABASE.md` — schema reference
- `PAYMENTS.md` — payment lifecycle, idempotency, webhooks, reconciliation
- `MOBILE.md` — Flutter app architecture, config, builds
- `SECURITY.md` — threat model and controls
- `INTEGRATIONS.md` — real vs mock vs not-implemented integration status
- `TESTING.md` — test coverage and how to run
- `DEPLOYMENT.md` — production deployment guide
- `PROJECT_AUDIT.md` — honest audit of completeness and gaps
