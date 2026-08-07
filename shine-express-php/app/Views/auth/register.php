<h2>Create account</h2>
<form method="post" action="<?= e(url('/register')) ?>" class="stack-form">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>First name<input name="first_name" required value="<?= e((string) old('first_name')) ?>"></label>
        <label>Last name<input name="last_name" required value="<?= e((string) old('last_name')) ?>"></label>
    </div>
    <label>Email<input type="email" name="email" required value="<?= e((string) old('email')) ?>"></label>
    <label>Phone<input name="phone" value="<?= e((string) old('phone')) ?>"></label>
    <label>Password<input type="password" name="password" required minlength="6"></label>
    <button class="btn" type="submit">Register</button>
</form>
<p class="muted">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
