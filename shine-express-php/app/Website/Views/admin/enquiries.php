<section class="page-head">
    <div>
        <h1>Website enquiries</h1>
        <p class="muted">Messages from the public contact form.</p>
    </div>
</section>
<?php if (empty($cmsReady)): ?>
<div class="alert alert-error">Run migration <code>010_website_cms.sql</code> first.</div>
<?php endif; ?>
<div class="table-wrap">
<table>
    <thead><tr><th>When</th><th>Name</th><th>Phone</th><th>Email</th><th>Message</th></tr></thead>
    <tbody>
    <?php foreach ($items ?? [] as $row): ?>
        <tr>
            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
            <td><?= e((string) $row['name']) ?></td>
            <td><?= e((string) ($row['phone'] ?? '—')) ?></td>
            <td><?= e((string) ($row['email'] ?? '—')) ?></td>
            <td><?= nl2br(e((string) $row['message'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (($items ?? []) === []): ?>
        <tr><td colspan="5" class="muted">No enquiries yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
