<?php
use App\Helpers\BookingStatus;
$actionBase = $base !== '' ? $base . '/bookings/' . $booking['id'] : '/bookings/' . $booking['id'];
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h3><?= e($booking['booking_number']) ?></h3>
            <p class="muted"><?= e($booking['service_name']) ?> · <?= e(BookingStatus::label($booking['status'])) ?></p>
        </div>
        <strong><?= e(money_format_inr($booking['total_amount'])) ?></strong>
    </div>
    <p><?= e($booking['customer_name']) ?> · <?= e($booking['scheduled_date'] . ' at ' . $booking['scheduled_time']) ?></p>
    <p class="muted"><?= e($booking['line1'] . ', ' . $booking['city'] . ' ' . $booking['pincode']) ?></p>
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
                <li><?= e($a['first_name'] . ' ' . $a['last_name']) ?> (<?= e($a['employee_code']) ?>)</li>
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
    <form method="post" action="<?= e(url($actionBase . '/assign')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <?php foreach ($staff as $s): ?>
            <label class="check">
                <input type="checkbox" name="employee_ids[]" value="<?= e($s['id']) ?>">
                <?= e($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['employee_code'] . ')') ?>
            </label>
        <?php endforeach; ?>
        <button class="btn btn-sm" type="submit">Assign</button>
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
