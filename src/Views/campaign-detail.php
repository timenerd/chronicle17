<?php
$pageTitle = htmlspecialchars($campaign['name']) . ' - TTRPG Recap';
ob_start();
?>

<div class="container">
    <div class="mb-4">
        <a href="<?= route('/campaigns') ?>" class="text-muted" style="text-decoration: none;">← Back to Campaigns</a>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 style="font-family: 'Cinzel', serif; font-size: 2.5rem;">
                <?= htmlspecialchars($campaign['name']) ?>
            </h1>
            <?php if ($campaign['game_system']): ?>
                <p class="text-muted"><?= htmlspecialchars($campaign['game_system']) ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= route('/campaigns/' . $campaign['id'] . '/upload') ?>" class="btn btn-primary">
            + Upload Session
        </a>
    </div>

    <?php if ($campaign['description']): ?>
        <div class="card mb-4">
            <h3 style="margin-bottom: 0.5rem;">Campaign Description</h3>
            <p class="text-muted"><?= nl2br(htmlspecialchars($campaign['description'])) ?></p>
        </div>
    <?php endif; ?>

    <!-- AI Workflow Debugging (Development Mode Only) -->
    <?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
        <?php
        // Fetch recent jobs for this campaign's sessions
        $db = \App\Services\Database::getInstance();
        
        // Fixed query - MySQL doesn't support LIMIT in IN subquery
        // Instead, we'll use UNION to combine campaign jobs and recent jobs
        $stmt = $db->prepare("
            (
                SELECT j.*, s.title as session_title, s.id as session_id
                FROM jobs j
                LEFT JOIN sessions s ON j.payload LIKE CONCAT('%\"session_id\":', s.id, '%')
                WHERE s.campaign_id = ?
                ORDER BY j.created_at DESC
                LIMIT 15
            )
            UNION
            (
                SELECT j.*, s.title as session_title, s.id as session_id
                FROM jobs j
                LEFT JOIN sessions s ON j.payload LIKE CONCAT('%\"session_id\":', s.id, '%')
                WHERE s.campaign_id IS NULL OR s.campaign_id != ?
                ORDER BY j.created_at DESC
                LIMIT 5
            )
            ORDER BY created_at DESC
            LIMIT 15
        ");
        $stmt->execute([$campaign['id'], $campaign['id']]);
        $recentJobs = $stmt->fetchAll();
        
        // Get processing stats
        $statsStmt = $db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM sessions
            WHERE campaign_id = ?
            GROUP BY status
        ");
        $statsStmt->execute([$campaign['id']]);
        $stats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
        
        <div class="card mb-4" style="border: 2px solid #8B5CF6;">
            <div class="flex items-center justify-between mb-3">
                <h2 style="margin: 0;">🔬 AI Workflow Debugging</h2>
                <span style="font-size: 0.875rem; color: var(--text-secondary); background: rgba(139, 92, 246, 0.2); padding: 0.25rem 0.75rem; border-radius: 9999px;">
                    DEV MODE
                </span>
            </div>
            
            <!-- Processing Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <?php foreach (['complete' => '✅', 'processing' => '⏳', 'transcribing' => '🎙️', 'pending' => '🟡', 'failed' => '❌'] as $status => $icon): ?>
                    <?php if (isset($stats[$status])): ?>
                        <div style="background: rgba(30, 41, 59, 0.5); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $icon ?></div>
                            <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.25rem;">
                                <?= $stats[$status] ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: capitalize;">
                                <?= $status ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Recent Jobs Queue -->
            <h3 style="margin-bottom: 1rem;">📋 Recent Jobs</h3>
            
            <?php if (empty($recentJobs)): ?>
                <p class="text-muted" style="text-align: center; padding: 2rem;">
                    No jobs in queue yet. Upload a session to see AI processing in action!
                </p>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table style="width: 100%; font-size: 0.875rem;">
                        <thead style="position: sticky; top: 0; background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid var(--border);">
                            <tr>
                                <th style="padding: 0.75rem; text-align: left;">Job ID</th>
                                <th style="padding: 0.75rem; text-align: left;">Type</th>
                                <th style="padding: 0.75rem; text-align: left;">Session</th>
                                <th style="padding: 0.75rem; text-align: center;">Status</th>
                                <th style="padding: 0.75rem; text-align: center;">Attempts</th>
                                <th style="padding: 0.75rem; text-align: left;">Created</th>
                                <th style="padding: 0.75rem; text-align: left;">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentJobs as $job): ?>
                                <?php 
                                // Extract job class from payload
                                $payloadData = json_decode($job['payload'], true);
                                $jobClass = $payloadData['job'] ?? '';
                                $jobType = basename(str_replace('\\', '/', $jobClass));
                                ?>
                                <tr style="border-bottom: 1px solid rgba(51, 65, 85, 0.5);">
                                    <td style="padding: 0.75rem; font-family: monospace;">#<?= $job['id'] ?></td>
                                    <td style="padding: 0.75rem;">
                                        <?php if (strpos($jobClass, 'Transcribe') !== false): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                🎙️ <span>Transcribe</span>
                                            </span>
                                        <?php elseif (strpos($jobClass, 'Summarize') !== false): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                ✨ <span>Summarize</span>
                                            </span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($jobType) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <?php if ($job['session_title']): ?>
                                            <a href="<?= route('/sessions/' . $job['session_id']) ?>" style="color: var(--primary);">
                                                <?= htmlspecialchars(substr($job['session_title'], 0, 30)) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <span class="status-badge status-<?= $job['status'] ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center; font-family: monospace;">
                                        <?= $job['attempts'] ?>/3
                                    </td>
                                    <td style="padding: 0.75rem; font-size: 0.75rem; color: var(--text-secondary);">
                                        <?= date('H:i:s', strtotime($job['created_at'])) ?>
                                    </td>
                                    <td style="padding: 0.75rem; font-family: monospace; font-size: 0.75rem;">
                                        <?php if ($job['status'] === 'completed' && $job['completed_at']): ?>
                                            <?php 
                                            $start = new DateTime($job['created_at']);
                                            $end = new DateTime($job['completed_at']);
                                            $diff = $start->diff($end);
                                            echo $diff->format('%im %ss');
                                            ?>
                                        <?php elseif ($job['status'] === 'pending'): ?>
                                            <span class="text-muted">waiting...</span>
                                        <?php else: ?>
                                            <span style="color: #3B82F6;">running...</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Live Refresh -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: between; align-items: center;">
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        <span id="lastRefresh">Last updated: <?= date('H:i:s') ?></span>
                    </div>
                    <button onclick="location.reload()" class="btn btn-secondary" style="margin-left: auto;">
                        🔄 Refresh
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Debug Hints -->
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3B82F6; border-radius: 0.5rem;">
                <strong>💡 Debug Hints:</strong>
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                    <li>Watch worker terminal: <code>php worker.php</code></li>
                    <li>Check error log: <code>c:/laragon/bin/apache/logs/error.log</code></li>
                    <li>See detailed guide: <code>DEBUG_AI_WORKFLOW.md</code></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sessions -->
    <div class="mb-4">
        <h2 style="margin-bottom: 1rem;">Sessions (<?= count($sessions) ?>)</h2>
        
        <?php if (empty($sessions)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎙️</div>
                <h3 style="margin-bottom: 0.5rem;">No Sessions Yet</h3>
                <p class="text-muted mb-4">Upload your first session recording to get started!</p>
                <a href="<?= route('/campaigns/' . $campaign['id'] . '/upload') ?>" class="btn btn-primary">
                    Upload Session
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($sessions as $session): ?>
                    <div class="card">
                        <div class="flex items-center justify-between mb-2">
                            <h3><?= htmlspecialchars($session['title']) ?></h3>
                            <span class="status-badge status-<?= $session['status'] ?>">
                                <?= ucfirst($session['status']) ?>
                            </span>
                        </div>
                        
                        <div class="text-muted mb-2">
                            <?php if ($session['session_number']): ?>
                                Session #<?= $session['session_number'] ?> • 
                            <?php endif; ?>
                            <?= date('M j, Y', strtotime($session['session_date'])) ?>
                        </div>
                        
                        <?php if ($session['status'] === 'complete'): ?>
                            <a href="<?= route('/sessions/' . $session['id']) ?>" class="btn btn-primary mt-2">
                                View Recap
                            </a>
                        <?php elseif ($session['status'] === 'failed'): ?>
                            <p class="text-muted" style="color: var(--error);">
                                <?= htmlspecialchars($session['error_message']) ?>
                            </p>
                        <?php else: ?>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: 50%;"></div>
                            </div>
                            <p class="text-muted" style="font-size: 0.875rem;">Processing...</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Campaign Wiki -->
    <?php if (!empty($entitiesByType)): ?>
        <div class="mb-4">
            <h2 style="margin-bottom: 1rem;">📚 Campaign Wiki</h2>
            
            <?php foreach ($entitiesByType as $type => $entities): ?>
                <div class="card mb-2">
                    <h3 style="margin-bottom: 1rem; text-transform: capitalize;">
                        <?= $type === 'npc' ? 'NPCs' : ucfirst($type).'s' ?>
                        <span class="text-muted">(<?= count($entities) ?>)</span>
                    </h3>
                    
                    <div class="grid grid-3">
                        <?php foreach ($entities as $entity): ?>
                            <div style="padding: 1rem; background: rgba(30, 41, 59, 0.3); border-radius: 0.5rem;">
                                <div class="flex items-center justify-between mb-1">
                                    <strong><?= htmlspecialchars($entity['name']) ?></strong>
                                    <span class="entity-type entity-<?= $type ?>">
                                        <?= $type ?>
                                    </span>
                                </div>
                                <?php if ($entity['description']): ?>
                                    <p class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
                                        <?= htmlspecialchars(substr($entity['description'], 0, 100)) ?>
                                        <?= strlen($entity['description']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Characters -->
    <?php if (!empty($characters)): ?>
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Party Members</h3>
            <div class="grid grid-3">
                <?php foreach ($characters as $char): ?>
                    <div style="padding: 1rem; background: rgba(30, 41, 59, 0.3); border-radius: 0.5rem;">
                        <strong><?= htmlspecialchars($char['name']) ?></strong>
                        <p class="text-muted" style="font-size: 0.875rem;">
                            <?= htmlspecialchars($char['race'] ?? '') ?>
                            <?= htmlspecialchars($char['class'] ?? '') ?>
                        </p>
                        <?php if ($char['player_name']): ?>
                            <p class="text-muted" style="font-size: 0.75rem;">
                                Played by <?= htmlspecialchars($char['player_name']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
