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
    <div class="grid-2">
        <label>Active
            <select name="is_active">
                <option value="1" <?= (($employee['is_active'] ?? 1) ? 'selected' : '') ?>>Yes</option>
                <option value="0" <?= (isset($employee['is_active']) && !$employee['is_active'] ? 'selected' : '') ?>>No</option>
            </select>
        </label>
        <label>Available
            <select name="is_available">
                <option value="1" <?= (($employee['is_available'] ?? 1) ? 'selected' : '') ?>>Yes</option>
                <option value="0" <?= (isset($employee['is_available']) && !$employee['is_available'] ? 'selected' : '') ?>>No</option>
            </select>
        </label>
    </div>
    <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create employee' ?></button>
</form>
