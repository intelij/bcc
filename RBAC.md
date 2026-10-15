# Role-Based Access Control

The platform enforces authorization **server-side** on every request — web
(Blade) and API (Sanctum tokens) share the same checks. Frontend visibility is
a display concern only; hiding a button is never treated as security.

## Model

```
users ──< user_roles >── roles ──< permission_role >── permissions
users ──< property_users >── properties ── municipal_accounts
```

Two orthogonal layers:

1. **Permissions** (`permissions.slug`, e.g. `payments.allocate`) — *what kind
   of action* a user may perform. Enforced via `user->can(permission)`, the
   `permission:` middleware, and `Gate::allows` for staff.
2. **Resource scope** (`property_users` link capabilities + ownership) —
   *which objects* a user may touch. Enforced by Policies
   (`PropertyPolicy`, `AccountPolicy`, `BillPolicy`, `PaymentPolicy`,
   `ServiceRequestPolicy`, `AttachmentPolicy`, …) on every owned resource.

A resident with `bills.view` can still only view bills on properties where
their **approved** `property_users` link has `can_view_bills = 1`. A tenant
link can grant any subset of: `can_view_bills`, `can_view_account`, `can_pay`,
`can_submit_requests`, `can_view_statements`. Pending/rejected links grant
nothing (`accessibleProperties()` filters on `status = approved`).

## Roles

| Role | Type | Scope |
|---|---|---|
| `resident` | resident | own linked properties/accounts |
| `property_owner` | resident | own properties incl. tenant access management |
| `tenant` | resident | only explicitly-linked properties, per-link capability flags |
| `customer_service` | staff | residents/properties/accounts/bills/payments/requests — read + ticket work; no financial mutation |
| `finance` | staff | billing, payments, reconciliation, statements, reports; no user/system admin |
| `content_admin` | staff | announcements, water/refuse schedules |
| `admin` | staff | full system incl. users, roles, integrations, audit |

Users can hold **multiple roles** — effective permission is the union
(`User::can()` aggregates all role permissions). `multi@example.com` in the
demo data holds `customer_service` + `finance` to prove this.

## Permissions

Naming convention: `<module>.<action>`. Defined in
`database/seeders/RolePermissionSeeder.php` — the authoritative list.
Staff modules: `users`, `properties`, `accounts`, `bills`, `payments`,
`payment_proofs`, `disconnections`, `water`, `refuse`, `announcements`,
`requests`, `notifications`, `reports`, `integrations`, `settings`,
`audit_logs`. Residents get `portal.*` style capabilities through property
links rather than global staff permissions.

## Enforcement points

- **Middleware** `permission:{slug}` on admin routes — controller-level gate.
- **Policies** `Gate::authorize()`/`authorize()` in controllers — per-object
  check for owned resources. `abort(403)` on failure.
- **Policy scope for lists** — index endpoints query through
  `accessibleProperties()`/`serviceRequests()`, never `Model::all()`.
- **API** — identical policies; `auth:sanctum` resolves the user then
  policies/middleware behave exactly as for web sessions.
- **Suspension** — `EnsureAccountActive` revokes API tokens / logs out web
  sessions the moment an account is suspended mid-session.

## Administration UI

`/admin/roles` — create/edit/disable roles, toggle permissions by category,
view user count + effective permission list. `/admin/users/{id}` — assign
roles, suspend/restore accounts, view audit history. Deletion of `admin` and
system roles is blocked.

## Auditing

Every security-relevant mutation goes through `AuditService::record($event,
$subject, $oldValues, $newValues)` → `audit_logs` (actor, action, old/new,
IP, user-agent, timestamp). Covered events: role create/update/disable,
permission grant/revoke, role assign/remove, user suspend/restore, property
link approve/reject, payment allocation, disconnection transitions.

## Testing

`tests/Feature/AuthorizationTest.php` + `tests/Feature/ApiTest.php` cover:
resident↔resident IDOR (bills, properties, payments, statements), tenant
capability flags (view_bills/pay denied), pending links grant nothing,
role-boundary violations (finance↛users, support↛reconciliation,
content↛payments), suspension mid-session (web + token), and API 401/403.
