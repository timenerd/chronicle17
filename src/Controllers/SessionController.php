<?php
namespace App\Controllers;

use App\Models\Campaign;
use App\Models\Session;
use App\Services\JobQueue;
use App\Jobs\TranscribeSessionJob;

class SessionController
{
    private Campaign $campaignModel;
    private Session $sessionModel;
    private JobQueue $queue;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
        $this->sessionModel = new Session();
        $this->queue = new JobQueue();
    }

    /**
     * Handle session upload
     */
    public function upload(): void
    {
        header('Content-Type: application/json');
        
        // For debugging - log request details
        $debug = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        if ($debug) {
            error_log("=== SESSION UPLOAD START ===");
            error_log("POST data: " . json_encode($_POST));
            error_log("FILES data: " . json_encode(array_map(function($file) {
                return [
                    'name' => $file['name'] ?? 'unknown',
                    'size' => $file['size'] ?? 0,
                    'error' => $file['error'] ?? -1,
                ];
            }, $_FILES)));
        }

        try {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $sessionNumber = !empty($_POST['session_number']) ? (int)$_POST['session_number'] : null;

            if ($debug) {
                error_log("Campaign ID: $campaignId, Title: $title");
            }

            // Validate campaign ID
            if ($campaignId <= 0) {
                throw new \Exception('Invalid campaign ID');
            }

            // Validate campaign exists
            $campaign = $this->campaignModel->getById($campaignId);
            if (!$campaign) {
                throw new \Exception('Campaign not found (ID: ' . $campaignId . ')');
            }

            if ($debug) {
                error_log("Campaign found: " . $campaign['name']);
            }

            // Validate file upload
            if (!isset($_FILES['audio'])) {
                throw new \Exception('No audio file in request. Make sure form has enctype="multipart/form-data"');
            }

            if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in form',
                    UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
                ];
                $errorCode = $_FILES['audio']['error'];
                $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error code: ' . $errorCode;
                throw new \Exception('Upload error: ' . $errorMsg);
            }

            $file = $_FILES['audio'];
            
            if ($debug) {
                error_log("File received: {$file['name']}, Size: {$file['size']} bytes");
            }
            
            // Validate file size
            $config = require __DIR__ . '/../../config/config.php';
            $maxSize = $config['storage']['max_upload_size_mb'] * 1024 * 1024;
            
            if ($file['size'] > $maxSize) {
                throw new \Exception("File too large. Maximum size: {$config['storage']['max_upload_size_mb']}MB");
            }

            // Validate file type
            $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/wav', 'audio/webm', 'audio/m4a', 'application/octet-stream'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($debug) {
                error_log("MIME type detected: $mimeType");
            }

            if (!in_array($mimeType, $allowedTypes) && !$this->hasAudioExtension($file['name'])) {
                throw new \Exception('Invalid file type: ' . $mimeType . '. Please upload an audio file (MP3, WAV, M4A, etc.)');
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('session_') . '.' . $extension;
            $storagePath = __DIR__ . '/../../storage/audio/' . $filename;

            if ($debug) {
                error_log("Saving to: $storagePath");
            }

            // Ensure storage directory exists
            $storageDir = __DIR__ . '/../../storage/audio/';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0775, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
                throw new \Exception('Failed to save uploaded file. Check storage directory permissions.');
            }

            if ($debug) {
                error_log("File saved successfully");
            }

            // Get audio duration (basic approach)
            $duration = $this->getAudioDuration($storagePath);

            // Create session record
            $sessionId = $this->sessionModel->create([
                'campaign_id' => $campaignId,
                'title' => $title ?: 'Session ' . ($sessionNumber ?? 'Untitled'),
                'session_number' => $sessionNumber,
                'session_date' => $_POST['session_date'] ?? date('Y-m-d'),
                'audio_file_path' => $storagePath,
                'audio_duration_seconds' => $duration,
                'status' => 'pending',
            ]);

            if ($debug) {
                error_log("Session created with ID: $sessionId");
            }

            // Queue transcription job
            $this->queue->push(TranscribeSessionJob::class, [
                'session_id' => $sessionId,
            ]);

            if ($debug) {
                error_log("Job queued for session $sessionId");
                error_log("=== SESSION UPLOAD COMPLETE ===");
            }

            echo json_encode([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Upload successful. Processing started.',
            ]);

        } catch (\PDOException $e) {
            if ($debug) {
                error_log("Database error: " . $e->getMessage());
            }
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            if ($debug) {
                error_log("Upload error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get session status (for AJAX polling)
     */
    public function status(int $sessionId): void
    {
        header('Content-Type: application/json');

        try {
            $session = $this->sessionModel->getById($sessionId);
            
            if (!$session) {
                throw new \Exception('Session not found');
            }

            echo json_encode([
                'success' => true,
                'status' => $session['status'],
                'error_message' => $session['error_message'],
            ]);

        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a session and its associated data
     */
    public function delete(int $sessionId): void
    {
        header('Content-Type: application/json');

        try {
            $session = $this->sessionModel->getById($sessionId);
            
            if (!$session) {
                throw new \Exception('Session not found');
            }

            // Delete audio file if it exists
            if (!empty($session['audio_file_path']) && file_exists($session['audio_file_path'])) {
                unlink($session['audio_file_path']);
            }

            // Delete from database (cascading deletes will handle related records)
            $db = \App\Services\Database::getInstance();
            
            // Delete session entities
            $stmt = $db->prepare("DELETE FROM session_entities WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            
            // Delete transcript
            $stmt = $db->prepare("DELETE FROM transcripts WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            
            // Delete recap
            $stmt = $db->prepare("DELETE FROM recaps WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            
            // Delete pending jobs for this session
            $stmt = $db->prepare("DELETE FROM jobs WHERE payload LIKE ?");
            $stmt->execute(['%"session_id":' . $sessionId . '%']);
            
            // Delete session
            $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$sessionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Session deleted successfully',
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * View session details
     */
    public function view(int $sessionId): void
    {
        $session = $this->sessionModel->getById($sessionId);
        
        if (!$session) {
            http_response_code(404);
            echo "Session not found";
            return;
        }

        $campaign = $this->campaignModel->getById($session['campaign_id']);
        $transcript = $this->sessionModel->getTranscript($sessionId);
        $recap = $this->sessionModel->getRecap($sessionId);
        $entities = $this->sessionModel->getEntities($sessionId);

        require __DIR__ . '/../Views/session-detail.php';
    }

    /**
     * Export session as markdown
     */
    public function exportMarkdown(int $sessionId): void
    {
        $session = $this->sessionModel->getById($sessionId);
        
        if (!$session) {
            http_response_code(404);
            return;
        }

        $campaign = $this->campaignModel->getById($session['campaign_id']);
        $recap = $this->sessionModel->getRecap($sessionId);

        $markdown = $this->generateMarkdown($session, $campaign, $recap);

        header('Content-Type: text/markdown');
        header('Content-Disposition: attachment; filename="session-' . $sessionId . '.md"');
        echo $markdown;
    }

    private function hasAudioExtension(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm']);
    }

    private function getAudioDuration(string $filePath): ?int
    {
        // Simple approach - would need ffmpeg or similar for accurate duration
        // For now, return null and we'll get it from Whisper
        return null;
    }

    private function generateMarkdown($session, $campaign, $recap): string
    {
        $md = "# {$session['title']}\n\n";
        $md .= "**Campaign:** {$campaign['name']}\n";
        $md .= "**Date:** {$session['session_date']}\n\n";

        if ($recap) {
            $md .= "## Brief Summary\n\n";
            $md .= $recap['brief_summary'] . "\n\n";

            $md .= "## Full Recap\n\n";
            $md .= $recap['narrative_recap'] . "\n\n";

            if (!empty($recap['memorable_quotes'])) {
                $md .= "## Memorable Quotes\n\n";
                foreach ($recap['memorable_quotes'] as $quote) {
                    $md .= "> \"{$quote['quote']}\" - {$quote['speaker']}\n";
                    if (!empty($quote['context'])) {
                        $md .= "> *{$quote['context']}*\n";
                    }
                    $md .= "\n";
                }
            }

            if (!empty($recap['plot_hooks'])) {
                $md .= "## Plot Hooks\n\n";
                foreach ($recap['plot_hooks'] as $hook) {
                    $importance = $hook['importance'] ?? 'minor';
                    $md .= "- **[{$importance}]** {$hook['hook']}\n";
                }
                $md .= "\n";
            }
        }

        return $md;
    }
}
