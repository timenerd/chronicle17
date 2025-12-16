# Troubleshooting Guide

## Error: "sessions:1 Failed to load resource: 400 Bad Request"

This error typically occurs when the application can't properly route requests. Here's how to diagnose and fix it:

### Step 1: Run Diagnostics

Visit the diagnostic page:
```
http://localhost/ttrpg-recap/debug.php
```

This will check:
- ✅ .env file loading
- ✅ Database connection
- ✅ Required tables
- ✅ File permissions
- ✅ PHP configuration
- ✅ Recent jobs

### Step 2: Common Causes & Fixes

#### A. Missing .env File

**Symptoms:**
- 400 or 500 errors
- "Error loading .env file" message

**Fix:**
```bash
# .env should be in parent directory
cd c:/laragon/www
cp ttrpg-recap/.env.example .env

# Edit with your credentials
notepad .env
```

Required variables:
```env
OPENAI_API_KEY=sk-your-key
ANTHROPIC_API_KEY=sk-ant-your-key
DB_NAME=ttrpg_recap
DB_USER=root
DB_PASS=your_password
```

#### B. Database Not Created

**Symptoms:**
- PDOException errors
- "Table doesn't exist" errors

**Fix:**
```bash
# Import database schema
cd c:/laragon/www/ttrpg-recap
mysql -u root -p < schema.sql
```

Or use phpMyAdmin to import `schema.sql`.

#### C. URL Rewriting Issues

**Symptoms:**
- 404 errors
- Routes not working

**Fix:**

Check `.htaccess` exists in:
- `c:/laragon/www/ttrpg-recap/.htaccess`
- `c:/laragon/www/ttrpg-recap/public/.htaccess`

Ensure Apache `mod_rewrite` is enabled.

#### D. Storage Directory Not Writable

**Symptoms:**
- "Failed to save uploaded file"
- Upload errors

**Fix:**
```bash
chmod -R 775 storage/
# Or on Windows, give IIS_IUSRS write permission
```

#### E. PHP Upload Limits Too Low

**Symptoms:**
- Large files fail to upload
- No error message

**Fix:**

Edit `php.ini`:
```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
```

Restart Apache after changes.

### Step 3: Enable Debug Mode

In `c:/laragon/www/.env`:
```env
APP_ENV=development
```

This will show detailed error messages in the browser.

### Step 4: Check Error Logs

**Windows (Laragon):**
```
c:/laragon/www/ttrpg-recap/storage/worker.log
c:/laragon/bin/apache/logs/error.log
```

**Look for:**
- PHP errors
- Database connection errors
- Missing file errors

### Step 5: Verify Database Tables

```sql
USE ttrpg_recap;
SHOW TABLES;

-- Should show:
-- campaigns
-- campaign_characters
-- entities
-- jobs
-- recaps
-- session_entities
-- sessions
-- transcripts
-- users
```

If tables are missing, re-import `schema.sql`.

### Step 6: Test Database Connection

```bash
cd c:/laragon/www/ttrpg-recap
php -r "require 'vendor/autoload.php'; use App\Services\Database; Database::getInstance(); echo 'Connected!';"
```

Should output: `Connected!`

### Step 7: Check Worker Status

The worker must be running for jobs to process:

```bash
cd c:/laragon/www/ttrpg-recap
php worker.php
```

You should see:
```
Worker started. Listening on queue: default
Press Ctrl+C to stop.
```

Leave this terminal open while using the app.

## Specific Error Messages

### "Error loading .env file"

**Cause:** .env file not found in parent directory

**Fix:**
```bash
cd c:/laragon/www
# Ensure .env exists here
ls -la .env

# If missing:
cp ttrpg-recap/.env.example .env
```

### "Database connection failed"

**Cause:** MySQL not running or wrong credentials

**Fix:**
1. Start MySQL in Laragon
2. Verify credentials in `.env`
3. Test connection:
   ```bash
   mysql -u root -p -e "SELECT 1;"
   ```

### "Table 'ttrpg_recap.sessions' doesn't exist"

**Cause:** Database tables not created

**Fix:**
```bash
mysql -u root -p ttrpg_recap < schema.sql
```

### "No audio file uploaded"

**Cause:** File upload failed or form misconfigured

**Fix:**
1. Check PHP upload limits
2. Verify form uses `enctype="multipart/form-data"`
3. Check file size < 500MB
4. Ensure storage/audio/ is writable

### "API Error: Invalid API key"

**Cause:** Wrong or missing API keys

**Fix:**
1. Get new API keys:
   - OpenAI: https://platform.openai.com/api-keys
   - Anthropic: https://console.anthropic.com/settings/keys

2. Update `.env`:
   ```env
   OPENAI_API_KEY=sk-your-new-key
   ANTHROPIC_API_KEY=sk-ant-your-new-key
   ```

3. Restart Apache

## Debug Checklist

Run through this checklist to diagnose issues:

- [ ] Visit `http://localhost/ttrpg-recap/debug.php`
- [ ] All environment variables set? (check diagnostic page)
- [ ] Database connection successful? (green checkmark)
- [ ] All 9 tables exist? (listed in diagnostics)
- [ ] Storage directories writable? (check file system section)
- [ ] Worker running? (check terminal)
- [ ] Can access dashboard? `http://localhost/ttrpg-recap/`

## Still Having Issues?

### 1. Check Browser Console

Press F12, go to Console tab. Look for JavaScript errors.

### 2. Check Network Tab

Press F12, go to Network tab. Click on failed request to see:
- Request URL
- Status code
- Response body
- Headers

### 3. Enable Verbose Logging

Add to `public/index.php` (after line 6):
```php
error_log("=== REQUEST START ===");
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST: " . json_encode($_POST));
error_log("GET: " . json_encode($_GET));
```

Check logs in `c:/laragon/bin/apache/logs/error.log`

### 4. Test Each Component

**Test routing:**
```bash
php -r "
require 'public/index.php';
\$_SERVER['REQUEST_URI'] = '/ttrpg-recap/';
\$_SERVER['REQUEST_METHOD'] = 'GET';
"
```

**Test database:**
```bash
php -r "
require 'vendor/autoload.php';
\$db = App\Services\Database::getInstance();
var_dump(\$db->query('SELECT 1')->fetch());
"
```

**Test API services:**
```bash
php -r "
require 'vendor/autoload.php';
\$config = require 'config/config.php';
echo 'OpenAI Key: ' . substr(\$config['apis']['openai']['key'], 0, 10) . '...\n';
echo 'Claude Key: ' . substr(\$config['apis']['anthropic']['key'], 0, 10) . '...\n';
"
```

## Quick Fixes

### Reset Everything

```bash
# 1. Drop and recreate database
mysql -u root -p -e "DROP DATABASE IF EXISTS ttrpg_recap; CREATE DATABASE ttrpg_recap;"

# 2. Import schema
mysql -u root -p ttrpg_recap < schema.sql

# 3. Verify .env
cat ../.env

# 4. Clear any cached files
rm -rf vendor/
composer install

# 5. Restart Apache
# (Use Laragon UI)

# 6. Visit diagnostic page
# http://localhost/ttrpg-recap/debug.php
```

### Permission Issues (Windows)

Right-click `storage` folder → Properties → Security → Edit
- Add `IIS_IUSRS` or `IUSR`
- Grant "Modify" permission
- Apply to subfolders

## Common Workflow Issues

### Upload works but nothing happens

**Cause:** Worker not running

**Fix:**
```bash
cd c:/laragon/www/ttrpg-recap
php worker.php
```

Must stay running!

### Processing stuck at "pending"

**Cause:** Job queue not processing or API error

**Check:**
1. Worker running?
2. Jobs table: `SELECT * FROM jobs WHERE status='failed'`
3. Worker terminal for errors
4. API keys valid and have credits?

### "Session not found" on valid session

**Cause:** Routing issue or wrong ID

**Check:**
1. Database: `SELECT * FROM sessions WHERE id=1`
2. URL is correct: `/ttrpg-recap/sessions/1`
3. Browser DevTools Network tab

## Support

If issues persist:

1. **Check Documentation:**
   - `README.md` - Full docs
   - `QUICKSTART.md` - Setup guide
   - `PROJECT_SUMMARY.md` - Architecture

2. **Collect Debug Info:**
   - Screenshot of diagnostic page
   - Error messages from browser console
   - Error messages from Apache/PHP logs
   - Worker terminal output

3. **Verify Minimum Requirements:**
   - PHP 8.0+
   - MySQL 8.0+
   - Composer installed
   - API keys valid

---

**Most Common Issue:** .env file in wrong location!  
**Location should be:** `c:/laragon/www/.env` (parent directory)  
**NOT:** `c:/laragon/www/ttrpg-recap/.env`
