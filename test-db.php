<?php
/**
 * Detailed Database Connection Test
 */

echo "=== Detailed Database Test ===\n\n";

// Load .env
require __DIR__ . '/vendor/autoload.php';

echo "1. Loading .env...\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "   DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "   DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "   DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "   DB_PASS: " . (isset($_ENV['DB_PASS']) ? (empty($_ENV['DB_PASS']) ? '(empty)' : '(set)') : 'NOT SET') . "\n";
echo "   DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NOT SET') . "\n\n";

echo "2. Testing PDO drivers...\n";
$drivers = PDO::getAvailableDrivers();
echo "   Available PDO drivers: " . implode(', ', $drivers) . "\n";

if (!in_array('mysql', $drivers)) {
    echo "   ❌ ERROR: mysql driver not available!\n";
    echo "   This means pdo_mysql is NOT loaded by this PHP process.\n\n";
    echo "   Check:\n";
    echo "   - Run: php -m | findstr pdo\n";
    echo "   - Ensure pdo_mysql shows up\n";
    echo "   - Restart Apache after enabling it\n";
    exit(1);
} else {
    echo "   ✅ mysql driver is available\n\n";
}

echo "3. Building DSN...\n";
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_PORT'] ?? '3306',
    $_ENV['DB_NAME'] ?? 'ttrpg_recap'
);
echo "   DSN: $dsn\n\n";

echo "4. Attempting connection...\n";
try {
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "   ✅ Connection successful!\n\n";
    
    echo "5. Testing query...\n";
    $stmt = $pdo->query('SELECT VERSION() as version, DATABASE() as db');
    $result = $stmt->fetch();
    
    echo "   MySQL Version: " . $result['version'] . "\n";
    echo "   Current Database: " . $result['db'] . "\n\n";
    
    echo "=== SUCCESS ===\n";
    echo "Database connection works!\n\n";
    
} catch (PDOException $e) {
    echo "   ❌ Connection failed\n\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n\n";
    
    // Specific error help
    if ($e->getCode() == 1045) {
        echo "This is ACCESS DENIED - wrong username or password\n";
        echo "Check your .env file: DB_USER and DB_PASS\n\n";
        echo "Try connecting manually:\n";
        echo "  mysql -u root -p\n";
        echo "  (enter password: Kx9#mPvL2\$nQr8Tw)\n";
    } elseif ($e->getCode() == 2002) {
        echo "This is CONNECTION REFUSED - MySQL not running\n";
        echo "Start MySQL in Laragon\n";
    } elseif (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "This is MISSING DRIVER\n";
        echo "pdo_mysql extension is not loaded by THIS PHP process\n";
        echo "Even though it may show in php -m, Apache might use different php.ini\n\n";
        echo "Solutions:\n";
        echo "1. Restart Apache in Laragon\n";
        echo "2. Check: Laragon → Menu → PHP Extensions\n";
        echo "3. Ensure pdo_mysql has a checkmark\n";
    }
    
    exit(1);
}
