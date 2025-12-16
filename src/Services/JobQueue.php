<?php
namespace App\Services;

use App\Services\Database;
use PDO;

class JobQueue
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add a job to the queue
     */
    public function push(string $jobClass, array $payload, string $queue = 'default'): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO jobs (queue, payload, status, available_at)
            VALUES (:queue, :payload, :status, :available_at)
        ');
        
        $stmt->execute([
            'queue' => $queue,
            'payload' => json_encode([
                'job' => $jobClass,
                'data' => $payload,
            ]),
            'status' => 'pending',
            'available_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get next available job
     */
    public function pop(string $queue = 'default'): ?array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                SELECT * FROM jobs 
                WHERE queue = ? 
                AND status = "pending" 
                AND available_at <= NOW()
                ORDER BY id ASC 
                LIMIT 1
                FOR UPDATE
            ');
            $stmt->execute([$queue]);
            $job = $stmt->fetch();

            if (!$job) {
                $this->db->rollBack();
                return null;
            }

            // Mark as processing
            $updateStmt = $this->db->prepare('
                UPDATE jobs 
                SET status = "processing", attempts = attempts + 1
                WHERE id = ?
            ');
            $updateStmt->execute([$job['id']]);

            $this->db->commit();

            $job['payload'] = json_decode($job['payload'], true);
            return $job;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mark job as complete
     */
    public function complete(int $jobId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE jobs 
            SET status = "complete", completed_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$jobId]);
    }

    /**
     * Mark job as failed
     */
    public function fail(int $jobId, string $errorMessage): bool
    {
        $stmt = $this->db->prepare('
            UPDATE jobs 
            SET status = "failed", error_message = ?, completed_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$errorMessage, $jobId]);
    }

    /**
     * Retry a failed job
     */
    public function retry(int $jobId, int $delaySeconds = 60): bool
    {
        $availableAt = date('Y-m-d H:i:s', time() + $delaySeconds);
        
        $stmt = $this->db->prepare('
            UPDATE jobs 
            SET status = "pending", available_at = ?
            WHERE id = ? AND attempts < 3
        ');
        return $stmt->execute([$availableAt, $jobId]);
    }
}
