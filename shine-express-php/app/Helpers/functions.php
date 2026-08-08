<?php

declare(strict_types=1);

/**
 * Global helpers (loaded before autoloader classes).
 */

function env_file(string $key, mixed $default = null): mixed
{
    static $loaded = false;
    static $vars = [];

    if (!$loaded) {
        $path = BASE_PATH . '/.env';
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $v = trim($v, "\"'");
                $vars[$k] = $v;
            }
        }
        $loaded = true;
    }

    return $vars[$key] ?? $default;
}

function config(string $file): array
{
    $path = APP_PATH . '/Config/' . $file . '.php';
    /** @var array<string, mixed> $data */
    $data = require $path;
    return $data;
}

function url(string $path = '/'): string
{
    $app = config('app');
    $base = rtrim((string) ($app['url'] ?? ''), '/');
    if ($path === '' || $path === '/') {
        return $base !== '' ? $base . '/' : '/';
    }
    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    $token = App\Core\Session::get('_csrf');
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        App\Core\Session::set('_csrf', $token);
    }
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    $session = App\Core\Session::get('_csrf');
    return is_string($token) && is_string($session) && hash_equals($session, $token);
}

function redirect_to(string $path): never
{
    App\Core\Response::redirect(url($path));
}

function old(string $key, mixed $default = ''): mixed
{
    $old = App\Core\Session::getFlash('_old') ?? [];
    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

/** CUID-like unique id (36-char safe for varchar PKs). */
function generate_id(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function money_format_inr($amount): string
{
    return '₹' . number_format((float) $amount, 2);
}

function flash_success(string $message): void
{
    App\Core\Session::flash('success', $message);
}

function flash_error(string $message): void
{
    App\Core\Session::flash('error', $message);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'item';
}

/** Normalize phone to digits; prepend 91 for 10-digit Indian mobiles. */
function phone_digits(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 10) {
        return '91' . $digits;
    }
    return $digits;
}

/** Build a wa.me deep link or null when phone is missing. */
function whatsapp_link(string $phone, string $message): ?string
{
    $digits = phone_digits($phone);
    if ($digits === '') {
        return null;
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
}

/** Pre-formatted WhatsApp message for a booking row. */
function whatsapp_booking_message(array $booking): string
{
    $fullName = trim((string) ($booking['customer_name'] ?? 'Customer'));
    $firstName = trim((string) ($booking['customer_first_name'] ?? ''));
    if ($firstName === '' && $fullName !== '') {
        $firstName = explode(' ', $fullName)[0];
    }
    if ($firstName === '') {
        $firstName = 'there';
    }

    $number = (string) ($booking['booking_number'] ?? '');
    $date = trim((string) ($booking['scheduled_date'] ?? '') . ' at ' . (string) ($booking['scheduled_time'] ?? ''));
    $status = \App\Helpers\BookingStatus::label((string) ($booking['status'] ?? 'PENDING'));

    return "Hello {$firstName}, your Shine Express booking #{$number} is {$status} for {$date}. Reply here if you have any questions.";
}

/** Great-circle distance in km between two WGS84 points, or null if any coordinate missing. */
function haversine_km(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
{
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return null;
    }

    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function format_distance_km(?float $km): string
{
    if ($km === null) {
        return 'Location unknown';
    }
    if ($km < 1) {
        return round($km * 1000) . ' m away';
    }

    return round($km, 1) . ' km away';
}
