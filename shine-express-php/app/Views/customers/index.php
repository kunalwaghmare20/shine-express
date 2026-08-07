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
        <tr>
            <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
            <td><?= e($c['email']) ?></td>
            <td><?= e((string) $c['phone']) ?></td>
            <td><?= e((string) $c['booking_count']) ?></td>
            <td class="actions-cell">
                <a href="<?= e(url($base . '/customers/' . $c['id'])) ?>">View</a>
                <a href="<?= e(url($base . '/customers/' . $c['id'] . '/edit')) ?>">Edit</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
