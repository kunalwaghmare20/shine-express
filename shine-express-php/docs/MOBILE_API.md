# Shine Express Mobile API

Base path: `/api/v1`

Auth: `Authorization: Bearer <token>`

Backend: **PHP + MySQL** (`shine-express-php`). Mobile client: **Flutter** (`shine-express-flutter`) — one codebase for Android + iOS.

## Migrations

```bash
mysql -u root -p shine_express < database/migrations/002_api_tokens.sql
mysql -u root -p shine_express < database/migrations/003_mobile_platform.sql
```

## Endpoints

| Method | Path | Who | Purpose |
|--------|------|-----|---------|
| POST | `/auth/register` | public | Customer register |
| POST | `/auth/login` | public | Login (customer/staff) |
| POST | `/auth/otp/send` | public | Email OTP (debug returns `debugOtp`) |
| POST | `/auth/otp/verify` | public | Verify OTP |
| POST | `/auth/forgot-password` | public | Reset token (debug: `debugToken`) |
| POST | `/auth/reset-password` | public | Set new password |
| GET | `/auth/me` | auth | Current user |
| POST | `/auth/logout` | auth | Revoke token |
| POST | `/auth/profile` | auth | Update profile |
| POST | `/devices` | auth | Register FCM device token |
| GET | `/home` | auth | Categories, offers, featured, bookings |
| GET | `/search?q=` | auth | Search services |
| GET | `/catalog` | auth | Services + branches |
| GET | `/services/{id}` | auth | Detail, packages, FAQs, reviews |
| GET/POST | `/addresses` | customer | List / add address |
| POST | `/addresses/{id}` | customer | Update address |
| GET/POST | `/bookings` | customer | List / create |
| GET | `/bookings/{id}` | customer | Detail |
| POST | `/bookings/{id}/complete` | customer | Complete + optional review |
| POST | `/bookings/{id}/review` | customer | Review completed job |
| GET | `/loyalty` | customer | Points + referral code |
| GET/POST | `/support/tickets` | auth | Complaints / tickets |
| GET | `/staff/dashboard` | staff | Job counts + attendance |
| POST | `/staff/attendance/check-in` | staff | Check in (+ lat/lng) |
| POST | `/staff/attendance/check-out` | staff | Check out |
| GET/POST | `/staff/leaves` | staff | List / apply leave |
| GET | `/jobs` | staff | Assigned jobs |
| GET | `/jobs/{id}` | staff | Job detail (+ notes, checklist, photos) |
| POST | `/jobs/{id}/accept` | staff | Accept / progress |
| POST | `/jobs/{id}/reject` | staff | Reject assignment |
| POST | `/jobs/{id}/start` | staff | Start job |
| POST | `/jobs/{id}/complete` | staff | Complete job |
| POST | `/jobs/{id}/photos` | staff | Upload BEFORE/AFTER (`multipart`) |
| POST | `/jobs/{id}/notes` | staff | Add job note |
| POST | `/jobs/{id}/checklist` | staff | Replace checklist `{ items: [{label,isDone}] }` |

## Flutter app

See `../shine-express-flutter/README.md`.
