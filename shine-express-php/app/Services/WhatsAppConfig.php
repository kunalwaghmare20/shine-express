<?php

declare(strict_types=1);

namespace App\Services;

/**
 * WhatsApp runtime config: database (app_settings) first, then .env.
 */
final class WhatsAppConfig
{
    public const DEFAULT_SUPPORT = '919673522737';

    public const DEFAULT_REBOOK_MESSAGE = "Hello {name},\n\nThank you for choosing Shine Express for your *{service}* service (booking {booking}).\n\nIt has been {days} days since your last service — now is a great time to book your *next appointment* so your space stays fresh and protected.\n\nReply on WhatsApp or message us at {admin_whatsapp} to schedule:\n{wa_link}\n\n— Shine Express";

    public const SECRET_KEYS = [
        'WHATSAPP_ACCESS_TOKEN',
        'WHATSAPP_WEBHOOK_TOKEN',
    ];

    public static function get(string $key, mixed $default = ''): string
    {
        return (string) SettingService::get($key, $default);
    }

    public static function enabled(): bool
    {
        return filter_var(self::get('WHATSAPP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function provider(): string
    {
        $provider = strtolower(trim(self::get('WHATSAPP_PROVIDER', 'log')));
        return in_array($provider, ['log', 'cloud', 'webhook'], true) ? $provider : 'log';
    }

    public static function supportWhatsApp(): string
    {
        $value = preg_replace('/\D+/', '', self::get('SUPPORT_WHATSAPP', self::DEFAULT_SUPPORT)) ?? '';
        return $value !== '' ? $value : self::DEFAULT_SUPPORT;
    }

    public static function accessToken(): string
    {
        return trim(self::get('WHATSAPP_ACCESS_TOKEN', ''));
    }

    public static function phoneNumberId(): string
    {
        return trim(self::get('WHATSAPP_PHONE_NUMBER_ID', ''));
    }

    public static function reminderTemplateName(): string
    {
        return trim(self::get('WHATSAPP_TEMPLATE_NAME', ''));
    }

    public static function templateLang(): string
    {
        $lang = trim(self::get('WHATSAPP_TEMPLATE_LANG', 'en'));
        return $lang !== '' ? $lang : 'en';
    }

    public static function webhookUrl(): string
    {
        return trim(self::get('WHATSAPP_WEBHOOK_URL', ''));
    }

    public static function webhookToken(): string
    {
        return trim(self::get('WHATSAPP_WEBHOOK_TOKEN', ''));
    }

    public static function broadcastTemplateName(): string
    {
        return trim(self::get('WHATSAPP_BROADCAST_TEMPLATE_NAME', ''));
    }

    public static function broadcastTemplateLang(): string
    {
        $lang = trim(self::get('WHATSAPP_BROADCAST_TEMPLATE_LANG', ''));
        return $lang !== '' ? $lang : self::templateLang();
    }

    public static function broadcastTemplateParams(): string
    {
        $spec = trim(self::get('WHATSAPP_BROADCAST_TEMPLATE_PARAMS', 'first_name,message'));
        return $spec !== '' ? $spec : 'first_name,message';
    }

    public static function broadcastDefault(): string
    {
        return trim(self::get('WHATSAPP_BROADCAST_DEFAULT', ''));
    }

    public static function rebookMessage(): string
    {
        $message = self::get('WHATSAPP_REBOOK_MESSAGE', self::DEFAULT_REBOOK_MESSAGE);
        return $message !== '' ? $message : self::DEFAULT_REBOOK_MESSAGE;
    }

    public static function maskSecret(string $value): string
    {
        $value = trim($value);
        $len = strlen($value);
        if ($len === 0) {
            return '';
        }
        if ($len <= 8) {
            return '•••• saved';
        }
        return '••••' . substr($value, -4);
    }

    /**
     * Effective values for the settings form (DB or .env fallback).
     *
     * @return array<string, string>
     */
    public static function formValues(): array
    {
        return [
            'WHATSAPP_ENABLED' => self::enabled() ? 'true' : 'false',
            'WHATSAPP_PROVIDER' => self::provider(),
            'SUPPORT_WHATSAPP' => self::supportWhatsApp(),
            'WHATSAPP_ACCESS_TOKEN' => self::accessToken(),
            'WHATSAPP_PHONE_NUMBER_ID' => self::phoneNumberId(),
            'WHATSAPP_TEMPLATE_NAME' => self::reminderTemplateName(),
            'WHATSAPP_TEMPLATE_LANG' => self::templateLang(),
            'WHATSAPP_WEBHOOK_URL' => self::webhookUrl(),
            'WHATSAPP_WEBHOOK_TOKEN' => self::webhookToken(),
            'WHATSAPP_BROADCAST_TEMPLATE_NAME' => self::broadcastTemplateName(),
            'WHATSAPP_BROADCAST_TEMPLATE_LANG' => self::broadcastTemplateLang(),
            'WHATSAPP_BROADCAST_TEMPLATE_PARAMS' => self::broadcastTemplateParams(),
            'WHATSAPP_BROADCAST_DEFAULT' => self::broadcastDefault(),
            'WHATSAPP_REBOOK_MESSAGE' => self::rebookMessage(),
        ];
    }
}
