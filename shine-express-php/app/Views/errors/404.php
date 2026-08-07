<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? '404') ?> · Shine Express</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
    <main class="container main">
        <section class="hero">
            <h1>404</h1>
            <p class="lede"><?= e($title ?? 'Page not found') ?></p>
            <a class="btn" href="<?= e(url('/')) ?>">Back home</a>
        </section>
    </main>
</body>
</html>
