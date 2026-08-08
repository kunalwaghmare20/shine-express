# Shine Express — Project Overview & Workflow

> **Purpose:** Complete reference for Shine Express — architecture, roles, data model, and end-to-end workflows.  
> Use this document to onboard team members, share with AI assistants (Gemini, ChatGPT), or convert to Google Docs.

**Last updated:** August 2026  
**Production URL:** https://kdtechnoservices.com/shine-express

---

## Table of Contents

1. [What Is Shine Express?](#1-what-is-shine-express)
2. [Repository Structure](#2-repository-structure)
3. [High-Level Architecture](#3-high-level-architecture)
4. [Technology Stack](#4-technology-stack)
5. [User Roles & Access](#5-user-roles--access)
6. [Core Data Model](#6-core-data-model)
7. [Booking Status Lifecycle](#7-booking-status-lifecycle)
8. [End-to-End Workflows](#8-end-to-end-workflows)
9. [Multi-Service Booking](#9-multi-service-booking)
10. [Multi-Employee Assignment](#10-multi-employee-assignment)
11. [Flutter Mobile App](#11-flutter-mobile-app)
12. [Admin Web Application](#12-admin-web-application)
13. [Mobile REST API](#13-mobile-rest-api)
14. [Payments](#14-payments)
15. [Notifications & WhatsApp Reminders](#15-notifications--whatsapp-reminders)
16. [Production Deployment](#16-production-deployment)
17. [Demo Logins](#17-demo-logins)
18. [Before Building a New APK](#18-before-building-a-new-apk)
19. [Sequence Diagram — Full Booking Journey](#19-sequence-diagram--full-booking-journey)
20. [Related Documentation](#20-related-documentation)

---

## 1. What Is Shine Express?

Shine Express is a **multi-service field operations platform** for businesses such as:

- House Cleaning
- Car Cleaning
- Water Tank Cleaning
- Sofa / Carpet Cleaning
- Pest Control
- Deep Cleaning
- Any future services (added via admin without code changes)

**What it does:**

- **Customers** browse services, book appointments (including multiple services at once), track progress, and leave reviews.
- **Super Admin / Branch Managers** manage branches, employees, services, bookings, and reports via a web dashboard.
- **Field staff (employees)** receive assigned jobs on mobile, accept/reject, travel to the site, upload photos, complete checklists, and mark jobs done.

---

## 2. Repository Structure

The project contains **three main parts**. Only the PHP backend and Flutter app are used in production on kdtechnoservices.

| Part | Folder | Purpose | Production use |
|------|--------|---------|----------------|
| **Backend + Admin Web** | `shine-express-php/` | PHP MVC app, REST API, browser admin/customer portal | **Live** at kdtechnoservices.com/shine-express |
| **Mobile App** | `shine-express-flutter/` | Single Flutter app for Customer and Staff roles | Built as APK/IPA; connects to PHP API |
| **Next.js SaaS (alternate)** | Repo root (`src/`, Drizzle ORM) | Original Next.js + MySQL scaffold | **Not** deployed on kdtechnoservices |

**Production stack:**

```
Flutter App  →  PHP API (/api/v1)  →  MySQL
Admin Web    →  PHP MVC (same database)
```

---

## 3. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENTS                               │
├──────────────┬──────────────┬──────────────┬────────────────┤
│ Super Admin  │ Branch Mgr   │ Customer     │ Staff          │
│ (Web)        │ (Web)        │ Web/Flutter  │ Flutter        │
└──────┬───────┴──────┬───────┴──────┬───────┴────────┬───────┘
       │              │              │                │
       └──────────────┴──────────────┴────────────────┘
                              │
              ┌───────────────▼───────────────┐
              │     shine-express-php         │
              ├───────────────────────────────┤
              │  Web MVC Routes  (/admin…)   │
              │  REST API        (/api/v1…)  │
              │  Services Layer              │
              │  MySQL Database              │
              └───────────────────────────────┘
```

**Key points:**

- One PHP application serves both the **web dashboard** and the **mobile REST API**.
- Web uses **session-based auth**; mobile uses **Bearer token auth**.
- Flutter does **not** run on the server — APK is built locally and installed on phones.

---

## 4. Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.1+, custom MVC (no Composer — shared hosting friendly) |
| Database | MySQL / MariaDB |
| Web UI | PHP views + CSS (server-rendered) |
| Mobile | Flutter 3.16+ (Android + iOS from one codebase) |
| Mobile API | REST JSON at `/api/v1/*` |
| Auth (Web) | PHP sessions + RBAC |
| Auth (Mobile) | API tokens (`002_api_tokens.sql` migration) |
| Hosting | cPanel shared hosting (kdtechnoservices) |

---

## 5. User Roles & Access

| Role | Slug | Access | Primary interface |
|------|------|--------|-------------------|
| **Super Admin** | `SUPER_ADMIN` | Full company: branches, employees, services, all bookings, reports, WhatsApp reminders | Web `/admin` |
| **Branch Manager** | `BRANCH_MANAGER` | Own branch only: bookings, staff, customers, reports | Web `/branch-manager` |
| **Service Staff** | `SERVICE_STAFF` | Assigned jobs only, attendance, leave | Flutter staff mode or web `/staff` |
| **Customer** | `CUSTOMER` | Book services, view history, profile, addresses | Flutter customer mode or web `/book` |

**Role home pages (after login):**

| Role | Redirect URL |
|------|--------------|
| Super Admin | `/admin` |
| Branch Manager | `/branch-manager` |
| Service Staff | `/staff/jobs` |
| Customer | `/book` |

**Permissions summary:**

- **Super Admin:** All permissions (`*`)
- **Branch Manager:** Branch bookings, branch employees, customers (view), branch reports, job status updates
- **Service Staff:** View assigned jobs, update job status
- **Customer:** Create booking, cancel own booking, view/download invoices

---

## 6. Core Data Model

A **booking** (service request) is the central entity. Related tables:

```
bookings
  ├── booking_items           → one or more services/packages in one request
  ├── booking_assignments     → one or more employees assigned by admin
  ├── booking_status_history  → audit trail of every status change
  ├── photos                  → before/after job photos (staff upload)
  ├── job_notes               → staff notes during job
  ├── job_checklist_items     → service checklist items
  ├── payments                → cash payment recorded on completion
  └── reviews                 → customer rating after completion
```

### Key tables

| Table | Purpose |
|-------|---------|
| `companies` | Business entity |
| `branches` | Multi-location support |
| `users` | All login accounts (role enum) |
| `customers` | Customer profile linked to user |
| `employees` | Staff profile linked to user + branch |
| `addresses` | Customer delivery/service addresses |
| `service_categories` | e.g. Cleaning, Pest Control |
| `services` | Individual services with base price, duration, reminder_days |
| `service_items` | Optional packages/items under a service |
| `bookings` | Main booking record |
| `booking_items` | Line items (supports multi-service) |
| `booking_assignments` | Staff assigned to booking (supports multi-employee) |
| `booking_status_history` | Status change log |
| `payments` | Payment records (cash on completion) |
| `notifications` | In-app notifications |
| `reviews` | Customer ratings |

### Important design choices

1. **Multi-service booking:** Customer can select **multiple services** in one booking. Each becomes a row in `booking_items`. The `bookings.service_id` column stores the **primary** (first) service for reporting compatibility.

2. **Multi-employee assignment:** Super Admin / Branch Manager can assign **multiple staff** to one booking via `booking_assignments`. One employee is marked **primary contact** (`is_primary = 1`).

3. **Extensible services:** New services are added in Admin → Services without changing application code.

---

## 7. Booking Status Lifecycle

### Status flow

```
PENDING
   ↓
CONFIRMED
   ↓
ASSIGNED  ← staff assigned by admin
   ↓
ACCEPTED  ← staff accepts job
   ↓
ON_THE_WAY
   ↓
STARTED
   ↓
COMPLETED

Side paths:
  PENDING / CONFIRMED → CANCELLED
  ASSIGNED / ACCEPTED → REJECTED → can go back to ASSIGNED (re-assign)
```

### Allowed transitions

| From | Can transition to |
|------|-------------------|
| PENDING | CONFIRMED, CANCELLED |
| CONFIRMED | ASSIGNED, CANCELLED |
| ASSIGNED | ACCEPTED, REJECTED |
| ACCEPTED | ON_THE_WAY, REJECTED |
| ON_THE_WAY | STARTED |
| STARTED | COMPLETED |
| COMPLETED | (terminal) |
| CANCELLED | (terminal) |
| REJECTED | ASSIGNED (re-assign staff) |

### Status labels (user-facing)

| Code | Label |
|------|-------|
| PENDING | Pending |
| CONFIRMED | Confirmed |
| ASSIGNED | Assigned |
| ACCEPTED | Accepted |
| ON_THE_WAY | On The Way |
| STARTED | Started |
| COMPLETED | Completed |
| CANCELLED | Cancelled |
| REJECTED | Rejected |

---

## 8. End-to-End Workflows

### A. Customer books a service

**Channels:** Web `/book` · Flutter Book tab · API `POST /api/v1/bookings`

**Steps:**

1. Customer selects **one or more services** (and optional packages/items).
2. Chooses **address**, **branch**, **date**, **time**, and optional **notes**.
3. System calculates **subtotal + 18% GST** → **total amount**.
4. Creates booking with status **PENDING**.
5. Creates `booking_items` rows for each selected service/package.
6. Sends **in-app notification** to customer ("Booking created").

**API request body (mobile):**

```json
{
  "serviceIds": ["svc_1", "svc_2"],
  "serviceId": "svc_1",
  "addressId": "addr_xxx",
  "branchId": "branch_xxx",
  "scheduledDate": "2026-08-15",
  "scheduledTime": "10:00:00",
  "customerNotes": "Please call before arriving",
  "serviceItemIds": ["item_1"]
}
```

---

### B. Admin / Branch Manager processes booking

**Channels:** Web `/admin/bookings/{id}` or `/branch-manager/bookings/{id}`

**Steps:**

1. Admin opens booking detail — sees line items, customer, address, schedule.
2. Optionally moves status **PENDING → CONFIRMED**.
3. **Assigns staff:**
   - Select **one or more employees** (checkboxes).
   - Choose **primary contact** (radio button).
   - Submit assign form.
4. On assign:
   - Rows inserted into `booking_assignments`.
   - If booking was PENDING, auto-confirms first.
   - Status becomes **ASSIGNED**.
   - **Notifications** sent to customer and **all assigned staff**.

---

### C. Staff executes the job

**Channels:** Flutter staff mode · Web `/staff/jobs`

| Step | Staff action | API endpoint | Status change |
|------|--------------|--------------|---------------|
| View jobs | See assigned job list | `GET /api/v1/jobs` | — |
| Open job | View services, team, address, customer | `GET /api/v1/jobs/{id}` | — |
| Accept | Accept the job | `POST /api/v1/jobs/{id}/accept` | → ACCEPTED |
| Decline | Reject with reason | `POST /api/v1/jobs/{id}/reject` | Only this employee declined |
| Start | Begin work | `POST /api/v1/jobs/{id}/start` | → STARTED |
| On site | Upload before/after photos | `POST /api/v1/jobs/{id}/photos` | — |
| On site | Add notes | `POST /api/v1/jobs/{id}/notes` | — |
| On site | Update checklist | `POST /api/v1/jobs/{id}/checklist` | — |
| Complete | Mark job done | `POST /api/v1/jobs/{id}/complete` | → COMPLETED |

**Staff dashboard also includes:**

- Attendance check-in/check-out (`/api/v1/staff/attendance/*`)
- Leave requests (`/api/v1/staff/leaves`)

---

### D. Customer after service

**Channels:** Flutter booking detail · Web `/bookings/{id}`

**Steps:**

1. Customer sees **assigned team** and **selected services** on booking detail.
2. Tracks status updates via notifications.
3. When status is **STARTED**, can mark complete from app (optional path).
4. When status is **COMPLETED**, submits **rating (1–5) + review comment**.
5. May receive **WhatsApp rebook reminder** N days later (if configured on the service).

---

## 9. Multi-Service Booking

Customers can book **multiple services in a single request**.

**How it works:**

- Customer selects multiple services via checkboxes (web or Flutter).
- Optional **packages/items** can be selected per service.
- Each service/package becomes a **line item** in `booking_items`.
- If no package is selected for a service, the **base price** of that service is used.
- **Total** = sum of all line items + 18% GST.
- **Estimated duration** = sum of all line durations.

**Example booking:**

| Line item | Price |
|-----------|-------|
| House Cleaning (base) | ₹1,500 |
| Sofa Cleaning — 3 Seater package | ₹800 |
| Pest Control (base) | ₹2,000 |
| **Subtotal** | **₹4,300** |
| GST (18%) | ₹774 |
| **Total** | **₹5,074** |

**API response includes:**

- `serviceName` — comma-separated list if multiple services
- `serviceNames` — array of service/item names
- `items` — full line item list with prices

---

## 10. Multi-Employee Assignment

Super Admin and Branch Manager can assign **multiple employees** to one service request.

### How it works

```
Admin assigns Employee A (Primary) + Employee B + Employee C
                    ↓
         booking_assignments table
                    ↓
    ┌───────────────┼───────────────┐
    ↓               ↓               ↓
Employee A      Employee B      Employee C
(Primary)       (Support)       (Support)
    ↓               ↓               ↓
    └───────────────┴───────────────┘
                    ↓
           Same booking / same job
```

### Rules

| Scenario | Behavior |
|----------|----------|
| Admin assigns 3 staff | All 3 receive notification; all 3 see the job in their app |
| One employee **rejects** | Only that employee's assignment is marked declined; booking stays **ASSIGNED** |
| **All** employees reject | Booking status becomes **REJECTED**; admin can re-assign |
| Primary contact | Marked with `is_primary = 1`; shown to customer as lead |
| Job progress | Any assigned (non-declined) staff can accept → start → complete |
| Customer view | Sees full **assigned team** with names and primary contact |

### Admin UI

- **Assign staff** form on booking detail page.
- Checkboxes for each branch employee.
- Radio button to set **primary contact**.
- Currently assigned staff are **pre-selected** on page load.
- Assignment list shows **Primary** and **Declined** badges.

### Database: `booking_assignments`

| Column | Purpose |
|--------|---------|
| `booking_id` | Which booking |
| `employee_id` | Which staff member |
| `assigned_by_id` | Admin who assigned |
| `is_primary` | Primary contact flag |
| `accepted_at` | When staff accepted |
| `rejected_at` | When staff declined (NULL = active) |
| `rejection_reason` | Reason for decline |

Unique constraint: one row per `(booking_id, employee_id)`.

---

## 11. Flutter Mobile App

**Location:** `shine-express-flutter/`  
**Package:** `com.shineexpress.shine_express_app`

### Single app, two modes

Login role determines which UI loads:

| Customer mode | Staff mode |
|---------------|------------|
| Home | Dashboard |
| Book | My Jobs |
| History | Attendance |
| Support | Leave |
| Profile | Profile |

Staff also has **Job Detail** screen (outside bottom nav) for accept/start/complete/photos.

### API configuration

The API base URL is **baked in at build time**:

| Environment | API_BASE_URL |
|-------------|--------------|
| Production | `https://kdtechnoservices.com/shine-express` |
| Android emulator | `http://10.0.2.2:8080` |
| iOS simulator | `http://127.0.0.1:8080` |
| Physical device (local) | `http://<your-mac-lan-ip>:8080` |

**Build production APK:**

```bash
cd shine-express-flutter
flutter build apk --dart-define=API_BASE_URL=https://kdtechnoservices.com/shine-express
```

Output: `build/app/outputs/flutter-apk/app-release.apk`

### Key Flutter screens

| Screen | Path | Role |
|--------|------|------|
| Login | `/login` | All |
| Register | `/register` | Customer |
| Home | `/home` | Customer |
| Book | `/book` | Customer |
| Booking Detail | `/booking/:id` | Customer |
| History | `/history` | Customer |
| Staff Dashboard | `/staff` | Staff |
| Jobs List | `/staff/jobs` | Staff |
| Job Detail | `/staff/job/:id` | Staff |
| Attendance | `/staff/attendance` | Staff |
| Leave | `/staff/leave` | Staff |

---

## 12. Admin Web Application

**Base URL (production):** `https://kdtechnoservices.com/shine-express/admin`

### Super Admin modules

| Module | URL | Capabilities |
|--------|-----|--------------|
| Dashboard | `/admin` | Stats overview |
| Bookings | `/admin/bookings` | List, create, view, assign staff, update status |
| Customers | `/admin/customers` | CRUD, view addresses |
| Employees | `/admin/employees` | CRUD, branch, skills, availability |
| Services | `/admin/services` | Categories, services, items/packages (add, edit, deactivate) |
| Branches | `/admin/branches` | Multi-location management |
| Reports | `/admin/reports` | Revenue, booking analytics |
| WhatsApp Reminders | `/admin/reminders` | Per-service rebook days; manual send |

### Branch Manager modules

Same as above but **scoped to their branch** under `/branch-manager/*`.

### Service items management

Under **Admin → Services → [Service Name]**:

- View all packages/items for a service
- **Add** new item
- **Edit** item (name, price, description, duration, sort order, active/inactive)
- **Deactivate** item (soft delete — keeps historical booking references valid)

---

## 13. Mobile REST API

**Base path:** `/api/v1`  
**Full production base:** `https://kdtechnoservices.com/shine-express/api/v1`

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Customer registration |
| POST | `/auth/login` | Login (returns token) |
| POST | `/auth/otp/send` | Send OTP |
| POST | `/auth/otp/verify` | Verify OTP |
| GET | `/auth/me` | Current user profile |
| POST | `/auth/logout` | Logout |
| POST | `/auth/profile` | Update profile |

All authenticated requests use header: `Authorization: Bearer <token>`

### Catalog (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/catalog` | All services, branches, categories |
| GET | `/services/{id}` | Service detail with items |
| GET | `/home` | Home screen data |
| GET | `/search` | Search services |

### Bookings (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/bookings` | Customer's booking list |
| POST | `/bookings` | Create booking (multi-service) |
| GET | `/bookings/{id}` | Booking detail + assigned staff |
| POST | `/bookings/{id}/complete` | Mark complete + optional review |
| POST | `/bookings/{id}/review` | Submit review |

### Addresses (Customer)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/addresses` | List addresses |
| POST | `/addresses` | Add address |
| POST | `/addresses/{id}` | Update address |
| POST | `/addresses/{id}/delete` | Delete address |

### Jobs (Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/jobs` | Assigned jobs list |
| GET | `/jobs/{id}` | Job detail (services, team, address) |
| POST | `/jobs/{id}/accept` | Accept job |
| POST | `/jobs/{id}/reject` | Decline job (self only) |
| POST | `/jobs/{id}/start` | Start job |
| POST | `/jobs/{id}/complete` | Complete job |
| POST | `/jobs/{id}/photos` | Upload before/after photo |
| POST | `/jobs/{id}/notes` | Add job note |
| POST | `/jobs/{id}/checklist` | Update checklist |

### Staff utilities

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/staff/dashboard` | Today's/upcoming/completed counts |
| POST | `/staff/attendance/check-in` | Check in |
| POST | `/staff/attendance/check-out` | Check out |
| GET | `/staff/leaves` | Leave history |
| POST | `/staff/leaves` | Apply for leave |

**Full API reference:** `shine-express-php/docs/MOBILE_API.md`

---

## 14. Payments

**Current model:** Cash on completion (no online payment gateway in PHP production app).

**When booking reaches COMPLETED:**

1. System auto-creates a `payments` row:
   - Method: `CASH`
   - Status: `COMPLETED`
   - Amount: booking `total_amount`
2. Payment is linked to booking and customer.

**Future:** Razorpay/Stripe integration exists in the Next.js scaffold but is not wired in the PHP production app.

---

## 15. Notifications & WhatsApp Reminders

### In-app notifications

Triggered automatically for:

| Event | Recipient | Type |
|-------|-----------|------|
| Booking created | Customer | BOOKING_CREATED |
| Booking confirmed | Customer | BOOKING_CONFIRMED |
| Staff assigned | Customer + all assigned staff | BOOKING_ASSIGNED |
| Service started | Customer | BOOKING_STARTED |
| Service completed | Customer | BOOKING_COMPLETED |
| Booking cancelled | Customer | BOOKING_CANCELLED |

### WhatsApp rebook reminders

**Purpose:** After a service is completed, remind the customer to book again.

**Flow:**

1. Super Admin sets **Rebook reminder (days)** on each service (e.g. 30 days for Pest Control).
2. Daily cron job runs: `database/cron/send_service_reminders.php`
3. If `completion_date + reminder_days = today` → send WhatsApp + in-app notification.
4. Set **0** days on a service to disable reminders for it.

**Admin UI:** `/admin/reminders` — view due list and manually trigger send.

**Documentation:** `shine-express-php/docs/WHATSAPP_REMINDERS.md`

---

## 16. Production Deployment

### Server layout (kdtechnoservices / cPanel)

```
public_html/shine-express/
  app/                  ← MVC application code
  database/             ← migrations, seeds, cron
  public/               ← web entry point (index.php)
  storage/              ← uploads, logs (writable)
  .env                  ← environment config
  .htaccess             ← URL rewriting
```

### Required `.env` settings

```env
APP_NAME="Shine Express"
APP_URL="https://kdtechnoservices.com/shine-express"
APP_BASE_PATH="/shine-express"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Kolkata

DB_HOST=localhost
DB_DATABASE=your_cpanel_db_name
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password
```

### Database migrations (run in order)

1. `001_schema.sql` — core tables
2. `002_api_tokens.sql` — mobile API tokens
3. `003_mobile_platform.sql` — OTP, loyalty, leave, attendance
4. `004_whatsapp_reminders.sql` — WhatsApp logs
5. `005_service_reminder_days.sql` — per-service rebook days

### Health check

```
GET https://kdtechnoservices.com/shine-express/health
→ {"database":"up", ...}
```

### Flutter app deployment

- Flutter **does not run on the server**.
- Build APK on your Mac/PC.
- Install on phones via USB, Drive, or adb.
- Always build with production API URL for release APKs.

**Full deployment guide:** `shine-express-php/docs/DEPLOY_KDTECHNOSERVICES.md`

---

## 17. Demo Logins

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@shineexpress.com | Admin@123 |
| Branch Manager | manager@shineexpress.com | Manager@123 |
| Staff | staff@shineexpress.com | Staff@123 |
| Customer | customer@shineexpress.com | Customer@123 |

> **Important:** Change all demo passwords before go-live.

---

## 18. Before Building a New APK

Checklist:

- [ ] Deploy latest PHP code to kdtechnoservices (includes multi-service booking, multi-staff assignment, service item edit, reject fix)
- [ ] Confirm all 5 database migrations are applied on production
- [ ] Verify health endpoint returns `"database":"up"`
- [ ] Test admin login at `/admin`
- [ ] Build APK with production URL:
  ```bash
  cd shine-express-flutter
  flutter build apk --dart-define=API_BASE_URL=https://kdtechnoservices.com/shine-express
  ```
- [ ] Test end-to-end on a real phone:
  1. Customer books multiple services
  2. Admin assigns 2+ staff (one primary)
  3. Both staff see the job in app
  4. One staff rejects → other can still proceed
  5. Staff completes job → customer sees COMPLETED + can review

### Common APK install issues

| Issue | Fix |
|-------|-----|
| "App wasn't installed" | Uninstall any previous version first (package conflict) |
| Play Protect blocks install | Disable Play Protect temporarily or use "Install anyway" |
| `INSTALL_FAILED_VERIFICATION_FAILURE` | Turn off "Verify apps over USB" in Developer options |
| APK corrupted | Don't send via WhatsApp; use Drive, USB, or adb |
| Wrong API / login fails | Rebuild with correct `--dart-define=API_BASE_URL=...` |

---

## 19. Sequence Diagram — Full Booking Journey

```
Customer (Flutter)          PHP API              Super Admin (Web)       Staff A              Staff B
       |                        |                        |                   |                    |
       |-- POST /bookings ------>|                        |                   |                    |
       |   (2 services)         |                        |                   |                    |
       |<-- PENDING booking ----|                        |                   |                    |
       |                        |                        |                   |                    |
       |                        |<-- Assign A + B --------|                   |                    |
       |                        |   (A = primary)        |                   |                    |
       |                        |-- Notify A ------------>|------------------>|                    |
       |                        |-- Notify B ----------------------------------|------------------->|
       |                        |                        |                   |                    |
       |                        |<-- Accept job ----------|-------------------|                    |
       |                        |   (Staff A)            |                   |                    |
       |                        |                        |                   |                    |
       |                        |<-- Reject job -----------------------------------------|        |
       |                        |   (Staff B declines)   |                   |                    |
       |                        |   Booking stays ASSIGNED                   |                    |
       |                        |                        |                   |                    |
       |                        |<-- Start job -----------|-------------------|                    |
       |                        |<-- Complete job --------|-------------------|                    |
       |                        |   → COMPLETED          |                   |                    |
       |                        |   → Cash payment       |                   |                    |
       |<-- COMPLETED + review --|                        |                   |                    |
       |-- POST /review -------->|                        |                   |                    |
```

---

## 20. Related Documentation

| Document | Path | Contents |
|----------|------|----------|
| Deployment (kdtechnoservices) | `docs/DEPLOY_KDTECHNOSERVICES.md` | cPanel setup, .env, migrations |
| Mobile API | `docs/MOBILE_API.md` | Full REST API reference |
| Architecture | `docs/ARCHITECTURE.md` | MVC layers, folder structure |
| WhatsApp Reminders | `docs/WHATSAPP_REMINDERS.md` | Rebook reminder setup |
| Google Maps | `docs/GOOGLE_MAPS.md` | Maps API keys for Flutter |
| Flutter README | `../shine-express-flutter/README.md` | Flutter setup and run |
| Android requirements | `Android-app-development.md` | Original mobile feature spec |

---

*End of document.*
