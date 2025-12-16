<?php
$pageTitle = '404 Not Found';
ob_start();
?>

<div class="container" style="max-width: 600px; padding-top: 5rem;">
    <div class="card text-center" style="padding: 4rem;">
        <div style="font-size: 5rem; margin-bottom: 1rem;">🗺️</div>
        <h1 style="font-family: 'Cinzel', serif; font-size: 2.5rem; margin-bottom: 1rem;">
            Lost in the Wilderness
        </h1>
        <p class="text-muted" style="font-size: 1.25rem; margin-bottom: 2rem;">
            The page you're looking for doesn't exist in this realm.
        </p>
        <a href="/" class="btn btn-primary">
            Return to Safety
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
