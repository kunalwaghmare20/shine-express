<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow">Gallery</p>
        <h1>After care</h1>
        <p class="mk-lede"><?= e((string) $site['gallery_intro']) ?></p>
    </div>
</section>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap">
        <?php if ($gallery === []): ?>
            <p class="mk-empty">Photos will appear here once they are added in the dashboard.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</section>
