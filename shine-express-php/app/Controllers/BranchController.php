<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class BranchController extends Controller
{
    public function index(): void
    {
        $branches = Database::connection()->query(
            'SELECT b.*, c.name AS company_name FROM branches b
             JOIN companies c ON c.id = b.company_id ORDER BY b.name'
        )->fetchAll();
        $this->view('branches/index', [
            'title' => 'Branches',
            'branches' => $branches,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function createForm(): void
    {
        $companies = Database::connection()->query(
            'SELECT id, name FROM companies WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
        $this->view('branches/form', [
            'title' => 'Add branch',
            'branch' => null,
            'companies' => $companies,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function store(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/branches/create');
        }

        $id = generate_id();
        $code = trim((string) Request::input('code'));
        if ($code === '') {
            $code = 'BR-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO branches (id, company_id, name, code, email, phone, address, city, state, pincode, latitude, longitude, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $id,
                Request::input('company_id'),
                trim((string) Request::input('name')),
                $code,
                Request::input('email') ?: null,
                Request::input('phone') ?: null,
                Request::input('address') ?: null,
                Request::input('city') ?: null,
                Request::input('state') ?: null,
                Request::input('pincode') ?: null,
                Request::input('latitude') !== '' ? Request::input('latitude') : null,
                Request::input('longitude') !== '' ? Request::input('longitude') : null,
                Request::input('is_active') ? 1 : 0,
            ]);
            flash_success('Branch created');
            $this->redirect('/admin/branches');
        } catch (\Throwable $e) {
            flash_error('Could not create branch (code may already exist).');
            $this->redirect('/admin/branches/create');
        }
    }

    public function editForm(string $id): void
    {
        $branch = $this->find($id);
        if (!$branch) {
            flash_error('Branch not found');
            $this->redirect('/admin/branches');
        }
        $companies = Database::connection()->query(
            'SELECT id, name FROM companies WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
        $this->view('branches/form', [
            'title' => 'Edit branch',
            'branch' => $branch,
            'companies' => $companies,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function update(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/branches/' . $id . '/edit');
        }

        if (!$this->find($id)) {
            flash_error('Branch not found');
            $this->redirect('/admin/branches');
        }

        try {
            Database::connection()->prepare(
                'UPDATE branches SET company_id=?, name=?, code=?, email=?, phone=?, address=?, city=?, state=?, pincode=?,
                 latitude=?, longitude=?, is_active=? WHERE id=?'
            )->execute([
                Request::input('company_id'),
                trim((string) Request::input('name')),
                trim((string) Request::input('code')),
                Request::input('email') ?: null,
                Request::input('phone') ?: null,
                Request::input('address') ?: null,
                Request::input('city') ?: null,
                Request::input('state') ?: null,
                Request::input('pincode') ?: null,
                Request::input('latitude') !== '' ? Request::input('latitude') : null,
                Request::input('longitude') !== '' ? Request::input('longitude') : null,
                Request::input('is_active') ? 1 : 0,
                $id,
            ]);
            flash_success('Branch updated');
            $this->redirect('/admin/branches');
        } catch (\Throwable $e) {
            flash_error('Could not update branch.');
            $this->redirect('/admin/branches/' . $id . '/edit');
        }
    }

    /** @return array<string, mixed>|null */
    private function find(string $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM branches WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
