# Deployment Checklist & Quick Start

## Pre-Deployment Setup (Development)

### 1. Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Update .env with your database and mail credentials
# Key settings:
# - APP_ENV=production
# - APP_DEBUG=false
# - DB_CONNECTION=mysql
# - MAIL_MAILER=smtp
# - QUEUE_CONNECTION=redis
```

### 2. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed initial data (users, roles, departments)
php artisan db:seed

# (Optional) Seed with demo data
php artisan db:seed --class=DemoDataSeeder
```

### 3. Cache & Config

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear and cache facades
php artisan optimize
```

### 4. File Permissions

```bash
# Set proper permissions on storage and bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# On shared hosting (cPanel):
# - Set storage directory to 755
# - Set storage/logs, storage/app to 755
# - Ensure web root points to public/
```

---

## Deployment to Production (cPanel/VPS)

### Step 1: Upload Files

- Upload all files EXCEPT: `node_modules/`, `.git/`, `.env` (use .env.production instead)
- Ensure `.htaccess` in public/ is present for URL rewriting

### Step 2: Setup Database

```bash
# Create MySQL database via cPanel or:
# mysql -u root -p
# CREATE DATABASE procurement_db;
# CREATE USER 'procurement'@'localhost' IDENTIFIED BY 'strong_password';
# GRANT ALL PRIVILEGES ON procurement_db.* TO 'procurement'@'localhost';
# FLUSH PRIVILEGES;
```

### Step 3: Environment Configuration

- Copy `.env.example` to `.env`
- Update credentials:
  ```env
  APP_ENV=production
  APP_DEBUG=false
  DB_HOST=localhost
  DB_DATABASE=procurement_db
  DB_USERNAME=procurement
  DB_PASSWORD=your_strong_password
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=your-email@gmail.com
  MAIL_PASSWORD=your-app-password
  QUEUE_CONNECTION=redis
  REDIS_HOST=127.0.0.1
  ```

### Step 4: Install Composer Dependencies

```bash
# SSH into server
cd /home/username/public_html/procurement

# Install dependencies
composer install --no-dev --optimize-autoloader
```

### Step 5: Run Migrations & Seeds

```bash
# Run database migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force
```

### Step 6: Generate Application Key

```bash
php artisan key:generate --force
```

### Step 7: Optimize Installation

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views (optional, but recommended)
# php artisan view:cache
```

### Step 8: Setup Queue Workers (for background jobs)

Create a cron job to process queues:

**Option A: Using Supervisor (Recommended)**

```bash
# Create supervisor config at /etc/supervisor/conf.d/procurement.conf
[program:procurement-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/username/public_html/procurement/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
user=nobody
redirect_stderr=true
stdout_logfile=/var/log/procurement-worker.log

# Restart supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

**Option B: Using Cron (Alternative)**
Add to crontab:

```bash
* * * * * php /home/username/public_html/procurement/artisan schedule:run >> /dev/null 2>&1
* * * * * php /home/username/public_html/procurement/artisan queue:work --once
```

### Step 9: Setup Scheduled Tasks

Add cron for Laravel scheduler:

```bash
* * * * * /usr/bin/php /home/username/public_html/procurement/artisan schedule:run >> /dev/null 2>&1
```

This will run:

- Daily FX rate updates
- Monthly audit log archival
- Low stock checks
- Budget threshold monitoring
- Scheduled report distribution

### Step 10: SSL/HTTPS Configuration

```bash
# Using Let's Encrypt (Free)
# Through cPanel: AutoSSL addon
# Or via command line:
certbot certonly --webroot -w /home/username/public_html -d yourdomain.com

# Update .env
APP_URL=https://yourdomain.com
```

### Step 11: Configure Mail (Using Gmail)

1. Enable 2FA on Gmail account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password in `.env`:
   ```env
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-16-char-app-password
   ```

### Step 12: Storage Link

```bash
php artisan storage:link
```

Creates symlink: `public/storage` → `storage/app/public`

---

## Post-Deployment Verification

### 1. Test Application

- Open https://yourdomain.com in browser
- Login with credentials created during seeding

### 2. Test Email Notifications

```bash
php artisan tinker
# In tinker:
Mail::raw('Test email', function ($m) { $m->to('your-email@example.com'); });
```

### 3. Verify Queue Processing

```bash
php artisan queue:work --timeout=60 # Start queue worker manually
```

### 4. Check Scheduled Tasks

```bash
# List all scheduled tasks
php artisan schedule:list
```

### 5. Monitor Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log
```

---

## Environment Variables Reference

### Required

```env
APP_NAME="Procurement System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=base64:xxxxx

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=procurement_db
DB_USERNAME=procurement
DB_PASSWORD=strong_password
```

### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=app-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Procurement System"
```

### Queue & Redis

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### SMS Configuration

```env
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_PHONE=+1234567890

AFRICAS_TALKING_API_KEY=your_api_key
AFRICAS_TALKING_USERNAME=your_username
```

### FX Rates (Exchange Rates)

```env
OPENEXCHANGERATES_API_KEY=your_api_key
FIXER_API_KEY=your_api_key
XE_API_KEY=your_api_key
```

---

## Troubleshooting

### 1. 500 Error

Check logs:

```bash
tail -f storage/logs/laravel.log
```

### 2. Database Connection Error

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo()
```

### 3. Queue Not Processing

```bash
# Check if Redis is running
redis-cli ping  # Should return PONG

# Check supervisor status (if using)
sudo supervisorctl status
```

### 4. Email Not Sending

```bash
# Test SMTP credentials
php artisan tinker
Mail::raw('Test', function ($m) { $m->to('test@example.com'); });
```

### 5. Cache Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Regular Maintenance

### Daily Backups

```bash
# Create automated backups
0 2 * * * /usr/bin/mysqldump -u procurement -ppassword procurement_db | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

### Monthly Tasks

- Review audit logs
- Check budget variance reports
- Verify supplier performance ratings
- Archive old purchase orders

### Quarterly Updates

- Update exchange rates manually if needed
- Review and update supplier details
- Analyze procurement metrics
- Plan budget for next period

---

## Getting Support

- Check logs in `storage/logs/laravel.log`
- Review error messages in database `audit_logs` table
- Contact system administrator
- Check application documentation at `/procurement/docs`
