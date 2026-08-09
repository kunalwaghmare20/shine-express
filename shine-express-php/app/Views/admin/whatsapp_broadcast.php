<section class="page-head">
    <div>
        <h1>WhatsApp broadcast</h1>
        <p class="muted">
            Send a custom message to all customers or a selected list.
            Business WhatsApp: <strong><?= e($adminWhatsApp) ?></strong>
            · Provider: <code><?= e($provider) ?></code>
            · <?= $enabled ? 'Enabled' : 'Disabled' ?>
        </p>
    </div>
</section>

<?php if (!$enabled): ?>
<div class="alert alert-error">
    WhatsApp is disabled. Set <code>WHATSAPP_ENABLED=true</code> in <code>.env</code>.
    With <code>WHATSAPP_PROVIDER=log</code>, messages are written to <code>storage/logs/whatsapp.log</code> for testing.
</div>
<?php endif; ?>

<div class="grid-2 panels" style="margin-bottom:1rem">
    <section class="panel">
        <h2>Saved templates</h2>
        <?php if ($savedTemplates === []): ?>
            <p class="muted">No saved templates yet. Compose a message below and click <strong>Save as template</strong>.</p>
            <p class="muted small">Requires migration <code>007_whatsapp_broadcast_templates.sql</code> on the server database.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Updated</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($savedTemplates as $t): ?>
                        <tr>
                            <td><?= e($t['name']) ?></td>
                            <td class="muted small"><?= e(substr((string) ($t['updatedAt'] ?? ''), 0, 16)) ?></td>
                            <td class="actions-cell">
                                <a href="<?= e(url('/admin/whatsapp-broadcast?load_template=' . urlencode((string) $t['id']))) ?>">Load</a>
                                <form method="post" action="<?= e(url('/admin/whatsapp-broadcast/templates/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this template?')">
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
        <h2>Save current message</h2>
        <form method="post" action="<?= e(url('/admin/whatsapp-broadcast/templates')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label>Template name
                <input type="text" name="template_name" maxlength="120" placeholder="e.g. Festival offer" required>
            </label>
            <textarea name="message" rows="6" maxlength="4096" required><?= e($template) ?></textarea>
            <button class="btn btn-sm" type="submit">Save as template</button>
        </form>
    </section>
</div>

<div class="panel" style="margin-bottom:1rem">
    <form method="post" action="<?= e(url('/admin/whatsapp-broadcast/preview')) ?>" class="stack-form" id="broadcast-form">
        <?= csrf_field() ?>

        <label>Message
            <textarea name="message" id="broadcast-message" rows="10" required maxlength="4096" placeholder="Write your message…"><?= e($template) ?></textarea>
        </label>

        <p class="muted small">
            Personalize with:
            <?php foreach ($placeholders as $i => $ph): ?>
                <?= $i > 0 ? ' · ' : '' ?><code><?= e($ph) ?></code>
            <?php endforeach; ?>
        </p>

        <fieldset class="form-fieldset">
            <legend>Recipients</legend>
            <p class="fieldset-help muted small">Choose who receives this broadcast.</p>
            <div class="choice-group">
                <?php $withPhone = count(array_filter($customers, fn ($c) => trim((string) ($c['phone'] ?? '')) !== '')); ?>
                <label class="choice-option">
                    <input type="radio" name="audience" value="all" id="audience-all">
                    <span class="choice-option-icon"><?= ui_icon('users') ?></span>
                    <span class="choice-option-body">
                        <span class="choice-option-title">All customers</span>
                        <span class="choice-option-desc">Everyone with a phone number on file (<?= $withPhone ?>)</span>
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
        <form method="get" action="<?= e(url('/admin/whatsapp-broadcast')) ?>" class="inline-form">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone">
            <button class="btn btn-sm" type="submit">Search</button>
        </form>
    </div>
    <p class="muted small">Only used when “Selected customers” is chosen. Customers without a phone are skipped automatically.</p>

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
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <?php
                $phone = trim((string) ($c['phone'] ?? ''));
                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                ?>
                <tr>
                    <td class="col-check">
                        <label class="table-check">
                            <input type="checkbox" form="broadcast-form" name="customer_ids[]" value="<?= e($c['id']) ?>"
                                class="customer-cb" <?= $phone === '' ? 'disabled' : '' ?>
                                aria-label="Select <?= e($name) ?>">
                        </label>
                    </td>
                    <td><?= e($name) ?></td>
                    <td><?= $phone !== '' ? e($phone) : '—' ?></td>
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
        Sent <?= (int) ($lastResult['sent'] ?? 0) ?> ·
        Failed <?= (int) ($lastResult['failed'] ?? 0) ?> ·
        Skipped <?= (int) ($lastResult['skipped'] ?? 0) ?>
    </p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>Phone</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
            <?php foreach (($lastResult['results'] ?? []) as $row): ?>
                <tr>
                    <td><?= e($row['customer'] ?? '') ?></td>
                    <td><?= e($row['phone'] ?? '') ?></td>
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
  var messageField = document.getElementById('broadcast-message');

  document.querySelectorAll('textarea[name="message"]').forEach(function (ta) {
    if (messageField && ta !== messageField) {
      ta.addEventListener('input', function () { messageField.value = ta.value; });
      messageField.addEventListener('input', function () { ta.value = messageField.value; });
    }
  });

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
