<div class="toolbar">
    <form method="post" action="<?= e(url('/staff/attendance/check-in')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm" type="submit">Check in today</button>
    </form>
</div>
<div class="table-wrap">
<table>
    <thead><tr><th>Date</th><th>Check in</th><th>Check out</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['date']) ?></td>
            <td><?= e((string) $r['check_in']) ?></td>
            <td><?= e((string) ($r['check_out'] ?? '—')) ?></td>
            <td><?= e($r['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <tr><td colspan="4" class="muted">No attendance records</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
