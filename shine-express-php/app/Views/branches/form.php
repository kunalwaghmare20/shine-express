<?php $isEdit = is_array($branch ?? null); ?>
<form method="post" action="<?= e(url($isEdit ? '/admin/branches/' . $branch['id'] : '/admin/branches')) ?>" class="stack-form panel">
    <?= csrf_field() ?>
    <label>Company
        <select name="company_id" required>
            <?php foreach ($companies as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= ($branch['company_id'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="grid-2">
        <label>Name<input name="name" required value="<?= e((string) ($branch['name'] ?? '')) ?>"></label>
        <label>Code<input name="code" value="<?= e((string) ($branch['code'] ?? '')) ?>" placeholder="Auto if empty on create"></label>
    </div>
    <div class="grid-2">
        <label>Email<input type="email" name="email" value="<?= e((string) ($branch['email'] ?? '')) ?>"></label>
        <label>Phone<input name="phone" value="<?= e((string) ($branch['phone'] ?? '')) ?>"></label>
    </div>
    <label>Address<input name="address" value="<?= e((string) ($branch['address'] ?? '')) ?>"></label>
    <div class="grid-2">
        <label>City<input name="city" value="<?= e((string) ($branch['city'] ?? '')) ?>"></label>
        <label>State<input name="state" value="<?= e((string) ($branch['state'] ?? '')) ?>"></label>
    </div>
    <div class="grid-2">
        <label>Pincode<input name="pincode" value="<?= e((string) ($branch['pincode'] ?? '')) ?>"></label>
    </div>
    <label class="form-switch">
        <input type="checkbox" name="is_active" value="1" <?= !isset($branch['is_active']) || !empty($branch['is_active']) ? 'checked' : '' ?>>
        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
        <span class="form-switch-label">
            <strong>Active branch</strong>
            <span class="muted small">Branch appears in booking and staff assignment</span>
        </span>
    </label>
    <div class="grid-2">
        <label>Latitude<input name="latitude" value="<?= e((string) ($branch['latitude'] ?? '')) ?>"></label>
        <label>Longitude<input name="longitude" value="<?= e((string) ($branch['longitude'] ?? '')) ?>"></label>
    </div>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create branch' ?></button>
    </div>
</form>
