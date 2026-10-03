<section class="mk-page-hero">
    <div class="mk-wrap">
        <p class="mk-eyebrow">Contact</p>
        <h1>Say hello</h1>
        <p class="mk-lede"><?= e((string) $site['contact_intro']) ?></p>
    </div>
</section>
<section class="mk-section" style="padding-top:0">
    <div class="mk-wrap mk-grid-2">
        <div>
            <hr class="mk-gold-rule">
            <h2>Studio</h2>
            <p><?= nl2br(e((string) $site['contact_address'])) ?></p>
            <p class="mk-meta"><?= e((string) $site['contact_hours']) ?></p>
            <?php if (($site['support_phone'] ?? '') !== ''): ?>
                <p>WhatsApp <?= e((string) $site['support_phone']) ?></p>
            <?php endif; ?>
            <div class="mk-hero-actions" style="margin-top:1.2rem">
                <a class="mk-btn mk-btn-whatsapp" href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp us</a>
                <a class="mk-btn mk-btn-primary" href="<?= e($bookHref) ?>">Book in the app</a>
            </div>
        </div>
        <form method="post" action="<?= e(url('/contact')) ?>" class="mk-form">
            <?= csrf_field() ?>
            <h2>Send a note</h2>
            <label>Name<input name="name" required value="<?= e((string) old('name')) ?>"></label>
            <label>Phone<input name="phone" value="<?= e((string) old('phone')) ?>"></label>
            <label>Email<input type="email" name="email" value="<?= e((string) old('email')) ?>"></label>
            <label>Message<textarea name="message" rows="5" required><?= e((string) old('message')) ?></textarea></label>
            <button class="mk-btn mk-btn-primary" type="submit">Send enquiry</button>
        </form>
    </div>
</section>
