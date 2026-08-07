<section class="hero">
    <p class="eyebrow">Shared hosting ready</p>
    <h1><?= e($headline ?? 'Shine Express') ?></h1>
    <p class="lede"><?= e($tagline ?? '') ?></p>
    <div class="actions">
        <a class="btn" href="<?= e(url('/login')) ?>">Sign in</a>
        <a class="btn btn-ghost" href="<?= e(url('/register')) ?>">Register</a>
        <a class="btn btn-ghost" href="<?= e(url('/health')) ?>">Health</a>
    </div>
    <ul class="meta">
        <li>Custom PHP MVC + OOP</li>
        <li>MySQL / MariaDB via PDO</li>
        <li>No Composer required</li>
    </ul>
</section>
