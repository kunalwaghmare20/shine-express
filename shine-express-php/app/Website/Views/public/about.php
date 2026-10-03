<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow">About</p>
        <h1><?= e((string) ($page['title'] ?? 'About')) ?></h1>
    </div>
</section>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap mk-grid-2">
        <div class="mk-prose">
            <?= nl2br(e((string) ($page['body'] ?? ''))) ?>
        </div>
        <div>
            <?php if (($site['about_photo'] ?? '') !== ''): ?>
                <img class="mk-about-photo" src="<?= e((string) $site['about_photo']) ?>" alt="">
            <?php else: ?>
                <div class="mk-about-photo"></div>
            <?php endif; ?>
            <div class="mk-values" style="margin-top:1.5rem">
                <?php foreach ($site['values'] as $value): ?>
                    <article>
                        <h3><?= e((string) $value['title']) ?></h3>
                        <p class="mk-meta"><?= e((string) $value['body']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php
$headline = (string) $site['book_headline'];
$lede = (string) $site['book_lede'];
require APP_PATH . '/Website/Views/public/_book_band.php';
?>
