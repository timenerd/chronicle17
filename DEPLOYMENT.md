# Production Deployment Guide

## Server Requirements

- PHP 8.0+ with extensions:
  - PDO, pdo_mysql
  - json, mbstring
  - fileinfo, curl
- MySQL 8.0+ or MariaDB 10.5+
- Nginx or Apache 2.4+
- Supervisor (for worker process)
- SSL certificate (Let's Encrypt recommended)
- 2GB+ RAM, 20GB+ storage

## Installation Steps

### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and dependencies
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-gd

# Install MySQL
sudo apt install -y mysql-server

# Install Supervisor
sudo apt install -y supervisor

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Application Deployment

```bash
# Clone/upload application
cd /var/www
sudo git clone <your-repo> ttrpg-recap
cd ttrpg-recap

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/ttrpg-recap
sudo chmod -R 755 /var/www/ttrpg-recap
sudo chmod -R 775 storage/

# Configure environment
cp .env.example .env
nano .env
```

### 3. Environment Configuration

```env
APP_URL=https://yourdomain.com
APP_ENV=production

DB_HOST=localhost
DB_NAME=ttrpg_recap_prod
DB_USER=ttrpg_user
DB_PASS=your_secure_password

OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

STORAGE_PATH=/var/www/ttrpg-recap/storage
MAX_UPLOAD_SIZE_MB=500
```

### 4. Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE ttrpg_recap_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ttrpg_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ttrpg_recap_prod.* TO 'ttrpg_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
mysql -u ttrpg_user -p ttrpg_recap_prod < schema.sql
```

### 5. Nginx Configuration

```nginx
# /etc/nginx/sites-available/ttrpg-recap

server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    root /var/www/ttrpg-recap/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Upload size
    client_max_body_size 500M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to storage directory
    location /storage/ {
        deny all;
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/ttrpg-recap /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6. SSL Certificate (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### 7. Supervisor Configuration

```ini
# /etc/supervisor/conf.d/ttrpg-recap-worker.conf

[program:ttrpg-recap-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ttrpg-recap/worker.php
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/ttrpg-recap/storage/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ttrpg-recap-worker:*

# Check status
sudo supervisorctl status
```

## Security Hardening

### 1. Firewall
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### 2. PHP Security (php.ini)
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### 3. File Permissions
```bash
# Ensure proper ownership
sudo chown -R www-data:www-data /var/www/ttrpg-recap
find /var/www/ttrpg-recap -type f -exec chmod 644 {} \;
find /var/www/ttrpg-recap -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/ttrpg-recap/storage/
```

### 4. Database Security
- Use strong passwords
- Limit user privileges
- Regular backups
- Enable binary logging

## Monitoring & Maintenance

### Log Files
```bash
# Application logs
tail -f /var/www/ttrpg-recap/storage/worker.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.1-fpm.log
```

### Database Backups
```bash
# Create backup script
cat > /usr/local/bin/backup-ttrpg.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/ttrpg"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
mysqldump -u ttrpg_user -p'your_password' ttrpg_recap_prod > $BACKUP_DIR/db_$DATE.sql
find $BACKUP_DIR -type f -mtime +7 -delete
EOF

chmod +x /usr/local/bin/backup-ttrpg.sh

# Add to crontab
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-ttrpg.sh
```

### Health Checks
```bash
# Worker status
sudo supervisorctl status ttrpg-recap-worker

# Database connection
mysql -u ttrpg_user -p -e "SELECT 1"

# Disk space
df -h

# Check pending jobs
mysql -u ttrpg_user -p ttrpg_recap_prod -e "SELECT COUNT(*) FROM jobs WHERE status='pending'"
```

## Scaling Considerations

### Multiple Workers
Edit supervisor config:
```ini
numprocs=4  # Run 4 worker processes
```

### S3 Storage (Optional)
For large deployments, use S3-compatible storage:
1. Install AWS SDK: `composer require aws/aws-sdk-php`
2. Update storage service to use S3
3. Configure bucket and credentials

### Database Optimization
```sql
-- Add indexes for common queries
CREATE INDEX idx_sessions_campaign_status ON sessions(campaign_id, status);
CREATE INDEX idx_entities_campaign_type ON entities(campaign_id, entity_type);

-- Regular optimization
OPTIMIZE TABLE sessions;
OPTIMIZE TABLE transcripts;
```

### CDN (Optional)
Serve static assets via CDN:
- Upload `/public/assets/` to CDN
- Update asset URLs in templates

## Troubleshooting

### Worker not processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart worker
sudo supervisorctl restart ttrpg-recap-worker:*

# Check logs
tail -f /var/www/ttrpg-recap/storage/worker.log
```

### High memory usage
- Increase PHP memory limit
- Process jobs serially (numprocs=1)
- Clear old completed jobs regularly

### Slow processing
- Check API rate limits
- Monitor network latency
- Consider multiple workers

---

**Questions?** Check main README.md or open an issue.
