<?php
use App\Helpers\BookingStatus;

$todayBookings = $todayBookings ?? [];
$pendingBookings = $pendingBookings ?? [];
$base = $base ?? '/admin';
$canAcceptPending = !empty($canAcceptPending);
?>
<section class="stats-grid">
    <article class="stat"><span>Today's bookings</span><strong><?= e((string) $today) ?></strong></article>
    <article class="stat"><span>Pending / active</span><strong><?= e((string) $pending) ?></strong></article>
    <article class="stat"><span>Completed</span><strong><?= e((string) $completed) ?></strong></article>
    <article class="stat"><span>Cash revenue</span><strong><?= e(money_format_inr($revenue)) ?></strong></article>
    <article class="stat"><span>Customers</span><strong><?= e((string) $customers) ?></strong></article>
    <article class="stat"><span>Employees</span><strong><?= e((string) $employees) ?></strong></article>
</section>

<div class="grid-2 panels">
    <div class="dashboard-col">
        <section class="panel">
            <h3>Popular services</h3>
            <ul class="plain-list">
                <?php foreach ($popular as $row): ?>
                    <li><span><?= e($row['name']) ?></span><strong><?= e((string) $row['total']) ?></strong></li>
                <?php endforeach; ?>
                <?php if ($popular === []): ?><li class="muted">No data yet</li><?php endif; ?>
            </ul>
        </section>

        <section class="panel">
            <div class="dashboard-list-head">
                <h3>Today's bookings</h3>
                <a class="muted small" href="<?= e(url($base . '/bookings')) ?>">View all</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>When</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($todayBookings as $b): ?>
                        <tr>
                            <td><?= e($b['booking_number']) ?></td>
                            <td><?= e($b['customer_name'] ?? '—') ?></td>
                            <td><?= e($b['service_name']) ?></td>
                            <td><?= e(substr((string) $b['scheduled_time'], 0, 5)) ?></td>
                            <td><span class="pill"><?= e(BookingStatus::label((string) $b['status'])) ?></span></td>
                            <td class="actions-cell"><a href="<?= e(url($base . '/bookings/' . $b['id'])) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($todayBookings === []): ?>
                        <tr><td colspan="6" class="muted">No bookings scheduled for today</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="dashboard-col">
        <section class="panel">
            <h3>Status breakdown</h3>
            <ul class="plain-list">
                <?php foreach ($statusBreakdown as $row): ?>
                    <li><span><?= e(BookingStatus::label($row['status'])) ?></span><strong><?= e((string) $row['total']) ?></strong></li>
                <?php endforeach; ?>
                <?php if ($statusBreakdown === []): ?><li class="muted">No bookings yet</li><?php endif; ?>
            </ul>
        </section>

        <section class="panel">
            <div class="dashboard-list-head">
                <h3>Pending bookings</h3>
                <a class="muted small" href="<?= e(url($base . '/bookings?status=PENDING')) ?>">View all</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingBookings as $b): ?>
                        <tr>
                            <td><?= e($b['booking_number']) ?></td>
                            <td><?= e($b['customer_name'] ?? '—') ?></td>
                            <td><?= e($b['service_name']) ?></td>
                            <td><?= e($b['scheduled_date'] . ' ' . substr((string) $b['scheduled_time'], 0, 5)) ?></td>
                            <td class="actions-cell">
                                <a href="<?= e(url($base . '/bookings/' . $b['id'])) ?>">Open</a>
                                <?php if ($canAcceptPending): ?>
                                    <form method="post" action="<?= e(url($base . '/bookings/' . $b['id'] . '/status')) ?>" class="inline-form dashboard-accept" onsubmit="return confirm('Accept this booking and mark it as confirmed?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="<?= e(BookingStatus::CONFIRMED) ?>">
                                        <input type="hidden" name="return_to" value="<?= e($base) ?>">
                                        <button class="btn btn-sm" type="submit">Accept</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($pendingBookings === []): ?>
                        <tr><td colspan="5" class="muted">No pending bookings</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
