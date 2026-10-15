# Security Model

Financial + PII platform — controls map to OWASP risks.

## Authentication

- Breeze stack: registration (name, email, phone, national ID optional, user type), email verification, password reset, change password.
- Password rules: min 8 chars (Laravel `Password::defaults()`).
- Per-account lockout: `failed_login_attempts` counter → `locked_until` +15 min at 10 failures.
- Per-IP+email throttling via `RateLimiter` (5 attempts) on top of account lockout.
- Suspended (`suspended`) and locked users are rejected at login **and** on subsequent requests via `EnsureAccountActive` middleware (session terminated immediately).
- Session: database driver, 120 min lifetime, `regenerate` on login (Breeze default).

## Authorization — no IDOR

- Every object-level action goes through a policy: `PropertyPolicy`, `AccountPolicy`, `BillPolicy`, `PaymentPolicy`, `ServiceRequestPolicy`, `AttachmentPolicy`.
- Resident access = approved `property_users` link + verified property + per-capability pivot flags (`can_view_bills`, `can_view_account`, `can_pay`, `can_submit_requests`, `can_view_statements`). Tenants see only what the owner grants.
- Pending/rejected/suspended links grant no access.
- Staff access = permission matrix via `permission:*` middleware + `Gate::before` permission-slug resolution; `EnsureAccountActive` blocks suspended staff too.
- Route model binding always resolves the model first, then the policy decides — URL/ID manipulation returns `403`, never data.

## Payments

- Payment state machine in `PaymentService::transition` — terminal states cannot re-transition.
- Only a **verified webhook signature** or **staff reconciliation action** finalizes a payment; redirect success is never trusted.
- Webhooks: HMAC-SHA256 signature verification, `event_id` dedup, amount mismatch flagged not finalized, unknown references logged-and-ignored.
- Idempotency keys on initiation (unique constraint) — double-submit safe; `lockForUpdate` on state transitions.
- Money stored as integer cents; no float math anywhere.

## Uploads

- Private `local` disk only; files served via `AttachmentController::download` with policy check (`AttachmentPolicy::view`).
- MIME allowlist (jpg/png/pdf), extension check, 4 MB cap, hashed filenames, original name stored separately.
- `php`/executables rejected by extension + MIME checks.

## Web tier

- CSRF on all state-changing routes (Laravel default `web` group).
- `SecurityHeaders` middleware: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP allowing self + inline styles (Tailwind/vite needs; tighten in production with nonces).
- XSS: all output escaped via Blade `{{ }}`; announcement body rendered as text (`whitespace-pre-line`), not raw HTML.
- SQL injection: Eloquent/query builder throughout; no raw SQL interpolation.
- Throttling: `payments` POST (10/min), webhooks (60/min), password/login via RateLimiter.

## Audit & observability

- `AuditService::record()` captures actor, action, entity, old/new values, IP, user agent for: bill issue/status, property link approve/reject, payment events, reconciliation, disconnection transitions, announcement publish, user status/role changes, settings.
- `integration_logs` + `payment_webhooks` + `sync_jobs` record every external interaction; webhook failures retained for forensics.
- Secrets live in `.env` (gitignored); `.env.example` has placeholders only. Mock webhook secret generated per-env.

## Known gaps (documented in PROJECT_AUDIT.md)

- CSP allows `unsafe-inline` scripts (needed for inline chart/bootstrap scripts) — add nonces for production.
- MFA architecture ready (`two_factor_*` columns unused) but not implemented.
- No WAF/rate-limit by ASN — edge layer concern.
