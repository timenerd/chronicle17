<?php
namespace App\Models;

use App\Services\Database;
use PDO;

class Session
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO sessions (campaign_id, title, session_number, session_date, audio_file_path, audio_duration_seconds, status)
            VALUES (:campaign_id, :title, :session_number, :session_date, :audio_file_path, :audio_duration_seconds, :status)
        ');
        
        $stmt->execute([
            'campaign_id' => $data['campaign_id'],
            'title' => $data['title'],
            'session_number' => $data['session_number'] ?? null,
            'session_date' => $data['session_date'] ?? date('Y-m-d'),
            'audio_file_path' => $data['audio_file_path'],
            'audio_duration_seconds' => $data['audio_duration_seconds'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $stmt = $this->db->prepare('
            UPDATE sessions 
            SET status = :status, error_message = :error_message
            WHERE id = :id
        ');
        
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    public function getTranscript(int $sessionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM transcripts WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        
        if ($result && isset($result['segments'])) {
            $result['segments'] = json_decode($result['segments'], true);
        }
        
        return $result ?: null;
    }

    public function saveTranscript(int $sessionId, array $data): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO transcripts (session_id, raw_text, segments, word_count)
            VALUES (:session_id, :raw_text, :segments, :word_count)
            ON DUPLICATE KEY UPDATE 
                raw_text = VALUES(raw_text),
                segments = VALUES(segments),
                word_count = VALUES(word_count)
        ');
        
        return $stmt->execute([
            'session_id' => $sessionId,
            'raw_text' => $data['text'],
            'segments' => json_encode($data['segments']),
            'word_count' => str_word_count($data['text']),
        ]);
    }

    public function getRecap(int $sessionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM recaps WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        
        if ($result) {
            if (isset($result['memorable_quotes'])) {
                $result['memorable_quotes'] = json_decode($result['memorable_quotes'], true);
            }
            if (isset($result['plot_hooks'])) {
                $result['plot_hooks'] = json_decode($result['plot_hooks'], true);
            }
        }
        
        return $result ?: null;
    }

    public function saveRecap(int $sessionId, array $data): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO recaps (session_id, narrative_recap, brief_summary, memorable_quotes, plot_hooks)
            VALUES (:session_id, :narrative_recap, :brief_summary, :memorable_quotes, :plot_hooks)
            ON DUPLICATE KEY UPDATE 
                narrative_recap = VALUES(narrative_recap),
                brief_summary = VALUES(brief_summary),
                memorable_quotes = VALUES(memorable_quotes),
                plot_hooks = VALUES(plot_hooks)
        ');
        
        return $stmt->execute([
            'session_id' => $sessionId,
            'narrative_recap' => $data['narrative_recap'] ?? '',
            'brief_summary' => $data['brief_summary'] ?? '',
            'memorable_quotes' => json_encode($data['memorable_quotes'] ?? []),
            'plot_hooks' => json_encode($data['plot_hooks'] ?? []),
        ]);
    }

    public function getEntities(int $sessionId): array
    {
        $stmt = $this->db->prepare('
            SELECT e.*, se.context
            FROM entities e
            JOIN session_entities se ON e.id = se.entity_id
            WHERE se.session_id = ?
            ORDER BY e.entity_type, e.name
        ');
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}
