<?php
namespace App\Jobs;

use App\Models\Session;
use App\Services\WhisperService;
use App\Services\JobQueue;

class TranscribeSessionJob
{
    private Session $sessionModel;
    private WhisperService $whisper;
    private JobQueue $queue;

    public function __construct()
    {
        $this->sessionModel = new Session();
        $this->whisper = new WhisperService();
        $this->queue = new JobQueue();
    }

    /**
     * Execute the transcription job
     */
    public function handle(array $data): void
    {
        $sessionId = $data['session_id'];
        $startTime = microtime(true);
        
        echo "[" . date('H:i:s') . "] 🎙️  Starting transcription for session $sessionId\n";
        
        try {
            // Update status
            $this->sessionModel->updateStatus($sessionId, 'transcribing');

            // Get session details
            $session = $this->sessionModel->getById($sessionId);
            if (!$session) {
                throw new \Exception("Session not found: $sessionId");
            }

            echo "[" . date('H:i:s') . "] 📄 Session: {$session['title']}\n";

            $audioPath = $session['audio_file_path'];
            if (!file_exists($audioPath)) {
                throw new \Exception("Audio file not found: $audioPath");
            }

            $fileSize = filesize($audioPath);
            echo "[" . date('H:i:s') . "] 📦 File size: " . number_format($fileSize / (1024 * 1024), 2) . " MB\n";

            // Transcribe with Whisper
            echo "[" . date('H:i:s') . "] 🚀 Sending to Whisper API...\n";
            $transcribeStart = microtime(true);
            
            $transcriptData = $this->whisper->transcribe($audioPath);
            
            $transcribeEnd = microtime(true);
            $transcribeDuration = round($transcribeEnd - $transcribeStart, 2);
            
            echo "[" . date('H:i:s') . "] ✅ Transcription received in {$transcribeDuration}s\n";
            echo "[" . date('H:i:s') . "] 📝 Transcript: " . number_format(strlen($transcriptData['text'])) . " characters, " . 
                  number_format(str_word_count($transcriptData['text'])) . " words\n";

            // Save transcript
            $this->sessionModel->saveTranscript($sessionId, $transcriptData);
            echo "[" . date('H:i:s') . "] 💾 Transcript saved to database\n";

            // Update session status
            $this->sessionModel->updateStatus($sessionId, 'processing');

            // Queue summarization job
            $this->queue->push(SummarizeSessionJob::class, [
                'session_id' => $sessionId,
            ]);

            $totalDuration = round(microtime(true) - $startTime, 2);
            echo "[" . date('H:i:s') . "] ✨ Transcription job complete in {$totalDuration}s. Queued summarization.\n\n";

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            echo "[" . date('H:i:s') . "] ❌ Transcription failed for session $sessionId after {$duration}s\n";
            echo "[" . date('H:i:s') . "] 🔴 Error: " . $e->getMessage() . "\n\n";
            
            $this->sessionModel->updateStatus($sessionId, 'failed', $e->getMessage());
            throw $e;
        }
    }
}
