<section class="stats-grid">
    <article class="stat"><span>Today's bookings</span><strong><?= e((string) $today) ?></strong></article>
    <article class="stat"><span>Pending / active</span><strong><?= e((string) $pending) ?></strong></article>
    <article class="stat"><span>Completed</span><strong><?= e((string) $completed) ?></strong></article>
    <article class="stat"><span>Cash revenue</span><strong><?= e(money_format_inr($revenue)) ?></strong></article>
    <article class="stat"><span>Customers</span><strong><?= e((string) $customers) ?></strong></article>
    <article class="stat"><span>Employees</span><strong><?= e((string) $employees) ?></strong></article>
</section>

<div class="grid-2 panels">
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
        <h3>Status breakdown</h3>
        <ul class="plain-list">
            <?php foreach ($statusBreakdown as $row): ?>
                <li><span><?= e(\App\Helpers\BookingStatus::label($row['status'])) ?></span><strong><?= e((string) $row['total']) ?></strong></li>
            <?php endforeach; ?>
            <?php if ($statusBreakdown === []): ?><li class="muted">No bookings yet</li><?php endif; ?>
        </ul>
    </section>
</div>
