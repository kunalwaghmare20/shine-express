<?php $isEdit = is_array($customer ?? null); ?>
<form method="post" action="<?= e(url($isEdit ? $base . '/customers/' . $customer['id'] : $base . '/customers')) ?>" class="stack-form panel">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>First name<input name="first_name" required value="<?= e((string) ($customer['first_name'] ?? '')) ?>"></label>
        <label>Last name<input name="last_name" required value="<?= e((string) ($customer['last_name'] ?? '')) ?>"></label>
    </div>
    <label>Email<input type="email" name="email" required value="<?= e((string) ($customer['email'] ?? '')) ?>"></label>
    <label>Phone<input name="phone" required value="<?= e((string) ($customer['phone'] ?? '')) ?>"></label>
    <label>Password<?= $isEdit ? ' (leave blank to keep)' : '' ?>
        <input name="password" <?= $isEdit ? '' : 'required' ?> value="<?= $isEdit ? '' : 'Customer@123' ?>" placeholder="<?= $isEdit ? '••••••••' : '' ?>">
    </label>
    <label>GST<input name="gst_number" value="<?= e((string) ($customer['gst_number'] ?? '')) ?>"></label>
    <label>Notes<textarea name="notes" rows="3"><?= e((string) ($customer['notes'] ?? '')) ?></textarea></label>
    <label class="form-switch">
        <input type="checkbox" name="is_active" value="1" <?= !isset($customer['is_active']) || !empty($customer['is_active']) ? 'checked' : '' ?>>
        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
        <span class="form-switch-label">
            <strong>Active customer</strong>
            <span class="muted small">Customer can sign in and book services</span>
        </span>
    </label>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create customer' ?></button>
    </div>
</form>
