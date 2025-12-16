<?php
$pageTitle = htmlspecialchars($session['title']) . ' - TTRPG Recap';
ob_start();
?>

<div class="container">
    <div class="mb-4">
        <a href="<?= route('/campaigns/' . $session['campaign_id']) ?>" class="text-muted" style="text-decoration: none;">
            ← Back to Campaign
        </a>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 style="font-family: 'Cinzel', serif; font-size: 2.5rem;">
                <?= htmlspecialchars($session['title']) ?>
            </h1>
            <p class="text-muted">
                <?= date('F j, Y', strtotime($session['session_date'])) ?>
                <?php if ($session['session_number']): ?>
                    • Session #<?= $session['session_number'] ?>
                <?php endif; ?>
            </p>
        </div>
        <span class="status-badge status-<?= $session['status'] ?>">
            <?= ucfirst($session['status']) ?>
        </span>
    </div>

    <?php if ($session['status'] === 'complete' && $recap): ?>
        
        <!-- Brief Summary -->
        <div class="card mb-4">
            <h2 style="margin-bottom: 1rem;">📜 Brief Summary</h2>
            <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary);">
                <?= nl2br(htmlspecialchars($recap['brief_summary'])) ?>
            </p>
        </div>

        <!-- Full Narrative Recap -->
        <div class="card mb-4">
            <div class="flex items-center justify-between mb-2">
                <h2>✨ Full Narrative Recap</h2>
                <div class="flex gap-2">
                    <button onclick="copyToClipboard()" class="btn btn-secondary">
                        📋 Copy
                    </button>
                    <a href="<?= route('/sessions/' . $session['id'] . '/export') ?>" class="btn btn-secondary">
                        ⬇️ Export MD
                    </a>
                </div>
            </div>
            
            <div id="narrativeRecap" style="font-size: 1.05rem; line-height: 1.8; color: var(--text-secondary); margin-top: 1.5rem;">
                <?= nl2br(htmlspecialchars($recap['narrative_recap'])) ?>
            </div>
        </div>

        <!-- Memorable Quotes -->
        <?php if (!empty($recap['memorable_quotes'])): ?>
            <div class="card mb-4">
                <h2 style="margin-bottom: 1rem;">💬 Memorable Quotes</h2>
                <div class="grid grid-2">
                    <?php foreach ($recap['memorable_quotes'] as $quote): ?>
                        <div style="padding: 1.5rem; background: rgba(30, 41, 59, 0.5); border-left: 4px solid var(--primary); border-radius: 0.5rem;">
                            <p style="font-size: 1.125rem; font-style: italic; margin-bottom: 0.5rem;">
                                "<?= htmlspecialchars($quote['quote']) ?>"
                            </p>
                            <p class="text-muted" style="font-size: 0.875rem;">
                                — <?= htmlspecialchars($quote['speaker']) ?>
                            </p>
                            <?php if (!empty($quote['context'])): ?>
                                <p class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
                                    <?= htmlspecialchars($quote['context']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Plot Hooks -->
        <?php if (!empty($recap['plot_hooks'])): ?>
            <div class="card mb-4">
                <h2 style="margin-bottom: 1rem;">🎣 Plot Hooks & Unresolved Threads</h2>
                <div class="grid grid-2">
                    <?php foreach ($recap['plot_hooks'] as $hook): ?>
                        <div style="padding: 1rem; background: rgba(30, 41, 59, 0.3); border-radius: 0.5rem;">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="entity-type entity-<?= ($hook['importance'] ?? 'minor') === 'major' ? 'npc' : 'event' ?>">
                                    <?= strtoupper($hook['importance'] ?? 'minor') ?>
                                </span>
                            </div>
                            <p class="text-muted"><?= htmlspecialchars($hook['hook']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Entities -->
        <?php if (!empty($entities)): ?>
            <div class="card mb-4">
                <h2 style="margin-bottom: 1rem;">📚 Entities Mentioned</h2>
                <div class="grid grid-3">
                    <?php 
                    $groupedEntities = [];
                    foreach ($entities as $entity) {
                        $groupedEntities[$entity['entity_type']][] = $entity;
                    }
                    ?>
                    
                    <?php foreach ($groupedEntities as $type => $entitiesOfType): ?>
                        <?php foreach ($entitiesOfType as $entity): ?>
                            <div style="padding: 1rem; background: rgba(30, 41, 59, 0.3); border-radius: 0.5rem;">
                                <div class="flex items-center justify-between mb-1">
                                    <strong><?= htmlspecialchars($entity['name']) ?></strong>
                                    <span class="entity-type entity-<?= $type ?>">
                                        <?= $type ?>
                                    </span>
                                </div>
                                <?php if (!empty($entity['context'])): ?>
                                    <p class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
                                        <?= htmlspecialchars($entity['context']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Full Transcript (Collapsible) -->
        <?php if ($transcript): ?>
            <div class="card">
                <button onclick="toggleTranscript()" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                    <span id="transcriptToggleText">📄 Show Full Transcript</span>
                </button>
                
                <div id="fullTranscript" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1rem;">Full Transcript</h3>
                    <div style="max-height: 600px; overflow-y: auto; padding: 1rem; background: rgba(30, 41, 59, 0.3); border-radius: 0.5rem;">
                        <p style="white-space: pre-wrap; color: var(--text-secondary); line-height: 1.8;">
                            <?= htmlspecialchars($transcript['raw_text']) ?>
                        </p>
                    </div>
                    <p class="text-muted mt-2">
                        Word count: <?= number_format($transcript['word_count']) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($session['status'] === 'failed'): ?>
        
        <div class="card" style="padding: 3rem; text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
            <h2 style="margin-bottom: 1rem; color: var(--error);">Processing Failed</h2>
            <p class="text-muted" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($session['error_message']) ?>
            </p>
            <a href="<?= route('/campaigns/' . $session['campaign_id'] . '/upload') ?>" class="btn btn-primary">
                Upload Again
            </a>
        </div>

    <?php else: ?>
        
        <!-- Processing in progress -->
        <div class="card" style="padding: 3rem; text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚙️</div>
            <h2 style="margin-bottom: 1rem;">Processing Your Session</h2>
            <p class="text-muted" style="margin-bottom: 2rem;">
                Current status: <strong><?= ucfirst($session['status']) ?></strong>
            </p>
            
            <div class="progress-container" style="max-width: 400px; margin: 0 auto 2rem;">
                <div class="progress-bar" style="width: <?= $session['status'] === 'transcribing' ? '33' : '66' ?>%;"></div>
            </div>
            
            <p class="text-muted">
                This page will automatically refresh when processing is complete.<br>
                Average processing time: 5-15 minutes
            </p>
        </div>

        <script>
            // Poll for status updates
            let pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(window.BASE_PATH + '/sessions/<?= $session['id'] ?>/status');
                    const data = await response.json();
                    
                    if (data.status === 'complete' || data.status === 'failed') {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Status check failed:', error);
                }
            }, 5000); // Check every 5 seconds
        </script>

    <?php endif; ?>
</div>

<?php if ($session['status'] === 'complete'): ?>
<script>
function toggleTranscript() {
    const transcript = document.getElementById('fullTranscript');
    const toggleText = document.getElementById('transcriptToggleText');
    
    if (transcript.style.display === 'none') {
        transcript.style.display = 'block';
        toggleText.textContent = '📄 Hide Full Transcript';
    } else {
        transcript.style.display = 'none';
        toggleText.textContent = '📄 Show Full Transcript';
    }
}

function copyToClipboard() {
    const recap = document.getElementById('narrativeRecap').innerText;
    navigator.clipboard.writeText(recap).then(() => {
        alert('Recap copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
