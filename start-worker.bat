@echo off
REM Start TTRPG Worker with Laragon's PHP

echo Starting TTRPG Recap Worker...
echo.

REM Find Laragon's PHP
set LARAGON_PHP=C:\laragon\bin\php\php.exe

if not exist "%LARAGON_PHP%" (
    echo ERROR: Could not find Laragon PHP at: %LARAGON_PHP%
    echo.
    echo Please update this script with your Laragon PHP path.
    echo Usually it's one of:
    echo   C:\laragon\bin\php\php.exe
    echo   C:\laragon\bin\php\php-8.3\php.exe
    echo   C:\laragon\bin\php\php-8.4\php.exe
    echo.
    pause
    exit /b 1
)

echo Using PHP: %LARAGON_PHP%
%LARAGON_PHP% --version
echo.

cd /d %~dp0

echo Worker Directory: %CD%
echo.

echo Starting worker... (Press Ctrl+C to stop)
echo ==========================================
echo.

%LARAGON_PHP% worker.php

pause
