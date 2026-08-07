<section class="panel">
    <div class="toolbar" style="justify-content:space-between">
        <div>
            <h3>Edit item</h3>
            <p class="muted"><?= e($service['name'] ?? '') ?></p>
        </div>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/services/' . $service['id'])) ?>">Back</a>
    </div>

    <form method="post" action="<?= e(url('/admin/services/' . $service['id'] . '/items/' . $item['id'])) ?>" class="stack-form">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Name<input name="name" required value="<?= e((string) $item['name']) ?>"></label>
            <label>Price<input type="number" step="0.01" name="price" required value="<?= e((string) $item['price']) ?>"></label>
        </div>
        <label>Description<input name="description" value="<?= e((string) ($item['description'] ?? '')) ?>"></label>
        <div class="grid-2">
            <label>Duration (minutes)<input type="number" name="duration" value="<?= e((string) ($item['duration'] ?? '')) ?>"></label>
            <label>Sort order<input type="number" name="sort_order" value="<?= e((string) ($item['sort_order'] ?? 0)) ?>"></label>
        </div>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
            Active (visible to customers)
        </label>
        <div class="actions">
            <button class="btn" type="submit">Save item</button>
            <a class="btn btn-ghost" href="<?= e(url('/admin/services/' . $service['id'])) ?>">Cancel</a>
        </div>
    </form>
</section>
