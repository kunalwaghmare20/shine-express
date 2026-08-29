<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\WhatsAppBroadcastService;
use App\Services\WhatsAppService;

final class CustomerController extends Controller
{
    public function index(): void
    {
        $q = trim((string) Request::input('q', ''));
        $sql = 'SELECT c.id, c.gst_number, u.first_name, u.last_name, u.email, u.phone, u.is_active, c.created_at,
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count
                FROM customers c
                JOIN users u ON u.id = c.user_id
                WHERE c.deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY c.created_at DESC LIMIT 100';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $wa = new WhatsAppBroadcastService();
        $this->view('customers/index', [
            'title' => 'Customers',
            'customers' => $stmt->fetchAll(),
            'q' => $q,
            'user' => Auth::user(),
            'base' => $this->basePath(),
            'savedTemplates' => $wa->listSavedTemplates(),
            'placeholders' => $wa->placeholders(),
            'adminWhatsApp' => $wa->adminWhatsApp(),
            'waReady' => (new WhatsAppService())->broadcastSetupStatus(),
        ], 'layouts/dashboard');
    }

    public function show(string $id): void
    {
        $customer = $this->find($id);
        if (!$customer) {
            flash_error('Customer not found');
            $this->redirect($this->basePath() . '/customers');
        }

        $addresses = Database::connection()->prepare('SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC');
        $addresses->execute([$id]);

        $bookings = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name FROM bookings b JOIN services s ON s.id = b.service_id
             WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 20'
        );
        $bookings->execute([$id]);

        $wa = new WhatsAppBroadcastService();
        $this->view('customers/show', [
            'title' => $customer['first_name'] . ' ' . $customer['last_name'],
            'customer' => $customer,
            'addresses' => $addresses->fetchAll(),
            'bookings' => $bookings->fetchAll(),
            'user' => Auth::user(),
            'base' => $this->basePath(),
            'savedTemplates' => $wa->listSavedTemplates(),
            'placeholders' => $wa->placeholders(),
            'adminWhatsApp' => $wa->adminWhatsApp(),
            'waReady' => (new WhatsAppService())->broadcastSetupStatus(),
        ], 'layouts/dashboard');
    }

    public function createForm(): void
    {
        $this->view('customers/form', [
            'title' => 'Add customer',
            'customer' => null,
            'user' => Auth::user(),
            'base' => $this->basePath(),
        ], 'layouts/dashboard');
    }

    public function store(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->basePath() . '/customers/create');
        }

        $first = trim((string) Request::input('first_name'));
        $last = trim((string) Request::input('last_name'));
        $email = trim((string) Request::input('email'));
        $phone = trim((string) Request::input('phone'));
        $password = (string) Request::input('password', 'Customer@123');

        $db = Database::connection();
        $userId = generate_id();
        $customerId = generate_id();
        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO users (id, email, password_hash, phone, first_name, last_name, role, is_active)
                 VALUES (?,?,?,?,?,?,"CUSTOMER",?)'
            )->execute([
                $userId, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $first, $last,
                Request::input('is_active') ? 1 : 0,
            ]);
            $db->prepare('INSERT INTO customers (id, user_id, gst_number, notes) VALUES (?,?,?,?)')
                ->execute([$customerId, $userId, Request::input('gst_number'), Request::input('notes')]);
            $db->commit();
            flash_success('Customer created');
            $this->redirect($this->basePath() . '/customers/' . $customerId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash_error('Could not create customer (email may exist).');
            $this->redirect($this->basePath() . '/customers/create');
        }
    }

    public function editForm(string $id): void
    {
        $customer = $this->find($id);
        if (!$customer) {
            flash_error('Customer not found');
            $this->redirect($this->basePath() . '/customers');
        }
        $this->view('customers/form', [
            'title' => 'Edit customer',
            'customer' => $customer,
            'user' => Auth::user(),
            'base' => $this->basePath(),
        ], 'layouts/dashboard');
    }

    public function update(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->basePath() . '/customers/' . $id . '/edit');
        }

        $customer = $this->find($id);
        if (!$customer) {
            flash_error('Customer not found');
            $this->redirect($this->basePath() . '/customers');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $db->prepare(
                'UPDATE users SET first_name=?, last_name=?, email=?, phone=?, is_active=? WHERE id=?'
            )->execute([
                trim((string) Request::input('first_name')),
                trim((string) Request::input('last_name')),
                trim((string) Request::input('email')),
                Request::input('phone'),
                Request::input('is_active') ? 1 : 0,
                $customer['user_id'],
            ]);

            $password = (string) Request::input('password', '');
            if ($password !== '') {
                $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $customer['user_id']]);
            }

            $db->prepare('UPDATE customers SET gst_number=?, notes=? WHERE id=?')->execute([
                Request::input('gst_number'),
                Request::input('notes'),
                $id,
            ]);
            $db->commit();
            flash_success('Customer updated');
            $this->redirect($this->basePath() . '/customers/' . $id);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash_error('Could not update customer (email may exist).');
            $this->redirect($this->basePath() . '/customers/' . $id . '/edit');
        }
    }

    public function sendWhatsApp(string $id): void
    {
        $base = $this->basePath();
        $returnTo = (string) Request::input('return_to', '');
        $allowed = [$base . '/customers', $base . '/customers/' . $id];
        if (!in_array($returnTo, $allowed, true)) {
            $returnTo = $base . '/customers';
        }

        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect($returnTo);
        }

        $customer = $this->find($id);
        if (!$customer) {
            flash_error('Customer not found');
            $this->redirect($base . '/customers');
        }

        $svc = new WhatsAppBroadcastService();
        $source = (string) Request::input('source', 'custom');
        $message = trim((string) Request::input('message', ''));

        if ($source === 'template') {
            $templateId = trim((string) Request::input('template_id', ''));
            $saved = $templateId !== '' ? $svc->getSavedTemplate($templateId) : null;
            if (!$saved) {
                flash_error('Select a saved message template');
                $this->redirect($returnTo);
            }
            $message = (string) $saved['body'];
        }

        $result = $svc->sendToCustomer($id, $message);
        if (!empty($result['ok'])) {
            flash_success('WhatsApp message sent to ' . trim($customer['first_name'] . ' ' . $customer['last_name']));
        } else {
            flash_error('WhatsApp not sent: ' . (string) ($result['detail'] ?? 'Unknown error'));
        }

        $this->redirect($returnTo);
    }

    /** @return array<string, mixed>|null */
    private function find(string $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, u.first_name, u.last_name, u.email, u.phone, u.is_active
             FROM customers c JOIN users u ON u.id = c.user_id
             WHERE c.id = ? AND c.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function basePath(): string
    {
        return Auth::role() === 'BRANCH_MANAGER' ? '/branch-manager' : '/admin';
    }
}
