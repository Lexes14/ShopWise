<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrf ?? '') ?>">
    <title><?= e(APP_NAME) ?> - Login</title>
    
    <!-- Google Fonts: Syne, DM Sans, JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?= e(ASSET_URL) ?>/css/app.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'DM Sans', 'Segoe UI', sans-serif;
            background: var(--sw-bg);
            color: var(--sw-text);
        }
    </style>
</head>
<body>
    <?= $content ?>
    
    <script src="<?= e(ASSET_URL) ?>/js/app.js"></script>
</body>
</html>
