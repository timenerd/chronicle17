<?php
/**
 * Production Server Diagnostic Script
 * Run this to check server compatibility before installing dependencies
 */

echo "=== TTRPG Recap - Server Diagnostic ===\n\n";

// 1. PHP Version Check
echo "1. PHP Version Check:\n";
$phpVersion = phpversion();
echo "   Current PHP version: $phpVersion\n";
$minVersion = '8.0.0';
if (version_compare($phpVersion, $minVersion, '>=')) {
    echo "   ✅ PHP version is compatible (>= $minVersion)\n";
} else {
    echo "   ❌ PHP version is too old. Required: >= $minVersion\n";
}
echo "\n";

// 2. Required Extensions
echo "2. Required PHP Extensions:\n";
$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'mbstring',
    'curl',
    'json',
    'fileinfo',
    'openssl'
];

$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✅' : '❌';
    echo "   $status $ext\n";
    if (!$loaded) {
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    echo "   All required extensions are installed!\n";
} else {
    echo "\n   ⚠️  Missing extensions: " . implode(', ', $missingExtensions) . "\n";
    echo "   Install them using: php -m or contact your hosting provider\n";
}
echo "\n";

// 3. File System Permissions
echo "3. File System Permissions:\n";
$directories = [
    __DIR__ . '/storage/audio',
    __DIR__ . '/storage/narrations',
    __DIR__ . '/public/uploads'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        $status = $writable ? '✅' : '❌';
        echo "   $status $dir " . ($writable ? '(writable)' : '(NOT writable)') . "\n";
    } else {
        echo "   ⚠️  $dir (does not exist - needs to be created)\n";
    }
}
echo "\n";

// 4. Composer Check
echo "4. Composer Availability:\n";
exec('which composer 2>&1', $composerPath, $composerReturn);
if ($composerReturn === 0) {
    echo "   ✅ Composer found at: " . implode("\n", $composerPath) . "\n";
    exec('composer --version 2>&1', $composerVersion);
    echo "   Version: " . implode("\n", $composerVersion) . "\n";
} else {
    echo "   ❌ Composer not found in PATH\n";
    echo "   Install from: https://getcomposer.org/\n";
}
echo "\n";

// 5. Vendor Directory Check
echo "5. Dependencies Status:\n";
$vendorDir = __DIR__ . '/vendor';
if (is_dir($vendorDir)) {
    echo "   ⚠️  /vendor directory exists\n";
    
    // Check if autoload.php exists
    $autoloadFile = $vendorDir . '/autoload.php';
    if (file_exists($autoloadFile)) {
        echo "   ✅ Autoloader exists\n";
        
        // Check for specific problematic file
        $dotenvFile = $vendorDir . '/vlucas/phpdotenv/src/Repository/Adapter/ServerConstAdapter.php';
        if (file_exists($dotenvFile)) {
            echo "   ✅ Dotenv library appears intact\n";
        } else {
            echo "   ❌ Dotenv library is CORRUPTED or incomplete\n";
            echo "   Action needed: Remove vendor directory and reinstall\n";
            echo "   Commands:\n";
            echo "      rm -rf vendor\n";
            echo "      composer clear-cache\n";
            echo "      composer install --no-dev --optimize-autoloader\n";
        }
    } else {
        echo "   ❌ Autoloader missing - dependencies corrupted\n";
    }
} else {
    echo "   ℹ️  /vendor directory not found (dependencies need to be installed)\n";
    echo "   Run: composer install --no-dev --optimize-autoloader\n";
}
echo "\n";

// 6. Environment File Check
echo "6. Environment Configuration:\n";
$envPaths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env'
];

$envFound = false;
foreach ($envPaths as $path) {
    if (file_exists($path)) {
        echo "   ✅ .env file found at: $path\n";
        $envFound = true;
        
        // Check if readable
        if (is_readable($path)) {
            echo "   ✅ .env file is readable\n";
        } else {
            echo "   ❌ .env file is NOT readable (check permissions)\n";
        }
        break;
    }
}

if (!$envFound) {
    echo "   ❌ .env file NOT found\n";
    echo "   Checked locations:\n";
    foreach ($envPaths as $path) {
        echo "      - $path\n";
    }
    echo "   Action needed: Create .env file from .env.example\n";
}
echo "\n";

// 7. PHP Configuration
echo "7. PHP Configuration:\n";
$importantSettings = [
    'upload_max_filesize',
    'post_max_size',
    'max_execution_time',
    'memory_limit',
    'display_errors'
];

foreach ($importantSettings as $setting) {
    $value = ini_get($setting);
    echo "   $setting = $value\n";
}
echo "\n";

// 8. Database Connection Test (if .env exists)
echo "8. Database Connection:\n";
if ($envFound) {
    // Try to load environment variables manually
    foreach ($envPaths as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0 || trim($line) === '') continue;
                list($key, $value) = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
            break;
        }
    }
    
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    
    if ($dbName && $dbUser) {
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            echo "   ✅ Database connection successful\n";
            echo "   Connected to: $dbName@$dbHost\n";
            
            // Check tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "   Tables found: " . count($tables) . "\n";
            if (count($tables) > 0) {
                echo "   Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "\n";
            } else {
                echo "   ⚠️  No tables found - schema may not be imported\n";
            }
        } catch (PDOException $e) {
            echo "   ❌ Database connection failed\n";
            echo "   Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  Database credentials not found in .env\n";
    }
} else {
    echo "   ⚠️  Skipped (no .env file found)\n";
}
echo "\n";

// Summary
echo "=== DIAGNOSTIC COMPLETE ===\n\n";
echo "Next Steps:\n";

$issues = [];
if (version_compare($phpVersion, $minVersion, '<')) {
    $issues[] = "❌ Upgrade PHP to version >= $minVersion";
}
if (!empty($missingExtensions)) {
    $issues[] = "❌ Install missing PHP extensions: " . implode(', ', $missingExtensions);
}
if (!is_dir($vendorDir) || !file_exists($vendorDir . '/autoload.php')) {
    $issues[] = "⚠️  Run: composer install --no-dev --optimize-autoloader";
}
if (!$envFound) {
    $issues[] = "❌ Create .env file from .env.example";
}

if (empty($issues)) {
    echo "✅ Everything looks good! Your server is ready.\n";
} else {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "  $issue\n";
    }
}
echo "\n";
