<?php

declare(strict_types=1);

/**
 * Seed script — run from project root:
 *   php database/seeds/seed.php
 *
 * Default admin: admin@shineexpress.com / Admin@123
 */

require dirname(__DIR__, 2) . '/public/bootstrap_cli.php';

use App\Core\Database;

function id(): string
{
    return generate_id();
}

$pdo = Database::connection();

try {
    // TRUNCATE implicitly commits in MySQL — keep it outside any transaction
    $tables = [
        'audit_logs', 'notifications', 'reviews', 'payments', 'invoices', 'photos',
        'booking_status_history', 'booking_assignments', 'booking_items', 'bookings',
        'attendance', 'documents', 'employees', 'addresses', 'customers', 'users',
        'service_items', 'services', 'service_categories', 'role_permissions',
        'permissions', 'roles', 'branches', 'companies',
    ];
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE `{$t}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $permissionDefs = [
        ['Manage Company', 'manage:company', 'company'],
        ['Manage Branches', 'manage:branches', 'branches'],
        ['Manage All Employees', 'manage:all_employees', 'employees'],
        ['Manage Branch Employees', 'manage:branch_employees', 'employees'],
        ['View Employees', 'view:employees', 'employees'],
        ['Manage All Customers', 'manage:all_customers', 'customers'],
        ['View Customers', 'view:customers', 'customers'],
        ['Manage Services', 'manage:services', 'services'],
        ['Manage Pricing', 'manage:pricing', 'services'],
        ['Manage All Bookings', 'manage:all_bookings', 'bookings'],
        ['Manage Branch Bookings', 'manage:branch_bookings', 'bookings'],
        ['View Assigned Jobs', 'view:assigned_jobs', 'bookings'],
        ['Update Job Status', 'update:job_status', 'bookings'],
        ['Create Booking', 'create:booking', 'bookings'],
        ['Cancel Own Booking', 'cancel:own_booking', 'bookings'],
        ['Manage Payments', 'manage:payments', 'payments'],
        ['View Invoices', 'view:invoices', 'invoices'],
        ['Download Invoice', 'download:invoice', 'invoices'],
        ['View All Reports', 'view:all_reports', 'reports'],
        ['View Branch Reports', 'view:branch_reports', 'reports'],
        ['Manage Settings', 'manage:settings', 'settings'],
    ];

    $permIds = [];
    $insPerm = $pdo->prepare('INSERT INTO permissions (id, name, slug, description, module) VALUES (?,?,?,?,?)');
    foreach ($permissionDefs as [$name, $slug, $module]) {
        $pid = id();
        $permIds[$slug] = $pid;
        $insPerm->execute([$pid, $name, $slug, $name, $module]);
    }

    $roleMap = [
        'SUPER_ADMIN' => ['Super Admin', array_keys($permIds)],
        'BRANCH_MANAGER' => ['Branch Manager', [
            'manage:branch_bookings', 'manage:branch_employees', 'view:customers',
            'view:branch_reports', 'update:job_status', 'view:employees',
        ]],
        'SERVICE_STAFF' => ['Service Staff', ['view:assigned_jobs', 'update:job_status']],
        'CUSTOMER' => ['Customer', [
            'create:booking', 'cancel:own_booking', 'view:invoices', 'download:invoice',
        ]],
    ];

    $insRole = $pdo->prepare('INSERT INTO roles (id, name, slug, description, is_system) VALUES (?,?,?,?,1)');
    $insRP = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)');
    foreach ($roleMap as $slug => [$name, $slugs]) {
        $rid = id();
        $insRole->execute([$rid, $name, $slug, $name]);
        foreach ($slugs as $ps) {
            $insRP->execute([$rid, $permIds[$ps]]);
        }
    }

    $companyId = id();
    $pdo->prepare(
        'INSERT INTO companies (id, name, slug, gst_number, email, phone, address, city, state, pincode, settings, is_active)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,1)'
    )->execute([
        $companyId, 'Shine Express', 'shine-express', '29AABCU9603R1ZM',
        'hello@shineexpress.com', '9876543210', 'MG Road', 'Bangalore', 'Karnataka', '560001',
        json_encode(['currency' => 'INR', 'taxRate' => 18, 'bookingPrefix' => 'SE', 'timezone' => 'Asia/Kolkata']),
    ]);

    $branchId = id();
    $pdo->prepare(
        'INSERT INTO branches (id, company_id, name, code, email, phone, address, city, state, pincode, latitude, longitude, is_active)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)'
    )->execute([
        $branchId, $companyId, 'Bangalore Main', 'BLR-001',
        'blr@shineexpress.com', '9876543210', 'MG Road', 'Bangalore', 'Karnataka', '560001',
        12.9716, 77.5946,
    ]);

    $catalog = [
        ['House Cleaning', 1499, 180, [
            ['Kitchen', 399, 45], ['Bathroom', 299, 30], ['Bedroom', 349, 40], ['Balcony', 199, 20],
        ]],
        ['Car Cleaning', 799, 90, [
            ['Interior', 399, 45], ['Exterior', 299, 30], ['Premium', 599, 60],
        ]],
        ['Water Tank Cleaning', 1999, 120, [
            ['Underground', 1499, 90], ['Overhead', 999, 60],
        ]],
        ['Sofa Cleaning', 899, 60, [['Standard Sofa', 899, 60]]],
        ['Carpet Cleaning', 699, 45, [['Standard Carpet', 699, 45]]],
        ['Pest Control', 2499, 90, [
            ['Cockroach', 1499, 60], ['Termite', 4999, 120], ['General', 2499, 90],
        ]],
        ['Deep Cleaning', 3999, 360, [
            ['1 BHK', 3999, 240], ['2 BHK', 5499, 300], ['3 BHK', 6999, 360],
        ]],
    ];

    $insCat = $pdo->prepare('INSERT INTO service_categories (id, name, slug, sort_order, is_active) VALUES (?,?,?,?,1)');
    $insSvc = $pdo->prepare('INSERT INTO services (id, category_id, name, slug, base_price, duration, reminder_days, images, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,1)');
    $insItem = $pdo->prepare('INSERT INTO service_items (id, service_id, name, price, duration, sort_order, is_active) VALUES (?,?,?,?,?,?,1)');

    $sort = 0;
    foreach ($catalog as [$catName, $base, $dur, $items]) {
        $catId = id();
        $baseSlug = slugify($catName);
        $insCat->execute([$catId, $catName, $baseSlug, $sort]);
        $svcId = id();
        // Pest control: remind after 90 days; others after 30
        $reminderDays = str_contains(strtolower($catName), 'pest') ? 90 : 30;
        // Service slug must be unique — suffix with sort index
        $insSvc->execute([$svcId, $catId, $catName, $baseSlug . '-svc', $base, $dur, $reminderDays, json_encode([]), $sort]);
        $i = 0;
        foreach ($items as [$iname, $iprice, $idur]) {
            $insItem->execute([id(), $svcId, $iname, $iprice, $idur, $i++]);
        }
        $sort++;
    }

    $adminId = id();
    $customerUserId = id();
    $staffUserId = id();
    $managerUserId = id();

    $insUser = $pdo->prepare(
        'INSERT INTO users (id, email, password_hash, phone, first_name, last_name, role, is_active)
         VALUES (?,?,?,?,?,?,?,1)'
    );
    $insUser->execute([$adminId, 'admin@shineexpress.com', password_hash('Admin@123', PASSWORD_DEFAULT), '9000000001', 'Super', 'Admin', 'SUPER_ADMIN']);
    $insUser->execute([$managerUserId, 'manager@shineexpress.com', password_hash('Manager@123', PASSWORD_DEFAULT), '9000000002', 'Branch', 'Manager', 'BRANCH_MANAGER']);
    $insUser->execute([$staffUserId, 'staff@shineexpress.com', password_hash('Staff@123', PASSWORD_DEFAULT), '9000000003', 'Service', 'Staff', 'SERVICE_STAFF']);
    $insUser->execute([$customerUserId, 'customer@shineexpress.com', password_hash('Customer@123', PASSWORD_DEFAULT), '9000000004', 'Demo', 'Customer', 'CUSTOMER']);

    $customerId = id();
    $pdo->prepare('INSERT INTO customers (id, user_id) VALUES (?,?)')->execute([$customerId, $customerUserId]);
    $pdo->prepare(
        'INSERT INTO addresses (id, customer_id, label, line1, city, state, pincode, country, is_default)
         VALUES (?,?,?,?,?,?,?,?,1)'
    )->execute([
        id(), $customerId, 'Home', '12 Residency Road', 'Bangalore', 'Karnataka', '560025', 'India',
    ]);

    $pdo->prepare(
        'INSERT INTO employees (id, user_id, branch_id, employee_code, salary, skills, is_available, joined_at)
         VALUES (?,?,?,?,?,?,1,NOW())'
    )->execute([
        id(), $staffUserId, $branchId, 'EMP-001', 18000, json_encode(['cleaning', 'pest']),
    ]);

    $pdo->prepare(
        'INSERT INTO employees (id, user_id, branch_id, employee_code, salary, skills, is_available, joined_at)
         VALUES (?,?,?,?,?,?,1,NOW())'
    )->execute([
        id(), $managerUserId, $branchId, 'MGR-001', 35000, json_encode(['management']),
    ]);

    echo "Seed complete.\n";
    echo "Admin:    admin@shineexpress.com / Admin@123\n";
    echo "Manager:  manager@shineexpress.com / Manager@123\n";
    echo "Staff:    staff@shineexpress.com / Staff@123\n";
    echo "Customer: customer@shineexpress.com / Customer@123\n";
    echo "Branch ID: {$branchId}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
