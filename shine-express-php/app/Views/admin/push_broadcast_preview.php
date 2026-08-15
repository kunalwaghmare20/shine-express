<section class="page-head">
    <div>
        <h1>Preview push broadcast</h1>
        <p class="muted">Review recipients and sample notifications before sending.</p>
    </div>
    <a class="btn btn-sm" href="<?= e(url('/admin/push-broadcast')) ?>">← Edit notification</a>
</section>

<div class="panel" style="margin-bottom:1rem">
    <h2>Summary</h2>
    <ul class="plain-list">
        <li><span>Audience</span><strong><?= ($draft['audience'] ?? '') === 'all' ? 'All customers' : 'Selected customers' ?></strong></li>
        <li><span>Total matched</span><strong><?= (int) ($preview['recipient_count'] ?? 0) ?></strong></li>
        <li><span>Will send</span><strong><?= (int) ($preview['sendable_count'] ?? 0) ?></strong></li>
        <li><span>Skipped (no app / inactive)</span><strong><?= (int) ($preview['skipped_count'] ?? 0) ?></strong></li>
        <li><span>FCM push</span><strong><?= $fcmEnabled ? 'Enabled' : 'Disabled (in-app only)' ?></strong></li>
    </ul>
</div>

<div class="panel" style="margin-bottom:1rem">
    <h2>Notification template</h2>
    <p class="small"><strong>Title:</strong> <?= e((string) ($draft['title'] ?? '')) ?></p>
    <pre class="broadcast-preview-message"><?= e((string) ($draft['message'] ?? '')) ?></pre>
</div>

<?php if (($preview['samples'] ?? []) !== []): ?>
<section class="panel" style="margin-bottom:1rem">
    <h2>Sample personalized notifications</h2>
    <p class="muted small">Showing up to 5 examples — each customer receives their own personalized title and message.</p>
    <?php foreach ($preview['samples'] as $sample): ?>
        <div class="broadcast-sample">
            <p class="small"><strong><?= e($sample['customer'] ?? '') ?></strong> · <?= (int) ($sample['devices'] ?? 0) ?> device(s)</p>
            <p class="small"><strong>Title:</strong> <?= e($sample['title'] ?? '') ?></p>
            <pre class="broadcast-preview-message"><?= e($sample['body'] ?? '') ?></pre>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ((int) ($preview['sendable_count'] ?? 0) === 0): ?>
<div class="alert alert-error">
    No customers can receive this broadcast. Customers must log in to the mobile app at least once to register a device.
</div>
<?php else: ?>
<div class="panel">
    <form method="post" action="<?= e(url('/admin/push-broadcast/send')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <label class="choice-option choice-option--confirm">
            <input type="checkbox" name="confirm" value="1" required>
            <span class="choice-option-icon"><?= ui_icon('check-circle') ?></span>
            <span class="choice-option-body">
                <span class="choice-option-title">Confirm broadcast</span>
                <span class="choice-option-desc">Send to <strong><?= (int) $preview['sendable_count'] ?></strong> customer(s) now<?= $fcmEnabled ? ' via push + in-app' : ' (in-app only)' ?></span>
            </span>
        </label>
        <div class="form-actions">
            <button class="btn" type="submit"><?= ui_icon('send') ?> Confirm &amp; send broadcast</button>
        </div>
    </form>
</div>
<?php endif; ?>
