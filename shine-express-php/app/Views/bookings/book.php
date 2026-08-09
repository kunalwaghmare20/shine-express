<?php
$adminMode = $adminMode ?? false;
$formAction = $adminMode ? url(($base ?? '/admin') . '/bookings') : url('/book');
?>
<div class="toolbar">
    <div></div>
    <?php if ($adminMode): ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(url(($base ?? '/admin') . '/bookings')) ?>">Back to list</a>
    <?php endif; ?>
</div>
<form method="post" action="<?= e($formAction) ?>" class="stack-form panel" id="book-form">
    <?= csrf_field() ?>

    <?php if ($adminMode): ?>
        <label>Customer
            <select name="customer_id" id="customer_id" required>
                <option value="">Select customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['email'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <fieldset class="form-fieldset">
        <legend>Services</legend>
        <p class="fieldset-help muted small">Select one or more services for this booking.</p>
        <div class="choice-group">
        <?php foreach ($services as $s): ?>
            <label class="service-opt choice-option choice-option--compact">
                <input type="checkbox" name="service_ids[]" value="<?= e($s['id']) ?>" class="service-check">
                <span class="choice-option-icon"><?= ui_icon('package') ?></span>
                <span class="choice-option-body">
                    <span class="choice-option-title"><?= e($s['category_name'] . ' — ' . $s['name']) ?></span>
                    <span class="choice-option-desc"><?= e(money_format_inr($s['base_price'])) ?></span>
                </span>
            </label>
        <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Packages / items</legend>
        <p class="fieldset-help muted small">Optional add-ons under selected services. If none are picked, the service base price is used.</p>
        <div id="items" class="choice-group">
            <?php foreach ($items as $item): ?>
                <label class="item-opt choice-option choice-option--compact" data-service="<?= e($item['service_id']) ?>" style="display:none">
                    <input type="checkbox" name="service_item_ids[]" value="<?= e($item['id']) ?>" disabled>
                    <span class="choice-option-body">
                        <span class="choice-option-title"><?= e($item['name']) ?></span>
                        <span class="choice-option-desc"><?= e(money_format_inr($item['price'])) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <label>Address
        <select name="address_id" id="address_id" required>
            <?php if (!$adminMode && $addresses === []): ?>
                <option value="">No address — add one in Profile</option>
            <?php elseif (!$adminMode): ?>
                <?php foreach ($addresses as $a): ?>
                    <option value="<?= e($a['id']) ?>"><?= e($a['label'] . ' — ' . $a['line1'] . ', ' . $a['city']) ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">Select customer first</option>
                <?php foreach ($addresses as $a): ?>
                    <option value="<?= e($a['id']) ?>" data-customer="<?= e($a['customer_id'] ?? $a['customer_id_ref'] ?? '') ?>" style="display:none">
                        <?= e($a['label'] . ' — ' . $a['line1'] . ', ' . $a['city']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </label>

    <label>Branch
        <select name="branch_id" required>
            <?php foreach ($branches as $b): ?>
                <option value="<?= e($b['id']) ?>"><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="grid-2">
        <label>Date<input type="date" name="scheduled_date" required min="<?= e(date('Y-m-d')) ?>"></label>
        <label>Time<input type="time" name="scheduled_time" required value="10:00"></label>
    </div>
    <label>Notes<textarea name="customer_notes" rows="3"></textarea></label>
    <div class="form-actions">
        <button class="btn" type="submit" <?= (!$adminMode && $addresses === []) ? 'disabled' : '' ?>>
            <?= $adminMode ? 'Create booking' : 'Create booking' ?>
        </button>
    </div>
</form>
<script>
(function () {
  var serviceChecks = document.querySelectorAll('.service-check');
  var opts = document.querySelectorAll('.item-opt');
  var form = document.getElementById('book-form');

  function selectedServiceIds() {
    var ids = {};
    serviceChecks.forEach(function (el) {
      if (el.checked) ids[el.value] = true;
    });
    return ids;
  }

  function syncItems() {
    var ids = selectedServiceIds();
    opts.forEach(function (el) {
      var on = !!ids[el.getAttribute('data-service')];
      el.style.display = on ? 'flex' : 'none';
      var input = el.querySelector('input');
      input.disabled = !on;
      if (!on) input.checked = false;
    });
  }

  serviceChecks.forEach(function (el) {
    el.addEventListener('change', syncItems);
  });
  syncItems();

  form.addEventListener('submit', function (e) {
    if (Object.keys(selectedServiceIds()).length === 0) {
      e.preventDefault();
      alert('Please select at least one service');
    }
  });

  var customer = document.getElementById('customer_id');
  var address = document.getElementById('address_id');
  if (customer && address) {
    function syncAddresses() {
      var cid = customer.value;
      Array.prototype.forEach.call(address.options, function (opt, i) {
        if (i === 0) return;
        var match = opt.getAttribute('data-customer') === cid;
        opt.style.display = match ? '' : 'none';
        opt.disabled = !match;
        if (!match && opt.selected) opt.selected = false;
      });
      address.selectedIndex = 0;
    }
    customer.addEventListener('change', syncAddresses);
    syncAddresses();
  }
})();
</script>
