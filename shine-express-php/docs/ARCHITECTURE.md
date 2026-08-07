# Shine Express — PHP Architecture

> Multi-service business SaaS for **shared PHP hosting** (no Composer).

## Tech Stack

| Layer | Technology |
|-------|------------|
| Language | PHP 8.1+ (OOP, strict types) |
| Pattern | Custom MVC (front controller) |
| Database | MySQL / MariaDB + PDO |
| Auth | Session + `password_hash` + RBAC |
| UI | Server-rendered PHP views + CSS |
| Hosting | Apache + `public/` document root |

## Request flow

```
HTTP → public/index.php → Router → Middleware → Controller → Service/Model → View
                                                      ↘ PDO
```

## Module status

All modules **1–14** are implemented. See [README.md](../README.md) and [DEPLOYMENT.md](./DEPLOYMENT.md).

## Roles

| Role | Home |
|------|------|
| SUPER_ADMIN | `/admin` |
| BRANCH_MANAGER | `/branch-manager` |
| SERVICE_STAFF | `/staff/jobs` |
| CUSTOMER | `/book` |

## Booking workflow

`PENDING → CONFIRMED → ASSIGNED → ACCEPTED → ON_THE_WAY → STARTED → COMPLETED`  
(+ cancel/reject paths). Completing a job auto-inserts a **CASH** payment.
