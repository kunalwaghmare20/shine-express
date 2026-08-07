<?php

declare(strict_types=1);

namespace App\Helpers;

final class BookingStatus
{
    public const PENDING = 'PENDING';
    public const CONFIRMED = 'CONFIRMED';
    public const ASSIGNED = 'ASSIGNED';
    public const ACCEPTED = 'ACCEPTED';
    public const ON_THE_WAY = 'ON_THE_WAY';
    public const STARTED = 'STARTED';
    public const COMPLETED = 'COMPLETED';
    public const CANCELLED = 'CANCELLED';
    public const REJECTED = 'REJECTED';

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        self::PENDING => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::ASSIGNED, self::CANCELLED],
        self::ASSIGNED => [self::ACCEPTED, self::REJECTED],
        self::ACCEPTED => [self::ON_THE_WAY, self::REJECTED],
        self::ON_THE_WAY => [self::STARTED],
        self::STARTED => [self::COMPLETED],
        self::COMPLETED => [],
        self::CANCELLED => [],
        self::REJECTED => [self::ASSIGNED],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::ON_THE_WAY => 'On The Way',
            default => ucwords(strtolower(str_replace('_', ' ', $status))),
        };
    }
}
