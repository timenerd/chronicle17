@echo off
echo ================================================
echo  TTRPG Recap - PHP Check and Worker Test
echo ================================================
echo.

echo [1/4] Checking which PHP is being used...
where php
echo.

echo [2/4] PHP Version:
php --version
echo.

echo [3/4] Checking PDO drivers available:
php -r "echo 'PDO Drivers: ' . implode(', ', PDO::getAvailableDrivers()) . chr(10);"
echo.

echo [4/4] Checking if pdo_mysql module is loaded:
php -m | findstr /i pdo_mysql
if %errorlevel% == 0 (
    echo ✅ pdo_mysql IS loaded
    echo.
    echo Testing database connection...
    php test-db.php
    echo.
    echo ================================================
    echo  Ready to start worker!
    echo ================================================
    echo.
    echo Press any key to start worker, or Ctrl+C to exit...
    pause >nul
    echo.
    echo Starting worker...
    php worker.php
) else (
    echo ❌ pdo_mysql is NOT loaded
    echo.
    echo PROBLEM: You are using a PHP installation that doesn't have pdo_mysql.
    echo.
    echo SOLUTION OPTIONS:
    echo.
    echo 1. Use Laragon Terminal (EASIEST):
    echo    - Open Laragon app
    echo    - Click "Terminal" button
    echo    - Run: cd c:\laragon\www\ttrpg-recap
    echo    - Run: php worker.php
    echo.
    echo 2. Close THIS PowerShell completely and open a NEW one
    echo    (Your PATH was updated but this session still uses old PATH)
    echo.
    echo 3. Enable pdo_mysql in C:\php\ext\php.ini:
    echo    - Open c:\php\php.ini
    echo    - Find: ;extension=pdo_mysql
    echo    - Change to: extension=pdo_mysql
    echo    - Restart this terminal
    echo.
    pause
)
