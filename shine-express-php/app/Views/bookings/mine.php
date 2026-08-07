<div class="table-wrap">
<table>
    <thead><tr><th>Number</th><th>Service</th><th>When</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><?= e($b['booking_number']) ?></td>
            <td><?= e($b['service_name']) ?></td>
            <td><?= e($b['scheduled_date'] . ' ' . $b['scheduled_time']) ?></td>
            <td><?= e(\App\Helpers\BookingStatus::label($b['status'])) ?></td>
            <td><?= e(money_format_inr($b['total_amount'])) ?></td>
            <td><a href="<?= e(url('/bookings/' . $b['id'])) ?>">View</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($bookings === []): ?>
        <tr><td colspan="6" class="muted">No bookings yet. <a href="<?= e(url('/book')) ?>">Book a service</a></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
