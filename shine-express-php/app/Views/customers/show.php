<section class="panel">
    <div class="toolbar">
        <div>
            <h3><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h3>
            <p class="muted"><?= e($customer['email']) ?> · <?= e((string) $customer['phone']) ?></p>
        </div>
        <a class="btn btn-sm" href="<?= e(url($base . '/customers/' . $customer['id'] . '/edit')) ?>">Edit</a>
    </div>
</section>
<div class="grid-2 panels">
    <section class="panel">
        <h3>Addresses</h3>
        <ul class="plain-list">
            <?php foreach ($addresses as $a): ?>
                <li><?= e($a['label']) ?> — <?= e($a['line1'] . ', ' . $a['city']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="panel">
        <h3>Recent bookings</h3>
        <ul class="plain-list">
            <?php foreach ($bookings as $b): ?>
                <li><span><?= e($b['booking_number'] . ' · ' . $b['service_name']) ?></span><strong><?= e($b['status']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
