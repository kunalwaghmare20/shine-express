<?php
use App\Helpers\BookingStatus;
$actionBase = $base !== '' ? $base . '/bookings/' . $booking['id'] : '/bookings/' . $booking['id'];
?>
<?php if (!empty($booking['requires_followup']) && in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
<section class="panel panel-danger">
    <strong>Urgent follow-up required</strong>
    <p class="muted">This booking received a low customer rating. Please call the customer as soon as possible.</p>
</section>
<?php endif; ?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h3><?= e($booking['booking_number']) ?></h3>
            <p class="muted"><?= e($booking['service_name']) ?> · <?= e(BookingStatus::label($booking['status'])) ?></p>
            <?php if (!empty($booking['requires_followup'])): ?>
                <span class="pill pill-danger">Follow-up</span>
            <?php endif; ?>
        </div>
        <strong><?= e(money_format_inr($booking['total_amount'])) ?></strong>
    </div>
    <p><?= e($booking['customer_name']) ?> · <?= e($booking['scheduled_date'] . ' at ' . $booking['scheduled_time']) ?></p>
    <p class="muted"><?= e($booking['line1'] . ', ' . $booking['city'] . ' ' . $booking['pincode']) ?></p>
    <?php
    $waLink = !empty($booking['customer_phone'])
        ? whatsapp_link((string) $booking['customer_phone'], whatsapp_booking_message($booking))
        : null;
    ?>
    <?php if ($waLink && in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
        <p style="margin-top:0.75rem">
            <a class="btn btn-sm btn-whatsapp" href="<?= e($waLink) ?>" target="_blank" rel="noopener noreferrer">
                WhatsApp customer
            </a>
        </p>
    <?php endif; ?>
</section>

<div class="grid-2 panels">
    <section class="panel">
        <h3>Line items</h3>
        <ul class="plain-list">
            <?php foreach ($items as $item): ?>
                <li><span><?= e($item['name']) ?> × <?= e((string) $item['quantity']) ?></span><strong><?= e(money_format_inr($item['price'])) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="panel">
        <h3>Assigned staff</h3>
        <ul class="plain-list">
            <?php foreach ($assignments as $a): ?>
                <?php
                $staffName = trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
                if ($staffName === '') {
                    $staffName = $a['employee_code'] ?? $a['employee_id'] ?? 'Staff';
                }
                ?>
                <li>
                    <?= e($staffName) ?><?php if (!empty($a['employee_code'])): ?> (<?= e($a['employee_code']) ?>)<?php endif; ?>
                    <?php if (!empty($a['is_primary'])): ?><span class="pill">Primary</span><?php endif; ?>
                    <?php if (!empty($a['rejected_at'])): ?><span class="muted">Declined</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($assignments === []): ?>
                <li class="muted">None yet</li>
                <?php if (in_array($booking['status'], [BookingStatus::ASSIGNED, BookingStatus::ACCEPTED], true)): ?>
                    <li class="muted">Status was changed without picking staff. Use <strong>Assign staff</strong> below so the job appears in the staff app.</li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </section>
</div>

<?php
$role = $user['role'] ?? '';
$isManager = in_array($role, ['SUPER_ADMIN', 'BRANCH_MANAGER'], true);
$canAssign = $canAssign ?? $isManager;
$statusOptions = [];
foreach ($transitions as $to) {
    if ($role === 'CUSTOMER' && $to !== BookingStatus::CANCELLED) {
        continue;
    }
    // Assigned/Accepted are staff-assignment and staff-app actions, not admin status clicks.
    if ($isManager && in_array($to, [BookingStatus::ASSIGNED, BookingStatus::ACCEPTED], true)) {
        continue;
    }
    $statusOptions[] = $to;
}
?>

<?php if ($canAssign): ?>
<section class="panel">
    <h3>Assign staff</h3>
    <p class="muted small">Tick the employee name, optionally mark a primary contact, then click Assign selected. This is what sends the job to the staff app — changing status alone does not.</p>
    <?php if ($staff === []): ?>
        <p class="muted">No active employees found<?= $role === 'BRANCH_MANAGER' ? ' in this branch' : '' ?>. Add staff first, then return here to assign.</p>
        <p><a class="btn btn-sm" href="<?= e(url(($base ?: '/admin') . (str_contains((string) $base, 'branch-manager') ? '/staff' : '/employees'))) ?>">Go to employees</a></p>
    <?php else: ?>
    <form method="post" action="<?= e(url($actionBase . '/assign')) ?>" class="stack-form" id="assign-staff-form">
        <?= csrf_field() ?>
        <div class="assign-list">
        <?php foreach ($staff as $s): ?>
            <?php
            $checked = in_array($s['id'], $assignedIds ?? [], true);
            $otherBranch = ($s['branch_id'] ?? '') !== ($booking['branch_id'] ?? '');
            ?>
            <div class="assign-row">
                <label class="choice-option choice-option--compact">
                    <input type="checkbox" name="employee_ids[]" value="<?= e($s['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                    <span class="choice-option-body">
                        <span class="choice-option-title">
                            <?= e($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['employee_code'] . ')') ?>
                            <?php if (empty($s['is_available'])): ?><span class="pill">Busy</span><?php endif; ?>
                            <?php if ($otherBranch && !empty($s['branch_name'])): ?><span class="pill"><?= e($s['branch_name']) ?></span><?php endif; ?>
                        </span>
                        <span class="choice-option-desc choice-option-meta">
                            <span class="choice-option-meta-icon"><?= ui_icon('map-pin') ?></span>
                            <?= e(format_distance_km(isset($s['distance_km']) ? (float) $s['distance_km'] : null)) ?>
                        </span>
                    </span>
                </label>
                <label class="choice-option choice-option--compact choice-option--primary">
                    <input type="radio" name="primary_employee_id" value="<?= e($s['id']) ?>"
                        <?= ($primaryEmployeeId ?? '') === $s['id'] ? 'checked' : '' ?>>
                    <span class="choice-option-icon"><?= ui_icon('star') ?></span>
                    <span class="choice-option-body">
                        <span class="choice-option-title">Primary contact</span>
                    </span>
                </label>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="form-actions">
            <button class="btn btn-sm" type="submit">Assign selected</button>
        </div>
    </form>
    <script>
    (function () {
        var form = document.getElementById('assign-staff-form');
        if (!form) return;
        form.querySelectorAll('input[name="primary_employee_id"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var row = radio.closest('.assign-row');
                var box = row && row.querySelector('input[name="employee_ids[]"]');
                if (box) box.checked = true;
            });
        });
        form.addEventListener('submit', function () {
            var primary = form.querySelector('input[name="primary_employee_id"]:checked');
            if (!primary) return;
            var row = primary.closest('.assign-row');
            var box = row && row.querySelector('input[name="employee_ids[]"]');
            if (box) box.checked = true;
        });
    })();
    </script>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($statusOptions !== [] && in_array($role, ['SUPER_ADMIN','BRANCH_MANAGER','SERVICE_STAFF','CUSTOMER'], true)): ?>
<section class="panel">
    <h3>Update status</h3>
    <form method="post" action="<?= e(url($actionBase . '/status')) ?>" class="inline-form inline-form-bar">
        <?= csrf_field() ?>
        <select name="status" required>
            <?php foreach ($statusOptions as $to): ?>
                <option value="<?= e($to) ?>"><?= e(BookingStatus::label($to)) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="notes" placeholder="Notes">
        <button class="btn btn-sm" type="submit">Update</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Status history</h3>
    <ul class="plain-list">
        <?php foreach ($history as $h): ?>
            <li><span><?= e(($h['from_status'] ?? '—') . ' → ' . $h['to_status']) ?></span><strong><?= e($h['created_at']) ?></strong></li>
        <?php endforeach; ?>
    </ul>
</section>
