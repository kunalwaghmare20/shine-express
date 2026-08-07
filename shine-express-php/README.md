# Shine Express (PHP)

Custom **PHP MVC + OOP** rebuild for shared hosting **without Composer**.

- PHP 8.1+, MySQL/MariaDB, Apache `mod_rewrite`
- Document root: **`public/`**
- Architecture: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Deploy: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- Deploy to kdtechnoservices.com: [docs/DEPLOY_KDTECHNOSERVICES.md](docs/DEPLOY_KDTECHNOSERVICES.md)
- WhatsApp before-service reminders: [docs/WHATSAPP_REMINDERS.md](docs/WHATSAPP_REMINDERS.md)
- Google Maps (Flutter): [docs/GOOGLE_MAPS.md](docs/GOOGLE_MAPS.md)

## Quick start

```bash
cp .env.example .env
# Configure DB_* in .env, then:
mysql -u root -p shine_express < database/migrations/001_schema.sql
php database/seeds/seed.php
php -S localhost:8080 -t public public/router.php
php tests/run.php
```

## Demo logins (after seed)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@shineexpress.com | Admin@123 |
| Branch Manager | manager@shineexpress.com | Manager@123 |
| Staff | staff@shineexpress.com | Staff@123 |
| Customer | customer@shineexpress.com | Customer@123 |

## Modules

| # | Module | Status |
|---|--------|--------|
| 1 | MVC core / folders | ✅ |
| 2 | Database schema + seed | ✅ |
| 3 | Auth + RBAC | ✅ |
| 4 | UI layouts | ✅ |
| 5 | Admin dashboard | ✅ |
| 6 | Customers | ✅ |
| 7 | Employees | ✅ |
| 8 | Services | ✅ |
| 9 | Bookings | ✅ |
| 10 | Cash payments on complete | ✅ |
| 11 | Reports | ✅ |
| 12 | Notifications | ✅ |
| 13 | Tests (`php tests/run.php`) | ✅ |
| 14 | Deployment docs | ✅ |

## Mobile (Flutter)

Customer + staff app: **`../shine-express-flutter`** (Android & iOS).

```bash
mysql -u root -p shine_express < database/migrations/002_api_tokens.sql
mysql -u root -p shine_express < database/migrations/003_mobile_platform.sql
# API docs: docs/MOBILE_API.md
```
