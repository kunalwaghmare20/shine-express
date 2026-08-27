<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Services\SettingService;
use App\Services\WhatsAppConfig;
use App\Services\WhatsAppService;

final class WhatsAppSettingsController extends Controller
{
    public function index(): void
    {
        $settings = SettingService::instance();
        $wa = new WhatsAppService();
        $values = WhatsAppConfig::formValues();
        $sources = [];
        foreach (array_keys($values) as $key) {
            $sources[$key] = $settings->source($key);
        }

        $this->view('admin/whatsapp_settings', [
            'title' => 'WhatsApp settings',
            'user' => Auth::user(),
            'values' => $values,
            'sources' => $sources,
            'tableReady' => $settings->tableReady(),
            'setup' => $wa->setupStatus(),
            'broadcastSetup' => $wa->broadcastSetupStatus(),
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/whatsapp-settings');
        }

        $settings = SettingService::instance();
        if (!$settings->tableReady()) {
            flash_error('Run database migration 009_app_settings.sql first, then save again.');
            $this->redirect('/admin/whatsapp-settings');
        }

        $provider = strtolower(trim((string) Request::input('WHATSAPP_PROVIDER', 'log')));
        if (!in_array($provider, ['log', 'cloud', 'webhook'], true)) {
            $provider = 'log';
        }

        $support = preg_replace('/\D+/', '', (string) Request::input('SUPPORT_WHATSAPP', '')) ?? '';
        $pairs = [
            'WHATSAPP_ENABLED' => Request::input('WHATSAPP_ENABLED') === '1' ? 'true' : 'false',
            'WHATSAPP_PROVIDER' => $provider,
            'SUPPORT_WHATSAPP' => $support !== '' ? $support : WhatsAppConfig::DEFAULT_SUPPORT,
            'WHATSAPP_PHONE_NUMBER_ID' => trim((string) Request::input('WHATSAPP_PHONE_NUMBER_ID', '')),
            'WHATSAPP_TEMPLATE_NAME' => trim((string) Request::input('WHATSAPP_TEMPLATE_NAME', '')),
            'WHATSAPP_TEMPLATE_LANG' => trim((string) Request::input('WHATSAPP_TEMPLATE_LANG', 'en')) ?: 'en',
            'WHATSAPP_WEBHOOK_URL' => trim((string) Request::input('WHATSAPP_WEBHOOK_URL', '')),
            'WHATSAPP_BROADCAST_TEMPLATE_NAME' => trim((string) Request::input('WHATSAPP_BROADCAST_TEMPLATE_NAME', '')),
            'WHATSAPP_BROADCAST_TEMPLATE_LANG' => trim((string) Request::input('WHATSAPP_BROADCAST_TEMPLATE_LANG', '')),
            'WHATSAPP_BROADCAST_TEMPLATE_PARAMS' => trim((string) Request::input('WHATSAPP_BROADCAST_TEMPLATE_PARAMS', 'first_name,message')) ?: 'first_name,message',
            'WHATSAPP_BROADCAST_DEFAULT' => trim((string) Request::input('WHATSAPP_BROADCAST_DEFAULT', '')),
            'WHATSAPP_REBOOK_MESSAGE' => (string) Request::input('WHATSAPP_REBOOK_MESSAGE', ''),
        ];

        foreach (WhatsAppConfig::SECRET_KEYS as $secretKey) {
            $submitted = trim((string) Request::input($secretKey, ''));
            if ($submitted !== '') {
                $pairs[$secretKey] = $submitted;
            }
        }

        try {
            $settings->setMany($pairs, Auth::id());
            flash_success('WhatsApp settings saved. Broadcasts and reminders now use these values.');
        } catch (\Throwable $e) {
            flash_error('Could not save settings. Run migration 009_app_settings.sql on the database.');
        }

        $this->redirect('/admin/whatsapp-settings');
    }
}
