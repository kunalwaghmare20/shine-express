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
    $relative = 'assets/' . ltrim($path, '/');
    $url = url($relative);
    $file = (defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(__DIR__) . '/public') . '/' . $relative;
    if (is_file($file)) {
        $url .= '?v=' . (string) filemtime($file);
    }
    return $url;
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

/**
 * Inline SVG icons for admin forms (stroke icons, currentColor).
 */
function ui_icon(string $name): string
{
    $stroke = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
    $icons = [
        'users' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'user-check' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>',
        'star' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'check-circle' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'send' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'package' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>',
        'map-pin' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'alert' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'toggle' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" ' . $stroke . '><rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="8" cy="12" r="3"/></svg>',
        'whatsapp' => '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.6-.8-1.9-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-1.6-.8-2.7-1.5-3.7-3.4-.2-.4.2-.4.6-1.3.1-.2 0-.4 0-.5l-.8-2c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.8.8-1.1 2-.9 3.1.3 1.8 1.4 3.5 4.1 5.4 3.2 2.1 4.4 1.8 5.2 1.7.8-.1 1.6-.7 1.8-1.3.2-.6.2-1.2.1-1.3-.1-.2-.3-.2-.6-.3z"/><path d="M12.1 2C6.6 2 2.1 6.5 2.1 12c0 1.8.5 3.4 1.3 4.9L2 22l5.2-1.4c1.4.8 3 1.2 4.8 1.2 5.5 0 10-4.5 10-10S17.6 2 12.1 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.1.8.8-3-.2-.3c-.8-1.4-1.3-2.9-1.3-4.5 0-4.5 3.7-8.2 8.2-8.2s8.2 3.7 8.2 8.2-3.7 8.4-8.2 8.4z"/></svg>',
    ];

    return $icons[$name] ?? '';
}
