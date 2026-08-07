# Shine Express PHP — Shared Hosting Deployment

No Composer. Upload files and point the document root at `public/`.

## Requirements

- PHP **8.1+** (cPanel → **MultiPHP Manager** — set for `shine-express`)
- MySQL or MariaDB
- Apache with `mod_rewrite` (typical cPanel)

> If you see `Parse error: unexpected '|'` the folder is still on PHP 7.x. Switch to 8.1/8.2/8.3 and retry.

## Steps

1. **Create a MySQL database** in cPanel and note host/user/password/name.
2. **Upload** the `shine-express-php` folder (FTP / File Manager).
3. **Document root** → `.../shine-express-php/public`  
   If you cannot change the docroot, uncomment the rewrite rules in the project-root `.htaccess`.
4. Copy `.env.example` → `.env` and set:

```env
APP_URL=https://your-domain.com
APP_DEBUG=false
DB_HOST=localhost
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

5. **Import schema** (phpMyAdmin → Import, or SSH):

```bash
mysql -u USER -p DB_NAME < database/migrations/001_schema.sql
```

6. **Seed** (SSH / terminal if available; otherwise run via a one-off protected PHP script):

```bash
php database/seeds/seed.php
```

7. Visit `https://your-domain.com/health` — expect `"database":"up"`.
8. Sign in: `admin@shineexpress.com` / `Admin@123` — **change this password** after first login.

### Subdirectory install (kdtechnoservices.com)

Full guide: **[DEPLOY_KDTECHNOSERVICES.md](DEPLOY_KDTECHNOSERVICES.md)**

Target: `https://kdtechnoservices.com/shine-express`

```env
APP_URL=https://kdtechnoservices.com/shine-express
APP_BASE_PATH=/shine-express
APP_DEBUG=false
```

Upload project to `public_html/shine-express/`. Root `.htaccess` rewrites into `public/`.

### Generic subdirectory

If the app lives at `https://domain.com/shine/public`:

- Set `APP_URL=https://domain.com/shine/public`
- Set `APP_BASE_PATH=/shine/public` (or `/shine` if using root rewrite)
- In `public/.htaccess`, set `RewriteBase` to match

## Ops checklist

| Item | Action |
|------|--------|
| Permissions | `storage/` writable by the web user |
| Secrets | Keep `.env` outside web access (already outside `public/`) |
| HTTPS | Force SSL in cPanel |
| Backups | Use cPanel MySQL + file backups |
| Cash payments | Recorded automatically when a booking is marked COMPLETED |

## Local development

```bash
cp .env.example .env
# start MySQL, import schema, seed
php -S localhost:8080 -t public public/router.php
php tests/run.php
```
