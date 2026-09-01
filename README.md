# Connextion Platform

A production-ready job board connecting US-based hiring companies with skilled international job seekers.

## Stack
- **Backend**: Laravel 11 (REST API)
- **Frontend**: Vue 3 + Vite + Pinia
- **Database**: MySQL 8
- **Auth**: Laravel Sanctum (token-based, role-aware)
- **Styling**: TailwindCSS 3

---

## Architecture Overview

```
connextion-platform/
├── backend/                  # Laravel 11 API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── Admin/
│   │   │   │   ├── Employer/
│   │   │   │   └── JobSeeker/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Notifications/
│   │   └── Policies/
│   ├── database/migrations/
│   └── routes/api.php
│
└── frontend/                 # Vue 3 SPA
    └── src/
        ├── api/              # Axios service modules
        ├── components/       # Reusable UI components
        ├── layouts/          # Page shell layouts
        ├── pages/            # Route-level views
        ├── router/
        └── stores/           # Pinia stores
```

---

## Quick Setup

### Prerequisites
- PHP 8.3+, Composer 2
- Node 20+, npm 10
- MySQL 8

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Edit .env with your DB credentials
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
cp .env.example .env
# Set VITE_API_BASE_URL=http://localhost:8000/api
npm run dev
```

---

## User Roles
| Role | Description |
|------|-------------|
| `admin` | Full platform management |
| `employer` | Post jobs, manage applications |
| `job_seeker` | Apply to jobs, manage profile |

## Key API Groups
| Prefix | Audience |
|--------|----------|
| `/api/auth/*` | Public (login, register) |
| `/api/employer/*` | Authenticated employers |
| `/api/job-seeker/*` | Authenticated job seekers |
| `/api/admin/*` | Admins only |
| `/api/jobs` | Public job listings |

---

## Employer & Candidate Verification

Trust signals on both sides of the marketplace. A **verified employer** badge on
job listings, and **ID / GitHub verified** badges on candidates.

`verifications` is a polymorphic table — one row per subject per type — and it
is the source of truth. `employer_profiles.is_verified` and
`job_seeker_profiles.verified_badges` are denormalised copies maintained by
`VerificationService`, so listings render a badge without a join.

### What runs where

| Check | Provider | Works out of the box |
|-------|----------|----------------------|
| `work_email_domain` | DNS | **Yes** — no credentials needed |
| `github_oauth` | GitHub | Needs `GITHUB_CLIENT_ID` / `GITHUB_CLIENT_SECRET` |
| `company_registry` | OpenCorporates | Not wired up — see below |
| `government_id` | Stripe Identity / Sumsub | Not wired up — see below |

**Domain verification** is the one that works on a fresh install, and it is the
strongest signal available without a vendor account:

1. The account email must be on a company domain — free and disposable
   providers (`gmail.com`, `mailinator.com`, …) are refused outright.
2. It must correspond to the company website when one is on file.
3. The domain must publish an MX record, so it genuinely receives mail.
4. The employer publishes a `TXT` record containing a token unique to their
   profile. **Passing this is what grants the verification** — the rest are
   preconditions.

`company_registry` and `government_id` resolve to an `UnconfiguredChecker`,
which reports itself unavailable. The API answers **503** rather than recording
a rejection an applicant cannot act on. Bind a real checker in
`AppServiceProvider` once you have credentials — anything implementing
`VerificationChecker` slots straight in.

### Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/employer/verification` | Status, available checks, DNS record to publish |
| `POST` | `/api/employer/verification` | Run a check (`type`, optional registration number) |
| `GET` | `/api/job-seeker/verification` | Candidate status and badges |
| `POST` | `/api/job-seeker/verification` | Complete a check (e.g. GitHub OAuth `code`) |
| `GET` | `/api/admin/verifications` | Review queue, pending first |
| `PATCH` | `/api/admin/verifications/{id}` | Manual approve / reject, reviewer recorded |

### Gating access

The `verified` middleware refuses unverified employers with `403` and
`code: VERIFICATION_REQUIRED`. Enforcement is off unless
`VERIFY_REQUIRE_FOR_POSTING=true`, so adding it to a route doesn't lock out
existing employers the day it ships.

Verifications expire (`VERIFY_DOMAIN_TTL_DAYS`, default a year). An approved
row past its expiry never reads as verified — `expireStale()` sweeps them and
refreshes the badges.

---

## Running the tests

```bash
cd backend
php vendor/bin/phpunit
```

Tests run against an in-memory SQLite database and never touch the network —
DNS is stubbed through the `DnsResolver` contract.

---

## Deployment (Production)

See `DEPLOYMENT.md` for full Nginx + MySQL + Supervisor config.

Environment highlights:
- Set `APP_ENV=production`, `APP_DEBUG=false`
- Use `QUEUE_CONNECTION=redis` for emails & notifications
- Run `php artisan config:cache && php artisan route:cache`
- Use S3 or similar for file storage in production
