# Step-by-Step Local Setup

## Prerequisites
- PHP 8.4+ (`php -v`)
- Composer 2 (`composer -V`)
- Node 20+ (`node -v`)
- MySQL 8 running locally
- Git

---

## Step 1 — Create MySQL Database

```sql
CREATE DATABASE connextion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'connextion'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON connextion.* TO 'connextion'@'localhost';
FLUSH PRIVILEGES;
```

---

## Step 2 — Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Edit .env — set these at minimum:
#   DB_DATABASE=connextion
#   DB_USERNAME=connextion
#   DB_PASSWORD=secret
#   FRONTEND_URL=http://localhost:5173

# Run migrations + seed demo data
php artisan migrate --seed

# Create storage symlink (for file uploads)
php artisan storage:link

# Start the API server
php artisan serve
# → API available at http://localhost:8000/api
```

---

## Step 3 — Frontend Setup

```bash
cd frontend

# Install JS dependencies
npm install

# Copy environment
cp .env.example .env
# .env already points to http://localhost:8000/api

# Start Vite dev server
npm run dev
# → Frontend available at http://localhost:5173
```

---

## Step 4 — Test the App

Open **http://localhost:5173** in your browser.

### Demo accounts (from seeder):

| Role       | Email                  | Password  |
|------------|------------------------|-----------|
| Admin      | admin@connextion.io    | password  |
| Employer   | employer@demo.com      | password  |
| Job Seeker | seeker@demo.com        | password  |

---

## Step 5 — Queue Worker (for email notifications)

In a separate terminal:

```bash
cd backend
php artisan queue:work
```

Or use `QUEUE_CONNECTION=sync` in `.env` during development to run jobs immediately (no worker needed).

---

## Useful Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# Create a new admin user
php artisan tinker
>>> App\Models\User::create(['name'=>'Admin','email'=>'a@b.com','password'=>bcrypt('pass'),'role'=>'admin'])

# Re-run migrations fresh
php artisan migrate:fresh --seed

# List all API routes
php artisan route:list --path=api
```

---

## API Testing with cURL

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"t@t.com","password":"password","password_confirmation":"password","role":"job_seeker"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"seeker@demo.com","password":"password"}'

# Search jobs (public)
curl http://localhost:8000/api/jobs?location_type=remote

# Get job detail
curl http://localhost:8000/api/jobs/senior-laravel-developer-remote-xxxxxx
```
