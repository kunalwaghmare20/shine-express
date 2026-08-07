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
        <label>Active
            <select name="is_active">
                <option value="1" <?= (($branch['is_active'] ?? 1) ? 'selected' : '') ?>>Yes</option>
                <option value="0" <?= (isset($branch['is_active']) && !$branch['is_active'] ? 'selected' : '') ?>>No</option>
            </select>
        </label>
    </div>
    <div class="grid-2">
        <label>Latitude<input name="latitude" value="<?= e((string) ($branch['latitude'] ?? '')) ?>"></label>
        <label>Longitude<input name="longitude" value="<?= e((string) ($branch['longitude'] ?? '')) ?>"></label>
    </div>
    <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create branch' ?></button>
</form>
