<section class="panel">
    <h3><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h3>
    <p class="muted"><?= e($user['email'] ?? '') ?></p>
    <?php if ($employee): ?>
        <p>Code: <strong><?= e($employee['employee_code']) ?></strong></p>
        <p>Available: <?= $employee['is_available'] ? 'Yes' : 'No' ?></p>
    <?php endif; ?>
</section>
