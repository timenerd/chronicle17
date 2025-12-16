# Fix: POST Content-Length Exceeds Limit

## The Error

```
POST Content-Length of 193221306 bytes exceeds the limit of 20971520 bytes
{"success":false,"error":"Invalid campaign ID"}
```

**Translation:** Your file is 193MB, but PHP only accepts 20MB.

## Quick Fix (Laragon)

### Step 1: Edit php.ini

In Laragon, click **Menu → PHP → php.ini**

Or manually open:
```
c:\laragon\bin\php\php-8.x.x\php.ini
```

### Step 2: Find and Update These Lines

Search for each setting and change:

```ini
; BEFORE (too small):
upload_max_filesize = 2M
post_max_size = 8M
max_execution_time = 30
memory_limit = 128M

; AFTER (for TTRPG files):
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
```

**Important Notes:**
- `post_max_size` must be **≥** `upload_max_filesize`
- Remove the semicolon (;) if there is one at the start of the line

### Step 3: Restart Apache

In Laragon:
1. Click **Stop All**
2. Click **Start All**

Or click **Menu → Apache → Restart**

### Step 4: Verify Changes

Create a test file `c:\laragon\www\ttrpg-recap\public\phpinfo.php`:

```php
<?php
phpinfo();
?>
```

Visit: `http://ttrpg-recap.test/phpinfo.php`

Search for:
- **upload_max_filesize** → Should show 500M
- **post_max_size** → Should show 500M

**Delete this file after checking!** (Security risk)

### Step 5: Try Upload Again

Your 193MB file should now upload successfully!

## Alternative: Quick Test with Smaller File

If you want to test immediately without changing PHP settings:

1. Create a small test audio file (< 20MB)
2. Upload that first
3. Verify the system works
4. Then increase PHP limits for real files

## How to Check Current Limits

In PowerShell:
```powershell
# Find your PHP version
php -v

# Check current settings
php -i | Select-String "upload_max_filesize"
php -i | Select-String "post_max_size"
```

## Common Mistakes

❌ **Editing wrong php.ini**
- Laragon may have multiple PHP versions
- Make sure you edit the active one
- Check: `Menu → PHP → Version → php.ini`

❌ **Not restarting Apache**
- Changes only take effect after restart
- Stop and Start in Laragon

❌ **Semicolon still there**
- `;upload_max_filesize = 500M` ← WRONG (commented out)
- `upload_max_filesize = 500M` ← CORRECT

## Recommended Settings for TTRPG Sessions

For typical 2-4 hour sessions:

```ini
; Basic
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
max_input_time = 300

; Optional (for very long sessions)
max_file_uploads = 20
```

## After Fixing

Once you've updated php.ini and restarted Apache:

1. ✅ Visit diagnostic page: `http://ttrpg-recap.test/debug.php`
2. ✅ Check "PHP Configuration" section
3. ✅ Should show 500M for upload limits
4. ✅ Try upload again!

---

**Your specific file (193MB) will now upload successfully!** 🎉
