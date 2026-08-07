<div class="toolbar">
    <div></div>
    <a class="btn btn-sm" href="<?= e(url('/admin/branches/create')) ?>">Add branch</a>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Name</th><th>Code</th><th>City</th><th>Phone</th><th>Active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($branches as $b): ?>
        <tr>
            <td><?= e($b['name']) ?></td>
            <td><?= e($b['code']) ?></td>
            <td><?= e((string) $b['city']) ?></td>
            <td><?= e((string) $b['phone']) ?></td>
            <td><?= $b['is_active'] ? 'Yes' : 'No' ?></td>
            <td><a href="<?= e(url('/admin/branches/' . $b['id'] . '/edit')) ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
