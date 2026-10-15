# Mobile Application — Flutter

First-class client of the platform's REST API (`/api/v1`). Android and iOS,
built from one codebase in `mobile/`. The app contains **no business rules and
no provider logic** — every screen renders what the API returns, and the
backend remains the sole authority for data and access control.

## Architecture

```
lib/
├── core/
│   ├── env.dart            # API base URL via --dart-define (dev/staging/prod)
│   ├── api_client.dart     # Dio wrapper: Bearer injection, error mapping,
│   │                       #   cached-GET fallback for flaky connectivity
│   ├── token_storage.dart  # Keychain/Keystore token + install device ID
│   ├── money.dart          # integer-cents formatting (USD)
│   └── theme.dart          # brand theme (crest palette #143E62/#285D9A/#FFBF00)
├── state/providers.dart    # Riverpod: auth session, API client
├── router.dart             # go_router with auth-state redirects
├── shell/app_shell.dart    # bottom navigation (Home/Bills/Pay/Requests/Account)
├── widgets/remote_view.dart# loading/error/empty/offline states
└── features/               # auth, home, bills, payments, requests,
                            # city (water/refuse/announcements),
                            # notifications, properties, profile
```

### Decisions

| Concern | Choice | Why |
|---|---|---|
| State | `flutter_riverpod` | compile-safe DI + reactive state, minimal boilerplate |
| Navigation | `go_router` | declarative routes + auth redirect |
| Networking | `dio` | interceptors for auth token + error normalization |
| Token storage | `flutter_secure_storage` | Keychain (iOS) / Keystore+AES-GCM (Android) |
| Read cache | `shared_preferences` | offline view of last-fetched GET payloads only |
| File upload | `image_picker` + `file_picker` + `MultipartFile` | camera/gallery/PDF proofs |
| Money | integer cents everywhere | no floating-point money |

## Screens

Splash (session restore) → Login/Register/Forgot → Home dashboard (balances,
quick actions, water alerts, latest bills) → Bills (list/detail) → Pay
(provider picker, amount, status polling, bank-transfer proof upload) →
Requests (list/create with photos/detail+comments) → Water, Refuse, Updates →
Notifications → Properties (list + link flow) → Account (statements PDF
download, notification preferences per event×channel, sign out).

## API usage

Auth is `Authorization: Bearer <sanctum token>`; the token is issued by
`POST /auth/login` or `/auth/register` with a `device_name` label. On login the
app registers its install (`POST /devices`) so a future push provider can
target it. `POST /auth/logout` + `DELETE /devices/{id}` on sign-out revokes
both the token and the push registration.

Payments use a **client-generated UUID idempotency key** created once per pay
form — double-taps and network retries hit the same server attempt. Payment
status is *server-verified*: the app polls `GET /payments/{uuid}`; the signed
webhook is what actually transitions the payment.

## Run

```bash
cd mobile
flutter pub get

# Android emulator → host machine API:
flutter run

# Physical device / iOS simulator / staging:
flutter run --dart-define=API_BASE_URL=http://<host-ip>:8899
flutter build apk --release --dart-define=API_BASE_URL=https://api.example.com
```

`API_BASE_URL` defaults to `http://10.0.2.2:8899` (Android emulator loopback to
`php artisan serve`).

## Environments

| Env | Flag |
|---|---|
| dev (default) | `--dart-define=APP_ENV=dev` |
| staging | `--dart-define=APP_ENV=staging --dart-define=API_BASE_URL=...` |
| production | `--dart-define=APP_ENV=prod --dart-define=API_BASE_URL=...` |

Cleartext HTTP is enabled **for debug builds only** via an Android manifest
placeholder (`build.gradle.kts`); release builds use HTTPS only. iOS release
builds must serve HTTPS (ATS default) — no ATS exception is configured.

## Security notes

- Token in secure storage; never in `shared_preferences` or logs.
- The offline cache stores only GET responses the user already saw, and is
  cleared on logout.
- iOS `Info.plist` declares camera/photo permissions with user-facing reasons.
- Android release signing is not committed — see `DEPLOYMENT.md`/`android/`
  guidance; create a keystore locally and reference it via `key.properties`
  (gitignored).

## Known limitations

- Push notifications: the `devices` API + registration is implemented; the
  actual FCM/APNs sender is not wired (no credentials in this demo). Add
  `firebase_messaging` + a `PushProvider` on the backend when credentials
  exist.
- Statement PDFs are downloaded then opened externally (`open_filex`) — no
  in-app PDF viewer.
- Biometric/PIN unlock: token storage is already secure; add
  `local_auth` + a re-auth screen if required by policy.
