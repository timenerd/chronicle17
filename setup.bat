@echo off
echo ========================================
echo  TTRPG Session Recap - Setup Script
echo ========================================
echo.

REM Check if .env exists in parent directory
if not exist ..\.env (
    echo Creating .env file in parent directory (outside web root)...
    copy .env.example ..\.env
    echo.
    echo IMPORTANT: Edit ..\.env ^(in parent directory^) and add your API keys:
    echo - OPENAI_API_KEY
    echo - ANTHROPIC_API_KEY
    echo - Database credentials
    echo.
    pause
)

REM Install Composer dependencies
echo Installing PHP dependencies...
composer install
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed
    pause
    exit /b 1
)
echo.

REM Create database
echo.
echo ========================================
echo  Database Setup
echo ========================================
echo.
echo Please run the following SQL to create the database:
echo   mysql -u root -p ^< schema.sql
echo.
echo Or manually in MySQL:
echo   mysql^> source schema.sql;
echo.
pause

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Edit ..\.env ^(parent directory^) with your API keys
echo 2. Create the database using schema.sql
echo 3. Start the background worker:
echo      php worker.php
echo 4. Access the app at http://localhost/ttrpg-recap
echo.
echo Happy adventuring! ⚔️
pause
