<div class="toolbar">
    <a class="btn btn-sm" href="<?= e(url($base . '/employees/create')) ?>">Add employee</a>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Code</th><th>Name</th><th>Branch</th><th>Phone</th><th>Available</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($employees as $e): ?>
        <tr>
            <td><?= e($e['employee_code']) ?></td>
            <td><?= e($e['first_name'] . ' ' . $e['last_name']) ?></td>
            <td><?= e($e['branch_name']) ?></td>
            <td><?= e((string) $e['phone']) ?></td>
            <td><?= $e['is_available'] ? 'Yes' : 'No' ?></td>
            <td><a href="<?= e(url($base . '/employees/' . $e['id'] . '/edit')) ?>">Edit</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
