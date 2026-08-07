<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class ServiceController extends Controller
{
    public function index(): void
    {
        $services = Database::connection()->query(
            'SELECT s.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM service_items si WHERE si.service_id = s.id) AS item_count
             FROM services s
             JOIN service_categories c ON c.id = s.category_id
             ORDER BY c.sort_order, s.sort_order'
        )->fetchAll();

        $this->view('services/index', [
            'title' => 'Services',
            'services' => $services,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function show(string $id): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, c.name AS category_name FROM services s
             JOIN service_categories c ON c.id = s.category_id WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if (!$service) {
            flash_error('Service not found');
            $this->redirect('/admin/services');
        }

        $items = Database::connection()->prepare('SELECT * FROM service_items WHERE service_id = ? ORDER BY sort_order');
        $items->execute([$id]);

        $this->view('services/show', [
            'title' => $service['name'],
            'service' => $service,
            'items' => $items->fetchAll(),
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function createForm(): void
    {
        $categories = Database::connection()->query(
            'SELECT id, name FROM service_categories WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();
        $this->view('services/form', [
            'title' => 'Add service',
            'categories' => $categories,
            'service' => null,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function store(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/services/create');
        }

        $name = trim((string) Request::input('name'));
        $id = generate_id();
        $reminderDays = max(0, (int) Request::input('reminder_days', 30));

        Database::connection()->prepare(
            'INSERT INTO services (id, category_id, name, slug, description, base_price, duration, reminder_days, images, sort_order, is_active)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            Request::input('category_id'),
            $name,
            slugify($name) . '-' . substr($id, 0, 4),
            Request::input('description'),
            Request::input('base_price'),
            (int) Request::input('duration'),
            $reminderDays,
            json_encode([]),
            (int) Request::input('sort_order', 0),
            Request::input('is_active') ? 1 : 0,
        ]);
        flash_success('Service created');
        $this->redirect('/admin/services/' . $id);
    }

    public function editForm(string $id): void
    {
        $stmt = Database::connection()->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if (!$service) {
            flash_error('Service not found');
            $this->redirect('/admin/services');
        }

        $categories = Database::connection()->query(
            'SELECT id, name FROM service_categories WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();

        $this->view('services/form', [
            'title' => 'Edit service',
            'categories' => $categories,
            'service' => $service,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function update(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/services/' . $id . '/edit');
        }

        $name = trim((string) Request::input('name'));
        $reminderDays = max(0, (int) Request::input('reminder_days', 30));

        Database::connection()->prepare(
            'UPDATE services SET category_id=?, name=?, slug=?, description=?, base_price=?, duration=?,
             reminder_days=?, sort_order=?, is_active=? WHERE id=?'
        )->execute([
            Request::input('category_id'),
            $name,
            slugify($name) . '-' . substr($id, 0, 4),
            Request::input('description'),
            Request::input('base_price'),
            (int) Request::input('duration'),
            $reminderDays,
            (int) Request::input('sort_order', 0),
            Request::input('is_active') ? 1 : 0,
            $id,
        ]);
        flash_success('Service updated');
        $this->redirect('/admin/services/' . $id);
    }

    public function storeItem(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/services/' . $id);
        }

        Database::connection()->prepare(
            'INSERT INTO service_items (id, service_id, name, description, price, duration, sort_order, is_active)
             VALUES (?,?,?,?,?,?,?,1)'
        )->execute([
            generate_id(),
            $id,
            Request::input('name'),
            Request::input('description'),
            Request::input('price'),
            Request::input('duration') !== '' ? (int) Request::input('duration') : null,
            (int) Request::input('sort_order', 0),
        ]);
        flash_success('Item added');
        $this->redirect('/admin/services/' . $id);
    }
}
