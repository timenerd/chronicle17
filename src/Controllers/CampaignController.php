<?php
namespace App\Controllers;

use App\Models\Campaign;

class CampaignController
{
    private Campaign $campaignModel;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
    }

    /**
     * List all campaigns for user
     */
    public function index(): void
    {
        // For MVP, using default user ID 1
        $userId = 1;
        $campaigns = $this->campaignModel->getAll($userId);

        require __DIR__ . '/../Views/campaigns.php';
    }

    /**
     * View single campaign with wiki
     */
    public function view(int $campaignId): void
    {
        $campaign = $this->campaignModel->getById($campaignId);
        
        if (!$campaign) {
            http_response_code(404);
            echo "Campaign not found";
            return;
        }

        $sessions = $this->campaignModel->getSessions($campaignId);
        $characters = $this->campaignModel->getCharacters($campaignId);
        $entities = $this->campaignModel->getEntities($campaignId);

        // Group entities by type
        $entitiesByType = [];
        foreach ($entities as $entity) {
            $entitiesByType[$entity['entity_type']][] = $entity;
        }

        require __DIR__ . '/../Views/campaign-detail.php';
    }

    /**
     * Create new campaign
     */
    public function create(): void
    {
        header('Content-Type: application/json');

        try {
            $data = $_POST;
            $data['user_id'] = 1; // MVP: default user

            $campaignId = $this->campaignModel->create($data);

            echo json_encode([
                'success' => true,
                'campaign_id' => $campaignId,
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
     * Update campaign
     */
    public function update(int $campaignId): void
    {
        header('Content-Type: application/json');

        try {
            $this->campaignModel->update($campaignId, $_POST);

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show upload form for campaign
     */
    public function upload(int $campaignId): void
    {
        $campaign = $this->campaignModel->getById($campaignId);
        
        if (!$campaign) {
            http_response_code(404);
            echo "Campaign not found";
            return;
        }

        $sessions = $this->campaignModel->getSessions($campaignId);
        $nextSessionNumber = count($sessions) + 1;

        require __DIR__ . '/../Views/upload.php';
    }
}
