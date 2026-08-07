<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Home') . ' · ' . (config('app')['name'] ?? 'Shine Express')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(url('/')) ?>">Shine Express</a>
            <nav class="nav">
                <a href="<?= e(url('/health')) ?>">Health</a>
            </nav>
        </div>
    </header>

    <main class="container main">
        <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= e((string) $flash) ?></div>
        <?php endif; ?>
        <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= e((string) $flash) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Shine Express · PHP MVC (no Composer)</p>
        </div>
    </footer>
</body>
</html>
