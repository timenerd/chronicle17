<?php
namespace App\Models;

use App\Services\Database;
use PDO;

class Entity
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM entities WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByName(int $campaignId, string $name): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM entities 
            WHERE campaign_id = ? AND LOWER(name) = LOWER(?)
        ');
        $stmt->execute([$campaignId, $name]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO entities (campaign_id, entity_type, name, description, first_session_id, metadata)
            VALUES (:campaign_id, :entity_type, :name, :description, :first_session_id, :metadata)
        ');
        
        $stmt->execute([
            'campaign_id' => $data['campaign_id'],
            'entity_type' => $data['type'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'first_session_id' => $data['first_session_id'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE entities 
            SET description = :description, 
                metadata = :metadata
            WHERE id = :id
        ');
        
        return $stmt->execute([
            'id' => $id,
            'description' => $data['description'] ?? '',
            'metadata' => json_encode($data['metadata'] ?? []),
        ]);
    }

    public function linkToSession(int $sessionId, int $entityId, string $context = ''): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO session_entities (session_id, entity_id, context)
            VALUES (:session_id, :entity_id, :context)
        ');
        
        return $stmt->execute([
            'session_id' => $sessionId,
            'entity_id' => $entityId,
            'context' => $context,
        ]);
    }

    public function getSessions(int $entityId): array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, se.context
            FROM sessions s
            JOIN session_entities se ON s.id = se.session_id
            WHERE se.entity_id = ?
            ORDER BY s.session_date DESC, s.session_number DESC
        ');
        $stmt->execute([$entityId]);
        return $stmt->fetchAll();
    }

    /**
     * Create or update entity from Claude's extracted data
     */
    public function upsertFromExtraction(int $campaignId, int $sessionId, array $entityData): int
    {
        // Try to find existing entity
        $existing = $this->findByName($campaignId, $entityData['name']);

        if ($existing) {
            // Update description if new info is provided
            if (!empty($entityData['description'])) {
                $currentDesc = $existing['description'];
                $newDesc = $entityData['description'];
                
                // Append new information if different
                if (stripos($currentDesc, $newDesc) === false) {
                    $entityData['description'] = $currentDesc . "\n\n" . $newDesc;
                } else {
                    $entityData['description'] = $currentDesc;
                }
                
                $this->update($existing['id'], $entityData);
            }
            
            $entityId = $existing['id'];
        } else {
            // Create new entity
            $entityData['campaign_id'] = $campaignId;
            $entityData['first_session_id'] = $sessionId;
            $entityId = $this->create($entityData);
        }

        // Link to current session
        $this->linkToSession($sessionId, $entityId);

        return $entityId;
    }
}
