# Phase 4 Setup — Offline Checklist & Photo Sync

Phase 4 adds **offline-first job updates** for field staff: checklist toggles, notes, and before/after photos queue locally and sync automatically when connectivity returns.

---

## What was added

### Flutter (staff app)

| Component | Purpose |
|-----------|---------|
| `hive` + `hive_flutter` | Local job cache + sync queue |
| `connectivity_plus` | Detect offline/online transitions |
| `path_provider` | Store pending photo files on device |
| `OfflineJobSyncService` | Queue, cache, auto-flush |
| `job_detail_screen.dart` | Offline banner, photo gallery, sync button |

### PHP (minor fix)

- Checklist query now orders by `updated_at` (table has no `created_at` column)

No new server endpoints or migrations are required — existing APIs are reused:

| Method | Endpoint |
|--------|----------|
| GET | `/api/v1/jobs/{id}` |
| POST | `/api/v1/jobs/{id}/checklist` |
| POST | `/api/v1/jobs/{id}/notes` |
| POST | `/api/v1/jobs/{id}/photos` |

---

## How it works

1. **Load job** — tries API first; on failure loads last cached copy from Hive.
2. **Checklist / notes / photos** — updates UI immediately; tries API if online.
3. **Offline** — writes to local cache + sync queue; copies photos to app documents dir.
4. **Reconnect** — `connectivity_plus` triggers automatic queue flush.
5. **Manual sync** — app bar **Sync (N)** button when items are pending.

### Queue rules

- **Checklist:** only the latest snapshot per job is kept (API replaces all items).
- **Notes:** each note is queued independently (append-only).
- **Photos:** each capture is queued with its local file path.

Status actions (accept, reject, start, complete) remain **online-only**.

---

## Staff UX

- Orange **Offline** banner when viewing cached data or pending changes exist
- **Pending sync** label on queued notes/photos
- Photo thumbnails for uploaded and queued images
- Clock icon on notes not yet synced

---

## Deploy checklist

1. Upload PHP fix (`ApiEmployeeJobController.php` checklist ORDER BY)
2. Run `flutter pub get` in `shine-express-flutter/`
3. Test on a physical device (airplane mode):

| Step | Expected |
|------|----------|
| Open job while online | Job loads and caches |
| Enable airplane mode | Orange offline banner |
| Toggle checklist, add note, take photo | Changes show locally; queue count increases |
| Disable airplane mode | Auto-sync; banner clears |

---

## Storage notes

- Cached jobs: Hive box `job_cache`
- Pending uploads: Hive box `sync_queue`
- Photo files: `{appDocuments}/pending_photos/{jobId}/`

Queued data persists across app restarts until successfully synced.

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Sync button stays at N>0 | Check API reachability; tap **Sync** manually |
| Photos fail after sync | Verify server `uploads/bookings/` is writable |
| Job detail empty offline | Open job once while online to populate cache |
| Checklist API 500 on server | Run migration `003_mobile_platform.sql` if missing |
