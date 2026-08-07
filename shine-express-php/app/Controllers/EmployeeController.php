<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class EmployeeController extends Controller
{
    public function index(): void
    {
        $branchScope = Auth::role() === 'BRANCH_MANAGER' ? Auth::branchId() : null;
        $sql = 'SELECT e.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, b.name AS branch_name
                FROM employees e
                JOIN users u ON u.id = e.user_id
                JOIN branches b ON b.id = e.branch_id
                WHERE e.deleted_at IS NULL';
        $params = [];
        if ($branchScope) {
            $sql .= ' AND e.branch_id = ?';
            $params[] = $branchScope;
        }
        $sql .= ' ORDER BY e.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->view('employees/index', [
            'title' => Auth::role() === 'BRANCH_MANAGER' ? 'Staff' : 'Employees',
            'employees' => $stmt->fetchAll(),
            'user' => Auth::user(),
            'base' => $this->basePath(),
        ], 'layouts/dashboard');
    }

    public function createForm(): void
    {
        $branches = Database::connection()->query('SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name')->fetchAll();
        $this->view('employees/form', [
            'title' => 'Add employee',
            'employee' => null,
            'branches' => $branches,
            'user' => Auth::user(),
            'base' => $this->basePath(),
            'defaultBranch' => Auth::branchId(),
        ], 'layouts/dashboard');
    }

    public function store(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->basePath() . '/employees/create');
        }

        $branchId = (string) Request::input('branch_id');
        if (Auth::role() === 'BRANCH_MANAGER') {
            $branchId = (string) Auth::branchId();
        }

        $db = Database::connection();
        $userId = generate_id();
        $empId = generate_id();
        $code = trim((string) Request::input('employee_code')) ?: ('EMP-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)));
        $role = Auth::role() === 'SUPER_ADMIN' && Request::input('role') === 'BRANCH_MANAGER'
            ? 'BRANCH_MANAGER'
            : 'SERVICE_STAFF';

        $db->beginTransaction();
        try {
            $db->prepare(
                'INSERT INTO users (id, email, password_hash, phone, first_name, last_name, role, is_active)
                 VALUES (?,?,?,?,?,?,?,?)'
            )->execute([
                $userId,
                trim((string) Request::input('email')),
                password_hash((string) Request::input('password', 'Staff@123'), PASSWORD_DEFAULT),
                Request::input('phone'),
                Request::input('first_name'),
                Request::input('last_name'),
                $role,
                Request::input('is_active') ? 1 : 0,
            ]);

            $skills = array_values(array_filter(array_map('trim', explode(',', (string) Request::input('skills', '')))));
            $db->prepare(
                'INSERT INTO employees (id, user_id, branch_id, employee_code, salary, skills, is_available, joined_at)
                 VALUES (?,?,?,?,?,?,?,NOW())'
            )->execute([
                $empId, $userId, $branchId, $code,
                Request::input('salary') !== '' ? Request::input('salary') : null,
                json_encode($skills),
                Request::input('is_available') ? 1 : 0,
            ]);
            $db->commit();
            flash_success('Employee created');
            $this->redirect($this->staffListPath());
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash_error('Could not create employee.');
            $this->redirect($this->basePath() . '/employees/create');
        }
    }

    public function editForm(string $id): void
    {
        $employee = $this->find($id);
        if (!$employee || !$this->canManage($employee)) {
            flash_error('Employee not found');
            $this->redirect($this->staffListPath());
        }

        $branches = Database::connection()->query('SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name')->fetchAll();
        $this->view('employees/form', [
            'title' => 'Edit employee',
            'employee' => $employee,
            'branches' => $branches,
            'user' => Auth::user(),
            'base' => $this->basePath(),
            'defaultBranch' => $employee['branch_id'],
        ], 'layouts/dashboard');
    }

    public function update(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->basePath() . '/employees/' . $id . '/edit');
        }

        $employee = $this->find($id);
        if (!$employee || !$this->canManage($employee)) {
            flash_error('Employee not found');
            $this->redirect($this->staffListPath());
        }

        $branchId = (string) Request::input('branch_id');
        if (Auth::role() === 'BRANCH_MANAGER') {
            $branchId = (string) Auth::branchId();
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $db->prepare(
                'UPDATE users SET first_name=?, last_name=?, email=?, phone=?, is_active=? WHERE id=?'
            )->execute([
                Request::input('first_name'),
                Request::input('last_name'),
                trim((string) Request::input('email')),
                Request::input('phone'),
                Request::input('is_active') ? 1 : 0,
                $employee['user_id'],
            ]);

            $password = (string) Request::input('password', '');
            if ($password !== '') {
                $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $employee['user_id']]);
            }

            if (Auth::role() === 'SUPER_ADMIN' && Request::input('role')) {
                $role = Request::input('role') === 'BRANCH_MANAGER' ? 'BRANCH_MANAGER' : 'SERVICE_STAFF';
                $db->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $employee['user_id']]);
            }

            $skills = array_values(array_filter(array_map('trim', explode(',', (string) Request::input('skills', '')))));
            $db->prepare(
                'UPDATE employees SET branch_id=?, employee_code=?, salary=?, skills=?, is_available=? WHERE id=?'
            )->execute([
                $branchId,
                trim((string) Request::input('employee_code')),
                Request::input('salary') !== '' ? Request::input('salary') : null,
                json_encode($skills),
                Request::input('is_available') ? 1 : 0,
                $id,
            ]);
            $db->commit();
            flash_success('Employee updated');
            $this->redirect($this->staffListPath());
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash_error('Could not update employee.');
            $this->redirect($this->basePath() . '/employees/' . $id . '/edit');
        }
    }

    /** @return array<string, mixed>|null */
    private function find(string $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, u.role AS user_role
             FROM employees e JOIN users u ON u.id = e.user_id
             WHERE e.id = ? AND e.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $employee */
    private function canManage(array $employee): bool
    {
        if (Auth::role() === 'SUPER_ADMIN') {
            return true;
        }
        return Auth::role() === 'BRANCH_MANAGER' && $employee['branch_id'] === Auth::branchId();
    }

    private function basePath(): string
    {
        return Auth::role() === 'BRANCH_MANAGER' ? '/branch-manager' : '/admin';
    }

    private function staffListPath(): string
    {
        return Auth::role() === 'BRANCH_MANAGER' ? '/branch-manager/staff' : '/admin/employees';
    }
}
