<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow"><?= e((string) $service['category_name']) ?></p>
        <h1><?= e((string) $service['name']) ?></h1>
        <p class="mk-lede"><?= e((string) ($service['description'] ?? '')) ?></p>
        <p class="mk-price">From <?= e((string) $service['from_price']) ?> · <?= e((string) $service['duration']) ?> min</p>
        <div class="mk-hero-actions">
            <a class="mk-btn mk-btn-primary" href="<?= e($bookHref) ?>">Book this service</a>
            <a class="mk-btn mk-btn-ghost" href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
</section>

<?php if (($service['cover_url'] ?? '') !== ''): ?>
<div class="mk-wrap" style="padding-bottom:2rem">
    <img class="mk-about-photo" src="<?= e((string) $service['cover_url']) ?>" alt="">
</div>
<?php endif; ?>

<?php if (($service['items'] ?? []) !== []): ?>
<section class="mk-section-tight">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <h2>What’s included</h2>
        <div class="mk-grid-2">
            <?php foreach ($service['items'] as $item): ?>
                <article class="mk-step">
                    <h3><?= e((string) $item['name']) ?></h3>
                    <p class="mk-price"><?= e(money_format_inr($item['price'])) ?></p>
                    <?php if (!empty($item['description'])): ?>
                        <p class="mk-meta"><?= e((string) $item['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($item['duration'])): ?>
                        <p class="mk-meta"><?= e((string) $item['duration']) ?> min</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (($service['faqs'] ?? []) !== []): ?>
<section class="mk-section-tight">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <h2>Questions</h2>
        <div class="mk-faq mk-prose" style="max-width:none">
            <?php foreach ($service['faqs'] as $faq): ?>
                <details>
                    <summary><?= e((string) $faq['question']) ?></summary>
                    <p><?= e((string) $faq['answer']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$headline = 'Book ' . (string) $service['name'];
$lede = (string) $site['book_lede'];
require APP_PATH . '/Website/Views/public/_book_band.php';
?>
