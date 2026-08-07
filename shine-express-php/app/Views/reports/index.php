<?php require APP_PATH . '/Views/dashboard/admin.php'; ?>
<section class="panel" style="margin-top:1rem">
    <h3>Monthly cash revenue</h3>
    <ul class="plain-list">
        <?php foreach ($monthly as $row): ?>
            <li><span><?= e($row['ym']) ?></span><strong><?= e(money_format_inr($row['revenue'])) ?></strong></li>
        <?php endforeach; ?>
        <?php if ($monthly === []): ?><li class="muted">No payments yet</li><?php endif; ?>
    </ul>
</section>
