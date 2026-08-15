<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\PushBroadcastService;
use InvalidArgumentException;

final class PushBroadcastController extends Controller
{
    public function index(): void
    {
        $svc = new PushBroadcastService();
        $q = trim((string) Request::input('q', ''));

        $defaults = $svc->defaultTemplate();
        $title = Session::getFlash('push_broadcast_title');
        $body = Session::getFlash('push_broadcast_body');
        if (!is_string($title) || $title === '') {
            $title = $defaults['title'];
        }
        if (!is_string($body) || $body === '') {
            $body = $defaults['body'];
        }

        $loadId = trim((string) Request::input('load_template', ''));
        if ($loadId !== '') {
            $saved = $svc->getSavedTemplate($loadId);
            if ($saved) {
                $title = (string) $saved['title'];
                $body = (string) $saved['body'];
            }
        }

        $fcmStatus = $svc->fcmSetupStatus();

        $this->view('admin/push_broadcast', [
            'title' => 'Push broadcast',
            'user' => Auth::user(),
            'fcmEnabled' => $fcmStatus['enabled'],
            'fcmDisabledReason' => $fcmStatus['reason'],
            'tokenStats' => $svc->deviceTokenStats(),
            'customers' => $svc->listCustomers($q !== '' ? $q : null),
            'q' => $q,
            'templateTitle' => $title,
            'templateBody' => $body,
            'placeholders' => $svc->placeholders(),
            'savedTemplates' => $svc->listSavedTemplates(),
            'lastResult' => Session::getFlash('push_broadcast_result'),
        ], 'layouts/dashboard');
    }

    public function saveTemplate(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/push-broadcast');
        }

        $name = trim((string) Request::input('template_name', ''));
        $title = trim((string) Request::input('title', ''));
        $body = trim((string) Request::input('message', ''));

        try {
            (new PushBroadcastService())->saveTemplate($name, $title, $body, Auth::id());
            Session::flash('push_broadcast_title', $title);
            Session::flash('push_broadcast_body', $body);
            flash_success('Template saved: ' . $name);
        } catch (InvalidArgumentException $e) {
            flash_error($e->getMessage());
            Session::flash('push_broadcast_title', $title);
            Session::flash('push_broadcast_body', $body);
        } catch (\Throwable $e) {
            flash_error('Could not save template. Run migration 008 on the database.');
            Session::flash('push_broadcast_title', $title);
            Session::flash('push_broadcast_body', $body);
        }

        $this->redirect('/admin/push-broadcast');
    }

    public function deleteTemplate(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/push-broadcast');
        }

        $id = trim((string) Request::input('template_id', ''));
        if ($id !== '') {
            try {
                (new PushBroadcastService())->deleteTemplate($id);
                flash_success('Template deleted');
            } catch (\Throwable $e) {
                flash_error('Could not delete template');
            }
        }

        $this->redirect('/admin/push-broadcast');
    }

    public function previewForm(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/push-broadcast');
        }

        $draft = $this->readDraftFromRequest();
        if ($draft['error'] !== null) {
            flash_error($draft['error']);
            Session::flash('push_broadcast_title', $draft['title']);
            Session::flash('push_broadcast_body', $draft['message']);
            $this->redirect('/admin/push-broadcast');
        }

        Session::set('push_broadcast_draft', $draft);
        $this->redirect('/admin/push-broadcast/preview');
    }

    public function preview(): void
    {
        $draft = Session::get('push_broadcast_draft');
        if (!is_array($draft)) {
            flash_error('Start a new broadcast first');
            $this->redirect('/admin/push-broadcast');
        }

        $svc = new PushBroadcastService();
        $preview = $svc->buildPreview(
            (string) $draft['title'],
            (string) $draft['message'],
            (string) $draft['audience'],
            $draft['customer_ids'] ?? []
        );

        $this->view('admin/push_broadcast_preview', [
            'title' => 'Preview push broadcast',
            'user' => Auth::user(),
            'draft' => $draft,
            'preview' => $preview,
            'fcmEnabled' => $svc->fcmEnabled(),
        ], 'layouts/dashboard');
    }

    public function send(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/push-broadcast');
        }

        $draft = Session::get('push_broadcast_draft');
        if (!is_array($draft)) {
            $draft = $this->readDraftFromRequest();
            if ($draft['error'] !== null) {
                flash_error($draft['error']);
                Session::flash('push_broadcast_title', $draft['title']);
                Session::flash('push_broadcast_body', $draft['message']);
                $this->redirect('/admin/push-broadcast');
            }
        } else {
            $draft = [
                'title' => (string) ($draft['title'] ?? ''),
                'message' => (string) ($draft['message'] ?? ''),
                'audience' => (string) ($draft['audience'] ?? 'selected'),
                'customer_ids' => is_array($draft['customer_ids'] ?? null) ? $draft['customer_ids'] : [],
                'error' => null,
            ];
        }

        if (Request::input('confirm') !== '1') {
            flash_error('Please confirm before sending');
            Session::flash('push_broadcast_title', $draft['title']);
            Session::flash('push_broadcast_body', $draft['message']);
            $this->redirect('/admin/push-broadcast/preview');
        }

        $result = (new PushBroadcastService())->send(
            $draft['title'],
            $draft['message'],
            $draft['audience'],
            $draft['customer_ids']
        );

        Session::forget('push_broadcast_draft');
        Session::flash('push_broadcast_title', $draft['title']);
        Session::flash('push_broadcast_body', $draft['message']);
        Session::flash('push_broadcast_result', $result);

        flash_success(sprintf(
            'Broadcast finished: %d matched, %d in-app, %d device push(es) delivered, %d failed, %d skipped',
            $result['total'],
            $result['in_app'],
            $result['push'],
            $result['failed'],
            $result['skipped']
        ));

        $this->redirect('/admin/push-broadcast');
    }

    /** @return array{title:string,message:string,audience:string,customer_ids:list<string>,error:?string} */
    private function readDraftFromRequest(): array
    {
        $title = trim((string) Request::input('title', ''));
        $message = trim((string) Request::input('message', ''));
        $audience = (string) Request::input('audience', 'selected');
        $customerIds = Request::input('customer_ids');
        if (!is_array($customerIds)) {
            $customerIds = $customerIds ? [$customerIds] : [];
        }

        if ($title === '') {
            return ['title' => '', 'message' => $message, 'audience' => $audience, 'customer_ids' => [], 'error' => 'Title is required'];
        }

        if ($message === '') {
            return ['title' => $title, 'message' => '', 'audience' => $audience, 'customer_ids' => [], 'error' => 'Message is required'];
        }

        if (mb_strlen($title) > 200) {
            return ['title' => $title, 'message' => $message, 'audience' => $audience, 'customer_ids' => [], 'error' => 'Title is too long (max 200 characters)'];
        }

        if (mb_strlen($message) > 1000) {
            return ['title' => $title, 'message' => $message, 'audience' => $audience, 'customer_ids' => [], 'error' => 'Message is too long (max 1000 characters)'];
        }

        if ($audience !== 'all' && $customerIds === []) {
            return [
                'title' => $title,
                'message' => $message,
                'audience' => $audience,
                'customer_ids' => [],
                'error' => 'Select at least one customer, or choose “All customers”',
            ];
        }

        return [
            'title' => $title,
            'message' => $message,
            'audience' => $audience,
            'customer_ids' => array_values(array_map('strval', $customerIds)),
            'error' => null,
        ];
    }
}
