<?php
use App\Core\Auth;
use App\Core\Database;

$nav = require APP_PATH . '/Config/navigation.php';
$role = Auth::role() ?? 'CUSTOMER';
$items = $nav[$role] ?? [];
$path = \App\Core\Request::path();
$user = $user ?? Auth::user();

$unread = 0;
if (Auth::id()) {
    $st = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $st->execute([Auth::id()]);
    $unread = (int) $st->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Dashboard') . ' · Shine Express') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="dashboard-body">
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <strong>Shine Express</strong>
            <span><?= e(str_replace('_', ' ', strtolower((string) $role))) ?></span>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($items as $item): ?>
                <?php
                $href = $item['href'];
                $active = $path === $href || ($href !== '/' && str_starts_with($path, $href));
                ?>
                <a class="<?= $active ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(url('/logout')) ?>" class="sidebar-logout">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost">Sign out</button>
        </form>
    </aside>

    <div class="shell-main">
        <header class="topbar">
            <h1><?= e($title ?? 'Dashboard') ?></h1>
            <div class="topbar-actions">
                <a class="bell" href="<?= e(url('/notifications')) ?>">
                    Notifications<?php if ($unread > 0): ?><span class="badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
                </a>
                <span class="user-chip"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></span>
            </div>
        </header>

        <main class="content">
            <?php if ($flash = \App\Core\Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= e((string) $flash) ?></div>
            <?php endif; ?>
            <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= e((string) $flash) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
