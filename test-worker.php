<?php
/**
 * Quick Test - Check if worker can start
 */

require __DIR__ . '/vendor/autoload.php';

echo "=== Worker Startup Test ===\n\n";

// 1. Check .env loads
echo "1. Testing .env file...\n";
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    echo "   ✅ .env loaded from: " . realpath(__DIR__ . '/..') . "\n";
} catch (\Exception $e) {
    echo "   ❌ Failed to load .env: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check database connection
echo "\n2. Testing database connection...\n";
try {
    $db = \App\Services\Database::getInstance();
    echo "   ✅ Database connected\n";
    
    // Test query
    $stmt = $db->query('SELECT 1');
    echo "   ✅ Test query successful\n";
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "   Check your .env file:\n";
    echo "   - DB_HOST=" . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "   - DB_NAME=" . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
    echo "   - DB_USER=" . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
    echo "   - DB_PASS=" . (empty($_ENV['DB_PASS']) ? 'NOT SET' : '***') . "\n";
    exit(1);
}

// 3. Check for pending jobs
echo "\n3. Checking for pending jobs...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'");
    $result = $stmt->fetch();
    $count = $result['count'];
    
    if ($count > 0) {
        echo "   🟡 Found $count pending job(s)\n";
        
        // Show them
        $stmt = $db->query("
            SELECT j.id, j.queue, j.status, j.created_at, j.payload
            FROM jobs j
            WHERE status = 'pending'
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $jobs = $stmt->fetchAll();
        
        echo "\n   Recent pending jobs:\n";
        foreach ($jobs as $job) {
            $payload = json_decode($job['payload'], true);
            $jobClass = basename($payload['job'] ?? 'Unknown');
            echo "   - Job #{$job['id']}: $jobClass (created: {$job['created_at']})\n";
        }
    } else {
        echo "   ✅ No pending jobs (queue is empty)\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error checking jobs: " . $e->getMessage() . "\n";
}

// 4. Check for sessions
echo "\n4. Checking sessions...\n";
try {
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM sessions GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (empty($stats)) {
        echo "   ℹ️  No sessions yet\n";
    } else {
        foreach ($stats as $status => $count) {
            $icon = ['pending' => '🟡', 'transcribing' => '🎙️', 'processing' => '⏳', 'complete' => '✅', 'failed' => '❌'][$status] ?? '❓';
            echo "   $icon $status: $count\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error checking sessions: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n\n";

// 5. Ready to start?
if (isset($count) && $count > 0) {
    echo "✅ Worker can start! Run: php worker.php\n";
    echo "   It will process $count pending job(s)\n";
} else {
    echo "✅ Worker can start, but no jobs to process yet.\n";
    echo "   Upload a session to create jobs!\n";
}

echo "\n";
