<section class="page-head">
    <div>
        <h1>Website pages</h1>
        <p class="muted">About lives at <code>/about</code>. Extra pages are public at <code>/p/{slug}</code>.</p>
    </div>
    <a class="btn btn-sm" href="<?= e(url('/admin/website/pages/create')) ?>">Add page</a>
</section>
<?php if (empty($cmsReady)): ?>
<div class="alert alert-error">Run migration <code>010_website_cms.sql</code> first.</div>
<?php endif; ?>
<div class="table-wrap">
<table>
    <thead><tr><th>Title</th><th>Slug</th><th>Public URL</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pages ?? [] as $p): ?>
        <?php $href = ($p['slug'] ?? '') === 'about' ? '/about' : '/p/' . $p['slug']; ?>
        <tr>
            <td><?= e((string) $p['title']) ?></td>
            <td><code><?= e((string) $p['slug']) ?></code></td>
            <td><a href="<?= e(url($href)) ?>" target="_blank" rel="noopener"><?= e($href) ?></a></td>
            <td class="actions-cell">
                <a href="<?= e(url('/admin/website/pages/' . $p['id'] . '/edit')) ?>">Edit</a>
                <?php if (($p['slug'] ?? '') !== 'about'): ?>
                    <form method="post" action="<?= e(url('/admin/website/pages/' . $p['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this page?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-ghost" style="margin:0;padding:0.2rem 0.5rem">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (($pages ?? []) === []): ?>
        <tr><td colspan="4" class="muted">No pages yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
