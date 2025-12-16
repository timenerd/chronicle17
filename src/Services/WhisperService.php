<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WhisperService
{
    private Client $client;
    private string $apiKey;
    private string $model = 'whisper-1';

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->apiKey = $config['apis']['openai']['key'];
        $this->model = $config['apis']['openai']['whisper_model'];

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 600, // 10 minutes for large files
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }

    /**
     * Transcribe audio file using Whisper API
     * 
     * @param string $audioFilePath Path to audio file
     * @return array ['text' => string, 'segments' => array]
     * @throws \Exception
     */
    public function transcribe(string $audioFilePath): array
    {
        $debug = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        if ($debug) {
            error_log("=== WHISPER TRANSCRIPTION START ===");
            error_log("Audio file: $audioFilePath");
        }
        
        if (!file_exists($audioFilePath)) {
            throw new \Exception("Audio file not found: $audioFilePath");
        }

        $fileSize = filesize($audioFilePath);
        $maxSize = 25 * 1024 * 1024; // 25MB - Whisper API limit
        
        if ($debug) {
            error_log("File size: " . number_format($fileSize / (1024 * 1024), 2) . " MB");
        }

        // If file is larger than 25MB, we would need to chunk it
        // For MVP, we'll throw an error and handle chunking in v2
        if ($fileSize > $maxSize) {
            throw new \Exception("Audio file too large. Please split files larger than 25MB (current implementation limitation).");
        }

        try {
            if ($debug) {
                error_log("Sending request to Whisper API...");
                $startTime = microtime(true);
            }
            
            $response = $this->client->post('audio/transcriptions', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($audioFilePath, 'r'),
                        'filename' => basename($audioFilePath),
                    ],
                    [
                        'name' => 'model',
                        'contents' => $this->model,
                    ],
                    [
                        'name' => 'response_format',
                        'contents' => 'verbose_json', // Get timestamps
                    ],
                    [
                        'name' => 'timestamp_granularities[]',
                        'contents' => 'segment',
                    ],
                ],
            ]);

            if ($debug) {
                $endTime = microtime(true);
                $duration = round($endTime - $startTime, 2);
                error_log("Whisper API response received in {$duration} seconds");
                error_log("Response status: " . $response->getStatusCode());
            }

            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($debug) {
                error_log("Transcription length: " . strlen($data['text'] ?? '') . " characters");
                error_log("Segments count: " . count($data['segments'] ?? []));
                error_log("Duration: " . ($data['duration'] ?? 'unknown') . " seconds");
                
                // Log cost estimate
                $cost = $this->estimateCost((int)($data['duration'] ?? 0));
                error_log("Estimated cost: $" . number_format($cost, 2));
                
                error_log("=== WHISPER TRANSCRIPTION COMPLETE ===");
            }

            return [
                'text' => $data['text'] ?? '',
                'segments' => $data['segments'] ?? [],
                'duration' => $data['duration'] ?? 0,
            ];

        } catch (GuzzleException $e) {
            if ($debug) {
                error_log("Whisper API error: " . $e->getMessage());
                if ($e->hasResponse()) {
                    error_log("Response body: " . $e->getResponse()->getBody()->getContents());
                }
            }
            throw new \Exception('Whisper API error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate estimated cost for transcription
     * 
     * @param int $durationSeconds
     * @return float Cost in USD
     */
    public function estimateCost(int $durationSeconds): float
    {
        $minutes = ceil($durationSeconds / 60);
        return $minutes * 0.006; // $0.006 per minute
    }
}
