<section class="page-head">
    <div>
        <h1>Push broadcast</h1>
        <p class="muted">
            Send a custom push notification to customers who have the mobile app installed and logged in.
            FCM: <?= $fcmEnabled ? 'Enabled' : 'Disabled' ?>
            <?php if (!$fcmEnabled): ?>
                · In-app notifications still work; enable <code>FCM_ENABLED=true</code> for device push.
            <?php endif; ?>
        </p>
    </div>
</section>

<?php if (!$fcmEnabled): ?>
<div class="alert alert-error">
    <strong>Firebase push is disabled on this server.</strong>
    <?= e((string) ($fcmDisabledReason ?? 'Add Firebase credentials to enable device push.')) ?>
    Broadcasts still create in-app notifications only until this is fixed.
    <p class="small" style="margin-top:0.5rem;margin-bottom:0">
        Quick fix: upload your Firebase service account JSON to
        <code>shine-express-php/storage/fcm-service-account.json</code> on <strong>this server</strong>
        (local copy is not enough if admin runs on production).
    </p>
</div>
<?php endif; ?>

<div class="panel" style="margin-bottom:1rem">
    <h2>Push diagnostics</h2>
    <ul class="plain-list">
        <li><span>FCM on this server</span><strong><?= $fcmEnabled ? 'Enabled' : 'Disabled' ?></strong></li>
        <li><span>Registered device tokens</span><strong><?= (int) ($tokenStats['tokens'] ?? 0) ?> token(s), <?= (int) ($tokenStats['users'] ?? 0) ?> user(s)</strong></li>
        <li><span>FCM log</span><strong><code>storage/logs/fcm.log</code></strong> (created after first send attempt)</li>
    </ul>
    <?php if ((int) ($tokenStats['tokens'] ?? 0) === 0): ?>
        <p class="muted small">No device tokens in this database — customers must log in on the mobile app while it points to <strong>this same server/API</strong>.</p>
    <?php endif; ?>
</div>

<div class="grid-2 panels" style="margin-bottom:1rem">
    <section class="panel">
        <h2>Saved templates</h2>
        <?php if ($savedTemplates === []): ?>
            <p class="muted">No saved templates yet. Compose a notification below and click <strong>Save as template</strong>.</p>
            <p class="muted small">Requires migration <code>008_push_broadcast_templates.sql</code> on the server database.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Title</th><th>Updated</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($savedTemplates as $t): ?>
                        <tr>
                            <td><?= e($t['name']) ?></td>
                            <td class="muted small"><?= e(mb_substr((string) ($t['title'] ?? ''), 0, 40)) ?></td>
                            <td class="muted small"><?= e(substr((string) ($t['updatedAt'] ?? ''), 0, 16)) ?></td>
                            <td class="actions-cell">
                                <a href="<?= e(url('/admin/push-broadcast?load_template=' . urlencode((string) $t['id']))) ?>">Load</a>
                                <form method="post" action="<?= e(url('/admin/push-broadcast/templates/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this template?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="template_id" value="<?= e($t['id']) ?>">
                                    <button type="submit" class="link-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Save current notification</h2>
        <form method="post" action="<?= e(url('/admin/push-broadcast/templates')) ?>" class="stack-form" id="save-template-form">
            <?= csrf_field() ?>
            <label>Template name
                <input type="text" name="template_name" maxlength="120" placeholder="e.g. Festival offer" required>
            </label>
            <label>Title
                <input type="text" name="title" maxlength="200" required value="<?= e($templateTitle) ?>">
            </label>
            <label>Message
                <textarea name="message" rows="6" maxlength="1000" required><?= e($templateBody) ?></textarea>
            </label>
            <div class="form-actions">
                <button class="btn btn-sm" type="submit">Save as template</button>
            </div>
        </form>
    </section>
</div>

<div class="panel" style="margin-bottom:1rem">
    <form method="post" action="<?= e(url('/admin/push-broadcast/preview')) ?>" class="stack-form" id="broadcast-form">
        <?= csrf_field() ?>

        <label>Notification title
            <input type="text" name="title" id="broadcast-title" maxlength="200" required placeholder="e.g. Special offer this week" value="<?= e($templateTitle) ?>">
        </label>

        <label>Message
            <textarea name="message" id="broadcast-message" rows="8" required maxlength="1000" placeholder="Write your message…"><?= e($templateBody) ?></textarea>
        </label>

        <p class="muted small">
            Personalize with:
            <?php foreach ($placeholders as $i => $ph): ?>
                <?= $i > 0 ? ' · ' : '' ?><code><?= e($ph) ?></code>
            <?php endforeach; ?>
        </p>

        <fieldset class="form-fieldset">
            <legend>Recipients</legend>
            <p class="fieldset-help muted small">Only customers with a registered app device receive push. Others are skipped.</p>
            <div class="choice-group">
                <?php $withDevices = count(array_filter($customers, fn ($c) => (int) ($c['device_count'] ?? 0) > 0 && !empty($c['is_active']))); ?>
                <label class="choice-option">
                    <input type="radio" name="audience" value="all" id="audience-all">
                    <span class="choice-option-icon"><?= ui_icon('users') ?></span>
                    <span class="choice-option-body">
                        <span class="choice-option-title">All customers</span>
                        <span class="choice-option-desc">Everyone with the app installed (<?= $withDevices ?>)</span>
                    </span>
                </label>
                <label class="choice-option">
                    <input type="radio" name="audience" value="selected" id="audience-selected" checked>
                    <span class="choice-option-icon"><?= ui_icon('user-check') ?></span>
                    <span class="choice-option-body">
                        <span class="choice-option-title">Selected customers only</span>
                        <span class="choice-option-desc">Pick specific recipients from the list below</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div class="form-actions">
            <button class="btn" type="submit">Preview before send</button>
        </div>
    </form>
</div>

<section class="panel" id="customer-picker">
    <div class="toolbar">
        <h2 style="margin:0">Select customers</h2>
        <form method="get" action="<?= e(url('/admin/push-broadcast')) ?>" class="inline-form">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone">
            <button class="btn btn-sm" type="submit">Search</button>
        </form>
    </div>
    <p class="muted small">Only used when “Selected customers” is chosen. Customers without the app are shown but cannot be selected.</p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th class="col-check">
                    <label class="table-check" title="Select all on this page">
                        <input type="checkbox" id="select-all-customers" aria-label="Select all on this page">
                    </label>
                </th>
                <th>Name</th>
                <th>Devices</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <?php
                $devices = (int) ($c['device_count'] ?? 0);
                $canSelect = $devices > 0 && !empty($c['is_active']);
                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                ?>
                <tr>
                    <td class="col-check">
                        <label class="table-check">
                            <input type="checkbox" form="broadcast-form" name="customer_ids[]" value="<?= e($c['id']) ?>"
                                class="customer-cb" <?= !$canSelect ? 'disabled' : '' ?>
                                aria-label="Select <?= e($name) ?>">
                        </label>
                    </td>
                    <td><?= e($name) ?></td>
                    <td><?= $devices > 0 ? e((string) $devices) : '—' ?></td>
                    <td><?= e($c['email'] ?? '') ?></td>
                    <td><?= !empty($c['is_active']) ? 'Active' : 'Inactive' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($customers === []): ?>
                <tr><td colspan="5" class="muted">No customers found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (is_array($lastResult)): ?>
<section class="panel" style="margin-top:1rem">
    <h2>Last send results</h2>
    <p class="muted">
        Total <?= (int) ($lastResult['total'] ?? 0) ?> ·
        In-app <?= (int) ($lastResult['in_app'] ?? $lastResult['sent'] ?? 0) ?> ·
        Push <?= (int) ($lastResult['push'] ?? 0) ?> ·
        Failed <?= (int) ($lastResult['failed'] ?? 0) ?> ·
        Skipped <?= (int) ($lastResult['skipped'] ?? 0) ?>
    </p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>Devices</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
            <?php foreach (($lastResult['results'] ?? []) as $row): ?>
                <tr>
                    <td><?= e($row['customer'] ?? '') ?></td>
                    <td><?= e((string) ($row['devices'] ?? '')) ?></td>
                    <td><?= e($row['status'] ?? '') ?></td>
                    <td class="muted small"><?= e(mb_substr((string) ($row['detail'] ?? ''), 0, 120)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<script>
(function () {
  var allRadio = document.getElementById('audience-all');
  var selectedRadio = document.getElementById('audience-selected');
  var picker = document.getElementById('customer-picker');
  var selectAll = document.getElementById('select-all-customers');
  var boxes = document.querySelectorAll('.customer-cb:not([disabled])');
  var titleField = document.getElementById('broadcast-title');
  var messageField = document.getElementById('broadcast-message');
  var saveForm = document.getElementById('save-template-form');

  function syncFields(sourceTitle, sourceMessage) {
    document.querySelectorAll('input[name="title"]').forEach(function (el) {
      if (el !== sourceTitle) el.value = sourceTitle.value;
    });
    document.querySelectorAll('textarea[name="message"]').forEach(function (el) {
      if (el !== sourceMessage) el.value = sourceMessage.value;
    });
  }

  if (titleField) {
    titleField.addEventListener('input', function () { syncFields(titleField, messageField); });
  }
  if (messageField) {
    messageField.addEventListener('input', function () { syncFields(titleField, messageField); });
  }
  if (saveForm && titleField && messageField) {
    saveForm.addEventListener('submit', function () { syncFields(titleField, messageField); });
  }

  function syncPicker() {
    if (!picker) return;
    picker.style.opacity = allRadio && allRadio.checked ? '0.55' : '1';
    boxes.forEach(function (cb) {
      cb.disabled = !!(allRadio && allRadio.checked);
    });
  }

  if (allRadio) allRadio.addEventListener('change', syncPicker);
  if (selectedRadio) selectedRadio.addEventListener('change', syncPicker);
  syncPicker();

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      boxes.forEach(function (cb) { cb.checked = selectAll.checked; });
    });
  }
})();
</script>
