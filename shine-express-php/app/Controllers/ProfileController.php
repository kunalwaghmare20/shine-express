<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class ProfileController extends Controller
{
    public function customer(): void
    {
        $customerId = Auth::customerId();
        $addresses = [];
        if ($customerId) {
            $stmt = Database::connection()->prepare('SELECT * FROM addresses WHERE customer_id = ?');
            $stmt->execute([$customerId]);
            $addresses = $stmt->fetchAll();
        }
        $this->view('profile/customer', [
            'title' => 'Profile',
            'user' => Auth::user(),
            'addresses' => $addresses,
        ], 'layouts/dashboard');
    }

    public function addAddress(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/profile');
        }
        $customerId = Auth::customerId();
        Database::connection()->prepare(
            'INSERT INTO addresses (id, customer_id, label, line1, line2, city, state, pincode, country, is_default)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            generate_id(),
            $customerId,
            Request::input('label', 'Home'),
            Request::input('line1'),
            Request::input('line2'),
            Request::input('city'),
            Request::input('state'),
            Request::input('pincode'),
            Request::input('country', 'India'),
            Request::input('is_default') ? 1 : 0,
        ]);
        flash_success('Address added');
        $this->redirect('/profile');
    }

    public function staff(): void
    {
        $this->view('profile/staff', [
            'title' => 'Profile',
            'user' => Auth::user(),
            'employee' => Auth::employee(),
        ], 'layouts/dashboard');
    }

    public function attendance(): void
    {
        $emp = Auth::employee();
        $rows = [];
        if ($emp) {
            $stmt = Database::connection()->prepare(
                'SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30'
            );
            $stmt->execute([$emp['id']]);
            $rows = $stmt->fetchAll();
        }
        $this->view('staff/attendance', [
            'title' => 'Attendance',
            'rows' => $rows,
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function checkIn(): void
    {
        $emp = Auth::employee();
        if (!$emp) {
            flash_error('No employee profile');
            $this->redirect('/staff/attendance');
        }
        $today = date('Y-m-d');
        $db = Database::connection();
        $exists = $db->prepare('SELECT id FROM attendance WHERE employee_id = ? AND date = ?');
        $exists->execute([$emp['id'], $today]);
        if ($exists->fetch()) {
            flash_error('Already checked in today');
        } else {
            $db->prepare(
                'INSERT INTO attendance (id, employee_id, date, check_in, status) VALUES (?,?,?,NOW(3),"PRESENT")'
            )->execute([generate_id(), $emp['id'], $today]);
            flash_success('Checked in');
        }
        $this->redirect('/staff/attendance');
    }
}
