<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow">Shine Express</p>
        <h1><?= e((string) ($page['title'] ?? '')) ?></h1>
    </div>
</section>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap mk-prose">
        <?= nl2br(e((string) ($page['body'] ?? ''))) ?>
    </div>
</section>
<?php
$headline = (string) $site['book_headline'];
$lede = (string) $site['book_lede'];
require APP_PATH . '/Website/Views/public/_book_band.php';
?>
