<?php
$isEdit = is_array($employee ?? null);
$skillsValue = '';
if ($isEdit && !empty($employee['skills'])) {
    $decoded = json_decode((string) $employee['skills'], true);
    $skillsValue = is_array($decoded) ? implode(', ', $decoded) : '';
}
$action = $isEdit
    ? $base . '/employees/' . $employee['id']
    : $base . '/employees';
?>
<form method="post" action="<?= e(url($action)) ?>" class="stack-form panel">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>First name<input name="first_name" required value="<?= e((string) ($employee['first_name'] ?? '')) ?>"></label>
        <label>Last name<input name="last_name" required value="<?= e((string) ($employee['last_name'] ?? '')) ?>"></label>
    </div>
    <label>Email<input type="email" name="email" required value="<?= e((string) ($employee['email'] ?? '')) ?>"></label>
    <label>Phone<input name="phone" value="<?= e((string) ($employee['phone'] ?? '')) ?>"></label>
    <label>Employee code<input name="employee_code" value="<?= e((string) ($employee['employee_code'] ?? '')) ?>" placeholder="<?= $isEdit ? '' : 'Auto if empty' ?>"></label>
    <label>Salary<input type="number" step="0.01" name="salary" value="<?= e((string) ($employee['salary'] ?? '')) ?>"></label>
    <label>Skills (comma separated)<input name="skills" value="<?= e($skillsValue) ?>" placeholder="cleaning, pest"></label>
    <label>Password<?= $isEdit ? ' (leave blank to keep)' : '' ?>
        <input name="password" <?= $isEdit ? '' : 'required' ?> value="<?= $isEdit ? '' : 'Staff@123' ?>">
    </label>
    <?php if (($user['role'] ?? '') === 'SUPER_ADMIN'): ?>
        <label>Branch
            <select name="branch_id" required>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= e($b['id']) ?>" <?= ($defaultBranch ?? ($employee['branch_id'] ?? '')) === $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Role
            <select name="role">
                <option value="SERVICE_STAFF" <?= (($employee['user_role'] ?? 'SERVICE_STAFF') === 'SERVICE_STAFF') ? 'selected' : '' ?>>Service Staff</option>
                <option value="BRANCH_MANAGER" <?= (($employee['user_role'] ?? '') === 'BRANCH_MANAGER') ? 'selected' : '' ?>>Branch Manager</option>
            </select>
        </label>
    <?php else: ?>
        <input type="hidden" name="branch_id" value="<?= e((string) $defaultBranch) ?>">
    <?php endif; ?>
    <div class="form-switch-grid">
        <label class="form-switch">
            <input type="checkbox" name="is_active" value="1" <?= !isset($employee['is_active']) || !empty($employee['is_active']) ? 'checked' : '' ?>>
            <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
            <span class="form-switch-label">
                <strong>Active account</strong>
                <span class="muted small">Staff can sign in when enabled</span>
            </span>
        </label>
        <label class="form-switch">
            <input type="checkbox" name="is_available" value="1" <?= !isset($employee['is_available']) || !empty($employee['is_available']) ? 'checked' : '' ?>>
            <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
            <span class="form-switch-label">
                <strong>Available for jobs</strong>
                <span class="muted small">Shown as available for new assignments</span>
            </span>
        </label>
    </div>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create employee' ?></button>
    </div>
</form>
