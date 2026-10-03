<?php
$heroStyle = ($site['hero_image'] ?? '') !== ''
    ? '--mk-hero-image: url(\'' . e((string) $site['hero_image']) . '\')'
    : '';
?>
<section class="mk-hero" style="<?= $heroStyle ?>">
    <div class="mk-wrap mk-hero-inner mk-reveal">
        <?php if (($site['hero_eyebrow'] ?? '') !== ''): ?>
            <p class="mk-eyebrow"><?= e((string) $site['hero_eyebrow']) ?></p>
        <?php endif; ?>
        <h1><?= e((string) $site['hero_headline']) ?></h1>
        <p><?= e((string) $site['hero_subcopy']) ?></p>
        <div class="mk-hero-actions">
            <a class="mk-btn mk-btn-primary" href="<?= e($bookHref) ?>"><?= e((string) $site['cta_primary']) ?></a>
            <a class="mk-btn mk-btn-ghost" href="<?= e($waHref) ?>" target="_blank" rel="noopener"><?= e((string) $site['cta_whatsapp']) ?></a>
        </div>
    </div>
</section>

<div class="mk-wrap">
    <div class="mk-trust mk-reveal">
        <?php foreach ($site['trust'] as $stat): ?>
            <article>
                <strong><?= e((string) $stat['value']) ?></strong>
                <span><?= e((string) $stat['label']) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<section class="mk-section">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <p class="mk-eyebrow">Services</p>
        <h2>Care for the rooms that matter</h2>
        <p class="mk-lede"><?= e((string) $site['services_intro']) ?></p>
        <div class="mk-grid-3">
            <?php foreach ($featured as $svc): ?>
                <a class="mk-service-card" href="<?= e(url('/services/' . $svc['slug'])) ?>">
                    <?php if (($svc['cover_url'] ?? '') !== ''): ?>
                        <img class="mk-service-media" src="<?= e((string) $svc['cover_url']) ?>" alt="">
                    <?php else: ?>
                        <div class="mk-service-media"></div>
                    <?php endif; ?>
                    <div class="mk-service-body">
                        <p class="mk-meta"><?= e((string) $svc['category_name']) ?></p>
                        <h3><?= e((string) $svc['name']) ?></h3>
                        <p class="mk-price">From <?= e((string) $svc['from_price']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($featured === []): ?>
            <p class="mk-empty">Services will appear here once they are marked active.</p>
        <?php endif; ?>
        <p style="margin-top:1.5rem"><a class="mk-btn mk-btn-ghost" href="<?= e(url('/services')) ?>">All services</a></p>
    </div>
</section>

<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <p class="mk-eyebrow">How it works</p>
        <h2>Three quiet steps</h2>
        <div class="mk-grid-3">
            <?php foreach ($site['how'] as $i => $step): ?>
                <article class="mk-step">
                    <div class="mk-step-num">0<?= $i + 1 ?></div>
                    <h3><?= e((string) $step['title']) ?></h3>
                    <p class="mk-meta"><?= e((string) $step['body']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($offers !== []): ?>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <p class="mk-eyebrow">Offers</p>
        <h2>Current care notes</h2>
        <div class="mk-grid-2">
            <?php foreach ($offers as $offer): ?>
                <article class="mk-offer-card">
                    <h3><?= e((string) $offer['title']) ?></h3>
                    <?php if (!empty($offer['code'])): ?>
                        <p class="mk-price">Code <?= e((string) $offer['code']) ?></p>
                    <?php endif; ?>
                    <p class="mk-meta"><?= e((string) ($offer['description'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($gallery !== []): ?>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <p class="mk-eyebrow">Gallery</p>
        <h2><?= e((string) $site['gallery_intro']) ?></h2>
        <div class="mk-gallery">
            <?php foreach ($gallery as $item): ?>
                <figure>
                    <img src="<?= e((string) $item['url']) ?>" alt="<?= e((string) ($item['caption'] ?? '')) ?>">
                    <?php if (($item['caption'] ?? '') !== ''): ?>
                        <figcaption><?= e((string) $item['caption']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:1.25rem"><a class="mk-btn mk-btn-ghost" href="<?= e(url('/gallery')) ?>">Full gallery</a></p>
    </div>
</section>
<?php endif; ?>

<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <p class="mk-eyebrow">Kind words</p>
        <h2>From homes we look after</h2>
        <div class="mk-grid-2">
            <?php foreach ($testimonials as $t): ?>
                <blockquote class="mk-quote">
                    <div class="mk-stars"><?= str_repeat('★', max(1, min(5, (int) ($t['rating'] ?? 5)))) ?></div>
                    <p><?= e((string) $t['quote']) ?></p>
                    <cite><?= e((string) $t['name']) ?><?= ($t['area'] ?? '') !== '' ? ' · ' . e((string) $t['area']) : '' ?></cite>
                </blockquote>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$headline = (string) $site['book_headline'];
$lede = (string) $site['book_lede'];
require APP_PATH . '/Website/Views/public/_book_band.php';
?>
