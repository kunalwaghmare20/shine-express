<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 · Shine Express</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<main class="container main">
    <section class="hero">
        <h1>403</h1>
        <p class="lede"><?= e($title ?? 'Forbidden') ?></p>
        <a class="btn" href="<?= e(url('/')) ?>">Home</a>
    </section>
</main>
</body>
</html>
