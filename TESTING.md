# Testing

## Run

```bash
php artisan test            # full suite — sqlite :memory:, isolated
vendor/bin/pint --test      # code style
npm run build               # asset compilation check
php artisan migrate:fresh --seed  # verify migrations + demo data
```

## Coverage (79 tests / ~180 assertions)

**Auth** (`tests/Feature/Auth/*`)
- Registration incl. property link request, weak-password rejection
- Login: rate-limit lockout, suspended rejection, locked-account rejection
- Password reset / email verification / profile (Breeze suite)

**Authorization / IDOR** (`tests/Feature/AuthorizationTest.php`)
- Resident→resident IDOR blocked on bills, payments, properties, statements
- Tenant capability flags enforced (`can_view_bills`, `can_pay`)
- Pending link grants no access
- Role boundaries: resident ✗ /admin, CS officer ✗ reconciliation, content admin ✗ payments, finance ✗ user management
- Suspended user session terminated; guest redirects

**Billing** (`tests/Feature/BillingTest.php`)
- Bill issue → items, totals, account balance
- NEW_BILL notification + email delivery recorded
- Overdue sweep marks bills + arrears
- Statement ledger math (opening/lines/closing)

**Payments** (`tests/Feature/PaymentTest.php`)
- Initiate → pending with provider reference
- Signed webhook → successful → allocated → bill paid → account balance 0
- Bad signature → 403, no transition
- Duplicate `event_id` → replay safe (idempotent)
- Amount mismatch → recorded failed, payment stays pending
- Failed/cancelled webhook transitions
- Idempotency key → same payment returned
- Overpayment → account credit; partial → `partially_paid`
- Unknown payment UUID → logged + ignored

**Reconciliation** (`tests/Feature/ReconciliationTest.php`)
- CSV match by account number / embedded reference
- Unmatched → `unallocated`
- Manual allocate → reconciled + account credit

**Portal** (`tests/Feature/PortalTest.php`)
- Dashboard, property linking, service request submit + notification
- Announcements only published; targeted water interruptions; refuse; statement PDF download

**Admin** (`tests/Feature/AdminTest.php`)
- Dashboard, property-link approval + audit + notification, announcement publish RBAC, sync job run, global search

## Manual E2E (verified)

register → verify → link property → approve (staff) → bill → statement PDF → mock checkout pay → signed webhook → receipt → notification → water/refuse pages → service request → admin reconcile → reports → audit log.

## Known gaps

- No browser-level (Dusk) suite — feature tests cover HTTP layer, live flows curl-verified.
- Notification template rendering edge cases (per-event `data.*` placeholders) minimal.
- Concurrency test for `lockForUpdate` payment transitions simulated via idempotency, not true parallel requests.
