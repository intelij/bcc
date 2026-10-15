# Implementation Plan

Greenfield build executed in phases. Each phase: implement → test → fix →
security review → update docs.

| # | Phase | Deliverables | Status |
|---|-------|--------------|--------|
| 1 | Architecture & tech selection | `ARCHITECTURE.md`, `TECHNOLOGY_DECISION.md`, this file | ✅ |
| 2 | Scaffolding | Laravel app, Breeze(Blade), Tailwind, SQLite, `.env.example`, PWA shell | ✅ |
| 3 | Database & domain models | 7 grouped migrations (~30 tables), Eloquent models, relations | ✅ |
| 4 | Auth & RBAC | register/login/verify/reset, roles, policies, account lock/suspend | ✅ |
| 5 | Resident portal | dashboard, properties, bills, statements, water, refuse, requests, notifications, profile/settings | ✅ |
| 6 | Billing | bills+items, meter readings, balance calc, statements w/ PDF | ✅ |
| 7 | Payments & reconciliation | provider interface, mock + bank adapters, signed webhooks, idempotency, state machine, allocations, CSV import | ✅ |
| 8 | City services | announcements CMS, water interruptions, refuse schedules | ✅ |
| 9 | Notifications | engine, templates, in-app/email/SMS(mock)/WhatsApp(mock), delivery tracking, preferences | ✅ |
| 10 | Municipal admin | users, properties, accounts, billing, payments, reconciliation, disconnections, requests, water, refuse, announcements, integrations, settings | ✅ |
| 11 | Reporting & audit | dashboards+charts, filtered reports+CSV export, global search, audit logs | ✅ |
| 12 | Security hardening | rate limits, upload validation, headers, policy sweep | ✅ |
| 13 | Seed data & tests | fictional ZW demo data, demo users, PHPUnit suites incl. payment/authz edge cases | ✅ |
| 14 | Performance | indexes, eager loading, pagination, queued jobs | ✅ |
| 15 | Documentation | README, API, SECURITY, INTEGRATIONS, TESTING, DEPLOYMENT | ✅ |
| 16 | Final audit | `PROJECT_AUDIT.md` with severity-rated findings | ✅ |
| 17 | REST API v1 | Sanctum token auth, 30+ endpoints, same policies as web, `devices` table for push, API tests | ✅ |
| 18 | Flutter mobile app | `mobile/` — auth, dashboard, bills, payments+proof upload, requests+photos, water/refuse/announcements, notifications, prefs, offline GET cache | ✅ |

## Mobile/API phase notes

- API mounted at `/api/v1` (`routes/api.php` via `bootstrap/app.php`);
  Sanctum personal-access tokens; web policies reused — no parallel authz.
- `EnsureAccountActive` returns JSON 403 for API requests and revokes tokens.
- Flutter app uses crest branding (`assets/bcc.svg`, `#143E62`/`#285D9A`/
  `#FFBF00`) matching the portal rebrand.
- Push *registration* is implemented; FCM/APNs send path is the documented
  next step (MOBILE.md).

## Scope honesty

Shipped but **mock**: all external providers (payments, SMS, WhatsApp, email
transport, municipal ERP). The *workflows* are real end-to-end inside this app;
only the external leg is simulated. Clearly marked in `INTEGRATIONS.md` and
`PROJECT_AUDIT.md`.
