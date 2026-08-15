<h2>Sign in</h2>
<p class="muted">Use your Shine Express account</p>
<form method="post" action="<?= e(url('/login')) ?>" class="stack-form">
    <?= csrf_field() ?>
    <label>Email
        <input type="email" name="email" required value="<?= e((string) old('email')) ?>">
    </label>
    <label>Password
        <input type="password" name="password" required>
    </label>
    <div class="form-actions">
        <button class="btn" type="submit">Sign in</button>
    </div>
</form>
<p class="muted">No account? <a href="<?= e(url('/register')) ?>">Register</a></p>
<p class="muted small">Demo admin: admin@shineexpress.com / Admin@123</p>
