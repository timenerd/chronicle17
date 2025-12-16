<?php
/**
 * Background Job Worker
 * 
 * Usage: php worker.php [queue_name]
 * Example: php worker.php default
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\JobQueue;

// Load environment from parent directory (outside web root)
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$queue = $argv[1] ?? 'default';
$jobQueue = new JobQueue();

echo "Worker started. Listening on queue: $queue\n";
echo "Press Ctrl+C to stop.\n\n";

while (true) {
    try {
        $job = $jobQueue->pop($queue);

        if (!$job) {
            // No jobs available, sleep for a bit
            sleep(2);
            continue;
        }

        echo "[" . date('Y-m-d H:i:s') . "] Processing job #{$job['id']}\n";

        $jobClass = $job['payload']['job'];
        $jobData = $job['payload']['data'];

        // Instantiate and execute the job
        if (!class_exists($jobClass)) {
            throw new \Exception("Job class not found: $jobClass");
        }

        $jobInstance = new $jobClass();
        
        if (!method_exists($jobInstance, 'handle')) {
            throw new \Exception("Job class must have a handle() method");
        }

        $jobInstance->handle($jobData);

        // Mark as complete
        $jobQueue->complete($job['id']);
        echo "[" . date('Y-m-d H:i:s') . "] Job #{$job['id']} completed successfully\n\n";

    } catch (\Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Job #{$job['id']} failed: " . $e->getMessage() . "\n";
        
        // Mark as failed
        $jobQueue->fail($job['id'], $e->getMessage());
        
        // Retry if attempts < 3
        if ($job['attempts'] < 2) {
            $jobQueue->retry($job['id'], 60);
            echo "[" . date('Y-m-d H:i:s') . "] Job #{$job['id']} queued for retry\n\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] Job #{$job['id']} max attempts reached, giving up\n\n";
        }
    }
}
