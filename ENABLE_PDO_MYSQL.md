# Enable pdo_mysql Extension in Laragon

## Problem
```
Database connection failed: could not find driver
```

PDO MySQL extension is not enabled in your PHP installation.

## Solution

### Quick Fix (Laragon):

1. **Open Laragon**
2. **Right-click Laragon tray icon**
3. **PHP → Quick settings → pdo_mysql**
4. **Click to enable it** (checkmark should appear)
5. **Restart Apache** (Right-click → Apache → Reload)

### OR Manual Fix:

1. **Find php.ini location:**
   - In Laragon: Menu → PHP → php.ini
   - Usually: `c:\laragon\bin\php\php-8.x.x\php.ini`

2. **Open php.ini in notepad**

3. **Find this line:**
   ```ini
   ;extension=pdo_mysql
   ```
   
4. **Remove the semicolon:**
   ```ini
   extension=pdo_mysql
   ```

5. **Save the file**

6. **Restart Apache** in Laragon

### Verify It Works:

```bash
php -m | findstr pdo_mysql
```

Should output: `pdo_mysql`

### Then Test Worker:

```bash
cd c:\laragon\www\ttrpg-recap
php test-worker.php
```

Should show:
```
✅ Database connected
✅ Test query successful
```

---

**This is required for the application to work!** PHP needs the pdo_mysql extension to connect to MySQL.
