<?php
/**
 * Diagnostic/Debug Page
 * Visit: http://localhost/ttrpg-recap/debug.php
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\Database;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTRPG Recap - Diagnostics</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0F172A;
            color: #F8FAFC;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #8B5CF6;
            border-bottom: 2px solid #8B5CF6;
            padding-bottom: 0.5rem;
        }
        h2 {
            color: #EC4899;
            margin-top: 2rem;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10B981;
            padding: 1rem;
            margin: 1rem 0;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #EF4444;
            padding: 1rem;
            margin: 1rem 0;
        }
        .info {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3B82F6;
            padding: 1rem;
            margin: 1rem 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        th {
            background: rgba(139, 92, 246, 0.2);
            color: #A78BFA;
        }
        code {
            background: rgba(30, 41, 59, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
        }
        pre {
            background: rgba(30, 41, 59, 0.5);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 TTRPG Recap - System Diagnostics</h1>

    <h2>1. Environment File</h2>
    <?php
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
        echo '<div class="success">✅ .env file loaded successfully from: ' . realpath(__DIR__ . '/../..') . '</div>';
        
        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'OPENAI_API_KEY', 'ANTHROPIC_API_KEY'];
        $missing = [];
        
        foreach ($requiredVars as $var) {
            if (empty($_ENV[$var])) {
                $missing[] = $var;
            }
        }
        
        if (empty($missing)) {
            echo '<div class="success">✅ All required environment variables are set</div>';
        } else {
            echo '<div class="error">❌ Missing environment variables: ' . implode(', ', $missing) . '</div>';
        }
        
        echo '<div class="info"><strong>Environment Variables:</strong><br>';
        echo 'APP_ENV: ' . ($_ENV['APP_ENV'] ?? 'not set') . '<br>';
        echo 'DB_HOST: ' . ($_ENV['DB_HOST'] ?? 'not set') . '<br>';
        echo 'DB_NAME: ' . ($_ENV['DB_NAME'] ?? 'not set') . '<br>';
        echo 'DB_USER: ' . ($_ENV['DB_USER'] ?? 'not set') . '<br>';
        echo 'OPENAI_API_KEY: ' . (empty($_ENV['OPENAI_API_KEY']) ? 'not set' : 'sk-....' . substr($_ENV['OPENAI_API_KEY'], -4)) . '<br>';
        echo 'ANTHROPIC_API_KEY: ' . (empty($_ENV['ANTHROPIC_API_KEY']) ? 'not set' : 'sk-ant-....' . substr($_ENV['ANTHROPIC_API_KEY'], -4)) . '<br>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Failed to load .env file: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div class="info">Looking for .env in: ' . realpath(__DIR__ . '/../..') . '</div>';
    }
    ?>

    <h2>2. PHP Configuration</h2>
    <?php
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
    
    $uploadMaxBytes = ini_get('upload_max_filesize');
    $postMaxBytes = ini_get('post_max_size');
    
    // Convert to bytes for comparison
    function parseSize($size) {
        $unit = strtoupper(substr($size, -1));
        $value = (int)$size;
        switch($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return $value;
        }
    }
    
    $uploadMaxMB = parseSize($uploadMaxBytes) / (1024 * 1024);
    $postMaxMB = parseSize($postMaxBytes) / (1024 * 1024);
    
    $phpChecks = [
        ['PHP Version', phpversion(), version_compare(phpversion(), '8.0.0', '>=') ? '✅' : '❌'],
        ['upload_max_filesize', $uploadMaxBytes, $uploadMaxMB >= 500 ? '✅' : '⚠️'],
        ['post_max_size', $postMaxBytes, $postMaxMB >= 500 ? '✅' : '⚠️'],
        ['max_execution_time', ini_get('max_execution_time') . 's', ''],
        ['memory_limit', ini_get('memory_limit'), ''],
    ];
    
    foreach ($phpChecks as $check) {
        echo '<tr><td>' . $check[0] . '</td><td><code>' . $check[1] . '</code></td><td>' . $check[2] . '</td></tr>';
    }
    echo '</table>';
    
    // Warning for low upload limits
    if ($uploadMaxMB < 500 || $postMaxMB < 500) {
        echo '<div class="error">';
        echo '<strong>⚠️ Upload Limits Too Low!</strong><br>';
        echo 'Your PHP upload limits are too small for typical TTRPG session recordings.<br><br>';
        echo '<strong>Current:</strong><br>';
        echo '- upload_max_filesize: ' . $uploadMaxBytes . ' (' . number_format($uploadMaxMB, 1) . ' MB)<br>';
        echo '- post_max_size: ' . $postMaxBytes . ' (' . number_format($postMaxMB, 1) . ' MB)<br><br>';
        echo '<strong>Recommended:</strong><br>';
        echo '- upload_max_filesize: 500M<br>';
        echo '- post_max_size: 500M<br><br>';
        echo '<strong>How to fix:</strong><br>';
        echo '1. In Laragon: Menu → PHP → php.ini<br>';
        echo '2. Search for "upload_max_filesize" and set to 500M<br>';
        echo '3. Search for "post_max_size" and set to 500M<br>';
        echo '4. Restart Apache<br><br>';
        echo 'See: <code>FIX_UPLOAD_LIMIT.md</code> for detailed instructions';
        echo '</div>';
    } else {
        echo '<div class="success">✅ Upload limits are configured correctly for TTRPG session files</div>';
    }
    ?>

    <h2>3. Database Connection</h2>
    <?php
    try {
        $db = Database::getInstance();
        echo '<div class="success">✅ Database connection successful</div>';
        
        // Test query
        $stmt = $db->query('SELECT VERSION() as version');
        $result = $stmt->fetch();
        echo '<div class="info">MySQL Version: ' . $result['version'] . '</div>';
        
        // Check tables
        $tables = ['users', 'campaigns', 'sessions', 'transcripts', 'recaps', 'entities', 'jobs'];
        $stmt = $db->query('SHOW TABLES');
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<div class="info"><strong>Database Tables:</strong><br>';
        foreach ($tables as $table) {
            $exists = in_array($table, $existingTables);
            echo ($exists ? '✅' : '❌') . ' ' . $table . '<br>';
        }
        echo '</div>';
        
        // Count records
        echo '<div class="info"><strong>Record Counts:</strong><br>';
        foreach ($tables as $table) {
            if (in_array($table, $existingTables)) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "$table: $count records<br>";
            }
        }
        echo '</div>';
        
    } catch (PDOException $e) {
        echo '<div class="error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <h2>4. File System</h2>
    <?php
    $directories = [
        'storage/audio' => __DIR__ . '/../storage/audio',
        'storage/narrations' => __DIR__ . '/../storage/narrations',
        'vendor' => __DIR__ . '/../vendor',
    ];
    
    echo '<table>';
    echo '<tr><th>Directory</th><th>Path</th><th>Exists</th><th>Writable</th></tr>';
    
    foreach ($directories as $name => $path) {
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        
        echo '<tr>';
        echo '<td>' . $name . '</td>';
        echo '<td><code>' . $path . '</code></td>';
        echo '<td>' . ($exists ? '✅ Yes' : '❌ No') . '</td>';
        echo '<td>' . ($writable ? '✅ Yes' : '❌ No') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>

    <h2>5. Routing Test</h2>
    <?php
    echo '<div class="info">';
    echo '<strong>Current Request:</strong><br>';
    echo 'URI: ' . $_SERVER['REQUEST_URI'] . '<br>';
    echo 'Method: ' . $_SERVER['REQUEST_METHOD'] . '<br>';
    echo 'Script: ' . $_SERVER['SCRIPT_NAME'] . '<br>';
    echo '<br><strong>Test Routes:</strong><br>';
    echo '<a href="/ttrpg-recap/" style="color: #A78BFA;">Home</a><br>';
    echo '<a href="/ttrpg-recap/campaigns" style="color: #A78BFA;">Campaigns</a><br>';
    echo '<a href="/ttrpg-recap/sessions/1" style="color: #A78BFA;">Session 1 (if exists)</a><br>';
    echo '</div>';
    ?>

    <h2>6. Recent Jobs</h2>
    <?php
    try {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10');
        $jobs = $stmt->fetchAll();
        
        if (empty($jobs)) {
            echo '<div class="info">No jobs in queue yet</div>';
        } else {
            echo '<table>';
            echo '<tr><th>ID</th><th>Queue</th><th>Status</th><th>Attempts</th><th>Created</th></tr>';
            foreach ($jobs as $job) {
                echo '<tr>';
                echo '<td>' . $job['id'] . '</td>';
                echo '<td>' . $job['queue'] . '</td>';
                echo '<td>' . $job['status'] . '</td>';
                echo '<td>' . $job['attempts'] . '</td>';
                echo '<td>' . $job['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<div class="error">Could not retrieve jobs: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <h2>7. PHP Loaded Extensions</h2>
    <?php
    $requiredExtensions = ['pdo_mysql', 'json', 'mbstring', 'fileinfo', 'curl'];
    $loadedExtensions = get_loaded_extensions();
    
    echo '<div class="info">';
    
    // Check PDO separately (it's loaded with pdo_mysql)
    $pdoAvailable = extension_loaded('pdo') || extension_loaded('pdo_mysql');
    echo ($pdoAvailable ? '✅' : '❌') . ' pdo (or pdo_mysql)<br>';
    
    foreach ($requiredExtensions as $ext) {
        $loaded = in_array($ext, $loadedExtensions);
        echo ($loaded ? '✅' : '❌') . ' ' . $ext . '<br>';
    }
    echo '</div>';
    ?>

    <h2>8. Recommendations</h2>
    <div class="info">
        <strong>Next Steps:</strong><br>
        1. Ensure all ❌ items above are resolved<br>
        2. Make sure <code>.env</code> file is in the parent directory<br>
        3. Import <code>schema.sql</code> if tables are missing<br>
        4. Set proper file permissions on storage directories<br>
        5. Start the worker: <code>php worker.php</code><br>
        6. Visit the main site: <a href="/ttrpg-recap/" style="color: #A78BFA;">http://localhost/ttrpg-recap/</a>
    </div>

    <hr style="margin: 2rem 0; border-color: #334155;">
    <p style="text-align: center; color: #94A3B8;">
        TTRPG Session Recap - Diagnostics v1.0<br>
        <a href="/ttrpg-recap/" style="color: #A78BFA;">← Back to Application</a>
    </p>
</body>
</html>
