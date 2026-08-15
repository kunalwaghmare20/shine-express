<?php

declare(strict_types=1);

/**
 * CLI: diagnose FCM setup and optionally send a test push.
 * Usage:
 *   php database/tools/fcm_diagnose.php
 *   php database/tools/fcm_diagnose.php --user-id=<users.id>
 */

require dirname(__DIR__, 2) . '/public/bootstrap_cli.php';

use App\Core\Database;
use App\Services\FcmService;

$fcm = new FcmService();
$status = $fcm->setupStatus();

echo "=== FCM diagnose ===\n";
echo 'Enabled: ' . ($status['enabled'] ? 'yes' : 'no') . "\n";
echo 'Reason: ' . ($status['reason'] ?? 'OK') . "\n";
echo 'Service account file: ' . (is_file(STORAGE_PATH . '/fcm-service-account.json') ? 'found' : 'missing') . "\n";
echo 'Logs dir writable: ' . (is_writable(STORAGE_PATH . '/logs') || is_writable(STORAGE_PATH) ? 'yes' : 'no') . "\n";

try {
    $db = Database::connection();
    $tokenCount = (int) $db->query('SELECT COUNT(*) FROM device_tokens')->fetchColumn();
    $userCount = (int) $db->query('SELECT COUNT(DISTINCT user_id) FROM device_tokens')->fetchColumn();
    echo "Device tokens: {$tokenCount} ({$userCount} user(s))\n";

    $stmt = $db->query(
        'SELECT u.email, u.id AS user_id, COUNT(dt.id) AS devices
         FROM device_tokens dt
         JOIN users u ON u.id = dt.user_id
         GROUP BY u.id, u.email
         ORDER BY u.email
         LIMIT 10'
    );
    echo "Registered users:\n";
    foreach ($stmt->fetchAll() as $row) {
        echo '  - ' . $row['email'] . ' (' . $row['devices'] . " device(s), user_id={$row['user_id']})\n";
    }
} catch (Throwable $e) {
    echo 'Database error: ' . $e->getMessage() . "\n";
    exit(1);
}

$userId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--user-id=')) {
        $userId = substr($arg, strlen('--user-id='));
    }
}

if ($userId === null || $userId === '') {
    echo "\nTip: php database/tools/fcm_diagnose.php --user-id=<users.id>\n";
    exit($status['enabled'] ? 0 : 1);
}

if (!$status['enabled']) {
    echo "\nCannot send test push — FCM not enabled.\n";
    exit(1);
}

$stmt = Database::connection()->prepare('SELECT token FROM device_tokens WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$row = $stmt->fetch();
if ($row === false) {
    echo "\nNo device token for user_id={$userId}. Log in on the mobile app first.\n";
    exit(1);
}

echo "\nSending test push to user_id={$userId}...\n";
$ok = $fcm->sendToToken((string) $row['token'], 'Shine Express test', 'If you see this, FCM is working.', ['type' => 'TEST']);
echo $ok ? "Test push: SENT (check phone + storage/logs/fcm.log)\n" : "Test push: FAILED (see storage/logs/fcm.log)\n";
exit($ok ? 0 : 1);
