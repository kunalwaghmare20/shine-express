<?php
/** @var list<array<string, mixed>> $savedTemplates */
/** @var list<string> $placeholders */
$savedTemplates = $savedTemplates ?? [];
$placeholders = $placeholders ?? [];
$adminWhatsApp = (string) ($adminWhatsApp ?? '');
$waReady = is_array($waReady ?? null) ? $waReady : [];
$base = $base ?? '/admin';
$returnTo = $returnTo ?? ($base . '/customers');
$templatesJson = [];
foreach ($savedTemplates as $t) {
    $templatesJson[] = [
        'id' => (string) ($t['id'] ?? ''),
        'name' => (string) ($t['name'] ?? ''),
        'body' => (string) ($t['body'] ?? ''),
    ];
}
?>
<div class="modal-backdrop" id="wa-modal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="wa-modal-title">
        <div class="dashboard-list-head">
            <h3 id="wa-modal-title">Send WhatsApp</h3>
            <button type="button" class="link-btn" id="wa-modal-close">Close</button>
        </div>
        <p class="muted small" id="wa-modal-customer"></p>

        <?php if (empty($waReady['ready'])): ?>
            <div class="alert alert-error">
                <?= e((string) ($waReady['reason'] ?? 'WhatsApp is not configured yet.')) ?>
                <?php if (($user['role'] ?? '') === 'SUPER_ADMIN'): ?>
                    <p class="small" style="margin:0.4rem 0 0"><a href="<?= e(url('/admin/whatsapp-settings')) ?>">Open WhatsApp settings</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="stack-form" id="wa-modal-form">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">

            <fieldset class="form-fieldset">
                <legend>Message</legend>
                <div class="choice-group">
                    <label class="choice-option">
                        <input type="radio" name="source" value="template" id="wa-source-template">
                        <span class="choice-option-body">
                            <span class="choice-option-title">Saved template</span>
                            <span class="choice-option-desc">Pick a message saved under WhatsApp broadcast</span>
                        </span>
                    </label>
                    <label class="choice-option">
                        <input type="radio" name="source" value="custom" id="wa-source-custom" checked>
                        <span class="choice-option-body">
                            <span class="choice-option-title">Custom message</span>
                            <span class="choice-option-desc">Write a one-off message for this customer</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div id="wa-template-wrap" hidden>
                <label>Saved template
                    <select name="template_id" id="wa-template-id">
                        <option value="">Select a template…</option>
                        <?php foreach ($savedTemplates as $t): ?>
                            <option value="<?= e((string) $t['id']) ?>"><?= e((string) $t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($savedTemplates === []): ?>
                    <p class="muted small">
                        No saved templates yet.
                        <?php if (($user['role'] ?? '') === 'SUPER_ADMIN'): ?>
                            Save one from <a href="<?= e(url('/admin/whatsapp-broadcast')) ?>">WhatsApp broadcast</a>.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <div id="wa-custom-wrap">
                <label>Message
                    <textarea name="message" id="wa-custom-message" rows="7" maxlength="4096" placeholder="Write your message…"></textarea>
                </label>
                <p class="muted small">
                    Personalize with:
                    <?php foreach ($placeholders as $i => $ph): ?>
                        <?= $i > 0 ? ' · ' : '' ?><code><?= e($ph) ?></code>
                    <?php endforeach; ?>
                </p>
            </div>

            <div id="wa-preview-wrap" hidden>
                <h4 class="form-section-title">Message that will be sent</h4>
                <p class="muted small" id="wa-preview-hint">This is the actual text after replacing this customer’s name, phone, and other placeholders.</p>
                <pre class="broadcast-preview-message" id="wa-preview"></pre>
            </div>

            <div class="form-actions">
                <button class="btn btn-whatsapp" type="submit" id="wa-send-btn" <?= empty($waReady['ready']) ? 'disabled' : '' ?>>
                    <?= ui_icon('send') ?> Send WhatsApp
                </button>
                <button class="btn btn-ghost" type="button" id="wa-modal-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
window.WA_CUSTOMER_TEMPLATES = <?= json_encode($templatesJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.WA_ADMIN_NUMBER = <?= json_encode($adminWhatsApp, JSON_UNESCAPED_UNICODE) ?>;
window.WA_SEND_BASE = <?= json_encode(url($base . '/customers'), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script>
(function () {
  var modal = document.getElementById('wa-modal');
  var form = document.getElementById('wa-modal-form');
  var customerLabel = document.getElementById('wa-modal-customer');
  var sourceTemplate = document.getElementById('wa-source-template');
  var sourceCustom = document.getElementById('wa-source-custom');
  var templateWrap = document.getElementById('wa-template-wrap');
  var customWrap = document.getElementById('wa-custom-wrap');
  var templateSelect = document.getElementById('wa-template-id');
  var customMessage = document.getElementById('wa-custom-message');
  var previewWrap = document.getElementById('wa-preview-wrap');
  var preview = document.getElementById('wa-preview');
  var recipient = { first_name: '', name: '', email: '', phone: '', admin_whatsapp: window.WA_ADMIN_NUMBER || '' };

  function personalize(text) {
    return String(text || '')
      .replaceAll('{first_name}', recipient.first_name || 'there')
      .replaceAll('{name}', recipient.name || 'Customer')
      .replaceAll('{email}', recipient.email || '')
      .replaceAll('{phone}', recipient.phone || '')
      .replaceAll('{admin_whatsapp}', recipient.admin_whatsapp || '');
  }

  function selectedTemplateBody() {
    var id = templateSelect.value;
    var list = window.WA_CUSTOMER_TEMPLATES || [];
    for (var i = 0; i < list.length; i++) {
      if (list[i].id === id) return list[i].body || '';
    }
    return '';
  }

  function isTemplateMode() {
    return !!(sourceTemplate && sourceTemplate.checked);
  }

  function syncMode() {
    var templateMode = isTemplateMode();
    if (templateWrap) templateWrap.hidden = !templateMode;
    if (customWrap) customWrap.hidden = templateMode;
    if (customMessage) customMessage.required = !templateMode;
    if (templateSelect) templateSelect.required = templateMode;
    updatePreview();
  }

  function updatePreview() {
    var body = isTemplateMode() ? selectedTemplateBody() : (customMessage ? customMessage.value : '');
    var show = isTemplateMode() ? body.trim() !== '' : body.trim() !== '';
    if (previewWrap) previewWrap.hidden = !show;
    if (preview) preview.textContent = show ? personalize(body) : '';
  }

  function openModal(btn) {
    recipient = {
      first_name: btn.getAttribute('data-first') || '',
      name: btn.getAttribute('data-name') || '',
      email: btn.getAttribute('data-email') || '',
      phone: btn.getAttribute('data-phone') || '',
      admin_whatsapp: window.WA_ADMIN_NUMBER || ''
    };
    if (customerLabel) {
      customerLabel.textContent = (recipient.name || 'Customer') + (recipient.phone ? ' · ' + recipient.phone : ' · no phone');
    }
    form.action = (window.WA_SEND_BASE || '') + '/' + encodeURIComponent(btn.getAttribute('data-id') || '') + '/whatsapp';
    if (sourceCustom) sourceCustom.checked = true;
    if (templateSelect) templateSelect.value = '';
    if (customMessage) customMessage.value = '';
    syncMode();
    modal.hidden = false;
    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
  }

  function closeModal() {
    modal.hidden = true;
    modal.classList.remove('is-open');
    document.body.classList.remove('modal-open');
  }

  document.querySelectorAll('.js-wa-open').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (btn.disabled) return;
      openModal(btn);
    });
  });

  ['wa-modal-close', 'wa-modal-cancel'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', closeModal);
  });
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  if (sourceTemplate) sourceTemplate.addEventListener('change', syncMode);
  if (sourceCustom) sourceCustom.addEventListener('change', syncMode);
  if (templateSelect) templateSelect.addEventListener('change', updatePreview);
  if (customMessage) customMessage.addEventListener('input', updatePreview);

  form.addEventListener('submit', function (e) {
    if (isTemplateMode() && !selectedTemplateBody()) {
      e.preventDefault();
      alert('Select a saved message template first.');
      return;
    }
    if (!isTemplateMode() && !(customMessage.value || '').trim()) {
      e.preventDefault();
      alert('Write a message first.');
    }
  });
})();
</script>
