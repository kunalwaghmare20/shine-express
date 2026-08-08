# Final Enhancements Setup — Tracking, Earnings & Proximity Dispatch

These complete the remaining items from `ShineExpress—PlatformEnhancement.md` (sections 1.3, 2.3, 3.3).

---

## 1. Service tracking & staff location (1.3)

### Staff app
- While a job is **ON_THE_WAY**, the app sends GPS to `POST /api/v1/staff/location` every ~45 seconds
- Triggered from **Jobs** list and **Job detail** screens
- Uses existing location permissions (`geolocator`)

### Customer app
- Booking detail shows an **OpenStreetMap** live tracking card when status is `ACCEPTED`, `ON_THE_WAY`, or `STARTED`
- Polls `GET /api/v1/bookings/{id}/tracking` every 20 seconds
- Shows customer address pin + staff car marker when location is available

### Server
- Updates `employees.current_latitude`, `current_longitude`, `location_updated_at`
- No migration required (columns exist in `001_schema.sql`)

---

## 2. Staff earnings dashboard (2.3)

### API
`GET /api/v1/staff/earnings` (staff auth)

**Response highlights:**
- `today`, `week`, `month` — completed jobs, job revenue, estimated bonus
- `baseSalary` — from `employees.salary`
- `perJobBonus` — configurable per-job completion bonus
- `month.estimatedTotal` — bonus + prorated base salary

### Optional `.env`
```env
STAFF_PER_JOB_BONUS=200
```

### Flutter
- New **Earnings** tab in staff bottom navigation
- Cards for today / week / month with job counts and payout estimates

---

## 3. Proximity dispatching (3.3)

### Admin / branch manager
- **Assign staff** on booking detail sorts employees by:
  1. Available first (`is_available = 1`)
  2. Nearest GPS to customer address (Haversine km)
- Shows distance label per employee (e.g. `2.4 km away`)
- Requires customer address lat/lng (from OSM map picker) and staff GPS updates

### Helpers
- `haversine_km()` and `format_distance_km()` in `app/Helpers/functions.php`

---

## Deploy checklist

1. Upload PHP changes (controllers, routes, helpers, admin view)
2. Set `STAFF_PER_JOB_BONUS` in production `.env` if you want a custom rate
3. Run `flutter pub get` (no new packages beyond existing Phase 3–4 deps)
4. Test flow:
   - Staff accepts job → marks **On the way** → location updates
   - Customer opens booking → sees tracking map
   - Admin opens booking assign form → staff sorted by distance
   - Staff opens **Earnings** tab

---

## Enhancement doc — full completion

| Section | Feature | Status |
|---------|---------|--------|
| 1.1 | UPI payments | Done |
| 1.2 | OSM address picker | Done |
| 1.3 | Staff location tracking | Done |
| 1.4 | FCM + WhatsApp alerts | Done |
| 2.1 | GPS navigation | Done |
| 2.2 | Offline sync | Done |
| 2.3 | Staff earnings | Done |
| 3.1 | wa.me admin buttons | Done |
| 3.2 | Low-rating escalation | Done |
| 3.3 | Proximity dispatch | Done |

**All enhancement doc items are implemented.** You can proceed to the final APK build when ready.
