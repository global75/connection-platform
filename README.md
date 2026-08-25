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

## AI Lead Qualification

Every incoming application is scored against the job it targets so employers can
work their pipeline highest-intent first. Scoring runs on the queue the moment an
application is created — the applicant is never kept waiting on an AI call.

A verdict carries an overall score (0–100), a **hot / warm / cold** tier, a
recommended action (`shortlist` / `review` / `reject`), a recruiter-facing summary,
concrete strengths and concerns, and a per-dimension breakdown across skills,
experience, compensation, location & work authorisation, and application intent.

### How it is scored

| Driver | When it is used |
|--------|-----------------|
| `claude` | An `ANTHROPIC_API_KEY` is configured (structured-output call to the Claude Messages API). |
| `heuristic` | No key configured, or the Claude call fails and `AI_LEAD_QUALIFICATION_FALLBACK` is on. |

The heuristic scorer is deterministic and dependency-free, so the feature works on
a fresh install with no credentials — and a Claude outage degrades the quality of a
verdict rather than losing it. Applicant-written text (cover letters, bios) is
passed to the model as untrusted data, and every field of the response is clamped
and whitelisted before it reaches the database.

### Configuration

All settings live in `config/ai.php` and are driven by `.env` — see
`backend/.env.example` for the full list. The most-used ones:

```env
ANTHROPIC_API_KEY=sk-ant-...
AI_LEAD_QUALIFICATION_ENABLED=true
AI_LEAD_QUALIFICATION_DRIVER=auto            # auto | claude | heuristic
AI_LEAD_QUALIFICATION_MODEL=claude-opus-5
AI_LEAD_QUALIFICATION_HOT_AT=75              # score at which a lead is "hot"
AI_LEAD_QUALIFICATION_WARM_AT=50
AI_LEAD_QUALIFICATION_NOTIFY_HOT=true        # email the employer on a hot lead
AI_LEAD_QUALIFICATION_AUTO_SHORTLIST=false   # auto-advance "shortlist" verdicts
```

> Qualification runs on the queue, so a worker must be running
> (`php artisan queue:work`) — or set `QUEUE_CONNECTION=sync` in development.

### Employer endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/employer/applications?tier=hot&min_score=70&sort=score` | Filter and sort the pipeline by verdict |
| `GET` | `/api/employer/applications/qualification-summary` | Tier counts, average scores, backlog |
| `POST` | `/api/employer/applications/{application}/qualify` | Re-run qualification for one application |

### Backfilling

```bash
php artisan leads:qualify                  # queue anything never qualified
php artisan leads:qualify --retry-failed   # also retry failures
php artisan leads:qualify --force --job=12 # re-qualify one job's applications
```

Scores are decision support, not a decision: they rank a pipeline, they do not
reject anyone. The UI states this wherever a score is shown.

---

## Deployment (Production)

See `DEPLOYMENT.md` for full Nginx + MySQL + Supervisor config.

Environment highlights:
- Set `APP_ENV=production`, `APP_DEBUG=false`
- Use `QUEUE_CONNECTION=redis` for emails & notifications
- Run `php artisan config:cache && php artisan route:cache`
- Use S3 or similar for file storage in production
