<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow">Services</p>
        <h1>The catalogue</h1>
        <p class="mk-lede"><?= e((string) $site['services_intro']) ?></p>
        <?php if ($grouped !== []): ?>
            <nav class="mk-cats">
                <?php foreach (array_keys($grouped) as $cat): ?>
                    <a class="mk-chip" href="#cat-<?= e(slugify($cat)) ?>"><?= e($cat) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <?php foreach ($grouped as $cat => $services): ?>
            <div class="mk-cat-block" id="cat-<?= e(slugify($cat)) ?>">
                <h2><?= e($cat) ?></h2>
                <div class="mk-grid-3">
                    <?php foreach ($services as $svc): ?>
                        <a class="mk-service-card" href="<?= e(url('/services/' . $svc['slug'])) ?>">
                            <?php if (($svc['cover_url'] ?? '') !== ''): ?>
                                <img class="mk-service-media" src="<?= e((string) $svc['cover_url']) ?>" alt="">
                            <?php else: ?>
                                <div class="mk-service-media"></div>
                            <?php endif; ?>
                            <div class="mk-service-body">
                                <h3><?= e((string) $svc['name']) ?></h3>
                                <p class="mk-price">From <?= e((string) $svc['from_price']) ?></p>
                                <p class="mk-meta"><?= e((string) $svc['duration']) ?> min</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($grouped === []): ?>
            <p class="mk-empty">No services are listed yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php
$headline = (string) $site['book_headline'];
$lede = (string) $site['book_lede'];
require APP_PATH . '/Website/Views/public/_book_band.php';
?>
