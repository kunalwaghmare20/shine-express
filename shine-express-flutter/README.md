# Shine Express (Flutter)

Single **Flutter** app for **Customer** and **Staff** roles — Android now, iOS from the same codebase.

Backend: **PHP + MySQL** API in `../shine-express-php` (`/api/v1`).

This matches `Android-app-development.md` (auth/OTP, home, catalog, booking, history, support, loyalty, staff jobs, attendance, leave, dark mode). FCM push and full Google Maps navigation can be wired with production keys later (`POST /api/v1/devices` is ready).

**Google Maps:** see [`../shine-express-php/docs/GOOGLE_MAPS.md`](../shine-express-php/docs/GOOGLE_MAPS.md) for production API keys and Android/iOS setup.

## Prerequisites

1. PHP API running (`php -S localhost:8080 -t public public/router.php` in `shine-express-php`)
2. Migrations `002_api_tokens.sql` and `003_mobile_platform.sql` applied
3. Flutter SDK 3.16+

## Configure API URL

Default (Android emulator): `http://10.0.2.2:8080`

```bash
# Android emulator
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080

# iOS simulator
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8080

# Physical device (use your Mac LAN IP)
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8080
```

## Run

```bash
cd shine-express-flutter
flutter pub get
flutter run
```

## Demo logins

| Role | Email | Password |
|------|-------|----------|
| Customer | customer@shineexpress.com | Customer@123 |
| Staff | staff@shineexpress.com | Staff@123 |
| Manager | manager@shineexpress.com | Manager@123 |

## Why Flutter (not Expo)?

One codebase ships **Android + iOS**; Maps/camera/location plugins are mature; matches the MD tech choice. The older Expo folder `shine-express-mobile` is superseded by this app.
