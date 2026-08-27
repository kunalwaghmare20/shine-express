# WhatsApp rebook reminders (per service)

After a customer **completes** a service, Shine Express can WhatsApp them after **N days** (set on that service) to book their **next appointment**.

Business WhatsApp: **919673522737** (`SUPPORT_WHATSAPP`)

---

## Flow

1. Super Admin sets **Rebook reminder (days)** when adding/editing a service  
2. Customer booking is marked **COMPLETED**  
3. Cron / Admin runs daily  
4. If `completion date + reminder_days = today` → send WhatsApp + in-app notification  

Example: Pest Control with **30** days → completed on 1 Aug → reminder on **31 Aug**.

Set **0** on a service to disable reminders for it.

---

## Database

```bash
mysql -u USER -p DB_NAME < database/migrations/004_whatsapp_reminders.sql
mysql -u USER -p DB_NAME < database/migrations/005_service_reminder_days.sql
```

---

## Admin

- **Services → Add / Edit** → field **Rebook reminder (days after completed service)**  
- **WhatsApp reminders** (`/admin/reminders`) → due list + **Send due rebook reminders now**

---

## Message (default)

```text
Hello Priya,

Thank you for choosing Shine Express for your Sofa Cleaning service (booking BK-10021).

It has been 30 days since your last service — now is a great time to book your next appointment
so your space stays fresh and protected.

Reply on WhatsApp or message us at 919673522737 to schedule:
https://wa.me/919673522737

— Shine Express
```

Customize with `WHATSAPP_REBOOK_MESSAGE` placeholders: `{name}` `{service}` `{booking}` `{days}` `{admin_whatsapp}` `{wa_link}`

---

## `.env`

```env
SUPPORT_WHATSAPP=919673522737
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=log
```

Use `cloud` or `webhook` for real delivery (see previous provider notes). Start with `log` to test.

Admin **WhatsApp broadcast** does **not** use `WHATSAPP_TEMPLATE_NAME`. For Cloud API delivery to customers outside the 24-hour window, approve a Marketing template and set:

```env
WHATSAPP_BROADCAST_TEMPLATE_NAME=customer_broadcast
WHATSAPP_BROADCAST_TEMPLATE_PARAMS=first_name,message
```

---

## Cron

```bash
/usr/bin/php /home/USER/public_html/shine-express/database/cron/send_service_reminders.php
```

Run daily (e.g. 09:00).
