<?php

declare(strict_types=1);

/**
 * Cron: send WhatsApp before-service reminders.
 *
 * cPanel cron example (daily 9:00 AM):
 *   /usr/bin/php /home/USER/public_html/shine-express/database/cron/send_service_reminders.php
 *
 * Or HTTP (admin only): POST /admin/reminders/run
 */

require dirname(__DIR__, 2) . '/public/bootstrap_cli.php';

use App\Services\ServiceReminderService;

$service = new ServiceReminderService();
$result = $service->sendDueReminders();

$line = sprintf(
    "[%s] checked=%d sent=%d failed=%d skipped=%d\n",
    date('c'),
    $result['checked'],
    $result['sent'],
    $result['failed'],
    $result['skipped']
);

$dir = STORAGE_PATH . '/logs';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
@file_put_contents($dir . '/reminders.log', $line, FILE_APPEND);

if (PHP_SAPI === 'cli') {
    echo $line;
    foreach ($result['details'] as $d) {
        echo ' - ' . json_encode($d, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
