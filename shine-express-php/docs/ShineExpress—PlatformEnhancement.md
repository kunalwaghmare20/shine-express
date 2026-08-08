# Shine Express — Platform Enhancement & Transformation Plan

> **Goal:** Upgrade the Shine Express platform to deliver an industry-leading user experience for Customers, Service Staff, and Admins—without adding any paid third-party API costs (100% free tech stack).

---

## Technical Directives & Constraints

1. **Shared Hosting Compliance:** All backend enhancements must run natively on PHP 8.1+ & MySQL (cPanel shared hosting compatible). No Node.js background services required on the server.
2. **Zero Paid APIs:**
   - **Maps & Location:** Use OpenStreetMap (`flutter_map`) and native device geocoding/URL launching.
   - **Payments:** Use UPI Deep-Linking Intent flow (`upi_pro_sdk` / `flutter_upi_india`).
   - **Messaging & Notifications:** Use free Firebase Cloud Messaging (FCM) and Meta's Free Tier WhatsApp Cloud API (1,000 free conversations/mo) + `wa.me` deep-links.

---

## 1. Customer Experience Enhancements

### 1.1. Native UPI Deep-Link Payments (0% Gateway Fee)
* **Problem:** Current system relies solely on cash on completion.
* **Solution:** Integrate UPI Intent Flow in the Flutter app checkout screen.
* **Implementation Guidance:**
  - Flutter: Add `flutter_upi_india` or `upi_pro_sdk`.
  - When booking is placed or completed, generate dynamic UPI string:
    `upi://pay?pa=YOUR_BUSINESS_UPI@okicici&pn=ShineExpress&am={TOTAL_AMOUNT}&tr={BOOKING_ID}&cu=INR`
  - Show installed UPI apps (GPay, PhonePe, Paytm, BHIM) in a bottom sheet.
  - On payment return, send the transaction reference ID to PHP endpoint `POST /api/v1/bookings/{id}/pay-upi` to update payment status.

### 1.2. Free Interactive Map & Address Selection
* **Problem:** Text-only address entry leads to location ambiguities.
* **Solution:** Add an interactive map location picker during checkout using OpenStreetMap.
* **Implementation Guidance:**
  - Flutter: Integrate `flutter_map` with `latlong2`.
  - Fetch tiles from OpenStreetMap tile server: `https://tile.openstreetmap.org/{z}/{x}/{y}.png`.
  - Use native device geocoding (`geocoding` package) or Nominatim free API to auto-fill street address based on map pin coordinates.
  - Save `latitude` and `longitude` in the `addresses` table in MySQL.

### 1.3. Service Tracking & Staff Location View
* **Problem:** Customers receive `ON_THE_WAY` status but cannot gauge arrival time.
* **Solution:** Show staff member's last known location on an OpenStreetMap view within the booking detail screen.
* **Implementation Guidance:**
  - Staff app sends periodic lat/long updates to PHP `POST /api/v1/staff/location` when job status is `ON_THE_WAY`.
  - Customer app displays `flutter_map` with a custom marker showing the assigned staff member approaching the customer's pinned address.

### 1.4. Automated WhatsApp & Push Updates
* **Problem:** No instant updates unless opening the app.
* **Solution:** Trigger free Firebase Push Notifications + WhatsApp messages via PHP.
* **Implementation Guidance:**
  - PHP: Use `cURL` to call Meta's free WhatsApp Cloud API endpoint on status changes (`CONFIRMED`, `ASSIGNED`, `ON_THE_WAY`, `COMPLETED`).
  - PHP: Use `cURL` to hit Google FCM v1 API (`https://fcm.googleapis.com/v1/projects/{project_id}/messages:send`) for in-app push alerts.

---

## 2. Field Staff Experience Enhancements

### 2.1. One-Tap Native GPS Navigation
* **Problem:** Staff have to manually copy-paste addresses into navigation apps.
* **Solution:** Add a direct "Navigate to Customer" button on the Job Detail screen.
* **Implementation Guidance:**
  - Flutter: Use `url_launcher` package.
  - Launch device's native navigation app using stored coordinates:
    - Android: `google.navigation:q={lat},{lng}`
    - iOS: `https://maps.apple.com/?daddr={lat},{lng}`
  - Opens Google Maps / Apple Maps installed on the phone for turn-by-turn navigation with zero API costs.

### 2.2. Offline Job Mode & Local Caching
* **Problem:** Staff working in basements, parking garages, or poor connectivity zones cannot complete checklists or upload notes.
* **Solution:** Implement offline queueing in the Flutter app.
* **Implementation Guidance:**
  - Flutter: Use `hive` or `sqflite` for local data persistence.
  - When offline, queue checklist updates, notes, and local photo paths.
  - Listen for connectivity restore (`connectivity_plus`) and auto-flush queued requests to PHP API endpoints (`/jobs/{id}/checklist`, `/jobs/{id}/notes`, `/jobs/{id}/photos`).

### 2.3. Staff Earnings & Commission Dashboard
* **Problem:** Staff only see job schedules without clear performance incentives.
* **Solution:** Add a "My Earnings" tab in Staff Mode.
* **Implementation Guidance:**
  - PHP API: Add `GET /api/v1/staff/earnings` calculating completed jobs, base pay, and total earnings for current week/month.
  - Flutter: Render a clean visual card showing completed jobs breakdown and daily payout estimates.

---

## 3. Admin & Business Operations Enhancements

### 3.1. One-Click `wa.me` WhatsApp Action Buttons
* **Problem:** Admins need a quick fallback to contact customers without using API quota.
* **Solution:** Embed pre-formatted WhatsApp Web/App links inside the Web Admin portal.
* **Implementation Guidance:**
  - PHP Admin Views: Next to each booking record, add a green "WhatsApp Customer" button.
  - Link format: `https://wa.me/91{CUSTOMER_PHONE}?text=Hello%20{NAME},%20your%20Shine%20Express%20booking%20#{ID}%20is%20confirmed%20for%20{DATE}.`
  - Clicking opens WhatsApp Web on desktop or WhatsApp mobile app directly.

### 3.2. Automated Low-Rating Escalation Workflow
* **Problem:** Low customer ratings sit quietly in the database.
* **Solution:** Automatically escalate reviews $\le$ 2 stars to manager priority status.
* **Implementation Guidance:**
  - PHP: When `POST /api/v1/bookings/{id}/review` receives `rating <= 2`:
    - Set booking flag `requires_followup = 1`.
    - Generate an urgent notification for Branch Manager / Super Admin.
    - Highlight row in red on `/admin/bookings` UI for immediate customer callback.

### 3.3. Dynamic Staff Proximity Dispatching
* **Problem:** Assigning staff without knowing who is nearest to the customer.
* **Solution:** Rank available branch employees by geographical distance to the customer's booking address.
* **Implementation Guidance:**
  - PHP: Calculate distance using Haversine formula between customer lat/lng and staff's last reported lat/lng in `booking_assignments` modal.
  - Order the staff assignment checklist by "Nearest Available".

---

## 4. Prioritized Implementation Roadmap

| Priority | Feature Module | Target User | Technology Used |
| :--- | :--- | :--- | :--- |
| **Phase 1** | One-Tap Native GPS Navigation | Staff | Flutter `url_launcher` |
| **Phase 1** | One-Click `wa.me` Admin Buttons | Admin | PHP View String Formatting |
| **Phase 2** | Free UPI Intent Payments | Customer | Flutter `flutter_upi_india` + PHP API |
| **Phase 2** | FCM Push & Meta WhatsApp Cloud API | All | PHP `cURL` + Firebase FCM |
| **Phase 3** | OpenStreetMap Location Picker | Customer | Flutter `flutter_map` + Nominatim |
| **Phase 3** | Low-Rating Escalation System | Admin | PHP Core Logic + MySQL Flag |
| **Phase 4** | Offline Checklist & Photo Sync | Staff | Flutter `hive` / `connectivity_plus` |