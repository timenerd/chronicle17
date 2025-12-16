<?php
$pageTitle = 'Dashboard - TTRPG Recap';
ob_start();
?>

<div class="container">
    <div class="text-center mb-4">
        <h1 style="font-family: 'Cinzel', serif; font-size: 3rem; margin-bottom: 1rem;">
            Welcome to Chronicle
        </h1>
        <p class="text-muted" style="font-size: 1.25rem;">
            Transform your TTRPG sessions into epic narratives with AI-powered recaps
        </p>
    </div>

    <div class="grid grid-3 mt-4">
        <div class="card text-center fade-in">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎲</div>
            <h3 style="margin-bottom: 0.5rem;">Upload Sessions</h3>
            <p class="text-muted">Upload your session recordings and let AI do the magic</p>
        </div>

        <div class="card text-center fade-in" style="animation-delay: 0.1s;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✨</div>
            <h3 style="margin-bottom: 0.5rem;">Get Recaps</h3>
            <p class="text-muted">Receive narrative summaries perfect for your next session</p>
        </div>

        <div class="card text-center fade-in" style="animation-delay: 0.2s;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
            <h3 style="margin-bottom: 0.5rem;">Build Wiki</h3>
            <p class="text-muted">Automatically extract and track NPCs, locations, and more</p>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="<?= route('/campaigns') ?>" class="btn btn-primary" style="font-size: 1.25rem; padding: 1rem 2.5rem;">
            Get Started →
        </a>
    </div>

    <div class="mt-4">
        <div class="card">
            <h2 style="margin-bottom: 1rem;">How it Works</h2>
            <div class="grid grid-2">
                <div>
                    <h4 style="color: var(--primary-light); margin-bottom: 0.5rem;">1. Create a Campaign</h4>
                    <p class="text-muted">Set up your campaign with details about your world and characters</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-light); margin-bottom: 0.5rem;">2. Upload Audio</h4>
                    <p class="text-muted">Upload your session recordings (up to 500MB, 4 hours)</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-light); margin-bottom: 0.5rem;">3. AI Processing</h4>
                    <p class="text-muted">Whisper transcribes, Claude generates narrative recaps</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-light); margin-bottom: 0.5rem;">4. Share & Export</h4>
                    <p class="text-muted">Read recaps aloud or export to Markdown for your notes</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="card">
            <h3 style="margin-bottom: 1rem;">💰 Cost Estimate</h3>
            <p class="text-muted">Per 3-hour session:</p>
            <ul style="margin-left: 1.5rem; margin-top: 0.5rem; color: var(--text-secondary);">
                <li>Transcription (Whisper): ~$1.08</li>
                <li>Summarization (Claude): ~$0.15-0.30</li>
                <li><strong>Total: ~$1.25 per session</strong></li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
