# Go-Live Setup — Steps 3–8 (After PHP Upload)

Use this after you have uploaded the updated PHP files to **https://kdtechnoservices.com/shine-express** and run migration `006_booking_followup.sql` (if not done yet).

Edit the server file:

```text
public_html/shine-express/.env
```

(cPanel → **File Manager** → right-click → **Edit**)

---

## Step 3 — UPI payments (required for in-app pay)

### What you need
Your **business UPI ID** (Virtual Payment Address), e.g.:
- `shineexpress@okaxis`
- `yourname@okicici`
- `9876543210@paytm`

Get it from: Google Pay for Business, PhonePe for Business, Paytm for Business, or your bank’s merchant UPI.

### Add to `.env`

```env
UPI_ENABLED=true
UPI_VPA=YOUR_ACTUAL_UPI_ID@okaxis
UPI_MERCHANT_NAME=Shine Express
```

Replace `YOUR_ACTUAL_UPI_ID@okaxis` with your real VPA. `UPI_MERCHANT_NAME` is the name customers see in GPay/PhonePe.

### Verify
1. Log in to the mobile app as a **customer**
2. Open a booking in status CONFIRMED / STARTED (unpaid)
3. You should see **Pay via UPI**
4. Or call (with customer token):

```text
GET https://kdtechnoservices.com/shine-express/api/v1/bookings/payment-config
```

Response should include `"enabled": true` and your `upiId`.

---

## Step 4 — WhatsApp automated messages (optional)

Two modes:

### Option A — Testing / no Meta account (recommended first)

Messages are **logged**, not sent. Admin **wa.me** buttons still work (they use customer phone, no API).

```env
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=log
```

Check: `storage/logs/whatsapp.log` on the server after a booking status change.

### Option B — Real WhatsApp Cloud API (Meta)

1. Go to [Meta for Developers](https://developers.facebook.com/) → create an app → add **WhatsApp** product
2. In WhatsApp → **API Setup**, note:
   - **Phone number ID** → `WHATSAPP_PHONE_NUMBER_ID`
   - **Temporary access token** (or permanent System User token) → `WHATSAPP_ACCESS_TOKEN`
3. Add a test customer phone in Meta console (until app is approved)

```env
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=cloud
WHATSAPP_ACCESS_TOKEN=EAAxxxx...
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_TEMPLATE_LANG=en
```

Optional template for **rebook reminders** (pipe-separated body variables):

```env
WHATSAPP_TEMPLATE_NAME=booking_update
```

**WhatsApp broadcast** (Admin → WhatsApp broadcast) needs a separate **Marketing** template.
Meta will reject free-form text if the customer has not messaged you in the last 24 hours.

Example template body in Meta Business Manager:

```text
Hello {{1}},

{{2}}

— Shine Express
```

```env
WHATSAPP_BROADCAST_TEMPLATE_NAME=customer_broadcast
WHATSAPP_BROADCAST_TEMPLATE_LANG=en
WHATSAPP_BROADCAST_TEMPLATE_PARAMS=first_name,message
```

**Support number** (shown in app / rebook messages):

```env
SUPPORT_PHONE=919673522737
SUPPORT_WHATSAPP=919673522737
```

Use country code **91** for India, no `+`.

---

## Step 5 — Push notifications / FCM (optional)

Push needs **both** server credentials **and** Flutter Firebase config.

### 5a. Firebase project (one-time)

1. [Firebase Console](https://console.firebase.google.com/) → **Add project**
2. **Add Android app**
   - Package name: `com.shineexpress.shine_express_app`
3. Download **google-services.json**
4. Replace local file (do **not** commit to public Git):

```text
shine-express-flutter/android/app/google-services.json
```

5. Firebase → **Project settings** → **Service accounts** → **Generate new private key** (JSON)

### 5b. Server `.env`

**Option 1 — JSON file (easier on cPanel)**

Upload the service account JSON to:

```text
public_html/shine-express/storage/fcm-service-account.json
```

Set permissions so it is **not** web-accessible (inside `storage/` is fine). Then:

```env
FCM_ENABLED=true
```

(The app reads `storage/fcm-service-account.json` when present.)

**Option 2 — `.env` fields**

From the JSON file:

```env
FCM_ENABLED=true
FCM_PROJECT_ID=your-project-id
FCM_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
FCM_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----\n"
```

Use `\n` for line breaks inside the key string.

### 5c. Verify

1. Install app on a phone (after APK build with real `google-services.json`)
2. Log in → app registers token via `POST /api/v1/devices`
3. Change booking status in admin → customer/staff should get push
4. Server log: `storage/logs/fcm.log`

If `FCM_ENABLED=false`, in-app notifications still work; push is skipped.

---

## Step 6 — Staff earnings bonus (optional)

Default is **₹200 per completed job** if you omit this.

```env
STAFF_PER_JOB_BONUS=200
```

Change `200` to whatever bonus you pay per completed job. Staff see estimates in the app **Earnings** tab.

---

## Step 7 — Build production APK

On your Mac (from `shine-express-flutter/`):

```bash
flutter pub get
flutter build apk --release \
  --dart-define=API_BASE_URL=https://kdtechnoservices.com/shine-express
```

APK output:

```text
shine-express-flutter/build/app/outputs/flutter-apk/app-release.apk
```

Install on Android (enable “Install unknown apps” for your file manager if needed).

### Without FCM (UPI only)

You can build now with the **placeholder** `google-services.json` — UPI, maps, offline, tracking all work. Push will not deliver until Step 5 is complete.

---

## Step 8 — Replace Firebase file before push goes live

When Step 5 is ready:

1. Replace `android/app/google-services.json` with your real Firebase file
2. Rebuild APK (same command as Step 7)
3. Reinstall on test devices

---

## Quick test matrix

| Feature | Test |
|---------|------|
| UPI | Customer → booking detail → Pay via UPI → complete in GPay → payment recorded |
| wa.me admin | Admin → Bookings → WhatsApp link opens chat with customer |
| WhatsApp API | Change booking status → check `storage/logs/whatsapp.log` or customer WhatsApp |
| Push | Login on phone → admin updates booking → notification on device |
| Follow-up | Customer submits 1–2 star review → admin booking row turns red |
| Tracking | Staff ON_THE_WAY → customer booking map shows staff marker |
| Earnings | Staff app → Earnings tab shows job counts |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| UPI button hidden | Set `UPI_VPA` in `.env`; ensure booking unpaid |
| WhatsApp not sending | Use `WHATSAPP_PROVIDER=log` first; check `storage/logs/whatsapp.log` |
| Push not received | `FCM_ENABLED=true`, real `google-services.json`, reinstall APK |
| API 404 from app | Rebuild with correct `API_BASE_URL` (no trailing slash) |
| Follow-up flag error | Run `006_booking_followup.sql` in phpMyAdmin |

---

## Minimum to go live today

If you want the fastest path:

1. ✅ Migration `006_booking_followup.sql`
2. ✅ `UPI_VPA` + `UPI_MERCHANT_NAME` in `.env`
3. ✅ `WHATSAPP_PROVIDER=log` (skip Meta for now)
4. ✅ `FCM_ENABLED=false` (skip push for now)
5. ✅ Build APK with production `API_BASE_URL`

Add WhatsApp Cloud + FCM later without changing app code — only `.env` and a rebuild for FCM.
