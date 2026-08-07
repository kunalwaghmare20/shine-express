<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Auth') . ' · Shine Express') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
    <main class="auth-card">
        <a class="brand" href="<?= e(url('/')) ?>">Shine Express</a>
        <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= e((string) $flash) ?></div>
        <?php endif; ?>
        <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= e((string) $flash) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</body>
</html>
