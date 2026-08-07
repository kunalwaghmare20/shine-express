<?php

declare(strict_types=1);

/**
 * Minimal test runner (no Composer / PHPUnit).
 *   php tests/run.php
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

require APP_PATH . '/Helpers/functions.php';
require APP_PATH . '/Core/Autoloader.php';
App\Core\Autoloader::register(APP_PATH);

use App\Helpers\BookingStatus;

$passed = 0;
$failed = 0;

function assert_true(bool $cond, string $message): void
{
    global $passed, $failed;
    if ($cond) {
        echo "PASS  {$message}\n";
        $passed++;
    } else {
        echo "FAIL  {$message}\n";
        $failed++;
    }
}

assert_true(BookingStatus::canTransition(BookingStatus::PENDING, BookingStatus::CONFIRMED), 'PENDING → CONFIRMED');
assert_true(BookingStatus::canTransition(BookingStatus::PENDING, BookingStatus::CANCELLED), 'PENDING → CANCELLED');
assert_true(!BookingStatus::canTransition(BookingStatus::PENDING, BookingStatus::COMPLETED), 'PENDING ✗ COMPLETED');
assert_true(BookingStatus::canTransition(BookingStatus::STARTED, BookingStatus::COMPLETED), 'STARTED → COMPLETED');
assert_true(BookingStatus::canTransition(BookingStatus::REJECTED, BookingStatus::ASSIGNED), 'REJECTED → ASSIGNED');
assert_true(BookingStatus::TRANSITIONS[BookingStatus::COMPLETED] === [], 'COMPLETED terminal');
assert_true(BookingStatus::label(BookingStatus::ON_THE_WAY) === 'On The Way', 'label ON_THE_WAY');

assert_true(slugify('House Cleaning') === 'house-cleaning', 'slugify');
assert_true(strlen(generate_id()) === 36, 'generate_id length');
assert_true(money_format_inr(1499) === '₹1,499.00' || str_contains(money_format_inr(1499), '1,499'), 'money format');

$roles = require APP_PATH . '/Config/roles.php';
assert_true(($roles['role_permissions']['SUPER_ADMIN'] ?? null) === '*', 'super admin wildcard');
assert_true(in_array('create:booking', $roles['role_permissions']['CUSTOMER'], true), 'customer can book');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
