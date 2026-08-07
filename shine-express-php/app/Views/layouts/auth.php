<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Auth') . ' · Shine Express') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <aside class="auth-hero" style="--auth-hero-image: url('<?= e(asset('images/login-hero.jpg')) ?>')">
            <div class="auth-hero-overlay">
                <p class="auth-hero-eyebrow">Shine Express</p>
                <h1>Professional cleaning for every space</h1>
                <p class="auth-hero-lede">Homes, kitchens, and bathrooms — sparkling clean with a premium service experience.</p>
            </div>
        </aside>
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
    </div>
</body>
</html>
