<?php
/** @var array<string, mixed> $site */
$r = is_array($site['raw'] ?? null) ? $site['raw'] : [];
?>
<section class="page-head">
    <div>
        <h1>Website settings</h1>
        <p class="muted">Copy, photos, trust stats, and SEO for the public Shine Express site. The operations dashboard is unchanged.</p>
    </div>
    <a class="btn btn-sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website</a>
</section>

<?php if (empty($tableReady)): ?>
<div class="alert alert-error">Run <code>009_app_settings.sql</code> before saving.</div>
<?php endif; ?>
<?php if (empty($cmsReady)): ?>
<div class="alert alert-error">Run <code>database/migrations/010_website_cms.sql</code> (or the catch-up file) for pages, gallery, testimonials, and enquiries.</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/website/settings')) ?>" class="stack-form" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Hero</h2>
        <label>Eyebrow<input name="WEBSITE_HERO_EYEBROW" value="<?= e((string) ($r['WEBSITE_HERO_EYEBROW'] ?? '')) ?>"></label>
        <label>Headline<textarea name="WEBSITE_HERO_HEADLINE" rows="2"><?= e((string) ($r['WEBSITE_HERO_HEADLINE'] ?? '')) ?></textarea></label>
        <label>Subcopy<textarea name="WEBSITE_HERO_SUBCOPY" rows="3"><?= e((string) ($r['WEBSITE_HERO_SUBCOPY'] ?? '')) ?></textarea></label>
        <div class="grid-2">
            <label>Primary button<input name="WEBSITE_CTA_PRIMARY" value="<?= e((string) ($r['WEBSITE_CTA_PRIMARY'] ?? '')) ?>"></label>
            <label>WhatsApp button<input name="WEBSITE_CTA_WHATSAPP" value="<?= e((string) ($r['WEBSITE_CTA_WHATSAPP'] ?? '')) ?>"></label>
        </div>
        <label>Hero image
            <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
            <span class="form-hint">JPEG / PNG / WebP, max 4 MB. Leave empty to keep the current image.</span>
        </label>
        <?php if (($site['hero_image'] ?? '') !== ''): ?>
            <img src="<?= e((string) $site['hero_image']) ?>" alt="" style="max-width:280px;border-radius:12px">
        <?php endif; ?>
        <label>Logo
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if (($site['logo'] ?? '') !== ''): ?>
            <img src="<?= e((string) $site['logo']) ?>" alt="" style="max-height:48px">
        <?php endif; ?>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Trust row</h2>
        <div class="grid-2">
            <label>Stat 1 value<input name="WEBSITE_TRUST_1_VALUE" value="<?= e((string) ($r['WEBSITE_TRUST_1_VALUE'] ?? '')) ?>"></label>
            <label>Stat 1 label<input name="WEBSITE_TRUST_1_LABEL" value="<?= e((string) ($r['WEBSITE_TRUST_1_LABEL'] ?? '')) ?>"></label>
            <label>Stat 2 value<input name="WEBSITE_TRUST_2_VALUE" value="<?= e((string) ($r['WEBSITE_TRUST_2_VALUE'] ?? '')) ?>"></label>
            <label>Stat 2 label<input name="WEBSITE_TRUST_2_LABEL" value="<?= e((string) ($r['WEBSITE_TRUST_2_LABEL'] ?? '')) ?>"></label>
            <label>Stat 3 value<input name="WEBSITE_TRUST_3_VALUE" value="<?= e((string) ($r['WEBSITE_TRUST_3_VALUE'] ?? '')) ?>"></label>
            <label>Stat 3 label<input name="WEBSITE_TRUST_3_LABEL" value="<?= e((string) ($r['WEBSITE_TRUST_3_LABEL'] ?? '')) ?>"></label>
        </div>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>How it works</h2>
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <label>Step <?= $i ?> title<input name="WEBSITE_HOW_<?= $i ?>_TITLE" value="<?= e((string) ($r['WEBSITE_HOW_' . $i . '_TITLE'] ?? '')) ?>"></label>
            <label>Step <?= $i ?> copy<textarea name="WEBSITE_HOW_<?= $i ?>_BODY" rows="2"><?= e((string) ($r['WEBSITE_HOW_' . $i . '_BODY'] ?? '')) ?></textarea></label>
        <?php endfor; ?>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>About values</h2>
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <label>Value <?= $i ?> title<input name="WEBSITE_VALUE_<?= $i ?>_TITLE" value="<?= e((string) ($r['WEBSITE_VALUE_' . $i . '_TITLE'] ?? '')) ?>"></label>
            <label>Value <?= $i ?> copy<textarea name="WEBSITE_VALUE_<?= $i ?>_BODY" rows="2"><?= e((string) ($r['WEBSITE_VALUE_' . $i . '_BODY'] ?? '')) ?></textarea></label>
        <?php endfor; ?>
        <label>About photo
            <input type="file" name="about_photo" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if (($site['about_photo'] ?? '') !== ''): ?>
            <img src="<?= e((string) $site['about_photo']) ?>" alt="" style="max-width:280px;border-radius:12px">
        <?php endif; ?>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>Contact &amp; footer</h2>
        <label>Address<textarea name="WEBSITE_CONTACT_ADDRESS" rows="3"><?= e((string) ($r['WEBSITE_CONTACT_ADDRESS'] ?? '')) ?></textarea></label>
        <label>Hours<input name="WEBSITE_CONTACT_HOURS" value="<?= e((string) ($r['WEBSITE_CONTACT_HOURS'] ?? '')) ?>"></label>
        <label>Footer blurb<textarea name="WEBSITE_FOOTER_BLURB" rows="2"><?= e((string) ($r['WEBSITE_FOOTER_BLURB'] ?? '')) ?></textarea></label>
        <label>WhatsApp prefill<textarea name="WEBSITE_WA_PREFILL" rows="2"><?= e((string) ($r['WEBSITE_WA_PREFILL'] ?? '')) ?></textarea></label>
        <label>Book band headline<input name="WEBSITE_BOOK_HEADLINE" value="<?= e((string) ($r['WEBSITE_BOOK_HEADLINE'] ?? '')) ?>"></label>
        <label>Book band copy<textarea name="WEBSITE_BOOK_LEDE" rows="2"><?= e((string) ($r['WEBSITE_BOOK_LEDE'] ?? '')) ?></textarea></label>
        <div class="grid-2">
            <label>Instagram URL<input name="WEBSITE_SOCIAL_INSTAGRAM" value="<?= e((string) ($r['WEBSITE_SOCIAL_INSTAGRAM'] ?? '')) ?>"></label>
            <label>Facebook URL<input name="WEBSITE_SOCIAL_FACEBOOK" value="<?= e((string) ($r['WEBSITE_SOCIAL_FACEBOOK'] ?? '')) ?>"></label>
            <label>Play Store URL<input name="WEBSITE_PLAY_STORE" value="<?= e((string) ($r['WEBSITE_PLAY_STORE'] ?? '')) ?>"></label>
            <label>App Store URL<input name="WEBSITE_APP_STORE" value="<?= e((string) ($r['WEBSITE_APP_STORE'] ?? '')) ?>"></label>
        </div>
    </section>

    <section class="panel" style="margin-bottom:1rem">
        <h2>SEO &amp; intros</h2>
        <label>Default title<input name="WEBSITE_SEO_TITLE" value="<?= e((string) ($r['WEBSITE_SEO_TITLE'] ?? '')) ?>"></label>
        <label>Default meta description<textarea name="WEBSITE_SEO_DESCRIPTION" rows="2"><?= e((string) ($r['WEBSITE_SEO_DESCRIPTION'] ?? '')) ?></textarea></label>
        <label>Services intro<textarea name="WEBSITE_SERVICES_INTRO" rows="2"><?= e((string) ($r['WEBSITE_SERVICES_INTRO'] ?? '')) ?></textarea></label>
        <label>Gallery intro<textarea name="WEBSITE_GALLERY_INTRO" rows="2"><?= e((string) ($r['WEBSITE_GALLERY_INTRO'] ?? '')) ?></textarea></label>
        <label>Contact intro<textarea name="WEBSITE_CONTACT_INTRO" rows="2"><?= e((string) ($r['WEBSITE_CONTACT_INTRO'] ?? '')) ?></textarea></label>
    </section>

    <div class="form-actions">
        <button class="btn" type="submit">Save website settings</button>
    </div>
</form>
