# Shine Express — Architecture & Folder Structure

> Multi-service business management SaaS (House Cleaning, Car Cleaning, Pest Control, etc.)

## Tech Stack

| Layer          | Technology                                      |
| -------------- | ----------------------------------------------- |
| Frontend       | Next.js 15+, React 19, TypeScript, Tailwind CSS |
| UI Components  | Shadcn UI, React Hook Form, Zod                 |
| Data Fetching  | TanStack Query                                  |
| Backend        | Next.js API Routes (Clean Architecture)         |
| ORM            | Drizzle ORM + MySQL                             |
| Auth           | Clerk / NextAuth + JWT + RBAC                   |
| File Storage   | UploadThing / Cloudinary                        |
| Maps           | Google Maps API                                 |
| Deployment     | Vercel + PlanetScale / AWS RDS / local MySQL    |

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  src/app/          Next.js pages & API route handlers        │
│  src/components/   Shared UI (Shadcn, layout, charts)        │
│  src/features/     Feature-specific components & hooks         │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                     Application Layer                        │
│  src/server/services/   Business logic & orchestration       │
│  src/server/dto/        Request/response data shapes         │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                      Domain Layer                            │
│  src/features/*/types/  Domain types & enums                 │
│  src/lib/validations/     Zod schemas (shared validation)      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   Infrastructure Layer                       │
│  src/server/repositories/  Drizzle data access (Repository)   │
│  src/lib/db/               Drizzle client + MySQL pool        │
│  src/lib/auth/             Auth provider integration         │
└─────────────────────────────────────────────────────────────┘
```

---

## Folder Structure

```
shine-express/
├── docs/                           # Documentation
│   └── ARCHITECTURE.md             # This file
│
├── drizzle/                        # Migrations & seed
│   ├── migrations/                 # SQL migration files
│   └── seed.ts                     # Initial data seed
│
├── drizzle.config.ts               # Drizzle Kit configuration
│
├── public/
│   └── assets/                     # Static assets (images, icons)
│
├── src/
│   ├── app/                        # Next.js App Router
│   │   ├── (auth)/                 # Public auth routes
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   └── forgot-password/
│   │   │
│   │   ├── (dashboard)/            # Role-based admin portal
│   │   │   ├── admin/              # Super Admin routes
│   │   │   ├── branch-manager/     # Branch Manager routes
│   │   │   └── staff/              # Service Staff routes
│   │   │
│   │   ├── (customer)/             # Customer self-service portal
│   │   │   ├── book/
│   │   │   ├── bookings/
│   │   │   ├── profile/
│   │   │   ├── history/
│   │   │   └── invoices/
│   │   │
│   │   ├── api/                    # REST API endpoints
│   │   │   ├── auth/
│   │   │   ├── users/
│   │   │   ├── customers/
│   │   │   ├── employees/
│   │   │   ├── services/
│   │   │   ├── bookings/
│   │   │   ├── payments/
│   │   │   ├── invoices/
│   │   │   ├── reports/
│   │   │   ├── notifications/
│   │   │   ├── upload/
│   │   │   └── branches/
│   │   │
│   │   ├── layout.tsx              # Root layout
│   │   ├── page.tsx                # Landing page
│   │   └── globals.css
│   │
│   ├── features/                   # Feature modules (vertical slices)
│   │   ├── auth/
│   │   ├── customers/
│   │   ├── employees/
│   │   ├── services/
│   │   ├── bookings/
│   │   ├── payments/
│   │   ├── invoices/
│   │   ├── reports/
│   │   ├── notifications/
│   │   ├── branches/
│   │   ├── dashboard/
│   │   └── settings/
│   │       ├── components/         # Feature-specific UI
│   │       ├── hooks/              # Feature-specific React hooks
│   │       ├── services/           # Client-side API calls
│   │       ├── types/              # Feature domain types
│   │       └── validators/         # Feature Zod schemas
│   │
│   ├── components/                 # Shared / reusable UI
│   │   ├── ui/                     # Shadcn UI primitives
│   │   ├── layout/                 # Sidebar, Navbar, Footer
│   │   ├── forms/                  # Reusable form components
│   │   ├── tables/                 # Data table components
│   │   ├── charts/                 # Dashboard chart wrappers
│   │   └── common/                 # Loading, empty states, etc.
│   │
│   ├── lib/                        # Shared utilities & config
│   │   ├── db/                     # Drizzle client + schema
│   │   │   ├── client.ts           # MySQL pool singleton
│   │   │   └── schema/             # Table definitions & relations
│   │   ├── auth/                   # Auth helpers & middleware
│   │   ├── api/                    # HTTP client, error handling
│   │   ├── validations/            # Cross-feature Zod schemas
│   │   ├── utils/                  # cn(), formatters, etc.
│   │   ├── constants/              # App-wide constants
│   │   ├── hooks/                  # Global React hooks
│   │   └── providers/              # QueryClient, Theme, Auth providers
│   │
│   ├── server/                     # Server-side business logic
│   │   ├── repositories/           # Data access (Repository Pattern)
│   │   ├── services/               # Business logic (Service Layer)
│   │   ├── middleware/             # API middleware (auth, rate limit)
│   │   └── dto/                    # Data Transfer Objects
│   │
│   ├── types/                      # Global TypeScript declarations
│   └── config/                     # App configuration
│       ├── site.ts                 # Site metadata
│       ├── roles.ts                # RBAC roles & permissions
│       └── navigation.ts           # Sidebar nav per role
│
├── tests/
│   ├── unit/                       # Unit tests
│   ├── integration/                # API integration tests
│   └── e2e/                        # End-to-end tests (Playwright)
│
├── .env.example                    # Environment variable template
├── components.json                 # Shadcn UI configuration
└── package.json
```

---

## User Roles & Route Access

| Role            | Route Prefix        | Capabilities                              |
| --------------- | ------------------- | ----------------------------------------- |
| Super Admin     | `/admin/*`          | Full system access                        |
| Branch Manager  | `/branch-manager/*` | Branch-scoped bookings, staff, reports    |
| Service Staff   | `/staff/*`          | Assigned jobs, photos, location tracking  |
| Customer        | `/(customer)/*`     | Book, track, pay, review                  |

---

## API Design Conventions

All REST endpoints follow these patterns:

```
GET    /api/{resource}           → List (paginated, filtered, sorted)
GET    /api/{resource}/:id       → Get single
POST   /api/{resource}           → Create
PATCH  /api/{resource}/:id       → Update
DELETE /api/{resource}/:id       → Delete
```

**Standard query params:** `page`, `limit`, `sort`, `order`, `search`, `filter[field]`

**Standard response envelope:**

```json
{
  "success": true,
  "data": {},
  "meta": { "page": 1, "limit": 20, "total": 100 }
}
```

---

## Service Extensibility

New services (e.g., "Pool Cleaning") are added **without code changes** via the admin UI:

1. Admin creates a **Category** (e.g., "Pool Cleaning")
2. Admin creates **Service** with sub-items (e.g., "Standard", "Deep")
3. Pricing, duration, and images are stored in the database
4. Booking flow dynamically renders available services from DB

The `Service` + `ServiceItem` + `Category` schema (Module 2) enables this.

---

## Module Implementation Order

| #  | Module              | Status      |
| -- | ------------------- | ----------- |
| 1  | Folder Structure    | ✅ Complete |
| 2  | Database Schema     | ✅ Complete (MySQL) |
| 3  | Authentication      | ✅ Complete |
| 4  | UI Layout           | ✅ Complete |
| 5  | Admin Dashboard     | ✅ Complete |
| 6  | Customer Module     | ✅ Complete |
| 7  | Employee Module     | ✅ Complete |
| 8  | Service Module      | ✅ Complete |
| 9  | Booking Module      | ✅ Complete |
| 10 | Payment Module      | ⏭️ Skipped (cash-only on job completion) |
| 11 | Reports             | ✅ Complete |
| 12 | Notifications       | ✅ Complete |
| 13 | Testing             | ✅ Complete |
| 14 | Deployment          | ✅ Complete |

> Production deploy steps: [docs/DEPLOYMENT.md](./DEPLOYMENT.md)


---

## Naming Conventions

| Item              | Convention              | Example                        |
| ----------------- | ----------------------- | ------------------------------ |
| Files             | kebab-case              | `booking-form.tsx`             |
| Components        | PascalCase              | `BookingForm`                  |
| Hooks             | camelCase with `use`    | `useBookings`                  |
| API routes        | kebab-case folders      | `/api/booking-items`           |
| DB tables         | snake_case plural       | `bookings`, `service_items`    |
| Drizzle models    | camelCase singular      | `bookings`, `serviceItems`     |
| Env variables     | SCREAMING_SNAKE_CASE    | `DATABASE_URL`                 |
| Types/Interfaces  | PascalCase              | `BookingStatus`, `CreateBookingDto` |
