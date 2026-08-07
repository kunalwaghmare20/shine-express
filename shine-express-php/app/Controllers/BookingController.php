<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Helpers\BookingStatus;
use App\Services\BookingService;
use RuntimeException;

final class BookingController extends Controller
{
    public function index(): void
    {
        [$sql, $params] = $this->listQuery();
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->view('bookings/index', [
            'title' => 'Bookings',
            'bookings' => $stmt->fetchAll(),
            'user' => Auth::user(),
            'base' => $this->portalBase(),
        ], 'layouts/dashboard');
    }

    public function createForm(): void
    {
        $this->view('bookings/book', array_merge($this->bookingFormData(), [
            'title' => 'Add booking',
            'adminMode' => true,
            'user' => Auth::user(),
            'base' => $this->portalBase(),
        ]), 'layouts/dashboard');
    }

    public function store(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->portalBase() . '/bookings/create');
        }

        $customerId = (string) Request::input('customer_id');
        $itemIds = Request::input('service_item_ids');
        if (!is_array($itemIds)) {
            $itemIds = $itemIds ? [$itemIds] : [];
        }

        try {
            $id = (new BookingService())->create([
                'service_id' => (string) Request::input('service_id'),
                'address_id' => (string) Request::input('address_id'),
                'branch_id' => (string) Request::input('branch_id'),
                'scheduled_date' => (string) Request::input('scheduled_date'),
                'scheduled_time' => (string) Request::input('scheduled_time'),
                'customer_notes' => Request::input('customer_notes'),
                'service_item_ids' => array_values($itemIds),
                'customer_id' => $customerId,
            ], (string) Auth::id(), $customerId);
            flash_success('Booking created');
            $this->redirect($this->portalBase() . '/bookings/' . $id);
        } catch (RuntimeException $e) {
            flash_error($e->getMessage());
            $this->redirect($this->portalBase() . '/bookings/create');
        }
    }

    public function show(string $id): void
    {
        $booking = $this->loadBooking($id);
        if (!$booking || !$this->canAccess($booking)) {
            flash_error('Booking not found');
            $this->redirect($this->portalBase() . '/bookings');
        }

        $items = Database::connection()->prepare('SELECT * FROM booking_items WHERE booking_id = ?');
        $items->execute([$id]);

        $assignments = Database::connection()->prepare(
            'SELECT ba.*, u.first_name, u.last_name, e.employee_code
             FROM booking_assignments ba
             JOIN employees e ON e.id = ba.employee_id
             JOIN users u ON u.id = e.user_id
             WHERE ba.booking_id = ?'
        );
        $assignments->execute([$id]);

        $history = Database::connection()->prepare(
            'SELECT * FROM booking_status_history WHERE booking_id = ? ORDER BY created_at ASC'
        );
        $history->execute([$id]);

        $staff = [];
        if (in_array(Auth::role(), ['SUPER_ADMIN', 'BRANCH_MANAGER'], true)) {
            $staffStmt = Database::connection()->prepare(
                'SELECT e.id, e.employee_code, u.first_name, u.last_name
                 FROM employees e JOIN users u ON u.id = e.user_id
                 WHERE e.deleted_at IS NULL AND e.branch_id = ? ORDER BY u.first_name'
            );
            $staffStmt->execute([$booking['branch_id']]);
            $staff = $staffStmt->fetchAll();
        }

        $this->view('bookings/show', [
            'title' => $booking['booking_number'],
            'booking' => $booking,
            'items' => $items->fetchAll(),
            'assignments' => $assignments->fetchAll(),
            'history' => $history->fetchAll(),
            'staff' => $staff,
            'transitions' => BookingStatus::TRANSITIONS[$booking['status']] ?? [],
            'user' => Auth::user(),
            'base' => $this->portalBase(),
        ], 'layouts/dashboard');
    }

    public function updateStatus(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->portalBase() . '/bookings/' . $id);
        }

        try {
            (new BookingService())->updateStatus(
                $id,
                (string) Request::input('status'),
                (string) Auth::id(),
                Request::input('notes'),
                Request::input('cancellation_reason')
            );
            flash_success('Status updated');
        } catch (RuntimeException $e) {
            flash_error($e->getMessage());
        }
        $this->redirect($this->portalBase() . '/bookings/' . $id);
    }

    public function assign(string $id): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect($this->portalBase() . '/bookings/' . $id);
        }

        $employeeIds = Request::input('employee_ids');
        if (!is_array($employeeIds)) {
            $employeeIds = $employeeIds ? [$employeeIds] : [];
        }

        try {
            (new BookingService())->assignStaff(
                $id,
                array_values($employeeIds),
                (string) Auth::id(),
                Request::input('primary_employee_id')
            );
            flash_success('Staff assigned');
        } catch (RuntimeException $e) {
            flash_error($e->getMessage());
        }
        $this->redirect($this->portalBase() . '/bookings/' . $id);
    }

    public function bookForm(): void
    {
        $customerId = Auth::customerId();
        if (!$customerId) {
            flash_error('Customer profile missing');
            $this->redirect('/profile');
        }

        $this->view('bookings/book', array_merge($this->bookingFormData($customerId), [
            'title' => 'Book service',
            'adminMode' => false,
            'user' => Auth::user(),
            'base' => '',
        ]), 'layouts/dashboard');
    }

    public function bookStore(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/book');
        }

        $customerId = Auth::customerId();
        $itemIds = Request::input('service_item_ids');
        if (!is_array($itemIds)) {
            $itemIds = $itemIds ? [$itemIds] : [];
        }

        try {
            $id = (new BookingService())->create([
                'service_id' => (string) Request::input('service_id'),
                'address_id' => (string) Request::input('address_id'),
                'branch_id' => (string) Request::input('branch_id'),
                'scheduled_date' => (string) Request::input('scheduled_date'),
                'scheduled_time' => (string) Request::input('scheduled_time'),
                'customer_notes' => Request::input('customer_notes'),
                'service_item_ids' => array_values($itemIds),
            ], (string) Auth::id(), $customerId);
            flash_success('Booking created');
            $this->redirect('/bookings/' . $id);
        } catch (RuntimeException $e) {
            flash_error($e->getMessage());
            $this->redirect('/book');
        }
    }

    /** @return array<string, mixed> */
    private function bookingFormData(?string $customerId = null): array
    {
        $services = Database::connection()->query(
            'SELECT s.*, c.name AS category_name FROM services s
             JOIN service_categories c ON c.id = s.category_id
             WHERE s.is_active = 1 ORDER BY c.sort_order, s.sort_order'
        )->fetchAll();

        $items = Database::connection()->query(
            'SELECT * FROM service_items WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();

        $branches = Database::connection()->query('SELECT id, name FROM branches WHERE is_active = 1')->fetchAll();

        $customers = Database::connection()->query(
            'SELECT c.id, u.first_name, u.last_name, u.email
             FROM customers c JOIN users u ON u.id = c.user_id
             WHERE c.deleted_at IS NULL ORDER BY u.first_name'
        )->fetchAll();

        $addresses = [];
        if ($customerId) {
            $stmt = Database::connection()->prepare('SELECT * FROM addresses WHERE customer_id = ?');
            $stmt->execute([$customerId]);
            $addresses = $stmt->fetchAll();
        } else {
            $addresses = Database::connection()->query(
                'SELECT a.*, c.id AS customer_id_ref FROM addresses a JOIN customers c ON c.id = a.customer_id'
            )->fetchAll();
        }

        return compact('services', 'items', 'branches', 'customers', 'addresses');
    }

    public function myBookings(): void
    {
        $customerId = Auth::customerId();
        $stmt = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name FROM bookings b
             JOIN services s ON s.id = b.service_id
             WHERE b.customer_id = ? ORDER BY b.created_at DESC'
        );
        $stmt->execute([$customerId]);
        $this->view('bookings/mine', [
            'title' => 'My bookings',
            'bookings' => $stmt->fetchAll(),
            'user' => Auth::user(),
            'historyOnly' => false,
        ], 'layouts/dashboard');
    }

    public function history(): void
    {
        $customerId = Auth::customerId();
        $stmt = Database::connection()->prepare(
            "SELECT b.*, s.name AS service_name FROM bookings b
             JOIN services s ON s.id = b.service_id
             WHERE b.customer_id = ? AND b.status IN ('COMPLETED','CANCELLED')
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$customerId]);
        $this->view('bookings/mine', [
            'title' => 'History',
            'bookings' => $stmt->fetchAll(),
            'user' => Auth::user(),
            'historyOnly' => true,
        ], 'layouts/dashboard');
    }

    public function staffJobs(): void
    {
        $emp = Auth::employee();
        if (!$emp) {
            flash_error('Employee profile missing');
            $this->redirect('/staff/profile');
        }
        $stmt = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name FROM booking_assignments ba
             JOIN bookings b ON b.id = ba.booking_id
             JOIN services s ON s.id = b.service_id
             WHERE ba.employee_id = ?
             ORDER BY b.scheduled_date DESC, b.scheduled_time DESC'
        );
        $stmt->execute([$emp['id']]);
        $this->view('bookings/staff-jobs', [
            'title' => 'My jobs',
            'bookings' => $stmt->fetchAll(),
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    /** @return array{0:string,1:list<mixed>} */
    private function listQuery(): array
    {
        $sql = 'SELECT b.*, s.name AS service_name,
                       CONCAT(u.first_name, " ", u.last_name) AS customer_name
                FROM bookings b
                JOIN services s ON s.id = b.service_id
                JOIN customers c ON c.id = b.customer_id
                JOIN users u ON u.id = c.user_id
                WHERE 1=1';
        $params = [];

        if (Auth::role() === 'BRANCH_MANAGER') {
            $sql .= ' AND b.branch_id = ?';
            $params[] = Auth::branchId();
        } elseif (Auth::role() === 'CUSTOMER') {
            $sql .= ' AND b.customer_id = ?';
            $params[] = Auth::customerId();
        } elseif (Auth::role() === 'SERVICE_STAFF') {
            $emp = Auth::employee();
            $sql .= ' AND EXISTS (SELECT 1 FROM booking_assignments ba WHERE ba.booking_id = b.id AND ba.employee_id = ?)';
            $params[] = $emp['id'] ?? '';
        }

        $status = Request::input('status');
        if ($status) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY b.created_at DESC LIMIT 100';
        return [$sql, $params];
    }

    /** @return array<string, mixed>|null */
    private function loadBooking(string $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.*, s.name AS service_name, br.name AS branch_name,
                    CONCAT(u.first_name, " ", u.last_name) AS customer_name,
                    a.line1, a.city, a.state, a.pincode
             FROM bookings b
             JOIN services s ON s.id = b.service_id
             JOIN branches br ON br.id = b.branch_id
             JOIN customers c ON c.id = b.customer_id
             JOIN users u ON u.id = c.user_id
             JOIN addresses a ON a.id = b.address_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $booking */
    private function canAccess(array $booking): bool
    {
        $role = Auth::role();
        if ($role === 'SUPER_ADMIN') {
            return true;
        }
        if ($role === 'BRANCH_MANAGER') {
            return $booking['branch_id'] === Auth::branchId();
        }
        if ($role === 'CUSTOMER') {
            return $booking['customer_id'] === Auth::customerId();
        }
        if ($role === 'SERVICE_STAFF') {
            $emp = Auth::employee();
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM booking_assignments WHERE booking_id = ? AND employee_id = ?'
            );
            $stmt->execute([$booking['id'], $emp['id'] ?? '']);
            return (bool) $stmt->fetchColumn();
        }
        return false;
    }

    private function portalBase(): string
    {
        return match (Auth::role()) {
            'SUPER_ADMIN' => '/admin',
            'BRANCH_MANAGER' => '/branch-manager',
            'SERVICE_STAFF' => '/staff',
            default => '',
        };
    }
}
