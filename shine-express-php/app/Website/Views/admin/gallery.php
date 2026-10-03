<section class="page-head">
    <div>
        <h1>Gallery</h1>
        <p class="muted">JPEG / PNG / WebP. Tick “Show on home” for the homepage teaser grid.</p>
    </div>
</section>
<?php if (empty($cmsReady)): ?>
<div class="alert alert-error">Run migration <code>010_website_cms.sql</code> first.</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/website/gallery')) ?>" class="stack-form panel" enctype="multipart/form-data" style="margin-bottom:1rem">
    <?= csrf_field() ?>
    <h2>Add photo</h2>
    <label>Image<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
    <label>Caption<input name="caption"></label>
    <div class="grid-2">
        <label>Sort order<input type="number" name="sort_order" value="0"></label>
        <label class="form-switch">
            <input type="checkbox" name="show_on_home" value="1" checked>
            <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
            <span class="form-switch-label"><strong>Show on home</strong></span>
        </label>
    </div>
    <div class="form-actions">
        <button class="btn" type="submit">Upload</button>
    </div>
</form>

<div class="table-wrap">
<table>
    <thead><tr><th>Photo</th><th>Caption</th><th>Sort</th><th>Home</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items ?? [] as $item): ?>
        <tr>
            <td>
                <?php if (($item['url'] ?? '') !== ''): ?>
                    <img src="<?= e((string) $item['url']) ?>" alt="" style="width:88px;height:64px;object-fit:cover;border-radius:8px">
                <?php endif; ?>
            </td>
            <td colspan="4">
                <form method="post" action="<?= e(url('/admin/website/gallery/' . $item['id'])) ?>" class="inline-form" style="display:flex;gap:0.6rem;flex-wrap:wrap;align-items:center">
                    <?= csrf_field() ?>
                    <input name="caption" value="<?= e((string) ($item['caption'] ?? '')) ?>" placeholder="Caption">
                    <input type="number" name="sort_order" value="<?= e((string) ($item['sort_order'] ?? 0)) ?>" style="width:5rem">
                    <label class="form-switch" style="margin:0">
                        <input type="checkbox" name="show_on_home" value="1" <?= !empty($item['show_on_home']) ? 'checked' : '' ?>>
                        <span class="form-switch-track"><span class="form-switch-thumb"></span></span>
                        <span class="form-switch-label"><strong>Home</strong></span>
                    </label>
                    <button class="btn btn-sm" type="submit">Save</button>
                </form>
                <form method="post" action="<?= e(url('/admin/website/gallery/' . $item['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Remove this photo?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (($items ?? []) === []): ?>
        <tr><td colspan="5" class="muted">No photos yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
