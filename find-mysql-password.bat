@echo off
echo ========================================
echo  MySQL Password Finder for Laragon
echo ========================================
echo.

echo Checking common Laragon MySQL configurations...
echo.

REM Test with no password
echo [1/3] Testing with NO password...
mysql -u root -e "SELECT 'SUCCESS: No password needed!' as Result;" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ MySQL works with NO PASSWORD
    echo.
    echo In your .env file, use:
    echo   DB_PASS=
    echo.
    pause
    exit /b 0
)

REM Test with common passwords
echo [2/3] Testing with common password 'root'...
mysql -u root -proot -e "SELECT 'SUCCESS: Password is root' as Result;" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ MySQL password is: root
    echo.
    echo In your .env file, use:
    echo   DB_PASS=root
    echo.
    pause
    exit /b 0
)

echo [3/3] Testing with blank password ''...
mysql -u root -p"" -e "SELECT 'SUCCESS: Blank password works!' as Result;" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ MySQL works with blank password
    echo.
    echo In your .env file, use:
    echo   DB_PASS=
    echo.
    pause
    exit /b 0
)

echo.
echo ❌ Could not determine MySQL password
echo.
echo Please try:
echo 1. Open Laragon → MySQL → Change password
echo 2. Or use HeidiSQL/phpMyAdmin to check
echo 3. Or reset root password
echo.
echo To reset password:
echo 1. Stop MySQL in Laragon
echo 2. Laragon → MySQL → Root Password → (set new password)
echo 3. Start MySQL again
echo.
pause
