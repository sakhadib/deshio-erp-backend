# Deshio ERP - Deployment Guide

## 🚀 Quick Deployment Guide

### Prerequisites
- PHP 8.1 or higher
- Composer 2.x
- MySQL 8.0 or higher
- Node.js 16+ and NPM (for frontend assets)
- Git

---

## 📦 Initial Setup (After Git Clone)

### 1. Clone Repository
```bash
git clone https://github.com/sakhadib/deshio-erp-backend.git
cd deshio-erp-backend/backend
```

### 2. Install PHP Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

**For Development:**
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure `.env` File
Edit `.env` and set your database credentials:

```env
APP_NAME="Deshio ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deshio_erp_2
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# JWT Secret (will be auto-generated if not set)
JWT_SECRET=

# Mail Configuration (Optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@deshio.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Database Setup

**Fresh Installation:**
```bash
# Run migrations and seeders
php artisan migrate:fresh --seed
```

**Production Migration (Preserve Data):**
```bash
# Run only new migrations
php artisan migrate --force
```

### 6. Generate JWT Secret (if not auto-generated)
```bash
php artisan jwt:secret
```

### 7. Storage & Cache Configuration
```bash
# Create symbolic link for storage
php artisan storage:link

# Clear and optimize caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Set Permissions (Linux/Ubuntu)
```bash
# Set ownership
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
```

**For Shared Hosting:**
```bash
chmod -R 755 storage bootstrap/cache
```

---

## 🔐 Default Admin Credentials

After running seeders, you can login with:

```
Email: mueedibnesami.anoy@gmail.com
Password: password
Store: Main Store
```

**⚠️ IMPORTANT:** Change the default password immediately after first login!

---

## 🌐 Web Server Configuration

### Apache (.htaccess)

The `.htaccess` file is already included in the `public` folder. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Virtual Host Configuration:**
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/deshio-erp-backend/backend/public

    <Directory /var/www/deshio-erp-backend/backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/deshio-erp-error.log
    CustomLog ${APACHE_LOG_DIR}/deshio-erp-access.log combined
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/deshio-erp-backend/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🔄 Update/Deployment Process

### Pull Latest Changes
```bash
# Navigate to project
cd /var/www/deshio-erp-backend/backend

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations (if any)
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart queue workers (if using)
php artisan queue:restart
```

---

## 📊 Database Backup

### Manual Backup
```bash
# Backup database
mysqldump -u username -p deshio_erp_2 > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Backup
```bash
mysql -u username -p deshio_erp_2 < backup_20251122_120000.sql
```

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan optimize
```

### Issue: Permission Denied

**Solution:**
```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Issue: Database Connection Failed

**Solution:**
- Verify database credentials in `.env`
- Check if MySQL service is running: `sudo systemctl status mysql`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Issue: JWT Token Invalid

**Solution:**
```bash
# Regenerate JWT secret
php artisan jwt:secret --force

# Clear config cache
php artisan config:clear
php artisan config:cache
```

---

## 🔧 Performance Optimization

### Production Optimizations
```bash
# Enable OPcache (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2

# Laravel optimizations
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Enable query caching in database
```

### Queue Workers (Optional)
```bash
# Run queue worker (for background jobs)
php artisan queue:work --daemon

# Supervisor configuration (recommended for production)
# Create: /etc/supervisor/conf.d/deshio-worker.conf
```

---

## 📱 API Testing

### Test API Endpoints
```bash
# Health check
curl https://your-domain.com/api/health

# Login test
curl -X POST https://your-domain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"mueedibnesami.anoy@gmail.com","password":"password"}'

# Dashboard test (with token)
curl https://your-domain.com/api/dashboard/today-metrics \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Set `APP_DEBUG=false` in production
- [ ] Set `APP_ENV=production`
- [ ] Use HTTPS (SSL/TLS certificate)
- [ ] Set strong `APP_KEY` and `JWT_SECRET`
- [ ] Restrict database access to localhost only
- [ ] Configure firewall rules
- [ ] Set up regular database backups
- [ ] Enable Laravel rate limiting
- [ ] Review and restrict file permissions
- [ ] Keep PHP and dependencies updated

---

## 📞 Support

- **GitHub**: https://github.com/sakhadib/deshio-erp-backend
- **Issues**: https://github.com/sakhadib/deshio-erp-backend/issues
- **Developer**: @sakhadib

---

## 📝 Quick Command Reference

```bash
# Essential Commands
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed  # Fresh install
php artisan migrate --force       # Update only
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan optimize

# Maintenance
php artisan down                  # Enable maintenance mode
php artisan up                    # Disable maintenance mode
php artisan cache:clear           # Clear application cache
php artisan config:clear          # Clear config cache

# Logs
tail -f storage/logs/laravel.log  # Monitor logs
```

---

**Last Updated**: November 22, 2025  
**Version**: 2.0  
**Laravel Version**: 11.x