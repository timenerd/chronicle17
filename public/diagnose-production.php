<?php
/**
 * Production Diagnostic - Check what's causing 500 errors
 */

// Don't rely on the app's error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== CHRONICLE PRODUCTION DIAGNOSTIC ===\n\n";

// 1. PHP Version
echo "1. PHP Version: " . phpversion() . "\n";
echo "   Required: 8.0+\n";
echo "   Status: " . (version_compare(phpversion(), '8.0.0', '>=') ? '✅ OK' : '❌ TOO OLD') . "\n\n";

// 2. Check if vendor exists
$vendorPath = __DIR__ . '/../vendor/autoload.php';
echo "2. Composer Dependencies:\n";
echo "   Path: " . realpath(__DIR__ . '/../vendor') . "\n";
echo "   Status: " . (file_exists($vendorPath) ? '✅ FOUND' : '❌ MISSING - Run composer install!') . "\n\n";

// 3. Check .env file
$envPath1 = __DIR__ . '/../.env';
$envPath2 = __DIR__ . '/../../.env';
echo "3. Environment File:\n";
echo "   Looking in: " . dirname(__DIR__) . "/.env\n";
echo "   Status: " . (file_exists($envPath1) ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
echo "   OR Looking in: " . dirname(dirname(__DIR__)) . "/.env\n";
echo "   Status: " . (file_exists($envPath2) ? '✅ FOUND' : '❌ NOT FOUND') . "\n\n";

// 4. Try to load autoloader
if (file_exists($vendorPath)) {
    echo "4. Loading Autoloader:\n";
    try {
        require $vendorPath;
        echo "   Status: ✅ SUCCESS\n\n";
        
        // 5. Try to load .env
        echo "5. Loading Environment:\n";
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
            echo "   Status: ✅ SUCCESS\n";
            echo "   DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
            echo "   DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
            echo "   OPENAI_API_KEY: " . (empty($_ENV['OPENAI_API_KEY']) ? '❌ NOT SET' : '✅ SET') . "\n";
            echo "   CLAUDE_API_KEY: " . (empty($_ENV['CLAUDE_API_KEY']) ? '❌ NOT SET' : '✅ SET') . "\n\n";
            
            // 6. Try database connection
            echo "6. Database Connection:\n";
            try {
                $pdo = new PDO(
                    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASS']
                );
                echo "   Status: ✅ CONNECTED\n";
                
                // Check tables
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "   Tables found: " . count($tables) . "\n";
                echo "   Tables: " . implode(', ', $tables) . "\n\n";
                
            } catch (PDOException $e) {
                echo "   Status: ❌ FAILED\n";
                echo "   Error: " . $e->getMessage() . "\n\n";
            }
            
        } catch (Exception $e) {
            echo "   Status: ❌ FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n\n";
        }
        
    } catch (Exception $e) {
        echo "   Status: ❌ FAILED\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "4. Skipping (vendor not found)\n\n";
}

// 7. Storage directories
echo "7. Storage Directories:\n";
$audioDir = __DIR__ . '/../storage/audio';
echo "   Audio: " . realpath($audioDir) . " - " . (is_dir($audioDir) && is_writable($audioDir) ? '✅ OK' : '❌ MISSING/NOT WRITABLE') . "\n";
$narrationDir = __DIR__ . '/../storage/narrations';
echo "   Narrations: " . realpath($narrationDir) . " - " . (is_dir($narrationDir) && is_writable($narrationDir) ? '✅ OK' : '❌ MISSING/NOT WRITABLE') . "\n\n";

// 8. PHP Settings
echo "8. PHP Settings:\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n\n";

echo "=== END DIAGNOSTIC ===\n";
