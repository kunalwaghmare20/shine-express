<div class="toolbar">
    <a class="btn btn-sm" href="<?= e(url('/admin/services/create')) ?>">Add service</a>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Service</th><th>Category</th><th>Price</th><th>Duration</th><th>Rebook days</th><th>Items</th><th>Active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($services as $s): ?>
        <tr>
            <td><?= e($s['name']) ?></td>
            <td><?= e($s['category_name']) ?></td>
            <td><?= e(money_format_inr($s['base_price'])) ?></td>
            <td><?= e((string) $s['duration']) ?>m</td>
            <td><?= (int) ($s['reminder_days'] ?? 0) > 0 ? e((string) $s['reminder_days']) : '—' ?></td>
            <td><?= e((string) $s['item_count']) ?></td>
            <td><?= $s['is_active'] ? 'Yes' : 'No' ?></td>
            <td><a href="<?= e(url('/admin/services/' . $s['id'])) ?>">Manage</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
