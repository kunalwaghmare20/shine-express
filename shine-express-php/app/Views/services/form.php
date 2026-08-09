<?php
/** @var array<string, mixed>|null $service */
$isEdit = is_array($service ?? null);
$action = $isEdit ? url('/admin/services/' . $service['id']) : url('/admin/services');
?>
<form method="post" action="<?= e($action) ?>" class="stack-form panel">
    <?= csrf_field() ?>
    <label>Category
        <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= $isEdit && $service['category_id'] === $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Name<input name="name" required value="<?= e($isEdit ? (string) $service['name'] : '') ?>"></label>
    <label>Description<textarea name="description" rows="3"><?= e($isEdit ? (string) ($service['description'] ?? '') : '') ?></textarea></label>
    <div class="grid-2">
        <label>Base price<input type="number" step="0.01" name="base_price" required value="<?= e($isEdit ? (string) $service['base_price'] : '') ?>"></label>
        <label>Duration (min)<input type="number" name="duration" required value="<?= e($isEdit ? (string) $service['duration'] : '') ?>"></label>
    </div>
    <label>Rebook reminder (days after completed service)
        <input type="number" name="reminder_days" min="0" value="<?= e($isEdit ? (string) ($service['reminder_days'] ?? 30) : '30') ?>" required>
        <span class="muted" style="font-size:0.85rem;display:block;margin-top:0.35rem">
            WhatsApp the customer this many days after the service is completed, asking them to book their next appointment.
            Use <strong>0</strong> to disable reminders for this service.
        </span>
    </label>
    <label>Sort order<input type="number" name="sort_order" value="<?= e($isEdit ? (string) $service['sort_order'] : '0') ?>"></label>
    <label class="form-switch">
        <input type="checkbox" name="is_active" value="1" <?= !$isEdit || !empty($service['is_active']) ? 'checked' : '' ?>>
        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
        <span class="form-switch-label">
            <strong>Active</strong>
            <span class="muted small">Visible to customers when booking</span>
        </span>
    </label>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create' ?></button>
        <?php if ($isEdit): ?>
            <a class="btn btn-ghost" href="<?= e(url('/admin/services/' . $service['id'])) ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>
