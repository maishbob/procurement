# Kenya School Procurement System - Deployment Guide

## System Overview

**Purpose**: Institutional governance system for procurement, inventory, and financial control  
**Context**: Kenya compliance-aware (KRA, eTIMS, WHT, VAT)  
**Architecture**: Modular Monolith  
**Stack**: Laravel 10+ | PHP 8+ | MySQL 8 | cPanel/VPS

---

## Pre-Deployment Checklist

### Server Requirements

- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Composer**: Latest version
- **Extensions Required**:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

### cPanel/VPS Configuration

1. **PHP Configuration**
   - `memory_limit`: 256M minimum
   - `max_execution_time`: 300
   - `upload_max_filesize`: 20M
   - `post_max_size`: 25M

2. **MySQL Configuration**
   - Create database: `procurement_db`
   - Create user with full privileges
   - Character set: `utf8mb4`
   - Collation: `utf8mb4_unicode_ci`

---

## Installation Steps

### 1. Upload Files

Upload all project files to your web root directory (e.g., `public_html/procurement`).

```bash
# Using FTP/SFTP, upload:
- All /app files
- All /config files
- All /database files
- All /routes files
- All /resources files
- /vendor folder (or install via Composer)
- composer.json and composer.lock
- .env.example
```

### 2. Install Dependencies

```bash
cd /path/to/procurement
composer install --optimize-autoloader --no-dev
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

**Edit `.env` file**:

```env
APP_NAME="School Procurement System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourschool.ac.ke/procurement

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=procurement_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Kenya Configuration
APP_TIMEZONE=Africa/Nairobi
DEFAULT_CURRENCY=KES

# Tax Rates (Kenya)
DEFAULT_VAT_RATE=16
DEFAULT_WHT_RATE_SERVICES=5
DEFAULT_WHT_RATE_PROFESSIONAL=5

# Procurement Thresholds (KES)
THRESHOLD_HOD_APPROVAL=50000
THRESHOLD_PRINCIPAL_APPROVAL=200000
THRESHOLD_BOARD_APPROVAL=1000000
THRESHOLD_TENDER_REQUIRED=500000

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourschool.ac.ke
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="procurement@yourschool.ac.ke"

# SMS Configuration (Optional - Africa's Talking)
SMS_ENABLED=false
SMS_DRIVER=africastalking
SMS_API_KEY=your_api_key
SMS_USERNAME=your_username
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### 5. Storage Configuration

```bash
# Create symbolic link for storage
php artisan storage:link

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 6. cPanel Configuration

#### A. Document Root

Set your domain's document root to: `/public_html/procurement/public`

#### B. .htaccess Configuration

Ensure `/public/.htaccess` contains:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## Queue Worker Setup (Critical)

The system uses queues for:

- Email notifications
- SMS notifications
- Report generation
- Audit log archiving

### Option 1: Supervisor (Recommended for VPS)

Create `/etc/supervisor/conf.d/procurement-worker.conf`:

```ini
[program:procurement-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/procurement/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/procurement/storage/logs/worker.log
stopwaitsecs=3600
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start procurement-worker:*
```

### Option 2: Cron Job (For cPanel)

Add to crontab:

```bash
* * * * * cd /path/to/procurement && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/procurement && php artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

---

## First-Time System Setup

### 1. Create Super Administrator

```bash
php artisan tinker
```

```php
$user = new App\Modules\Users\Models\User();
$user->name = 'System Administrator';
$user->email = 'admin@yourschool.ac.ke';
$user->password = bcrypt('SecurePassword123!');
$user->is_active = true;
$user->save();

// Assign super-admin role
$role = DB::table('roles')->where('slug', 'super-admin')->first();
DB::table('user_roles')->insert([
    'user_id' => $user->id,
    'role_id' => $role->id,
    'is_primary' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### 2. Initial System Configuration

Login with super admin credentials and configure:

1. **Departments**
   - Create all school departments
   - Assign Heads of Department

2. **Cost Centers**
   - Map cost centers to departments
   - Assign budget owners

3. **Budget Lines**
   - Create budget lines for current fiscal year
   - Set allocated amounts

4. **Approval Hierarchies**
   - Configure approval levels per department
   - Set threshold amounts

5. **Suppliers**
   - Register approved suppliers
   - Verify KRA PINs
   - Upload compliance certificates

6. **Exchange Rates**
   - Set initial exchange rates (USD, GBP, EUR to KES)

---

## Security Configuration

### 1. Production Security

```env
APP_DEBUG=false
APP_ENV=production
```

### 2. SSL/HTTPS

- Force HTTPS in production
- Update `APP_URL=https://...` in `.env`

### 3. Folder Permissions

```bash
# Application folders
find /path/to/procurement -type d -exec chmod 755 {} \;
find /path/to/procurement -type f -exec chmod 644 {} \;

# Storage and cache (writable)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 4. Hide Sensitive Files

Add to root `.htaccess`:

```apache
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## Backup Strategy

### 1. Database Backup (Daily)

```bash
#!/bin/bash
# /usr/local/bin/backup-procurement-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/procurement"
DB_NAME="procurement_db"
DB_USER="your_db_user"
DB_PASS="your_db_password"

mkdir -p $BACKUP_DIR

mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/procurement_$DATE.sql.gz

# Keep last 30 days
find $BACKUP_DIR -name "procurement_*.sql.gz" -mtime +30 -delete
```

Add to cron:

```bash
0 2 * * * /usr/local/bin/backup-procurement-db.sh
```

### 2. File Backup (Weekly)

```bash
#!/bin/bash
# /usr/local/bin/backup-procurement-files.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backups/procurement"
APP_DIR="/path/to/procurement"

tar -czf $BACKUP_DIR/files_$DATE.tar.gz \
    $APP_DIR/storage/app/attachments \
    $APP_DIR/storage/app/audits \
    $APP_DIR/.env

# Keep last 12 weeks
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +84 -delete
```

Add to cron:

```bash
0 3 * * 0 /usr/local/bin/backup-procurement-files.sh
```

### 3. Audit Log Archiving (Monthly)

The system auto-archives audit logs older than 365 days.

Manual archive:

```bash
php artisan audit:archive
```

---

## Maintenance Operations

### 1. Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 3. Update Exchange Rates

```bash
# Manual via tinker
php artisan tinker
```

```php
DB::table('exchange_rates')->insert([
    'from_currency' => 'USD',
    'to_currency' => 'KES',
    'rate' => 132.50,
    'effective_date' => now()->toDateString(),
    'source' => 'manual',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### 4. Monitor Queue

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Logging & Monitoring

### 1. Log Files

- **Application**: `storage/logs/laravel.log`
- **Queue**: `storage/logs/worker.log`
- **Audit**: Database table `audit_logs`

### 2. Log Rotation

Add to logrotate config `/etc/logrotate.d/procurement`:

```
/path/to/procurement/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

### 3. Monitoring Checklist

Daily:

- Check queue worker status
- Review failed jobs
- Monitor disk space

Weekly:

- Review error logs
- Check database size
- Verify backup completion

Monthly:

- Audit log archiving
- Performance review
- Security updates

---

## Troubleshooting

### Queue Not Processing

```bash
# Check if worker is running
ps aux | grep queue:work

# Restart worker (supervisor)
sudo supervisorctl restart procurement-worker:*

# Check failed jobs
php artisan queue:failed
```

### Database Connection Issues

```bash
# Test connection
php artisan tinker
DB::connection()->getPdo();
```

### Permission Errors

```bash
# Reset permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 500 Server Error

1. Check `storage/logs/laravel.log`
2. Verify `.env` configuration
3. Clear cache: `php artisan cache:clear`
4. Check file permissions

---

## System Updates

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
sudo supervisorctl restart procurement-worker:*
```

---

## Support & Documentation

### Key Configuration Files

- `/config/procurement.php` - Kenya-specific settings
- `/.env` - Environment configuration
- `/config/database.php` - Database settings

### Database Schema Documentation

See: `/database/migrations/` - All table structures with comments

### API Documentation

(To be developed in Phase 2)

---

## Compliance Notes

### Kenya KRA Requirements

1. **Supplier KRA PIN**: Mandatory for all suppliers
2. **WHT Deduction**: Automatic calculation and certificate generation
3. **VAT Handling**: 16% standard rate, with exempt/zero-rated support
4. **eTIMS Integration**: Structure ready, activate when available

### Audit Requirements

- All transactions logged (immutable)
- 7-year retention policy
- State transition tracking
- Approval decision recording
- Segregation of duties enforcement

---

## Production Checklist

- [ ] Database created and migrated
- [ ] Super admin user created
- [ ] `.env` configured for production
- [ ] APP_DEBUG=false
- [ ] HTTPS forced
- [ ] Queue workers running
- [ ] Cron jobs configured
- [ ] Backups scheduled
- [ ] Log rotation configured
- [ ] File permissions set correctly
- [ ] Exchange rates loaded
- [ ] Departments created
- [ ] Budget lines configured
- [ ] Approval hierarchies defined
- [ ] Initial suppliers registered
- [ ] Email/SMS tested

---

## Emergency Contacts

**System Administrator**: [Your IT Team]  
**Database Admin**: [DBA Contact]  
**Hosting Provider**: [Support Details]

---

**Document Version**: 1.0  
**Last Updated**: February 2026  
**System Version**: Phase 1 - Core Procurement & Governance
