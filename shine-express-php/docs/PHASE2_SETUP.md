# Phase 2 Setup — UPI Payments & Push / WhatsApp Alerts

Phase 2 adds:

1. **UPI intent payments** in the Flutter customer app (zero gateway fee)
2. **FCM push notifications** via PHP when booking status changes
3. **WhatsApp Cloud API alerts** on key booking statuses (when enabled)

---

## 1. UPI Payments (Server)

Add to `.env` on production:

```env
UPI_ENABLED=true
UPI_VPA=yourbusiness@okicici
UPI_MERCHANT_NAME=Shine Express
```

| Variable | Description |
|----------|-------------|
| `UPI_VPA` | Your business UPI ID (e.g. `shineexpress@okaxis`) |
| `UPI_MERCHANT_NAME` | Payee name shown in UPI apps |
| `UPI_ENABLED` | Set `false` to hide UPI pay button in app |

### API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/bookings/payment-config` | Public UPI config for logged-in customer |
| POST | `/api/v1/bookings/{id}/pay-upi` | Record payment after UPI app returns |

**POST body:**

```json
{
  "transactionRef": "SE-20260808-ABC123",
  "transactionId": "1234567890",
  "status": "SUCCESS"
}
```

Booking detail API now includes `payment`, `canPayUpi`, and `upi` fields.

---

## 2. WhatsApp Status Alerts (Server)

Uses existing `WhatsAppService`. Set in `.env`:

```env
WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=cloud
WHATSAPP_ACCESS_TOKEN=your_meta_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
```

**Statuses that trigger WhatsApp to the customer:**

- CONFIRMED
- ASSIGNED (also when staff are assigned)
- ON_THE_WAY
- COMPLETED

For testing without Meta API, use `WHATSAPP_PROVIDER=log` — messages go to `storage/logs/whatsapp.log`.

---

## 3. FCM Push Notifications (Server)

Add to `.env`:

```env
FCM_ENABLED=true
FCM_PROJECT_ID=your-firebase-project-id
FCM_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
FCM_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
```

**Alternative:** upload Firebase service account JSON to:

```text
storage/fcm-service-account.json
```

(chmod 640, not web-accessible)

Push is sent whenever an in-app notification is created (booking updates, payment received, job assigned).

Logs: `storage/logs/fcm.log`

---

## 4. Flutter — UPI

Dependencies added: `url_launcher` (UPI deep links) + `firebase_core` / `firebase_messaging`

**Customer flow:**

1. Open booking detail
2. Tap **Pay via UPI** (when `canPayUpi` is true)
3. Pick GPay / PhonePe / Paytm / BHIM
4. Complete payment in UPI app
5. App sends transaction reference to `POST /bookings/{id}/pay-upi`

Rebuild APK after server `.env` has `UPI_VPA` set.

---

## 5. Flutter — Firebase Push

Dependencies added: `firebase_core`, `firebase_messaging`

### Setup steps

1. Create a [Firebase project](https://console.firebase.google.com/)
2. Add Android app with package `com.shineexpress.shine_express_app`
3. Download **google-services.json** → replace:
   ```text
   shine-express-flutter/android/app/google-services.json
   ```
4. Enable **Cloud Messaging** in Firebase console
5. Create a **Service Account** key (JSON) for PHP FCM (section 3 above)
6. Rebuild and install the app — token registers on login via `POST /api/v1/devices`

The placeholder `google-services.json` allows builds; replace it for real push delivery.

---

## 6. Deploy checklist

- [ ] Set `UPI_VPA` and `UPI_MERCHANT_NAME` in production `.env`
- [ ] Upload updated PHP files (`PaymentService`, `FcmService`, `BookingAlertService`, `NotificationService`, API routes)
- [ ] Optional: enable `WHATSAPP_PROVIDER=cloud` with Meta credentials
- [ ] Optional: enable `FCM_ENABLED=true` with service account credentials
- [ ] Replace Flutter `google-services.json` with your Firebase file
- [ ] Build APK:
  ```bash
  flutter build apk --dart-define=API_BASE_URL=https://kdtechnoservices.com/shine-express
  ```
- [ ] Test: customer pays via UPI on a STARTED booking → payment shows as UPI COMPLETED
- [ ] Test: change booking status in admin → customer gets in-app + push + WhatsApp (if enabled)

---

## 7. Security notes

- UPI payments are recorded on customer attestation; verify high-value payments manually if needed
- Never commit real `FCM_PRIVATE_KEY` or `google-services.json` to public Git
- Keep `storage/fcm-service-account.json` outside web root access
