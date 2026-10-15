# Technology Decisions

## Selected stack

| Layer | Choice | Rationale |
|---|---|---|
| Language | **PHP 8.3** | Mature, huge talent pool in ZA/ZW, cheap hosting, proven for municipal/finance CRUD+workflow systems |
| Backend framework | **Laravel 13** | First-class ORM/migrations, queues, scheduler, auth, notifications, policies, validation, testing — the most batteries-included PHP framework; fastest path to a *working* financial platform, not a pile of glued libraries |
| Frontend | **Blade + Tailwind CSS + Alpine.js** | Server-rendered mobile-first UI; no SPA build complexity; progressive enhancement; ideal for portals with lots of forms/tables. Alpine gives interactivity without a JS framework tax |
| Admin portal | Same Blade stack, role-gated `/admin` | One codebase, one design system, one auth/session model |
| Database | **SQLite (dev/test)** → **MySQL or PostgreSQL (prod)** | Zero-config local dev; Eloquent/migrations keep prod target flexible |
| Cache / Queue | **Database driver (dev)** → **Redis (prod)** | Runnable with no infra; one-line config swap for scale |
| Auth | **Laravel Breeze** (sessions, email verification, password reset, rate-limited) + custom roles | Battle-tested flows; MFA-ready (Fortify-compatible upgrade path) |
| Authorization | **Laravel Policies + Gates** | Server-side IDOR protection on every resource |
| Payments | **Custom `PaymentProviderInterface` + adapters** (Mock, BankTransfer shipped) | EcoCash/OneMoney/Paynow/card plug in later without domain changes |
| Municipal integration | **`MunicipalBillingProvider` interface + mock + CSV import** | Real REST/CSV vendor adapters later |
| Notifications | Laravel Notifications + custom channels (`MockSms`, `MockWhatsApp`, mail log) | Template + delivery-tracking model already matches spec |
| PDF statements | **barryvdh/laravel-dompdf** | Pure-PHP PDF, no headless-browser infra |
| File storage | Laravel Filesystem, **private local disk** → S3-compatible in prod | Signed/authorized download routes |
| Search | SQL `LIKE`/indexes now; interface-ready for Meilisearch/Elastic later | Sufficient at municipal scale for v1 |
| Tests | **PHPUnit** (feature + unit), SQLite in-memory | Native Laravel testing stack |
| Charts | Chart.js (CDN-free vendored via npm) | Lightweight dashboards |
| PWA | `manifest.webmanifest` + minimal service worker | App-installable resident portal; offline = safe shell only, no sensitive caching |
| Containers | Dockerfiles/compose documented; SQLite or MySQL service | Simple municipal hosting |
| Logging/monitoring | Monolog (structured), Laravel `audit_logs`/`integration_logs` tables; Sentry optional | Ops visibility without vendor lock-in |

## Alternatives considered

- **Node/Next.js + Prisma** — great DX, but auth/queues/PDF/RBAC all become
  hand-assembled; slower to a trustworthy financial back office.
- **Django + DRF** — strong admin, but server-rendered UX + two-framework split
  costs more for this shape; PHP hosting in-region is cheaper.
- **Rails** — comparable fit; Laravel chosen for deeper local talent pool and
  hosting economics.
- **Microservices / SPA + API** — rejected: premature distribution for a
  monolithic municipal workload; modular monolith keeps transactional integrity
  across billing↔payment boundaries.

## Decision criteria applied

Development speed (single framework, convention-heavy), security maturity,
transactional integrity for money, low hosting cost/ops burden, African
developer availability, replaceable integrations, and a path to PWA/native
mobile without rewriting the backend.

## Explicit non-goals for v1

Real payment-gateway calls, real municipal ERP sync, real SMS/WhatsApp sends —
all behind mocks pending actual API specs and credentials (see `INTEGRATIONS.md`).
