# Quick Start Worker - No PATH Issues

## Problem
Your system PATH uses `C:\php\php.exe` which doesn't have pdo_mysql.
Laragon's PHP has it, but isn't in PATH first.

## Immediate Solution

### Option 1: Use Full Path (Works Right Now)

Find your Laragon PHP directory. Usually one of:
- `C:\laragon\bin\php\php.exe`
- `C:\laragon\bin\php\php-8.3\php.exe` 
- `C:\laragon\bin\php\php-8.4\php.exe`

Then run:
```powershell
cd c:\laragon\www\ttrpg-recap

# Try each until one works:
C:\laragon\bin\php\php.exe worker.php
# OR
C:\laragon\bin\php\php-8.3\php.exe worker.php
# OR  
C:\laragon\bin\php\php-8.4\php.exe worker.php
```

### Option 2: Find Laragon PHP Path

```powershell
# List Laragon PHP versions
dir C:\laragon\bin\php

# Use the one that exists, like:
C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe worker.php
```

### Option 3: Use Laragon Terminal

1. **Open Laragon**
2. **Click "Terminal" button** (uses Laragon's PHP automatically)
3. Run:
```bash
cd c:\laragon\www\ttrpg-recap
php worker.php
```

## Fix PATH Permanently

1. **Windows Search:** "Environment Variables"
2. **Edit System Environment Variables**
3. **Environment Variables**
4. **Under "System variables", select PATH**
5. **Edit**
6. **Find:** `C:\php` entry
7. **Add above it:** `C:\laragon\bin\php`
8. **Move Laragon entry to TOP**
9. **OK → OK → OK**
10. **Close ALL terminals**
11. **Open new PowerShell**
12. **Test:** `where.exe php` should show Laragon first

## Verify It Works

After using Laragon's PHP:
```bash
php test-db.php
```

Should show:
```
✅ mysql driver is available
✅ Connection successful!
✅ Test query successful
  MySQL Version: 8.x.x
```

Then run worker:
```bash
php worker.php
```

---

**Quick fix:** Just use Laragon's Terminal button! It automatically uses the right PHP! 🎯
