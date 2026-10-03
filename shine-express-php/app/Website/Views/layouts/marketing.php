<?php
use App\Core\Auth;
use App\Website\Services\WebsiteService;

$site = $site ?? (new WebsiteService())->settings();
$path = \App\Core\Request::path();
$nav = [
    ['Home', '/'],
    ['Services', '/services'],
    ['About', '/about'],
    ['Gallery', '/gallery'],
    ['Contact', '/contact'],
];
$logo = (string) ($site['logo'] ?? '');
$seoTitle = (string) ($seoTitle ?? $title ?? $site['seo_title'] ?? 'Shine Express');
$seoDesc = (string) ($seoDescription ?? $site['seo_description'] ?? '');
$ogImage = (string) ($ogImage ?? $site['hero_image'] ?? '');
$waHref = $waHref ?? ($site['whatsapp_href'] ?? '#');
$bookHref = Auth::role() === 'CUSTOMER' ? url('/book') : url('/register');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <?php if ($seoDesc !== ''): ?>
        <meta name="description" content="<?= e($seoDesc) ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= e($seoTitle) ?>">
    <?php if ($seoDesc !== ''): ?>
        <meta property="og:description" content="<?= e($seoDesc) ?>">
    <?php endif; ?>
    <?php if ($ogImage !== ''): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/marketing.css')) ?>">
</head>
<body class="mk-body">
<header class="mk-header" id="mk-header">
    <div class="mk-wrap mk-header-inner">
        <a class="mk-logo" href="<?= e(url('/')) ?>">
            <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt=""><?php endif; ?>
            Shine Express
        </a>
        <button class="mk-menu-toggle" type="button" id="mk-menu-toggle" aria-label="Menu">Menu</button>
        <nav class="mk-nav" id="mk-nav">
            <?php foreach ($nav as [$label, $href]): ?>
                <?php $active = $href === '/' ? $path === '/' : str_starts_with($path, $href); ?>
                <a class="<?= $active ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <span class="mk-nav-cta">
                <a class="mk-btn mk-btn-ghost mk-btn-sm" href="<?= e($waHref) ?>" target="_blank" rel="noopener"><?= e((string) ($site['cta_whatsapp'] ?? 'WhatsApp')) ?></a>
                <a class="mk-btn mk-btn-primary mk-btn-sm" href="<?= e($bookHref) ?>"><?= e((string) ($site['cta_primary'] ?? 'Book now')) ?></a>
            </span>
        </nav>
    </div>
</header>

<?php if ($flash = \App\Core\Session::getFlash('error')): ?>
    <div class="mk-wrap" style="padding-top:1rem"><div class="mk-alert mk-alert-error"><?= e((string) $flash) ?></div></div>
<?php endif; ?>
<?php if ($flash = \App\Core\Session::getFlash('success')): ?>
    <div class="mk-wrap" style="padding-top:1rem"><div class="mk-alert mk-alert-ok"><?= e((string) $flash) ?></div></div>
<?php endif; ?>

<?= $content ?>

<footer class="mk-footer">
    <div class="mk-wrap mk-footer-grid">
        <div>
            <h3>Shine Express</h3>
            <p><?= e((string) ($site['footer_blurb'] ?? 'Hotel-level care for homes, kitchens, and living spaces.')) ?></p>
        </div>
        <div>
            <h3>Visit</h3>
            <p><?= nl2br(e((string) ($site['contact_address'] ?? ''))) ?></p>
            <p><?= e((string) ($site['contact_hours'] ?? '')) ?></p>
            <p><?= e((string) ($site['support_phone'] ?? '')) ?></p>
            <div class="mk-social">
                <?php if (($site['social_instagram'] ?? '') !== ''): ?>
                    <a href="<?= e((string) $site['social_instagram']) ?>" target="_blank" rel="noopener">Instagram</a>
                <?php endif; ?>
                <?php if (($site['social_facebook'] ?? '') !== ''): ?>
                    <a href="<?= e((string) $site['social_facebook']) ?>" target="_blank" rel="noopener">Facebook</a>
                <?php endif; ?>
            </div>
            <div class="mk-stores">
                <?php if (($site['play_store'] ?? '') !== ''): ?>
                    <a href="<?= e((string) $site['play_store']) ?>" target="_blank" rel="noopener">Play Store</a>
                <?php endif; ?>
                <?php if (($site['app_store'] ?? '') !== ''): ?>
                    <a href="<?= e((string) $site['app_store']) ?>" target="_blank" rel="noopener">App Store</a>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <h3>Explore</h3>
            <ul>
                <li><a href="<?= e(url('/services')) ?>">Services</a></li>
                <li><a href="<?= e(url('/about')) ?>">About</a></li>
                <li><a href="<?= e(url('/gallery')) ?>">Gallery</a></li>
                <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                <li><a href="<?= e(url('/login')) ?>">Staff / customer login</a></li>
            </ul>
        </div>
    </div>
    <div class="mk-wrap mk-copy">&copy; <?= date('Y') ?> Shine Express. All rights reserved.</div>
</footer>
<script>
(function () {
  var header = document.getElementById('mk-header');
  var toggle = document.getElementById('mk-menu-toggle');
  var nav = document.getElementById('mk-nav');
  window.addEventListener('scroll', function () {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 12);
  });
  if (toggle && nav) {
    toggle.addEventListener('click', function () { nav.classList.toggle('is-open'); });
  }
})();
</script>
</body>
</html>
