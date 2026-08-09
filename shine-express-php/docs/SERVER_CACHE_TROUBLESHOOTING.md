# Server shows old UI after upload — troubleshooting

If local works but **https://kdtechnoservices.com/shine-express** looks old (no login banner, no WhatsApp buttons, etc.), use this checklist.

---

## 1. Confirm the live site actually has new files

Open these URLs in a **private/incognito** window:

| URL | What you should see |
|-----|---------------------|
| https://kdtechnoservices.com/shine-express/login | Two-column login: **image panel on the left**, form on the right |
| https://kdtechnoservices.com/shine-express/assets/images/login-hero.jpg | Large cleaning photo (not 404) |
| https://kdtechnoservices.com/shine-express/assets/css/app.css | Search page source for `.auth-hero` and `.btn-whatsapp` |

If login-hero.jpg is **404**, upload:

```text
public/assets/images/login-hero.jpg
```

(~1.9 MB — easy to miss in FTP uploads)

---

## 2. Browser cache (most common)

LiteSpeed caches CSS/images for **7 days**. Your browser may still use old CSS without `.auth-hero` styles, so the banner image URL exists but **doesn't display**.

**Fix:**
- Hard refresh: **Ctrl+Shift+R** (Windows) or **Cmd+Shift+R** (Mac)
- Or open site in **Incognito / Private** mode
- Or clear browser cache for kdtechnoservices.com

After uploading the latest `functions.php`, CSS URLs get `?v=timestamp` and cache busts automatically on each deploy.

---

## 3. Upload the correct folder

Upload **`shine-express-php`** contents to:

```text
public_html/shine-express/
```

Must include:

```text
app/                          ← PHP code (controllers, views, services)
public/assets/css/app.css
public/assets/images/login-hero.jpg
database/migrations/006_booking_followup.sql  ← run in phpMyAdmin
.env                          ← on server only, not from git
```

**Do not** upload only `public/` — admin/API changes live under `app/`.

---

## 4. PHP OPcache (server cache)

Shared hosts cache compiled PHP. After uploading `app/`, clear cache:

- cPanel → **MultiPHP INI Editor** → OPcache → reset, or
- cPanel → **LiteSpeed Web Cache** → Purge All, or
- Re-save `public/index.php` (touch file) via File Manager

---

## 5. Database migration for new admin features

These need migration **006** on production MySQL:

- Red **Follow-up** rows on bookings
- Follow-up filter checkbox

Run in phpMyAdmin → SQL:

```sql
ALTER TABLE bookings
  ADD COLUMN requires_followup TINYINT(1) NOT NULL DEFAULT 0 AFTER cancellation_reason,
  ADD INDEX idx_bookings_followup (requires_followup);
```

(Skip if column already exists.)

WhatsApp admin buttons and login banner **do not** need this migration.

---

## 6. Verify enhancement features (after login as admin)

| Feature | Where to check |
|---------|----------------|
| Login banner | `/login` — left hero image |
| WhatsApp button | Admin → Bookings → **WhatsApp** link on a row |
| Follow-up filter | Admin → Bookings → **Follow-up only** checkbox |
| UPI | Customer app booking detail (needs `UPI_VPA` in `.env`) |

---

## 7. Quick self-test commands

From your computer:

```bash
curl -s "https://kdtechnoservices.com/shine-express/login" | grep auth-hero
curl -sI "https://kdtechnoservices.com/shine-express/assets/images/login-hero.jpg" | head -3
```

First should show `auth-hero`; second should be `HTTP/2 200`.

---

## If still broken

1. Note exact URL you open
2. Screenshot what you see vs local
3. Confirm whether **incognito** shows the new login layout
4. Re-upload `app/Helpers/functions.php` + `public/assets/` and purge LiteSpeed cache
