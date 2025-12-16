# Debugging Upload 400 Error

## Quick Fix Steps

### 1. Open Browser Console

Press **F12** in your browser, go to the **Console** tab.

Now try uploading again. You'll see:
- `Upload response status: 400`
- `Upload response: {...}` - **This shows the actual error!**

The error message will tell you exactly what's wrong.

### 2. Check Apache Error Log

In development mode, the backend logs detailed information.

**Windows (Laragon):**
```powershell
Get-Content c:\laragon\bin\apache\logs\error.log -Tail 50
```

Or open in notepad:
```powershell
notepad c:\laragon\bin\apache\logs\error.log
```

Look for lines containing:
- `=== SESSION UPLOAD START ===`
- `Campaign ID:`
- `File received:`
- `Upload error:`

### 3. Common 400 Errors and Solutions

#### Error: "Campaign not found"

**Cause:** Campaign ID doesn't exist in database

**Fix:**
```sql
-- Check campaigns
SELECT * FROM ttrpg_recap.campaigns;

-- If empty, create one via the web interface
-- Or manually:
INSERT INTO campaigns (user_id, name, game_system) 
VALUES (1, 'Test Campaign', 'D&D 5e');
```

#### Error: "No audio file in request"

**Cause:** Form is not sending the file properly

**Check:**
1. Form has `enctype="multipart/form-data"`
2. Input field name is "audio"
3. File is actually selected

#### Error: "File exceeds upload_max_filesize"

**Cause:** PHP upload limit too small

**Fix:** Edit `php.ini`:
```ini
upload_max_filesize = 500M
post_max_size = 500M
```

Restart Apache in Laragon.

#### Error: "Failed to save uploaded file"

**Cause:** Storage directory not writable

**Fix Windows:**
1. Right-click `c:\laragon\www\ttrpg-recap\storage`
2. Properties → Security → Edit
3. Add `Everyone` or `IIS_IUSRS`
4. Grant "Modify" permission
5. Apply to subfolders

**Fix Linux:**
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

#### Error: "Database error: SQLSTATE..."

**Cause:** Database connection issue or missing tables

**Fix:**
1. Run diagnostic page: `http://ttrpg-recap.test/debug.php`
2. Check all tables exist
3. Re-import if needed: `mysql -u root -p ttrpg_recap < schema.sql`

### 4. Enable Debug Mode

Edit `c:/laragon/www/.env`:
```env
APP_ENV=development
```

This enables:
- Detailed error logging
- Stack traces
- Request/response logging

**Remember to set `APP_ENV=production` before deploying!**

### 5. Test Upload Manually

Use this command to test if the upload endpoint works:

```powershell
# Create a test audio file
echo "test" > test.mp3

# Test upload with curl (if installed)
curl -X POST http://ttrpg-recap.test/campaigns/1/sessions `
  -F "campaign_id=1" `
  -F "title=Test Session" `
  -F "audio=@test.mp3" `
  -v
```

Look for the response body showing the error.

### 6. Check Database Connection

Make sure database is accessible:

```sql
-- In MySQL:
USE ttrpg_recap;
SELECT COUNT(*) FROM campaigns;
SELECT COUNT(*) FROM sessions;
```

### 7. Verify File Upload Settings

Check PHP info:

Create `phpinfo.php`:
```php
<?php
phpinfo();
```

Visit it in browser and search for:
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`
- `memory_limit`

All should be sufficient for your needs.

## Debugging Process

1. **Try Upload** → Note exact error message
2. **Check Browser Console** → See detailed error
3. **Check Apache Logs** → See backend process
4. **Fix Issue** → Based on error message
5. **Retry**

## Real-Time Debugging

Keep two windows open:

**Window 1:** Browser with Console open (F12)
**Window 2:** Tail Apache error log

```powershell
# PowerShell - watch log in real-time
Get-Content c:\laragon\bin\apache\logs\error.log -Wait -Tail 20
```

Now upload and see exactly what happens!

## Complete Debug Example

```
=== Browser Console Output ===
Upload response status: 400
Upload response: {"success":false,"error":"Campaign not found (ID: 1)"}

=== Apache Error Log ===
[Sun Dec 15 10:03:22 2024] === SESSION UPLOAD START ===
[Sun Dec 15 10:03:22 2024] POST data: {"campaign_id":"1","title":"Test Session"}
[Sun Dec 15 10:03:22 2024] FILES data: {"audio":{"name":"test.mp3","size":1024,"error":0}}
[Sun Dec 15 10:03:22 2024] Campaign ID: 1, Title: Test Session
[Sun Dec 15 10:03:22 2024] Upload error: Campaign not found (ID: 1)

=== Solution ===
Campaign #1 doesn't exist. Create it first via the web interface.
```

## Still Stuck?

1. Visit diagnostic page: `http://ttrpg-recap.test/debug.php`
2. Screenshot the output
3. Share browser console errors
4. Share Apache error log lines

The error message will tell you exactly what to fix!

---

**Most Common Issue:** .env file in wrong location!  
Should be: `c:/laragon/www/.env`  
NOT: `c:/laragon/www/ttrpg-recap/.env`
