<section class="panel">
    <h3><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h3>
    <p class="muted"><?= e($user['email'] ?? '') ?> · <?= e((string) ($user['phone'] ?? '')) ?></p>
</section>

<section class="panel">
    <h3>Addresses</h3>
    <ul class="plain-list">
        <?php foreach ($addresses as $a): ?>
            <li><?= e($a['label'] . ' — ' . $a['line1'] . ', ' . $a['city'] . ' ' . $a['pincode']) ?></li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="<?= e(url('/profile/addresses')) ?>" class="stack-form" style="margin-top:1rem">
        <?= csrf_field() ?>
        <h4>Add address</h4>
        <div class="grid-2">
            <label>Label<input name="label" value="Home" required></label>
            <label>Pincode<input name="pincode" required></label>
        </div>
        <label>Line 1<input name="line1" required></label>
        <label>Line 2<input name="line2"></label>
        <div class="grid-2">
            <label>City<input name="city" required></label>
            <label>State<input name="state" required></label>
        </div>
        <label><input type="checkbox" name="is_default" value="1"> Default</label>
        <button class="btn btn-sm" type="submit">Save address</button>
    </form>
</section>
