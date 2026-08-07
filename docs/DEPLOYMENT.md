# Shine Express — Deployment Guide

Deploy the Next.js app on **Vercel** and MySQL on a managed provider. Clerk handles auth in production.

---

## Prerequisites

- GitHub repo connected to Vercel
- Clerk production application
- Managed MySQL with a reachable `DATABASE_URL` (SSL recommended)
- Optional: Resend for email notifications

---

## 1. Database

Recommended managed MySQL options:

| Provider | Notes |
|----------|--------|
| [PlanetScale](https://planetscale.com) / [TiDB Cloud](https://tidbcloud.com) | Serverless-friendly MySQL |
| [Railway](https://railway.app) / [Aiven](https://aiven.io) | Simple managed MySQL |
| AWS RDS / Azure MySQL | Traditional production MySQL |

### Apply schema

From a machine that can reach production MySQL:

```bash
# Set production connection string
export DATABASE_URL="mysql://USER:PASSWORD@HOST:3306/shine_express?ssl={\"rejectUnauthorized\":true}"

npm run db:migrate   # preferred for production
# or (dev / first bring-up):
npm run db:push

npm run db:seed      # roles, company, sample services
```

Local MySQL via Docker:

```bash
docker compose up -d mysql
# DATABASE_URL=mysql://shine:shine@127.0.0.1:3306/shine_express
```

---

## 2. Clerk (production)

1. Create a **Production** instance in Clerk.
2. Copy Production publishable + secret keys into Vercel env.
3. Set allowed redirect URLs to your Vercel domain:
   - `https://YOUR_DOMAIN/login`
   - `https://YOUR_DOMAIN/register`
   - `https://YOUR_DOMAIN/*` (after sign-in/up)
4. Create a webhook endpoint:
   - URL: `https://YOUR_DOMAIN/api/webhooks/clerk`
   - Events: `user.created`, `user.updated` (and `user.deleted` if desired)
   - Copy signing secret → `CLERK_WEBHOOK_SECRET`
5. Assign roles via Clerk `publicMetadata.role`:
   - `SUPER_ADMIN` | `BRANCH_MANAGER` | `SERVICE_STAFF` | `CUSTOMER`

---

## 3. Vercel

### Import & build

1. Import the GitHub repository in Vercel.
2. Framework preset: **Next.js** (auto-detected).
3. Build command: `npm run build`
4. Install command: `npm ci`
5. Output: Next.js default (no change needed).

`vercel.json` pins the framework and region.

### Environment variables

Set these in **Project → Settings → Environment Variables** (Production + Preview as appropriate):

| Variable | Required | Notes |
|----------|----------|--------|
| `DATABASE_URL` | ✅ | MySQL connection string |
| `NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY` | ✅ | Clerk production `pk_live_…` |
| `CLERK_SECRET_KEY` | ✅ | Clerk production `sk_live_…` |
| `CLERK_WEBHOOK_SECRET` | ✅ | `whsec_…` from Clerk webhook |
| `NEXT_PUBLIC_APP_URL` | ✅ | `https://YOUR_DOMAIN` |
| `NEXT_PUBLIC_CLERK_SIGN_IN_URL` | ✅ | `/login` |
| `NEXT_PUBLIC_CLERK_SIGN_UP_URL` | ✅ | `/register` |
| `NEXT_PUBLIC_CLERK_AFTER_SIGN_IN_URL` | ✅ | `/admin` (or role-aware default) |
| `NEXT_PUBLIC_CLERK_AFTER_SIGN_UP_URL` | ✅ | `/book` for customer signups |
| `RESEND_API_KEY` | Optional | Email notifications |
| `EMAIL_FROM` | Optional | Verified sender on Resend |

Use the checklist in [`.env.example`](../.env.example) for the full list.

### Domains

1. Add a custom domain in Vercel.
2. Update `NEXT_PUBLIC_APP_URL` and Clerk allowed origins/redirects to match.

---

## 4. Post-deploy checks

```bash
# Health (DB ping)
curl -s https://YOUR_DOMAIN/api/health | jq

# Expect: { "success": true, "data": { "status": "ok", "database": "up" } }
```

Manual smoke:

1. Open `/login` — Clerk sign-in loads
2. Sign in as Super Admin → `/admin` dashboard
3. Create a booking as Customer → appears in admin bookings
4. Assign staff → staff sees job; notifications appear in bell
5. Complete job → cash payment recorded; reports update

---

## 5. CI

GitHub Actions (`.github/workflows/ci.yml`) runs on push/PR:

- `npm ci`
- `npm run lint`
- `npm run typecheck`
- `npm test`
- `npm run build` (with placeholder public env for compile)

Database and Clerk secrets are **not** required for CI build when public Clerk keys are stubbed for compile-only.

---

## 6. Operational notes

- **Migrations:** run `db:migrate` as part of release (local/CI job or one-off), not inside the Vercel serverless cold start.
- **Cash-only payments:** no payment gateway env required.
- **Email:** without `RESEND_API_KEY`, notifications stay in-app (email channel skipped).
- **Monitoring:** point an uptime checker at `/api/health`.
- **Rollback:** redeploy the previous Vercel deployment; reverse DB migrations only if a migration shipped with the bad release.
