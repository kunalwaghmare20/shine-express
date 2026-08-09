# Deploy to kdtechnoservices.com/shine-express

Target URL: **https://kdtechnoservices.com/shine-express**

This guide assumes typical **cPanel** shared hosting (PHP + MySQL, no Composer).

---

## 0. Set PHP version (required)

Shine Express needs **PHP 8.1+**.

cPanel → **MultiPHP Manager** → select `shine-express` (or the domain) → **PHP 8.1 / 8.2 / 8.3** → Apply.

A `Parse error: unexpected '|'` means the folder is still on PHP 7.x.

---

## 1. Create MySQL database (cPanel)

1. **MySQL® Databases** → create database (e.g. `kdtechno_shine`)
2. Create a MySQL user and password
3. **Add user to database** with **ALL PRIVILEGES**
4. Note the full names (cPanel often prefixes with `kdtechno_`)

---

## 2. Upload the PHP project

Upload the entire `shine-express-php` folder contents to:

```text
public_html/shine-express/
```

So you have:

```text
public_html/shine-express/
  app/
  database/
  public/
  storage/
  .htaccess          ← already set for /shine-express/
  .env               ← create this (see step 3)
  ...
```

Use **FTP**, **File Manager → Upload → Extract**, or Git if the host supports it.

**Do not** leave `.env` world-readable. Keep `storage/` writable (chmod `775` or `755` as needed).

---

## 3. Create `.env` on the server

Copy from `.env.kdtechnoservices.example` (or rename that file to `.env`) and fill DB credentials:

```env
APP_NAME="Shine Express"
APP_URL="https://kdtechnoservices.com/shine-express"
APP_BASE_PATH="/shine-express"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Kolkata
SESSION_NAME=shine_express_session

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_cpanel_db_name
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password
DB_CHARSET=utf8mb4
```

`APP_BASE_PATH` must match the URL path (`/shine-express`) so routing and links work under the subdirectory.

---

## 4. Import database

In **phpMyAdmin** → select your database → **Import**, run in order:

1. `database/migrations/001_schema.sql`
2. `database/migrations/002_api_tokens.sql` (mobile API tokens)
3. `database/migrations/003_mobile_platform.sql` (OTP, loyalty, leave, etc.)
4. `database/migrations/004_whatsapp_reminders.sql` (WhatsApp logs)
5. `database/migrations/005_service_reminder_days.sql` (per-service rebook days)
6. `database/migrations/006_booking_followup.sql` (low-rating follow-up flag — **required for Phase 3**)

If the site is **already live**, you only need to run **new** migrations (e.g. `006_booking_followup.sql`) in phpMyAdmin → SQL tab — not the full list again.

---

## 5. Seed demo data

If SSH / Terminal is available:

```bash
cd ~/public_html/shine-express
php database/seeds/seed.php
```

If not, ask hosting support to run that once, or run seed from a temporary protected script and delete it afterward.

Demo logins (change passwords after go-live):

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@shineexpress.com | Admin@123 |
| Staff | staff@shineexpress.com | Staff@123 |
| Customer | customer@shineexpress.com | Customer@123 |

---

## 6. Verify

Open these in a browser:

| URL | Expect |
|-----|--------|
| https://kdtechnoservices.com/shine-express/health | JSON with `"database":"up"` |
| https://kdtechnoservices.com/shine-express/login | Login page |
| https://kdtechnoservices.com/shine-express/admin | After admin login |

If you get **404** on every page except assets:

- Confirm `.htaccess` exists in `shine-express/` and `shine-express/public/`
- Confirm **mod_rewrite** is on (usually yes on cPanel)
- In cPanel → **Apache Handlers** / host docs: AllowOverride for `.htaccess`

If CSS is missing, confirm `APP_URL` has **no** trailing slash and matches the live URL.

---

## 7. Point the Flutter app at production

API base (no trailing slash):

```text
https://kdtechnoservices.com/shine-express
```

Example:

```bash
flutter run --dart-define=API_BASE_URL=https://kdtechnoservices.com/shine-express
```

Endpoints become:

```text
https://kdtechnoservices.com/shine-express/api/v1/auth/login
```

Rebuild a release APK/IPA with that `API_BASE_URL` for production devices.

---

## 8. Security checklist

- [ ] `APP_DEBUG=false`
- [ ] Change all demo passwords
- [ ] Force HTTPS in cPanel
- [ ] `storage/` not publicly listable (covered by `storage/.htaccess`)
- [ ] Backups: files + MySQL in cPanel

---

## Folder / rewrite summary

| Setting | Value |
|---------|--------|
| Upload path | `public_html/shine-express/` |
| Public entry | `public/index.php` (via root `.htaccess`) |
| RewriteBase (root) | `/shine-express/` |
| RewriteBase (public) | `/shine-express/public/` |
| `APP_URL` | `https://kdtechnoservices.com/shine-express` |
| `APP_BASE_PATH` | `/shine-express` |

---

## Optional: hide `/public` in a different layout

If your host lets you set the subdomain/folder document root to `.../shine-express/public` only, upload the full project one level above web root and set the docroot to `public/`. Then still set:

```env
APP_URL=https://kdtechnoservices.com/shine-express
APP_BASE_PATH=/shine-express
```

and in `public/.htaccess` use `RewriteBase /shine-express/`.
