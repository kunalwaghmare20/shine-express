<div class="toolbar">
    <form method="get" class="inline-form">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (array_keys(\App\Helpers\BookingStatus::TRANSITIONS) as $st): ?>
                <option value="<?= e($st) ?>" <?= (\App\Core\Request::input('status') === $st) ? 'selected' : '' ?>><?= e(\App\Helpers\BookingStatus::label($st)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm" type="submit">Filter</button>
        <?php if (in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
            <label class="toolbar-chip">
                <input type="checkbox" name="followup" value="1" <?= (\App\Core\Request::input('followup') === '1') ? 'checked' : '' ?>>
                <?= ui_icon('alert') ?> Follow-up only
            </label>
        <?php endif; ?>
    </form>
    <?php if (in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
        <a class="btn btn-sm" href="<?= e(url($base . '/bookings/create')) ?>">Add booking</a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Number</th><th>Customer</th><th>Service</th><th>When</th><th>Status</th><th>Total</th><th>Alerts</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <?php
        $waLink = !empty($b['customer_phone'])
            ? whatsapp_link((string) $b['customer_phone'], whatsapp_booking_message($b))
            : null;
        ?>
        <tr class="<?= !empty($b['requires_followup']) ? 'row-followup' : '' ?>">
            <td><?= e($b['booking_number']) ?></td>
            <td><?= e($b['customer_name'] ?? '—') ?></td>
            <td><?= e($b['service_name']) ?></td>
            <td><?= e($b['scheduled_date'] . ' ' . $b['scheduled_time']) ?></td>
            <td><span class="pill"><?= e(\App\Helpers\BookingStatus::label($b['status'])) ?></span></td>
            <td><?= e(money_format_inr($b['total_amount'])) ?></td>
            <td>
                <?php if (!empty($b['requires_followup'])): ?>
                    <span class="pill pill-danger" title="Low rating — callback required">Follow-up</span>
                <?php else: ?>
                    <span class="muted">—</span>
                <?php endif; ?>
            </td>
            <td class="actions-cell">
                <a href="<?= e(url(rtrim($base . '/bookings/' . $b['id'], '/'))) ?>">Open</a>
                <?php if ($waLink && in_array(($user['role'] ?? ''), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)): ?>
                    · <a class="wa-link" href="<?= e($waLink) ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp customer">WhatsApp</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
