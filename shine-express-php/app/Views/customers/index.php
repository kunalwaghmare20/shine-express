<div class="toolbar">
    <form method="get" class="inline-form">
        <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search customers">
        <button class="btn btn-sm" type="submit">Search</button>
    </form>
    <?php if (($user['role'] ?? '') === 'SUPER_ADMIN'): ?>
        <a class="btn btn-sm" href="<?= e(url($base . '/customers/create')) ?>">Add customer</a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
        <?php
        $phone = trim((string) ($c['phone'] ?? ''));
        $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
        ?>
        <tr>
            <td><?= e($name) ?></td>
            <td><?= e($c['email']) ?></td>
            <td><?= $phone !== '' ? e($phone) : '—' ?></td>
            <td><?= e((string) $c['booking_count']) ?></td>
            <td class="actions-cell">
                <a href="<?= e(url($base . '/customers/' . $c['id'])) ?>">View</a>
                <a href="<?= e(url($base . '/customers/' . $c['id'] . '/edit')) ?>">Edit</a>
                <button type="button" class="wa-icon-btn js-wa-open" <?= $phone === '' ? 'disabled' : '' ?>
                    title="<?= $phone === '' ? 'No phone number' : 'Send WhatsApp' ?>"
                    aria-label="Send WhatsApp to <?= e($name) ?>"
                    data-id="<?= e($c['id']) ?>"
                    data-name="<?= e($name) ?>"
                    data-first="<?= e((string) ($c['first_name'] ?? '')) ?>"
                    data-email="<?= e((string) ($c['email'] ?? '')) ?>"
                    data-phone="<?= e($phone) ?>">
                    <?= ui_icon('whatsapp') ?>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
$returnTo = $base . '/customers';
require APP_PATH . '/Views/customers/_whatsapp_modal.php';
?>
