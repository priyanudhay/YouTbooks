# YouTbooks Studio - Deployment Guide

## 🚀 Quick Start with Docker

### Prerequisites
- Docker and Docker Compose installed
- Git (for cloning)
- At least 4GB RAM available

### 1. Environment Setup

Copy the environment file and configure:
```bash
cp .env.example .env
```

Update these key variables in `.env`:
```env
# Application
APP_NAME="YouTbooks Studio"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=youtbooks_studio
DB_USERNAME=youtbooks_user
DB_PASSWORD=youtbooks_pass_123

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@youtbooks.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateways
STRIPE_KEY=pk_test_your_stripe_key
STRIPE_SECRET=sk_test_your_stripe_secret
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret

PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox

RAZORPAY_KEY=your_razorpay_key
RAZORPAY_SECRET=your_razorpay_secret

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/api/v1/auth/google/callback

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

### 2. Generate Application Key

```bash
# Generate Laravel application key
docker run --rm -v ${PWD}:/app composer/composer:latest composer install --no-dev --optimize-autoloader
docker run --rm -v ${PWD}:/app -w /app php:8.2-cli php artisan key:generate
```

### 3. Start Services

```bash
# Start all services
docker-compose up -d

# Check service status
docker-compose ps
```

### 4. Initialize Database

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database with sample data
docker-compose exec app php artisan db:seed

# Create storage link
docker-compose exec app php artisan storage:link
```

### 5. Install Frontend Dependencies

```bash
# Install and build assets
docker-compose exec node npm install
docker-compose exec node npm run build
```

### 6. Access Your Application

- **Main Application**: http://localhost
- **Admin Dashboard**: http://localhost/admin/dashboard
- **API Documentation**: http://localhost/api/documentation
- **MailHog (Email Testing)**: http://localhost:8025

## 🔧 Manual Installation (Without Docker)

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Redis
- Node.js 18+
- Composer
- Nginx/Apache

### 1. Clone and Install

```bash
git clone <repository-url> youtbooks-studio
cd youtbooks-studio

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install
npm run build
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE youtbooks_studio;"

# Run migrations
php artisan migrate

# Seed data
php artisan db:seed
```

### 4. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/youtbooks-studio/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    client_max_body_size 100M;
}
```

### 6. Start Services

```bash
# Start queue worker
php artisan queue:work --daemon

# Start scheduler (add to crontab)
* * * * * cd /path/to/youtbooks-studio && php artisan schedule:run >> /dev/null 2>&1
```

## 🔐 Security Configuration

### 1. SSL Certificate (Production)

```bash
# Using Let's Encrypt
certbot --nginx -d yourdomain.com
```

### 2. Environment Security

```bash
# Set proper permissions
chmod 600 .env

# Hide sensitive files
echo ".env" >> .gitignore
echo "storage/logs/*" >> .gitignore
```

### 3. Database Security

```sql
-- Create dedicated database user
CREATE USER 'youtbooks_prod'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON youtbooks_studio.* TO 'youtbooks_prod'@'localhost';
FLUSH PRIVILEGES;
```

## 📊 Monitoring & Maintenance

### 1. Log Monitoring

```bash
# Application logs
tail -f storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Docker logs
docker-compose logs -f app
```

### 2. Database Backups

```bash
# Create backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u youtbooks_user -p youtbooks_studio > backup_$DATE.sql
```

### 3. Performance Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Composer autoloader
composer dump-autoload --optimize
```

## 🔄 Updates & Maintenance

### 1. Application Updates

```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 2. Database Maintenance

```bash
# Optimize tables
php artisan db:optimize

# Clean old sessions
php artisan session:gc
```

## 🐛 Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

2. **Database Connection**
   ```bash
   # Check database credentials in .env
   # Ensure MySQL service is running
   systemctl status mysql
   ```

3. **Queue Not Processing**
   ```bash
   # Restart queue worker
   php artisan queue:restart
   php artisan queue:work
   ```

4. **File Upload Issues**
   ```bash
   # Check PHP upload limits
   php -i | grep upload_max_filesize
   php -i | grep post_max_size
   ```

### Docker Troubleshooting

```bash
# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Check container logs
docker-compose logs app
docker-compose logs mysql
docker-compose logs nginx

# Access container shell
docker-compose exec app bash
```

## 📈 Scaling & Performance

### 1. Load Balancing

Use multiple app containers:
```yaml
# docker-compose.yml
services:
  app1:
    # ... app configuration
  app2:
    # ... app configuration
  
  nginx:
    # Configure upstream servers
```

### 2. Database Optimization

```sql
-- Add indexes for performance
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_payments_gateway ON payments(gateway);
```

### 3. Caching Strategy

```bash
# Redis configuration for sessions and cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🎯 Production Checklist

- [ ] Environment configured (.env)
- [ ] Application key generated
- [ ] Database migrated and seeded
- [ ] SSL certificate installed
- [ ] File permissions set correctly
- [ ] Queue worker running
- [ ] Scheduler configured
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Payment gateways configured
- [ ] Email service configured
- [ ] Google OAuth configured
- [ ] Error tracking setup

## 📞 Support

For deployment issues:
1. Check logs first
2. Review this guide
3. Check Laravel documentation
4. Contact support team

---

**YouTbooks Studio** - Professional Book Editing & Design Services Platform
