<?php
namespace App\Jobs;

use App\Models\Session;
use App\Models\Campaign;
use App\Models\Entity;
use App\Services\ClaudeService;

class SummarizeSessionJob
{
    private Session $sessionModel;
    private Campaign $campaignModel;
    private Entity $entityModel;
    private ClaudeService $claude;

    public function __construct()
    {
        $this->sessionModel = new Session();
        $this->campaignModel = new Campaign();
        $this->entityModel = new Entity();
        $this->claude = new ClaudeService();
    }

    /**
     * Execute the summarization job
     */
    public function handle(array $data): void
    {
        $sessionId = $data['session_id'];
        $startTime = microtime(true);
        
        echo "[" . date('H:i:s') . "] ✨ Starting summarization for session $sessionId\n";
        
        try {
            // Get session and transcript
            $session = $this->sessionModel->getById($sessionId);
            if (!$session) {
                throw new \Exception("Session not found: $sessionId");
            }

            echo "[" . date('H:i:s') . "] 📄 Session: {$session['title']}\n";

            $transcript = $this->sessionModel->getTranscript($sessionId);
            if (!$transcript) {
                throw new \Exception("Transcript not found for session: $sessionId");
            }

            echo "[" . date('H:i:s') . "] 📝 Transcript: " . number_format(strlen($transcript['raw_text'])) . " characters, " .
                  number_format($transcript['word_count']) . " words\n";

            // Get campaign context
            $campaign = $this->campaignModel->getById($session['campaign_id']);
            $characters = $this->campaignModel->getCharacters($session['campaign_id']);

            echo "[" . date('H:i:s') . "] 🏰 Campaign: {$campaign['name']}\n";
            echo "[" . date('H:i:s') . "] 👥 Party: " . count($characters) . " characters\n";

            $context = [
                'setting_context' => $campaign['setting_context'] ?? '',
                'characters' => $characters,
            ];

            // Generate recap with Claude
            echo "[" . date('H:i:s') . "] 🤖 Sending to Claude API...\n";
            $claudeStart = microtime(true);
            
            $recap = $this->claude->generateRecap($transcript['raw_text'], $context);
            
            $claudeEnd = microtime(true);
            $claudeDuration = round($claudeEnd - $claudeStart, 2);
            
            echo "[" . date('H:i:s') . "] ✅ Recap received in {$claudeDuration}s\n";
            echo "[" . date('H:i:s') . "] 📖 Narrative: " . number_format(strlen($recap['narrative_recap'] ?? '')) . " characters\n";
            echo "[" . date('H:i:s') . "] 💬 Quotes: " . count($recap['memorable_quotes'] ?? []) . "\n";
            echo "[" . date('H:i:s') . "] 🎣 Plot hooks: " . count($recap['plot_hooks'] ?? []) . "\n";

            // Save recap
            $this->sessionModel->saveRecap($sessionId, $recap);
            echo "[" . date('H:i:s') . "] 💾 Recap saved to database\n";

            // Process extracted entities
            if (!empty($recap['entities'])) {
                $entityCount = count($recap['entities']);
                echo "[" . date('H:i:s') . "] 📚 Processing $entityCount entities...\n";
                
                $entityTypes = [];
                $newEntities = 0;
                $updatedEntities = 0;
                
                foreach ($recap['entities'] as $entityData) {
                    $isNew = $entityData['is_new'] ?? true;
                    $type = $entityData['type'] ?? 'unknown';
                    
                    $entityTypes[$type] = ($entityTypes[$type] ?? 0) + 1;
                    
                    $this->entityModel->upsertFromExtraction(
                        $session['campaign_id'],
                        $sessionId,
                        $entityData
                    );
                    
                    if ($isNew) {
                        $newEntities++;
                    } else {
                        $updatedEntities++;
                    }
                }
                
                echo "[" . date('H:i:s') . "] 🆕 New entities: $newEntities\n";
                echo "[" . date('H:i:s') . "] 🔄 Updated entities: " . ($entityCount - $newEntities) . "\n";
                
                // Show breakdown by type
                foreach ($entityTypes as $type => $count) {
                    $icon = ['npc' => '👤', 'location' => '📍', 'item' => '⚔️', 'faction' => '🏛️', 'event' => '📅'][$type] ?? '❓';
                    echo "[" . date('H:i:s') . "] {$icon} {$type}: $count\n";
                }
            } else {
                echo "[" . date('H:i:s') . "] ℹ️  No entities extracted\n";
            }

            // Update session status to complete
            $this->sessionModel->updateStatus($sessionId, 'complete');

            $totalDuration = round(microtime(true) - $startTime, 2);
            echo "[" . date('H:i:s') . "] 🎉 Session $sessionId processing complete in {$totalDuration}s!\n";
            echo str_repeat("=", 60) . "\n\n";

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            echo "[" . date('H:i:s') . "] ❌ Summarization failed for session $sessionId after {$duration}s\n";
            echo "[" . date('H:i:s') . "] 🔴 Error: " . $e->getMessage() . "\n";
            echo str_repeat("=", 60) . "\n\n";
            
            $this->sessionModel->updateStatus($sessionId, 'failed', $e->getMessage());
            throw $e;
        }
    }
}
