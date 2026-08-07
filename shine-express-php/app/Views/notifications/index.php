<div class="toolbar">
    <form method="post" action="<?= e(url('/notifications/read-all')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm" type="submit">Mark all read</button>
    </form>
</div>
<div class="stack">
<?php foreach ($items as $n): ?>
    <article class="panel <?= $n['is_read'] ? '' : 'panel-unread' ?>">
        <div class="toolbar">
            <strong><?= e($n['title']) ?></strong>
            <span class="muted"><?= e($n['created_at']) ?></span>
        </div>
        <p><?= e($n['body']) ?></p>
        <?php if (!$n['is_read']): ?>
            <form method="post" action="<?= e(url('/notifications/' . $n['id'] . '/read')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-ghost" type="submit">Mark read</button>
            </form>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?php if ($items === []): ?>
    <p class="muted">No notifications</p>
<?php endif; ?>
</div>
