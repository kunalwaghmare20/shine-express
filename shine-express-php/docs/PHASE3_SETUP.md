# Phase 3 Setup — OSM Map Picker & Low-Rating Escalation

Phase 3 adds:

1. **OpenStreetMap location picker** in the Flutter customer app (free tiles + Nominatim geocoding)
2. **Low-rating escalation** when a customer submits a review with rating ≤ 2

---

## 1. Database migration (Server)

Run on production MySQL:

```sql
-- database/migrations/006_booking_followup.sql
ALTER TABLE bookings
  ADD COLUMN requires_followup TINYINT(1) NOT NULL DEFAULT 0 AFTER cancellation_reason,
  ADD INDEX idx_bookings_followup (requires_followup);
```

Upload updated PHP files and run the migration via phpMyAdmin or CLI before testing.

---

## 2. Low-rating escalation (PHP)

When `POST /api/v1/bookings/{id}/review` (or completion with rating) receives `rating <= 2`:

- Sets `bookings.requires_followup = 1`
- Sends in-app + FCM notification to **Super Admin** and the booking branch’s **Branch Manager**
- Highlights the booking in red on `/admin/bookings` and `/branch-manager/bookings`
- Shows an urgent banner on the booking detail page

### Admin UI

| Location | Behavior |
|----------|----------|
| Bookings list | Red row + **Follow-up** badge; filter checkbox **Follow-up only** |
| Booking detail | Red alert panel at top when follow-up is required |

No extra `.env` variables are required for escalation.

---

## 3. OSM map picker (Flutter)

### Packages

- `flutter_map` — OpenStreetMap tile layer
- `latlong2` — map coordinates
- `geolocator` — device GPS (already in project)
- Nominatim reverse geocoding via `http` (no API key)

### Customer flow

1. On **Book a service**, tap the **+** next to Address
2. Map opens centered on current location (or Delhi fallback)
3. Tap map to move pin; address fields auto-fill from Nominatim
4. Edit label/address if needed → **Save address**
5. Lat/lng stored in `addresses` table via `POST /api/v1/addresses`

### Permissions

Ensure location permissions are declared (already used for staff GPS):

- **Android:** `ACCESS_FINE_LOCATION` / `ACCESS_COARSE_LOCATION` in `AndroidManifest.xml`
- **iOS:** `NSLocationWhenInUseUsageDescription` in `Info.plist`

### Nominatim usage

The app sends a `User-Agent` header as required by [Nominatim usage policy](https://operations.osmfoundation.org/policies/nominatim/). Avoid hammering the API — geocoding runs only when the pin moves.

---

## 4. Deploy checklist

1. Run migration `006_booking_followup.sql` on production DB
2. Upload PHP changes (`ReviewEscalationService`, controllers, views, CSS)
3. Rebuild/run Flutter app (APK build can wait until all phases are done)
4. Test: add address via map → book service → submit 1–2 star review → verify admin follow-up flag

---

## 5. API reference

| Method | Endpoint | Notes |
|--------|----------|-------|
| POST | `/api/v1/bookings/{id}/review` | Body: `{ "rating": 1-5, "comment": "..." }` — triggers escalation if rating ≤ 2 |
| POST | `/api/v1/addresses` | Body includes `latitude`, `longitude`, `line1`, `city`, etc. |
