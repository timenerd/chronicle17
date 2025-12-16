# Production Deployment Guide

## 🚀 Deploying Chronicle17 to Production

This guide walks you through deploying the TTRPG Session Recap Generator to a production server.

---

## Prerequisites

- SSH access to your server
- PHP 8.0+ installed
- MySQL/MariaDB database
- Composer installed on server
- Basic command line knowledge

---

## Step-by-Step Deployment

### 1. Clone Repository to Server

SSH into your server and navigate to your web directory:

```bash
cd /home/iamrlw/public_html
git clone https://github.com/timenerd/chronicle17.git ttrpg-recap
cd ttrpg-recap
```

### 2. Install Dependencies

**CRITICAL:** The `/vendor/` directory is not in Git, so you must install dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

**Flags:**
- `--no-dev` - Skip development-only packages
- `--optimize-autoloader` - Optimize for production performance

If you don't have Composer on the server, install it first:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```

### 3. Set Up Environment File

Create your `.env` file **one directory above** the project root (outside web root for security):

```bash
cd /home/iamrlw/public_html
nano .env
```

Add your production configuration:

```env
# OpenAI API Key (for Whisper transcription)
OPENAI_API_KEY=sk-proj-your-actual-key-here

# Anthropic API Key (for Claude AI recap generation)
ANTHROPIC_API_KEY=sk-ant-your-actual-key-here

# Database Configuration
DB_HOST=localhost
DB_NAME=iamrlw_ttrpg_recap
DB_USER=iamrlw_dbuser
DB_PASS=your-secure-password

# Application Environment
APP_ENV=production

# Base URL (update with your actual domain)
BASE_URL=https://yourdomain.com/ttrpg-recap
```

**Security tip:** Make sure `.env` is NOT inside the `public_html` directory!

### 4. Create Database

Access MySQL:

```bash
mysql -u root -p
```

Create database and user:

```sql
CREATE DATABASE iamrlw_ttrpg_recap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'iamrlw_dbuser'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON iamrlw_ttrpg_recap.* TO 'iamrlw_dbuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import schema:

```bash
cd /home/iamrlw/public_html/ttrpg-recap
mysql -u iamrlw_dbuser -p iamrlw_ttrpg_recap < schema.sql
```

### 5. Set Up Storage Directories

Create and set permissions for upload directories:

```bash
cd /home/iamrlw/public_html/ttrpg-recap

# Create storage directories if they don't exist
mkdir -p storage/audio
mkdir -p storage/narrations
mkdir -p public/uploads

# Add .gitkeep files
touch storage/audio/.gitkeep
touch storage/narrations/.gitkeep
touch public/uploads/.gitkeep

# Set permissions (adjust based on your server's user)
chmod 755 storage
chmod 755 storage/audio
chmod 755 storage/narrations
chmod 755 public/uploads

# If using Apache/nginx with a different user (e.g., www-data):
# chown -R www-data:www-data storage
# chown -R www-data:www-data public/uploads
```

### 6. Configure PHP Settings

Check your PHP configuration for file uploads:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time|memory_limit"
```

You need:
- `upload_max_filesize` = 512M or higher
- `post_max_size` = 512M or higher
- `max_execution_time` = 300 or higher
- `memory_limit` = 512M or higher

If using cPanel, edit PHP settings through:
- **cPanel → Select PHP Version → Options**

Or edit `php.ini` or `.user.ini`:

```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 300
memory_limit = 512M
```

### 7. Set Up Background Worker

The background worker processes transcription and AI jobs. You need to keep it running.

**Option A: Using systemd (recommended for VPS)**

Create service file:
```bash
sudo nano /etc/systemd/system/ttrpg-worker.service
```

Add:
```ini
[Unit]
Description=TTRPG Recap Background Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/home/iamrlw/public_html/ttrpg-recap
ExecStart=/usr/bin/php /home/iamrlw/public_html/ttrpg-recap/worker.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable ttrpg-worker
sudo systemctl start ttrpg-worker
sudo systemctl status ttrpg-worker
```

**Option B: Using cron (for shared hosting)**

Add to crontab:
```bash
crontab -e
```

Add this line to run worker every minute:
```cron
* * * * * /usr/bin/php /home/iamrlw/public_html/ttrpg-recap/worker.php >> /home/iamrlw/ttrpg-worker.log 2>&1
```

**Note:** This is less efficient than a persistent worker, but works on shared hosting.

**Option C: Using screen (manual)**

Start a persistent session:
```bash
screen -S ttrpg-worker
cd /home/iamrlw/public_html/ttrpg-recap
php worker.php
```

Detach with `Ctrl+A`, then `D`. Reattach with: `screen -r ttrpg-worker`

### 8. Configure Web Server

**For Apache (.htaccess already included):**

Verify `.htaccess` in `/public/` is working:
```bash
cat /home/iamrlw/public_html/ttrpg-recap/public/.htaccess
```

Should contain:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**For Nginx:**

Add to your server block:
```nginx
location /ttrpg-recap {
    alias /home/iamrlw/public_html/ttrpg-recap/public;
    try_files $uri $uri/ /ttrpg-recap/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

### 9. Test the Installation

Visit your diagnostic page:
```
https://yourdomain.com/ttrpg-recap/debug.php
```

This will verify:
- ✅ PHP version
- ✅ Required extensions (PDO, PDO_MySQL)
- ✅ Database connection
- ✅ File permissions
- ✅ Environment variables loaded

### 10. Secure Your Installation

1. **Remove debug page in production:**
   ```bash
   rm /home/iamrlw/public_html/ttrpg-recap/public/debug.php
   ```

2. **Verify .env is outside web root:**
   ```bash
   # Should NOT be accessible via browser
   ls -la /home/iamrlw/public_html/.env
   ```

3. **Set restrictive file permissions:**
   ```bash
   # Make .env readable only by you
   chmod 600 /home/iamrlw/public_html/.env
   ```

4. **Enable HTTPS** (via cPanel or Let's Encrypt)

---

## 🔄 Updating Your Production Site

When you push changes to GitHub:

```bash
cd /home/iamrlw/public_html/ttrpg-recap

# Pull latest changes
git pull origin main

# Update dependencies if composer.json changed
composer install --no-dev --optimize-autoloader

# Restart worker if using systemd
sudo systemctl restart ttrpg-worker
```

---

## 🐛 Troubleshooting Production Issues

### Error: "Failed to open stream: No such file or directory" for autoload.php

**Cause:** Missing `/vendor/` directory

**Fix:**
```bash
cd /home/iamrlw/public_html/ttrpg-recap
composer install --no-dev --optimize-autoloader
```

### Error: "Class 'Dotenv\Repository\Adapter\ServerConstAdapter' not found"

**Cause:** Corrupted or incomplete Composer dependencies

**Fix:**
```bash
cd /home/iamrlw/public_html/ttrpg-recap

# Remove corrupted vendor directory
rm -rf vendor

# Clear Composer cache
composer clear-cache

# Reinstall fresh
composer install --no-dev --optimize-autoloader
```

**Alternative check:** Run the diagnostic script to identify the issue:
```bash
php diagnose.php
```

### Error: "Could not find driver" (PDO MySQL)

**Cause:** MySQL PDO extension not installed

**Fix:**
```bash
# Check installed extensions
php -m | grep -i pdo

# If missing, install (on Ubuntu/Debian)
sudo apt-get install php8.3-mysql
sudo systemctl restart apache2

# On cPanel: Enable in Select PHP Version
```

### Jobs stuck in "pending" status

**Cause:** Worker not running

**Fix:** Check worker status:
```bash
# If using systemd
sudo systemctl status ttrpg-worker

# If using screen
screen -ls

# If using cron, check logs
tail -f /home/iamrlw/ttrpg-worker.log
```

### Upload fails with 413 error

**Cause:** PHP upload limits too low or nginx client_max_body_size

**Fix:**
```bash
# PHP
upload_max_filesize = 512M
post_max_size = 512M

# Nginx (add to server block)
client_max_body_size 512M;
```

---

## 📊 Monitoring

### Check Worker Logs

```bash
# If using systemd
sudo journalctl -u ttrpg-worker -f

# If using screen
screen -r ttrpg-worker

# If using cron
tail -f /home/iamrlw/ttrpg-worker.log
```

### Check Job Queue

```bash
mysql -u iamrlw_dbuser -p iamrlw_ttrpg_recap -e "SELECT id, type, status, created_at FROM jobs ORDER BY created_at DESC LIMIT 10;"
```

### Check Storage Usage

```bash
du -sh /home/iamrlw/public_html/ttrpg-recap/storage/audio/*
```

---

## 🎯 Post-Deployment Checklist

- [ ] Dependencies installed (`composer install`)
- [ ] `.env` file configured (outside web root)
- [ ] Database created and schema imported
- [ ] Storage directories created with correct permissions
- [ ] PHP settings configured for large uploads
- [ ] Background worker running
- [ ] Diagnostic page shows all green checks
- [ ] Test upload and processing of a small audio file
- [ ] HTTPS enabled
- [ ] Debug/test files removed from production

---

## 💰 Production Costs

Estimated monthly costs:
- **Hosting:** $5-20/month (shared hosting) or $5-50/month (VPS)
- **API costs:** ~$5-10/month for weekly sessions
  - OpenAI Whisper: $0.006/minute (~$1.08 per 3-hour session)
  - Claude API: ~$0.15-0.30 per session
  - **Total per session:** ~$1.25-1.40

---

## 🆘 Getting Help

If you encounter issues:
1. Check `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
2. Run diagnostic page: `/debug.php`
3. Review `TROUBLESHOOTING.md`
4. Check worker logs
5. Verify database connection: `php test-db.php`

---

**Happy deploying!** 🎲⚔️
