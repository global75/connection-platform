# Connextion Platform — Deployment Guide

## Production Stack
- **Server**: Ubuntu 22.04 (AWS EC2, DigitalOcean, etc.)
- **Web**: Nginx + PHP 8.4-FPM
- **DB**: MySQL 8
- **Queue**: Redis + Laravel Horizon
- **Storage**: AWS S3 (or local with Nginx serving `/storage`)
- **SSL**: Let's Encrypt (Certbot)

---

## 1. Server Setup

```bash
# Install dependencies
sudo apt update && sudo apt install -y git curl unzip nginx mysql-server redis-server

# PHP 8.4
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.4-fpm php8.4-mysql php8.4-redis php8.4-gd \
     php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Clone & Configure

```bash
cd /var/www
git clone https://github.com/your-org/connextion-platform.git
cd connextion-platform

# Backend
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Edit .env — set DB, mail, Stripe, APP_URL, etc.
nano .env

php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd ../frontend
npm ci
cp .env.example .env
# Set VITE_API_BASE_URL=https://yourdomain.com/api
npm run build
```

---

## 3. Nginx Config

```nginx
# /etc/nginx/sites-available/connextion

server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name yourdomain.com;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Frontend SPA (built static files)
    root /var/www/connextion-platform/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api {
        root /var/www/connextion-platform/backend/public;
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/run/php/php8.4-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/connextion-platform/backend/public$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Laravel storage
    location /storage {
        alias /var/www/connextion-platform/backend/storage/app/public;
    }

    client_max_body_size 10M;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/connextion /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# SSL
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## 4. Permissions

```bash
sudo chown -R www-data:www-data /var/www/connextion-platform/backend/storage
sudo chown -R www-data:www-data /var/www/connextion-platform/backend/bootstrap/cache
sudo chmod -R 775 /var/www/connextion-platform/backend/storage
```

---

## 5. Queue Worker (Supervisor)

```bash
sudo apt install supervisor
```

```ini
# /etc/supervisor/conf.d/connextion-worker.conf
[program:connextion-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/connextion-platform/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/connextion-platform/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start connextion-worker:*
```

---

## 6. Scheduled Tasks (Cron)

```bash
sudo crontab -e -u www-data
# Add:
* * * * * php /var/www/connextion-platform/backend/artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Production `.env` Highlights

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=connextion_prod
DB_USERNAME=connextion
DB_PASSWORD=strong_password_here

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1

MAIL_MAILER=ses           # or mailgun, postmark
FILESYSTEM_DISK=s3        # for file uploads in production
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=connextion-uploads

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
```

---

## 8. Database Backups (mysqldump + cron)

```bash
# /etc/cron.d/connextion-backup
0 2 * * * root mysqldump connextion_prod | gzip > /backups/connextion_$(date +\%Y\%m\%d).sql.gz
```

---

## 9. Monitoring Checklist

- [ ] Set up **Sentry** for error tracking (`composer require sentry/sentry-laravel`)
- [ ] Enable **Laravel Telescope** in staging only
- [ ] Monitor queue health with **Laravel Horizon**
- [ ] Set up uptime monitoring (Better Uptime, UptimeRobot)
- [ ] Configure **log rotation** for storage/logs
