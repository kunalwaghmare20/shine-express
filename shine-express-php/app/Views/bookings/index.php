<div class="toolbar">
    <form method="get" class="inline-form">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (array_keys(\App\Helpers\BookingStatus::TRANSITIONS) as $st): ?>
                <option value="<?= e($st) ?>" <?= (\App\Core\Request::input('status') === $st) ? 'selected' : '' ?>><?= e(\App\Helpers\BookingStatus::label($st)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm" type="submit">Filter</button>
    </form>
    <?php if (in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
        <a class="btn btn-sm" href="<?= e(url($base . '/bookings/create')) ?>">Add booking</a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Number</th><th>Customer</th><th>Service</th><th>When</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><?= e($b['booking_number']) ?></td>
            <td><?= e($b['customer_name'] ?? '—') ?></td>
            <td><?= e($b['service_name']) ?></td>
            <td><?= e($b['scheduled_date'] . ' ' . $b['scheduled_time']) ?></td>
            <td><span class="pill"><?= e(\App\Helpers\BookingStatus::label($b['status'])) ?></span></td>
            <td><?= e(money_format_inr($b['total_amount'])) ?></td>
            <td><a href="<?= e(url(rtrim($base . '/bookings/' . $b['id'], '/'))) ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
