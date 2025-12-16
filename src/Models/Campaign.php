<?php
namespace App\Models;

use App\Services\Database;
use PDO;

class Campaign
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, COUNT(s.id) as session_count
            FROM campaigns c
            LEFT JOIN sessions s ON c.id = s.campaign_id
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM campaigns WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO campaigns (user_id, name, description, game_system, setting_context)
            VALUES (:user_id, :name, :description, :game_system, :setting_context)
        ');
        
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'game_system' => $data['game_system'] ?? null,
            'setting_context' => $data['setting_context'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE campaigns 
            SET name = :name, 
                description = :description, 
                game_system = :game_system, 
                setting_context = :setting_context
            WHERE id = :id
        ');
        
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'game_system' => $data['game_system'] ?? null,
            'setting_context' => $data['setting_context'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM campaigns WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getCharacters(int $campaignId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM campaign_characters 
            WHERE campaign_id = ? 
            ORDER BY name
        ');
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function getSessions(int $campaignId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM sessions 
            WHERE campaign_id = ? 
            ORDER BY session_number DESC, session_date DESC
        ');
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function getEntities(int $campaignId, ?string $type = null): array
    {
        $sql = 'SELECT * FROM entities WHERE campaign_id = ?';
        $params = [$campaignId];

        if ($type) {
            $sql .= ' AND entity_type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
