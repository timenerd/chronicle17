@echo off
echo Testing MySQL connection with different passwords...
echo.

set MYSQL=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe

if not exist "%MYSQL%" (
    echo Could not find MySQL at: %MYSQL%
    echo Please update this script with correct path.
    echo.
    echo Common locations:
    dir /b C:\laragon\bin\mysql 2>nul
    pause
    exit /b 1
)

echo Found MySQL: %MYSQL%
echo.

echo [1/3] Testing with NO password...
"%MYSQL%" -u root -e "SELECT 'NO PASSWORD WORKS!' as result" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ SUCCESS: MySQL has NO PASSWORD
    echo.
    echo Update your .env file:
    echo   DB_PASS=
    echo.
    echo (Leave it completely empty after the = sign)
    pause
    exit /b 0
)

echo [2/3] Testing with password: root...
"%MYSQL%" -u root -proot -e "SELECT 'PASSWORD IS: root' as result" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ SUCCESS: MySQL password is: root
    echo.
    echo Update your .env file:
    echo   DB_PASS=root
    pause
    exit /b 0
)

echo [3/3] Testing with the password from .env...
"%MYSQL%" -u root -pKx9#mPvL2$nQr8Tw -e "SELECT 'PASSWORD WORKS!' as result" 2>nul
if %errorlevel% == 0 (
    echo.
    echo ✅ SUCCESS: The password in .env IS correct
    echo.
    echo There must be another issue...
    pause
    exit /b 0
)

echo.
echo ❌ Could not connect with any common password.
echo.
echo Please check Laragon → Right-click → MySQL → Root password
echo Or try connecting with HeidiSQL to find the correct password.
echo.
pause
