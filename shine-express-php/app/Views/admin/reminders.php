<section class="page-head">
    <div>
        <h1>WhatsApp rebook reminders</h1>
        <p class="muted">
            After a service is completed, customers are reminded (per service days) to book their next appointment.
            Business WhatsApp: <strong><?= e($adminWhatsApp) ?></strong>
        </p>
    </div>
</section>

<div class="panel" style="margin-bottom:1rem">
    <p><strong>Status:</strong> <?= $enabled ? 'Enabled' : 'Disabled' ?> · Provider: <code><?= e($provider) ?></code></p>
    <p class="muted">Set reminder days on each service under <a href="<?= e(url('/admin/services')) ?>">Services</a> (Add / Edit).</p>
    <form method="post" action="<?= e(url('/admin/reminders/run')) ?>">
        <?= csrf_field() ?>
        <button class="btn" type="submit">Send due rebook reminders now</button>
    </form>
</div>

<section class="panel" style="margin-bottom:1rem">
    <h2>Due today</h2>
    <p class="muted">Completed bookings whose service reminder window falls on today.</p>
    <table class="table">
        <thead>
        <tr>
            <th>Booking</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Service</th>
            <th>Reminder days</th>
            <th>Completed</th>
            <th>WhatsApp</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($due as $r): ?>
            <tr>
                <td><?= e($r['booking_number']) ?></td>
                <td><?= e(trim(($r['customer_first_name'] ?? '') . ' ' . ($r['customer_last_name'] ?? ''))) ?></td>
                <td><?= e($r['customer_phone'] ?: '—') ?></td>
                <td><?= e($r['service_name']) ?></td>
                <td><?= e((string) $r['reminder_days']) ?></td>
                <td><?= e($r['completed_at'] ? substr((string) $r['completed_at'], 0, 10) : (string) $r['scheduled_date']) ?></td>
                <td><?= $r['whatsapp_reminder_sent_at'] ? 'Sent' : 'Pending' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($due === []): ?>
            <tr><td colspan="7" class="muted">No rebook reminders due today</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="panel" style="margin-bottom:1rem">
    <h2>Service reminder settings</h2>
    <table class="table">
        <thead><tr><th>Service</th><th>Days after completion</th><th>Active</th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
            <tr>
                <td><?= e($s['name']) ?></td>
                <td><?= (int) $s['reminder_days'] > 0 ? e((string) $s['reminder_days']) . ' days' : 'Disabled' ?></td>
                <td><?= !empty($s['is_active']) ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($services === []): ?>
            <tr><td colspan="3" class="muted">Run migration 005 or add services first</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Recent WhatsApp logs</h2>
    <table class="table">
        <thead>
        <tr><th>Time</th><th>Phone</th><th>Status</th><th>Provider</th><th>Message</th></tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= e($log['created_at']) ?></td>
                <td><?= e($log['phone']) ?></td>
                <td><?= e($log['status']) ?></td>
                <td><?= e($log['provider']) ?></td>
                <td class="muted"><?= e(mb_substr((string) $log['message'], 0, 80)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($logs === []): ?>
            <tr><td colspan="5" class="muted">No logs yet</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
