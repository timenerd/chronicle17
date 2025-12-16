<?php
$pageTitle = 'Campaigns - TTRPG Recap';
ob_start();
?>

<div class="container">
    <div class="flex items-center justify-between mb-4">
        <h1 style="font-family: 'Cinzel', serif; font-size: 2.5rem;">Your Campaigns</h1>
        <button onclick="showCreateCampaignModal()" class="btn btn-primary">
            + New Campaign
        </button>
    </div>

    <?php if (empty($campaigns)): ?>
        <div class="card text-center" style="padding: 4rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🎭</div>
            <h2 style="margin-bottom: 1rem;">No Campaigns Yet</h2>
            <p class="text-muted mb-4">Create your first campaign to start recording epic adventures!</p>
            <button onclick="showCreateCampaignModal()" class="btn btn-primary">
                Create Campaign
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-2">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="card fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <h2><?= htmlspecialchars($campaign['name']) ?></h2>
                        <?php if ($campaign['game_system']): ?>
                            <span class="status-badge status-complete">
                                <?= htmlspecialchars($campaign['game_system']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($campaign['description']): ?>
                        <p class="text-muted mb-2">
                            <?= htmlspecialchars(substr($campaign['description'], 0, 150)) ?>
                            <?= strlen($campaign['description']) > 150 ? '...' : '' ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-2 mt-4">
                        <span class="text-muted">
                            📝 <?= $campaign['session_count'] ?? 0 ?> sessions
                        </span>
                    </div>
                    
                    <div class="flex gap-2 mt-4">
                        <a href="<?= route('/campaigns/' . $campaign['id']) ?>" class="btn btn-primary">
                            View Campaign
                        </a>
                        <a href="<?= route('/campaigns/' . $campaign['id'] . '/upload') ?>" class="btn btn-secondary">
                            Upload Session
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Campaign Modal -->
<div id="createCampaignModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom: 1.5rem;">Create New Campaign</h2>
        
        <form id="createCampaignForm" onsubmit="createCampaign(event)">
            <div class="form-group">
                <label class="form-label">Campaign Name *</label>
                <input type="text" name="name" class="form-input" required placeholder="e.g., The Lost Mines">
            </div>
            
            <div class="form-group">
                <label class="form-label">Game System</label>
                <input type="text" name="game_system" class="form-input" placeholder="e.g., D&D 5e, Pathfinder 2e">
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" placeholder="Brief description of your campaign..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Setting Context</label>
                <textarea name="setting_context" class="form-textarea" placeholder="Describe your campaign setting. This helps the AI generate better recaps. Include genre, tone, recurring themes, etc."></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Create Campaign</button>
                <button type="button" onclick="hideCreateCampaignModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateCampaignModal() {
    document.getElementById('createCampaignModal').style.display = 'flex';
}

function hideCreateCampaignModal() {
    document.getElementById('createCampaignModal').style.display = 'none';
    document.getElementById('createCampaignForm').reset();
}

async function createCampaign(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/campaigns', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = '/campaigns/' + data.campaign_id;
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error creating campaign: ' + error.message);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
