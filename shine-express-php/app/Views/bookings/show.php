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
                <li>
                    <?= e($a['first_name'] . ' ' . $a['last_name']) ?> (<?= e($a['employee_code']) ?>)
                    <?php if (!empty($a['is_primary'])): ?><span class="pill">Primary</span><?php endif; ?>
                    <?php if (!empty($a['rejected_at'])): ?><span class="muted">Declined</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($assignments === []): ?><li class="muted">None yet</li><?php endif; ?>
        </ul>
    </section>
</div>

<?php if ($transitions !== [] && in_array(($user['role'] ?? ''), ['SUPER_ADMIN','BRANCH_MANAGER','SERVICE_STAFF','CUSTOMER'], true)): ?>
<section class="panel">
    <h3>Update status</h3>
    <form method="post" action="<?= e(url($actionBase . '/status')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <select name="status" required>
            <?php foreach ($transitions as $to): ?>
                <?php if (($user['role'] ?? '') === 'CUSTOMER' && $to !== BookingStatus::CANCELLED) continue; ?>
                <option value="<?= e($to) ?>"><?= e(BookingStatus::label($to)) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="notes" placeholder="Notes">
        <button class="btn btn-sm" type="submit">Update</button>
    </form>
</section>
<?php endif; ?>

<?php if ($staff !== [] && in_array(($user['role'] ?? ''), ['SUPER_ADMIN','BRANCH_MANAGER'], true)): ?>
<section class="panel">
    <h3>Assign staff</h3>
    <p class="muted small">Employees are sorted by nearest available GPS location to the customer address.</p>
    <form method="post" action="<?= e(url($actionBase . '/assign')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <?php foreach ($staff as $s): ?>
            <?php $checked = in_array($s['id'], $assignedIds ?? [], true); ?>
            <div class="grid-2" style="align-items:center">
                <label class="check">
                    <input type="checkbox" name="employee_ids[]" value="<?= e($s['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                    <?= e($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['employee_code'] . ')') ?>
                    <span class="muted small"> · <?= e(format_distance_km(isset($s['distance_km']) ? (float) $s['distance_km'] : null)) ?></span>
                    <?php if (empty($s['is_available'])): ?><span class="pill">Busy</span><?php endif; ?>
                </label>
                <label class="check">
                    <input type="radio" name="primary_employee_id" value="<?= e($s['id']) ?>"
                        <?= ($primaryEmployeeId ?? '') === $s['id'] ? 'checked' : '' ?>>
                    Primary contact
                </label>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-sm" type="submit">Assign selected</button>
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
