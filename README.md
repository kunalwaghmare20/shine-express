# Shine Express

Production-ready SaaS platform for managing multi-service businesses — house cleaning, car cleaning, water tank cleaning, pest control, and more.

## Tech Stack

- **Frontend:** Next.js 15+, React 19, TypeScript, Tailwind CSS, Shadcn UI
- **Backend:** Next.js API Routes, Drizzle ORM, MySQL
- **Auth:** Clerk + RBAC
- **Deployment:** Vercel + PlanetScale / AWS RDS / local MySQL

## Getting Started

```bash
# Install dependencies
npm install

# Copy environment variables
cp .env.example .env.local

# Configure MySQL + Clerk in .env.local (required for auth routes)
# DATABASE_URL=mysql://user:password@localhost:3306/shine_express
# NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY=pk_test_...
# CLERK_SECRET_KEY=sk_test_...
# CLERK_WEBHOOK_SECRET=whsec_...

# Push schema to database
npm run db:push

# Seed roles, permissions, company, and services
npm run db:seed

# Run development server
npm run dev
```

### Database Commands

| Command | Description |
|---------|-------------|
| `npm run db:generate` | Generate Drizzle migrations |
| `npm run db:push` | Push schema to MySQL (dev) |
| `npm run db:migrate` | Run migrations |
| `npm run db:seed` | Seed initial data |
| `npm run db:studio` | Open Drizzle Studio GUI |

### Testing

```bash
# Unit + integration (Vitest)
npm test

# Watch mode
npm run test:watch

# E2E smoke (start the app first, then:)
npx playwright install chromium   # once
npm run test:e2e
```

| Suite | Location | Tool |
|-------|----------|------|
| Unit | `tests/unit` | Vitest |
| Integration | `tests/integration` | Vitest |
| E2E | `tests/e2e` | Playwright |

Open [http://localhost:3000](http://localhost:3000).

## Project Structure

See [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) for the full folder structure, architecture layers, and module implementation plan.

## Implementation Progress

| Module | Status |
|--------|--------|
| 1. Folder Structure | ✅ Complete |
| 2. Database Schema | ✅ Complete (MySQL + Drizzle) |
| 3. Authentication | ✅ Complete |
| 4. UI Layout | ✅ Complete |
| 5. Admin Dashboard | ✅ Complete |
| 6. Customer Module | ✅ Complete |
| 7. Employee Module | ✅ Complete |
| 8. Service Module | ✅ Complete |
| 9. Booking Module | ✅ Complete |
| 10. Payment Module | ⏭️ Skipped (cash-only) |
| 11. Reports | ✅ Complete |
| 12. Notifications | ✅ Complete |
| 13. Testing | ✅ Complete |
| 14. Deployment | ✅ Complete |

## Deployment

See **[docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md)** for Vercel + MySQL + Clerk production setup.

```bash
# Local MySQL
docker compose up -d mysql

# Quality gate (same as CI)
npm run ci

# Health check (after deploy)
curl https://YOUR_DOMAIN/api/health
```

## PHP shared-hosting port

A full PHP MVC rebuild (no Composer) lives in [`shine-express-php/`](./shine-express-php/). Use that for unlimited PHP + MySQL hosting. See its README for setup.

## Android mobile app

Expo app for customers & employees: [`shine-express-mobile/`](./shine-express-mobile/).  
Requires the PHP API (`/api/v1`) — see `shine-express-php/docs/MOBILE_API.md`.

## User Roles

- **Super Admin** — Full system access
- **Branch Manager** — Branch-scoped operations
- **Service Staff** — Job execution & tracking
- **Customer** — Booking & self-service portal
