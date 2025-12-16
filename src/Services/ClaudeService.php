<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ClaudeService
{
    private Client $client;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->apiKey = $config['apis']['anthropic']['key'];
        $this->model = $config['apis']['anthropic']['model'];

        $this->client = new Client([
            'base_uri' => 'https://api.anthropic.com/v1/',
            'timeout' => 300, // 5 minutes
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate recap from transcript
     * 
     * @param string $transcript Raw transcript text
     * @param array $campaignContext Campaign details and characters
     * @return array Structured recap data
     * @throws \Exception
     */
    public function generateRecap(string $transcript, array $campaignContext = []): array
    {
        $debug = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        if ($debug) {
            error_log("=== CLAUDE SUMMARIZATION START ===");
            error_log("Transcript length: " . strlen($transcript) . " characters");
            error_log("Word count: " . str_word_count($transcript));
            error_log("Campaign context: " . json_encode(array_keys($campaignContext)));
        }
        
        $prompt = $this->buildPrompt($transcript, $campaignContext);
        
        if ($debug) {
            error_log("Prompt length: " . strlen($prompt) . " characters");
            // Estimate tokens (rough: 1 token ≈ 4 characters)
            $estimatedTokens = (int)(strlen($prompt) / 4);
            error_log("Estimated input tokens: " . number_format($estimatedTokens));
        }

        try {
            if ($debug) {
                error_log("Sending request to Claude API...");
                $startTime = microtime(true);
            }
            
            $response = $this->client->post('messages', [
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            if ($debug) {
                $endTime = microtime(true);
                $duration = round($endTime - $startTime, 2);
                error_log("Claude API response received in {$duration} seconds");
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($debug) {
                error_log("Response status: " . $response->getStatusCode());
                error_log("Model used: " . ($data['model'] ?? 'unknown'));
                error_log("Stop reason: " . ($data['stop_reason'] ?? 'unknown'));
                
                // Log token usage
                if (isset($data['usage'])) {
                    error_log("Token usage:");
                    error_log("  Input: " . ($data['usage']['input_tokens'] ?? 0));
                    error_log("  Output: " . ($data['usage']['output_tokens'] ?? 0));
                    
                    $cost = $this->estimateCost(
                        $data['usage']['input_tokens'] ?? 0,
                        $data['usage']['output_tokens'] ?? 0
                    );
                    error_log("  Estimated cost: $" . number_format($cost, 4));
                }
            }
            
            $content = $data['content'][0]['text'] ?? '';
            
            if ($debug) {
                error_log("Response content length: " . strlen($content) . " characters");
            }

            // Extract JSON from response (Claude sometimes wraps it in markdown)
            $jsonContent = $this->extractJSON($content);
            
            if ($debug) {
                error_log("Extracted JSON length: " . strlen($jsonContent) . " characters");
            }
            
            $recap = json_decode($jsonContent, true);

            if (!$recap) {
                if ($debug) {
                    error_log("JSON decode failed!");
                    error_log("Raw content: " . substr($content, 0, 500) . "...");
                    error_log("JSON error: " . json_last_error_msg());
                }
                throw new \Exception('Failed to parse Claude response as JSON: ' . json_last_error_msg());
            }
            
            if ($debug) {
                error_log("Recap parsed successfully:");
                error_log("  Narrative length: " . strlen($recap['narrative_recap'] ?? '') . " characters");
                error_log("  Quotes: " . count($recap['memorable_quotes'] ?? []));
                error_log("  Plot hooks: " . count($recap['plot_hooks'] ?? []));
                error_log("  Entities: " . count($recap['entities'] ?? []));
                
                if (!empty($recap['entities'])) {
                    $entityTypes = array_count_values(array_column($recap['entities'], 'type'));
                    error_log("  Entity breakdown: " . json_encode($entityTypes));
                }
                
                error_log("=== CLAUDE SUMMARIZATION COMPLETE ===");
            }

            return $recap;

        } catch (GuzzleException $e) {
            if ($debug) {
                error_log("Claude API error: " . $e->getMessage());
                if ($e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                    error_log("Error response: " . $errorBody);
                    
                    // Try to parse error details
                    $errorData = json_decode($errorBody, true);
                    if ($errorData && isset($errorData['error'])) {
                        error_log("Error type: " . ($errorData['error']['type'] ?? 'unknown'));
                        error_log("Error message: " . ($errorData['error']['message'] ?? 'unknown'));
                    }
                }
            }
            throw new \Exception('Claude API error: ' . $e->getMessage());
        }
    }

    /**
     * Build the summarization prompt
     */
    private function buildPrompt(string $transcript, array $context): string
    {
        $settingContext = $context['setting_context'] ?? 'A fantasy tabletop RPG campaign';
        $characters = $context['characters'] ?? [];
        
        $characterList = '';
        foreach ($characters as $char) {
            $characterList .= sprintf(
                "- %s (%s/%s) played by %s\n",
                $char['name'],
                $char['class'] ?? 'Unknown',
                $char['race'] ?? 'Unknown',
                $char['player_name'] ?? 'Unknown'
            );
        }

        return <<<PROMPT
You are a skilled chronicler summarizing a TTRPG session. Given a transcript of gameplay, create an engaging narrative recap.

CAMPAIGN CONTEXT:
$settingContext

KNOWN CHARACTERS:
$characterList

TRANSCRIPT:
$transcript

Respond with JSON in this exact format (NO markdown code blocks, just raw JSON):
{
  "narrative_recap": "A 500-1500 word prose summary written in past tense, third person, suitable for reading aloud at the start of the next session. Include dramatic moments, key decisions, and character interactions. Write in an engaging fantasy chronicle style.",
  
  "brief_summary": "2-3 sentence summary of the most important events.",
  
  "memorable_quotes": [
    {"speaker": "Character name or Unknown", "quote": "The actual quote", "context": "Brief context"}
  ],
  
  "plot_hooks": [
    {"hook": "Description of unresolved thread", "importance": "major"}
  ],
  
  "entities": [
    {
      "type": "npc",
      "name": "Entity name",
      "description": "Brief description based on what was learned this session",
      "is_new": true
    }
  ]
}

Remember: Return ONLY valid JSON, no additional text or markdown formatting.
PROMPT;
    }

    /**
     * Extract JSON from Claude's response (handles markdown wrapping)
     */
    private function extractJSON(string $content): string
    {
        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $content, $matches)) {
            return $matches[1];
        }
        
        // Try to find JSON object directly
        if (preg_match('/(\{.*\})/s', $content, $matches)) {
            return $matches[1];
        }
        
        return $content;
    }

    /**
     * Calculate estimated cost for summarization
     */
    public function estimateCost(int $inputTokens, int $outputTokens = 1500): float
    {
        // Approximate costs for Claude Sonnet (adjust based on actual model)
        $inputCostPerMToken = 3.00;  // $3 per million tokens
        $outputCostPerMToken = 15.00; // $15 per million tokens
        
        $inputCost = ($inputTokens / 1000000) * $inputCostPerMToken;
        $outputCost = ($outputTokens / 1000000) * $outputCostPerMToken;
        
        return $inputCost + $outputCost;
    }
}
