<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\ServiceReminderService;
use App\Services\WhatsAppService;

final class ReminderController extends Controller
{
    public function index(): void
    {
        $wa = new WhatsAppService();
        $svc = new ServiceReminderService();
        $due = $svc->previewDue();

        $services = [];
        try {
            $services = Database::connection()->query(
                'SELECT name, reminder_days, is_active FROM services ORDER BY name'
            )->fetchAll();
        } catch (\Throwable $e) {
            // migration 005 may be missing
        }

        $logs = [];
        try {
            $logs = Database::connection()->query(
                'SELECT * FROM whatsapp_logs ORDER BY created_at DESC LIMIT 30'
            )->fetchAll();
        } catch (\Throwable $e) {
        }

        $this->view('admin/reminders', [
            'title' => 'WhatsApp rebook reminders',
            'user' => Auth::user(),
            'enabled' => $wa->enabled(),
            'provider' => $wa->provider(),
            'adminWhatsApp' => $svc->adminWhatsApp(),
            'due' => $due,
            'services' => $services,
            'logs' => $logs,
        ], 'layouts/dashboard');
    }

    public function run(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/reminders');
        }

        $result = (new ServiceReminderService())->sendDueReminders();
        flash_success(sprintf(
            'Rebook reminders: checked %d, sent %d, failed %d, skipped %d',
            $result['checked'],
            $result['sent'],
            $result['failed'],
            $result['skipped']
        ));
        $this->redirect('/admin/reminders');
    }
}
