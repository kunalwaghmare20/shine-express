<?php
/** @var array<string, mixed>|null $page */
$isEdit = is_array($page ?? null);
$action = $isEdit ? url('/admin/website/pages/' . $page['id']) : url('/admin/website/pages');
$lockedSlug = $isEdit && ($page['slug'] ?? '') === 'about';
?>
<form method="post" action="<?= e($action) ?>" class="stack-form panel">
    <?= csrf_field() ?>
    <label>Title<input name="title" required value="<?= e($isEdit ? (string) $page['title'] : '') ?>"></label>
    <label>Slug
        <input name="slug" value="<?= e($isEdit ? (string) $page['slug'] : '') ?>" <?= $lockedSlug ? 'readonly' : '' ?>>
        <span class="form-hint"><?= $lockedSlug ? 'About always uses /about.' : 'Used in /p/{slug}. Leave blank to generate from the title.' ?></span>
    </label>
    <label>Body<textarea name="body" rows="12"><?= e($isEdit ? (string) ($page['body'] ?? '') : '') ?></textarea></label>
    <label>SEO title<input name="seo_title" value="<?= e($isEdit ? (string) ($page['seo_title'] ?? '') : '') ?>"></label>
    <label>SEO description<textarea name="seo_description" rows="2"><?= e($isEdit ? (string) ($page['seo_description'] ?? '') : '') ?></textarea></label>
    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Save page' : 'Create page' ?></button>
        <a class="btn btn-ghost" href="<?= e(url('/admin/website/pages')) ?>">Cancel</a>
    </div>
</form>
