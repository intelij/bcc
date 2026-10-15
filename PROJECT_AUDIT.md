# Project Audit — Digital City Rates, Water & Service Delivery Platform

**Date:** 2026-10-02 · **Auditor:** self-audit (Devin) · **Commit:** current working tree
**Test suite:** 91 backend tests + 3 Flutter tests — all passing · **Migrations:** clean `migrate:fresh` · **Seed:** complete demo dataset

## Executive summary

A working end-to-end municipal platform prototype. Resident flow (register → link → verify → bill → statement → pay → webhook confirm → notify) and admin flow (customers → billing → payments → reconciliation → disconnections → water/refuse → announcements → requests → reports → audit) are complete and tested. Every external integration is a realistic **mock adapter** behind a swap-ready contract — no real third-party integration is claimed.

---

## Completed

| Area | State | Notes |
|---|---|---|
| Architecture docs | ✅ | `ARCHITECTURE.md`, `TECHNOLOGY_DECISION.md`, `IMPLEMENTATION_PLAN.md` |
| Database | ✅ | ~30 tables, FKs, indexes, soft deletes, integer cents |
| Auth | ✅ | register/verify/reset/change, per-account lockout, IP throttle, suspend |
| RBAC | ✅ | 7 roles × 16 permissions, policies on all owned objects |
| Resident portal | ✅ | all 12 nav areas functional |
| Property linking + verification | ✅ | pending → staff approve/reject + audit |
| Billing + statements + PDF | ✅ | ledger math correct; dompdf |
| Payments | ✅ | state machine, idempotency, signed webhooks, replay-safe, allocations |
| Bank transfer workflow | ✅ | instructions + proof upload + finance approve/reject |
| Reconciliation | ✅ | CSV import, match-by-account/ref, manual allocate, unallocated register |
| Disconnections | ✅ | status pipeline + notifications |
| Water/refuse/announcements | ✅ | targeted by suburb/ward, schedules, interruption states |
| Service requests | ✅ | tickets, comments (public/internal), attachments |
| Notifications | ✅ | engine, per-event/channel templates, prefs, deliveries, outbox |
| Admin portal | ✅ | all modules + charts + global search |
| Reporting | ✅ | 7 filtered reports + CSV export |
| Audit log | ✅ | sensitive actions recorded with old/new + IP/UA |
| Automated tests | ✅ | auth, authz/IDOR, billing, payments, reconciliation, portal, admin |
| PWA | ✅ | manifest + service worker (no sensitive caching by design) |
| REST API v1 | ✅ | Sanctum tokens, 30+ endpoints, same policies as web, API tests incl. IDOR/idempotency/devices |
| Device registration | ✅ | `devices` table + API — push-token refresh + revoke on logout |
| Flutter app | ✅ | `mobile/` — auth, dashboard, bills, pay+proof, requests+photos, water/refuse/updates, notifications, prefs, offline cache |
| Branding | ✅ | BCC crest (`public/bcc.svg`) across web + app; palette `#143E62`/`#285D9A`/`#FFBF00` |
| Docs | ✅ | README, API, RBAC, DATABASE, PAYMENTS, MOBILE, SECURITY, INTEGRATIONS, TESTING, DEPLOYMENT |

## Integration status

**REAL:** bank-transfer CSV import (file workflow), PDF generation, internal audit/notification engine.

**MOCK:** DemoPay payment gateway, SMS, WhatsApp, email transport (`log` mailer), municipal billing provider.

**NOT IMPLEMENTED:** EcoCash/OneMoney/card real drivers, real municipal ERP API, FCM/APNs push send path (device registration API *is* implemented), MFA, OIDC/SSO, real payment-provider inbound webhook verification (mock signature scheme exists and is tested), biometric/PIN app lock, in-app PDF viewer.

## Findings

### HIGH
- **CSP `unsafe-inline`** — needed for inline chart/dropdown scripts; production should add per-request nonces. Mitigations: `X-Frame-Options`, `X-Content-Type-Options` present.
- **No MFA** — `two_factor_*` columns exist; TOTP flow not wired.
- **Provider webhook IP allowlist absent** — signature is the only guard for mock; real deployments should allowlist gateway IPs.

### MEDIUM
- **SQLite in dev only** — `lockForUpdate` is a no-op there; concurrency safety relies on Postgres/MySQL in prod (documented).
- **No Dusk/browser suite** — verified via feature tests + curl walkthrough; visual regression unverified.
- **Report queries un-indexed composites** — single-column indexes exist; add composites (`issue_date,status`, `paid_at,provider`) per production profile.
- **Statement email** uses `Mail::raw` attach — fine on `log` driver; untested against real transport.

### LOW
- Mobile bottom-nav covers 5 primary items; deep admin on mobile works via drawer but is dense.
- Flutter app: no FCM/APNs wiring yet (device registry ready); no biometric unlock; statement PDF opens externally.
- API pagination cursors use offset pagination — fine at this scale; consider cursor pagination for payment history at volume.
- Service worker is network-first shell only (intentional — no offline billing cache).
- Notifications `data.*` placeholders not yet exposed in template editor UI.
- Audit log has no retention/purge policy yet.

### NOT DONE (by design — awaiting real specs)
- Parking/licences/permits/smart-meter modules — architecture allows, not built.
- Real notification throughput guarantees — queue-ready, no provider SLA to test.

## Verification performed

- `php artisan test` — **91 passed** (incl. 12 API tests: token auth, IDOR, idempotent payment, devices, suspension)
- `php artisan migrate:fresh --seed` — clean run, fictional demo dataset
- `npm run build` — assets compile
- `php artisan route:list` — ~150 routes, all bound
- API curl walkthrough — meta/register/login/dashboard/bills/payments/water/requests/notifications/preferences all return real scoped data
- `flutter analyze` — clean · `flutter test` — 3 passed · `flutter build apk --debug` — compiles
- Live curl walkthrough: resident login → dashboard → bill → pay (mock checkout) → signed webhook → `successful`/`reconciled`/allocated → statement PDF → notifications; admin login → all 19 admin screens 200.
- `vendor/bin/pint` — style clean

## Recommendation

Ready for stakeholder demo and for real-provider onboarding. Before production: supply payment/billing/SMS/WhatsApp credentials + API specs, switch drivers, add CSP nonces + MFA, and run a load test on Postgres.
