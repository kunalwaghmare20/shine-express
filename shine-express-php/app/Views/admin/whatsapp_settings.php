<?php
/** @var array<string, string> $values */
/** @var array<string, string> $sources */
$v = $values;
$src = $sources;
$badge = static function (string $key) use ($src): string {
    $from = $src[$key] ?? 'env';
    return $from === 'database'
        ? '<span class="muted small">Saved in database</span>'
        : '<span class="muted small">From .env (save to store in database)</span>';
};
$tokenHint = \App\Services\WhatsAppConfig::maskSecret((string) ($v['WHATSAPP_ACCESS_TOKEN'] ?? ''));
$webhookTokenHint = \App\Services\WhatsAppConfig::maskSecret((string) ($v['WHATSAPP_WEBHOOK_TOKEN'] ?? ''));
$setup = is_array($setup ?? null) ? $setup : [];
$broadcastSetup = is_array($broadcastSetup ?? null) ? $broadcastSetup : [];
?>
<section class="page-head">
    <div>
        <h1>WhatsApp settings</h1>
        <p class="muted">
            Configure delivery here instead of editing <code>.env</code>. Saved values live in the database
            and apply to broadcasts, rebook reminders, and booking alerts.
        </p>
    </div>
    <div>
        <a class="btn btn-sm" href="<?= e(url('/admin/whatsapp-broadcast')) ?>">Broadcast</a>
        <a class="btn btn-sm" href="<?= e(url('/admin/reminders')) ?>">Rebook reminders</a>
    </div>
</section>

<?php if (empty($tableReady)): ?>
<div class="alert alert-error">
    The <code>app_settings</code> table is missing. Run migration
    <code>database/migrations/009_app_settings.sql</code> in phpMyAdmin, then save this form.
</div>
<?php endif; ?>

<?php if (empty($setup['ready'])): ?>
<div class="alert alert-error">
    <strong>Not ready to send yet.</strong>
    <?= e((string) ($setup['reason'] ?? '')) ?>
    <?php if (!empty($setup['hint'])): ?>
        <p class="small" style="margin-top:0.5rem;margin-bottom:0"><?= e((string) $setup['hint']) ?></p>
    <?php endif; ?>
</div>
<?php elseif (empty($broadcastSetup['ready'])): ?>
<div class="alert">
    <?= e((string) ($broadcastSetup['reason'] ?? 'Add a Marketing template name below to send promotional broadcasts.')) ?>
</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/whatsapp-settings')) ?>" class="stack-form">
    <?= csrf_field() ?>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Delivery</h2>
        <label class="form-switch">
            <input type="checkbox" name="WHATSAPP_ENABLED" value="1" <?= ($v['WHATSAPP_ENABLED'] ?? '') === 'true' ? 'checked' : '' ?>>
            <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
            <span class="form-switch-label">
                <strong>Enable WhatsApp sending</strong>
                <span class="muted small"><?= $badge('WHATSAPP_ENABLED') ?></span>
            </span>
        </label>
        <fieldset class="form-fieldset">
            <legend>Provider</legend>
            <p class="fieldset-help muted small">How messages leave the server. <?= $badge('WHATSAPP_PROVIDER') ?></p>
            <div class="choice-group">
                <label class="choice-option">
                    <input type="radio" name="WHATSAPP_PROVIDER" value="log" <?= ($v['WHATSAPP_PROVIDER'] ?? '') === 'log' ? 'checked' : '' ?>>
                    <span class="choice-option-body">
                        <span class="choice-option-title">Log only</span>
                        <span class="choice-option-desc">Write to storage/logs/whatsapp.log — no real WhatsApp delivery</span>
                    </span>
                </label>
                <label class="choice-option">
                    <input type="radio" name="WHATSAPP_PROVIDER" value="cloud" <?= ($v['WHATSAPP_PROVIDER'] ?? '') === 'cloud' ? 'checked' : '' ?>>
                    <span class="choice-option-body">
                        <span class="choice-option-title">Meta Cloud API</span>
                        <span class="choice-option-desc">Real delivery. Promotional broadcasts need an approved Marketing template.</span>
                    </span>
                </label>
                <label class="choice-option">
                    <input type="radio" name="WHATSAPP_PROVIDER" value="webhook" <?= ($v['WHATSAPP_PROVIDER'] ?? '') === 'webhook' ? 'checked' : '' ?>>
                    <span class="choice-option-body">
                        <span class="choice-option-title">Webhook</span>
                        <span class="choice-option-desc">POST JSON to Interakt / a custom gateway</span>
                    </span>
                </label>
            </div>
        </fieldset>
        <label>Business WhatsApp number
            <input name="SUPPORT_WHATSAPP" inputmode="numeric" value="<?= e($v['SUPPORT_WHATSAPP'] ?? '') ?>" placeholder="919673522737" required>
            <span class="form-hint">Digits with country code, no +. Shown in the app and in messages. <?= $badge('SUPPORT_WHATSAPP') ?></span>
        </label>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Meta Cloud API</h2>
        <p class="muted small">From Meta for Developers → WhatsApp → API Setup. Use a permanent System User token.</p>
        <label>Access token
            <input name="WHATSAPP_ACCESS_TOKEN" type="password" autocomplete="off" placeholder="<?= e($tokenHint !== '' ? $tokenHint . ' — leave blank to keep' : 'EAAxxxx…') ?>">
            <span class="form-hint"><?= $badge('WHATSAPP_ACCESS_TOKEN') ?></span>
        </label>
        <div class="grid-2">
            <label>Phone number ID
                <input name="WHATSAPP_PHONE_NUMBER_ID" value="<?= e($v['WHATSAPP_PHONE_NUMBER_ID'] ?? '') ?>" placeholder="123456789012345">
                <span class="form-hint"><?= $badge('WHATSAPP_PHONE_NUMBER_ID') ?></span>
            </label>
            <label>Template language
                <input name="WHATSAPP_TEMPLATE_LANG" value="<?= e($v['WHATSAPP_TEMPLATE_LANG'] ?? 'en') ?>" placeholder="en">
                <span class="form-hint">Usually <code>en</code> or <code>en_US</code>. <?= $badge('WHATSAPP_TEMPLATE_LANG') ?></span>
            </label>
        </div>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Promotional broadcast (Marketing template)</h2>
        <p class="muted small">
            Required to message all customers without a 24-hour reply.
            Create a Marketing template in Meta Business Manager, wait until <strong>Approved</strong>, then paste the name here.
            Example body: <code>Hello {{1}},</code> then <code>{{2}}</code>.
        </p>
        <div class="grid-2">
            <label>Template name
                <input name="WHATSAPP_BROADCAST_TEMPLATE_NAME" value="<?= e($v['WHATSAPP_BROADCAST_TEMPLATE_NAME'] ?? '') ?>" placeholder="customer_broadcast">
                <span class="form-hint"><?= $badge('WHATSAPP_BROADCAST_TEMPLATE_NAME') ?></span>
            </label>
            <label>Template language (optional)
                <input name="WHATSAPP_BROADCAST_TEMPLATE_LANG" value="<?= e($v['WHATSAPP_BROADCAST_TEMPLATE_LANG'] ?? '') ?>" placeholder="en">
                <span class="form-hint">Blank = use template language above. <?= $badge('WHATSAPP_BROADCAST_TEMPLATE_LANG') ?></span>
            </label>
        </div>
        <label>Body variables (comma-separated)
            <input name="WHATSAPP_BROADCAST_TEMPLATE_PARAMS" value="<?= e($v['WHATSAPP_BROADCAST_TEMPLATE_PARAMS'] ?? 'first_name,message') ?>">
            <span class="form-hint">
                Order must match {{1}}, {{2}} in Meta. Allowed: <code>first_name</code>, <code>name</code>, <code>message</code>, <code>phone</code>, <code>admin_whatsapp</code>.
                <?= $badge('WHATSAPP_BROADCAST_TEMPLATE_PARAMS') ?>
            </span>
        </label>
        <label>Default broadcast message
            <textarea name="WHATSAPP_BROADCAST_DEFAULT" rows="5" maxlength="4096"><?= e($v['WHATSAPP_BROADCAST_DEFAULT'] ?? '') ?></textarea>
            <span class="form-hint">Pre-fills Admin → WhatsApp broadcast. <?= $badge('WHATSAPP_BROADCAST_DEFAULT') ?></span>
        </label>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Rebook reminders</h2>
        <label>Reminder template name (optional)
            <input name="WHATSAPP_TEMPLATE_NAME" value="<?= e($v['WHATSAPP_TEMPLATE_NAME'] ?? '') ?>" placeholder="booking_update">
            <span class="form-hint">
                Meta template for rebook reminders only. Leave blank to send the message below as text (24-hour window).
                <?= $badge('WHATSAPP_TEMPLATE_NAME') ?>
            </span>
        </label>
        <label>Rebook message
            <textarea name="WHATSAPP_REBOOK_MESSAGE" rows="8"><?= e($v['WHATSAPP_REBOOK_MESSAGE'] ?? '') ?></textarea>
            <span class="form-hint">
                Placeholders: <code>{name}</code> <code>{service}</code> <code>{booking}</code> <code>{days}</code> <code>{admin_whatsapp}</code> <code>{wa_link}</code>.
                <?= $badge('WHATSAPP_REBOOK_MESSAGE') ?>
            </span>
        </label>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Webhook (optional)</h2>
        <p class="muted small">Only used when provider is Webhook.</p>
        <label>Webhook URL
            <input name="WHATSAPP_WEBHOOK_URL" value="<?= e($v['WHATSAPP_WEBHOOK_URL'] ?? '') ?>" placeholder="https://…">
            <span class="form-hint"><?= $badge('WHATSAPP_WEBHOOK_URL') ?></span>
        </label>
        <label>Webhook token
            <input name="WHATSAPP_WEBHOOK_TOKEN" type="password" autocomplete="off" placeholder="<?= e($webhookTokenHint !== '' ? $webhookTokenHint . ' — leave blank to keep' : 'Optional Bearer token') ?>">
            <span class="form-hint"><?= $badge('WHATSAPP_WEBHOOK_TOKEN') ?></span>
        </label>
    </section>

    <div class="form-actions">
        <button class="btn" type="submit">Save WhatsApp settings</button>
    </div>
</form>
