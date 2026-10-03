<?php
$bookHref = $bookHref ?? (\App\Core\Auth::role() === 'CUSTOMER' ? url('/book') : url('/register'));
$waHref = $waHref ?? '#';
$headline = $headline ?? 'Ready for a quieter, cleaner home?';
$lede = $lede ?? 'Book in the Shine Express app, or message us on WhatsApp — we will take it from there.';
?>
<section class="mk-band">
    <div class="mk-wrap">
        <hr class="mk-gold-rule">
        <h2><?= e($headline) ?></h2>
        <p class="mk-lede"><?= e($lede) ?></p>
        <div class="mk-band-actions">
            <a class="mk-btn mk-btn-primary" href="<?= e($bookHref) ?>">Book in the app</a>
            <a class="mk-btn mk-btn-whatsapp" href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp us</a>
        </div>
    </div>
</section>
