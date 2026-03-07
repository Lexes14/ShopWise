<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Error</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(ASSET_URL) ?>/css/app.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card p-4">
            <?= $content ?>
            <div class="mt-3 text-center">
                <a href="<?= e(BASE_URL) ?>/login" class="btn btn-sm btn-outline-primary">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
