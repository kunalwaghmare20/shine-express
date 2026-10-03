<section class="page-head">
    <div>
        <h1>Testimonials</h1>
        <p class="muted">Large quotes on the public home page. Inactive items stay hidden.</p>
    </div>
</section>
<?php if (empty($cmsReady)): ?>
<div class="alert alert-error">Run migration <code>010_website_cms.sql</code> first.</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/website/testimonials')) ?>" class="stack-form panel" enctype="multipart/form-data" style="margin-bottom:1rem">
    <?= csrf_field() ?>
    <h2>Add testimonial</h2>
    <label>Quote<textarea name="quote" rows="3" required></textarea></label>
    <div class="grid-2">
        <label>Name<input name="name" required></label>
        <label>Area<input name="area" placeholder="Koregaon Park"></label>
        <label>Rating (1–5)<input type="number" name="rating" min="1" max="5" value="5"></label>
        <label>Sort order<input type="number" name="sort_order" value="0"></label>
    </div>
    <label>Photo (optional)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
    <label class="form-switch">
        <input type="checkbox" name="is_active" value="1" checked>
        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
        <span class="form-switch-label"><strong>Active</strong></span>
    </label>
    <div class="form-actions"><button class="btn" type="submit">Add</button></div>
</form>

<?php foreach ($items ?? [] as $item): ?>
<form method="post" action="<?= e(url('/admin/website/testimonials/' . $item['id'])) ?>" class="stack-form panel" enctype="multipart/form-data" style="margin-bottom:1rem">
    <?= csrf_field() ?>
    <div class="toolbar" style="justify-content:space-between">
        <strong><?= e((string) $item['name']) ?></strong>
        <button form="del-<?= e($item['id']) ?>" class="btn btn-sm btn-ghost" type="submit" onclick="return confirm('Remove this testimonial?');">Delete</button>
    </div>
    <?php if (($item['photo_url'] ?? '') !== ''): ?>
        <img src="<?= e((string) $item['photo_url']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:50%">
    <?php endif; ?>
    <label>Quote<textarea name="quote" rows="3" required><?= e((string) $item['quote']) ?></textarea></label>
    <div class="grid-2">
        <label>Name<input name="name" required value="<?= e((string) $item['name']) ?>"></label>
        <label>Area<input name="area" value="<?= e((string) ($item['area'] ?? '')) ?>"></label>
        <label>Rating<input type="number" name="rating" min="1" max="5" value="<?= e((string) ($item['rating'] ?? 5)) ?>"></label>
        <label>Sort<input type="number" name="sort_order" value="<?= e((string) ($item['sort_order'] ?? 0)) ?>"></label>
    </div>
    <label>Replace photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
    <label class="form-switch">
        <input type="checkbox" name="is_active" value="1" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
        <span class="form-switch-label"><strong>Active</strong></span>
    </label>
    <div class="form-actions"><button class="btn btn-sm" type="submit">Save</button></div>
</form>
<form id="del-<?= e($item['id']) ?>" method="post" action="<?= e(url('/admin/website/testimonials/' . $item['id'] . '/delete')) ?>">
    <?= csrf_field() ?>
</form>
<?php endforeach; ?>
