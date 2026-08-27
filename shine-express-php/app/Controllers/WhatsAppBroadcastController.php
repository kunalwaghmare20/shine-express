<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\WhatsAppBroadcastService;
use App\Services\WhatsAppService;
use InvalidArgumentException;

final class WhatsAppBroadcastController extends Controller
{
    public function index(): void
    {
        $svc = new WhatsAppBroadcastService();
        $wa = new WhatsAppService();
        $q = trim((string) Request::input('q', ''));

        $flashed = Session::getFlash('whatsapp_broadcast_template');
        $template = is_string($flashed) && $flashed !== '' ? $flashed : $svc->defaultTemplate();

        $loadId = trim((string) Request::input('load_template', ''));
        if ($loadId !== '') {
            $saved = $svc->getSavedTemplate($loadId);
            if ($saved) {
                $template = (string) $saved['body'];
            }
        }

        $this->view('admin/whatsapp_broadcast', [
            'title' => 'WhatsApp broadcast',
            'user' => Auth::user(),
            'enabled' => $wa->enabled(),
            'provider' => $wa->provider(),
            'setup' => $wa->broadcastSetupStatus(),
            'broadcastTemplate' => $wa->broadcastTemplateName(),
            'adminWhatsApp' => $svc->adminWhatsApp(),
            'customers' => $svc->listCustomers($q !== '' ? $q : null),
            'q' => $q,
            'template' => $template,
            'placeholders' => $svc->placeholders(),
            'savedTemplates' => $svc->listSavedTemplates(),
            'lastResult' => Session::getFlash('whatsapp_broadcast_result'),
        ], 'layouts/dashboard');
    }

    public function saveTemplate(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $name = trim((string) Request::input('template_name', ''));
        $body = trim((string) Request::input('message', ''));

        try {
            (new WhatsAppBroadcastService())->saveTemplate($name, $body, Auth::id());
            Session::flash('whatsapp_broadcast_template', $body);
            flash_success('Template saved: ' . $name);
        } catch (InvalidArgumentException $e) {
            flash_error($e->getMessage());
            Session::flash('whatsapp_broadcast_template', $body);
        } catch (\Throwable $e) {
            flash_error('Could not save template. Run migration 007 on the database.');
            Session::flash('whatsapp_broadcast_template', $body);
        }

        $this->redirect('/admin/whatsapp-broadcast');
    }

    public function deleteTemplate(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $id = trim((string) Request::input('template_id', ''));
        if ($id !== '') {
            try {
                (new WhatsAppBroadcastService())->deleteTemplate($id);
                flash_success('Template deleted');
            } catch (\Throwable $e) {
                flash_error('Could not delete template');
            }
        }

        $this->redirect('/admin/whatsapp-broadcast');
    }

    public function previewForm(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $draft = $this->readDraftFromRequest();
        if ($draft['error'] !== null) {
            flash_error($draft['error']);
            Session::flash('whatsapp_broadcast_template', $draft['message']);
            $this->redirect('/admin/whatsapp-broadcast');
        }

        Session::set('whatsapp_broadcast_draft', $draft);
        $this->redirect('/admin/whatsapp-broadcast/preview');
    }

    public function preview(): void
    {
        $draft = Session::get('whatsapp_broadcast_draft');
        if (!is_array($draft)) {
            flash_error('Start a new broadcast first');
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $svc = new WhatsAppBroadcastService();
        $wa = new WhatsAppService();
        $preview = $svc->buildPreview(
            (string) $draft['message'],
            (string) $draft['audience'],
            $draft['customer_ids'] ?? []
        );

        $this->view('admin/whatsapp_broadcast_preview', [
            'title' => 'Preview broadcast',
            'user' => Auth::user(),
            'draft' => $draft,
            'preview' => $preview,
            'setup' => $wa->broadcastSetupStatus(),
            'adminWhatsApp' => $svc->adminWhatsApp(),
        ], 'layouts/dashboard');
    }

    public function send(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $draft = Session::get('whatsapp_broadcast_draft');
        if (!is_array($draft)) {
            $draft = $this->readDraftFromRequest();
            if ($draft['error'] !== null) {
                flash_error($draft['error']);
                Session::flash('whatsapp_broadcast_template', $draft['message']);
                $this->redirect('/admin/whatsapp-broadcast');
            }
        } else {
            $draft = [
                'message' => (string) ($draft['message'] ?? ''),
                'audience' => (string) ($draft['audience'] ?? 'selected'),
                'customer_ids' => is_array($draft['customer_ids'] ?? null) ? $draft['customer_ids'] : [],
                'error' => null,
            ];
        }

        if (Request::input('confirm') !== '1') {
            flash_error('Please confirm before sending');
            Session::flash('whatsapp_broadcast_template', $draft['message']);
            $this->redirect('/admin/whatsapp-broadcast/preview');
        }

        $setup = (new WhatsAppService())->broadcastSetupStatus();
        if (empty($setup['ready'])) {
            flash_error((string) ($setup['reason'] ?? 'WhatsApp is not configured on this server'));
            Session::flash('whatsapp_broadcast_template', $draft['message']);
            $this->redirect('/admin/whatsapp-broadcast');
        }

        $result = (new WhatsAppBroadcastService())->send(
            $draft['message'],
            $draft['audience'],
            $draft['customer_ids']
        );

        Session::forget('whatsapp_broadcast_draft');
        Session::flash('whatsapp_broadcast_template', $draft['message']);
        Session::flash('whatsapp_broadcast_result', $result);

        $summary = sprintf(
            'Broadcast finished: %d recipient(s), %d sent, %d failed, %d skipped',
            $result['total'],
            $result['sent'],
            $result['failed'],
            $result['skipped']
        );
        if ((int) $result['sent'] === 0) {
            flash_error($summary . '. See Last send results below for the error from WhatsApp.');
        } else {
            flash_success($summary);
        }

        $this->redirect('/admin/whatsapp-broadcast');
    }

    /** @return array{message:string,audience:string,customer_ids:list<string>,error:?string} */
    private function readDraftFromRequest(): array
    {
        $message = trim((string) Request::input('message', ''));
        $audience = (string) Request::input('audience', 'selected');
        $customerIds = Request::input('customer_ids');
        if (!is_array($customerIds)) {
            $customerIds = $customerIds ? [$customerIds] : [];
        }

        if ($message === '') {
            return ['message' => '', 'audience' => $audience, 'customer_ids' => [], 'error' => 'Message is required'];
        }

        if (mb_strlen($message) > 4096) {
            return ['message' => $message, 'audience' => $audience, 'customer_ids' => [], 'error' => 'Message is too long (max 4096 characters)'];
        }

        if ($audience !== 'all' && $customerIds === []) {
            return [
                'message' => $message,
                'audience' => $audience,
                'customer_ids' => [],
                'error' => 'Select at least one customer, or choose “All customers”',
            ];
        }

        return [
            'message' => $message,
            'audience' => $audience,
            'customer_ids' => array_values(array_map('strval', $customerIds)),
            'error' => null,
        ];
    }
}
