<section class="panel">
    <div class="toolbar" style="justify-content:space-between">
        <div>
            <h3><?= e($service['name']) ?></h3>
            <p class="muted"><?= e($service['category_name']) ?> · <?= e(money_format_inr($service['base_price'])) ?> · <?= e((string) $service['duration']) ?> min</p>
        </div>
        <a class="btn btn-sm" href="<?= e(url('/admin/services/' . $service['id'] . '/edit')) ?>">Edit service</a>
    </div>
    <p><?= e((string) $service['description']) ?></p>
    <p><strong>Rebook reminder:</strong>
        <?php if ((int) ($service['reminder_days'] ?? 0) > 0): ?>
            <?= (int) $service['reminder_days'] ?> day(s) after completion (WhatsApp ask to book next appointment)
        <?php else: ?>
            Disabled
        <?php endif; ?>
    </p>
</section>

<section class="panel">
    <h3>Service items</h3>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['name']) ?></td>
                <td><?= e(money_format_inr($item['price'])) ?></td>
                <td><?= e((string) ($item['duration'] ?? '—')) ?></td>
                <td>
                    <?php if (!empty($item['is_active'])): ?>
                        <span class="pill">Active</span>
                    <?php else: ?>
                        <span class="muted">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <a href="<?= e(url('/admin/services/' . $service['id'] . '/items/' . $item['id'] . '/edit')) ?>">Edit</a>
                    <?php if (!empty($item['is_active'])): ?>
                        <form method="post" action="<?= e(url('/admin/services/' . $service['id'] . '/items/' . $item['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Deactivate this item?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-ghost" style="margin:0;padding:0.2rem 0.5rem">Deactivate</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <tr><td colspan="5" class="muted">No items yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <form method="post" action="<?= e(url('/admin/services/' . $service['id'] . '/items')) ?>" class="stack-form" style="margin-top:1rem">
        <?= csrf_field() ?>
        <h4>Add item</h4>
        <div class="grid-2">
            <label>Name<input name="name" required></label>
            <label>Price<input type="number" step="0.01" name="price" required></label>
        </div>
        <label>Description<input name="description"></label>
        <label>Duration<input type="number" name="duration"></label>
        <button class="btn btn-sm" type="submit">Add item</button>
    </form>
</section>
