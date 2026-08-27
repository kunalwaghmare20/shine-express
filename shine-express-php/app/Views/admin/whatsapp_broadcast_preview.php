<section class="page-head">
    <div>
        <h1>Preview broadcast</h1>
        <p class="muted">Review recipients and sample messages before sending.</p>
    </div>
    <a class="btn btn-sm" href="<?= e(url('/admin/whatsapp-broadcast')) ?>">← Edit message</a>
</section>

<div class="panel" style="margin-bottom:1rem">
    <h2>Summary</h2>
    <ul class="plain-list">
        <li><span>Audience</span><strong><?= ($draft['audience'] ?? '') === 'all' ? 'All customers' : 'Selected customers' ?></strong></li>
        <li><span>Total matched</span><strong><?= (int) ($preview['recipient_count'] ?? 0) ?></strong></li>
        <li><span>Will send</span><strong><?= (int) ($preview['sendable_count'] ?? 0) ?></strong></li>
        <li><span>Skipped (no phone / inactive)</span><strong><?= (int) ($preview['skipped_count'] ?? 0) ?></strong></li>
    </ul>
</div>

<div class="panel" style="margin-bottom:1rem">
    <h2>Message template</h2>
    <pre class="broadcast-preview-message"><?= e((string) ($draft['message'] ?? '')) ?></pre>
</div>

<?php if (($preview['samples'] ?? []) !== []): ?>
<section class="panel" style="margin-bottom:1rem">
    <h2>Sample personalized messages</h2>
    <p class="muted small">Showing up to 5 examples — each customer receives their own personalized text.</p>
    <?php foreach ($preview['samples'] as $sample): ?>
        <div class="broadcast-sample">
            <p class="small"><strong><?= e($sample['customer'] ?? '') ?></strong> · <?= e($sample['phone'] ?? '') ?></p>
            <pre class="broadcast-preview-message"><?= e($sample['message'] ?? '') ?></pre>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php
$setup = is_array($setup ?? null) ? $setup : [];
$setupReady = !empty($setup['ready']);
$setupReason = (string) ($setup['reason'] ?? '');
$setupHint = (string) ($setup['hint'] ?? '');
?>
<?php if (!$setupReady): ?>
<div class="alert alert-error">
    <strong>Cannot send yet.</strong>
    <?= e($setupReason !== '' ? $setupReason : 'WhatsApp is not configured on this server.') ?>
    <?php if ($setupHint !== ''): ?>
        <p class="small" style="margin-top:0.5rem;margin-bottom:0"><?= e($setupHint) ?></p>
    <?php endif; ?>
</div>
<?php elseif ($setupHint !== ''): ?>
<div class="alert">
    <?= e($setupHint) ?>
</div>
<?php endif; ?>

<?php if ((int) ($preview['sendable_count'] ?? 0) === 0): ?>
<div class="alert alert-error">
    No customers can receive this broadcast. Add phone numbers or change your selection.
</div>
<?php elseif (!$setupReady): ?>
<div class="panel">
    <p class="muted">Fix the WhatsApp configuration above, then return here to send.</p>
    <a class="btn btn-sm" href="<?= e(url('/admin/whatsapp-broadcast')) ?>">← Back to broadcast</a>
</div>
<?php else: ?>
<div class="panel">
    <form method="post" action="<?= e(url('/admin/whatsapp-broadcast/send')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <label class="choice-option choice-option--confirm">
            <input type="checkbox" name="confirm" value="1" required>
            <span class="choice-option-icon"><?= ui_icon('check-circle') ?></span>
            <span class="choice-option-body">
                <span class="choice-option-title">Confirm broadcast</span>
                <span class="choice-option-desc">Send to <strong><?= (int) $preview['sendable_count'] ?></strong> customer(s) now via WhatsApp</span>
            </span>
        </label>
        <div class="form-actions">
            <button class="btn btn-whatsapp" type="submit"><?= ui_icon('send') ?> Confirm &amp; send broadcast</button>
        </div>
    </form>
</div>
<?php endif; ?>
