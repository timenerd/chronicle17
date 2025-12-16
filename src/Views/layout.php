<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'TTRPG Session Recap' ?></title>
    <meta name="description" content="AI-powered narrative recaps for your tabletop RPG sessions">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="/" class="logo">⚔️ Chronicle</a>
            <nav>
                <a href="/">Dashboard</a>
                <a href="/campaigns">Campaigns</a>
            </nav>
        </div>
    </header>

    <main>
        <?php if (isset($content)) echo $content; ?>
    </main>

    <script src="/assets/js/app.js"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
