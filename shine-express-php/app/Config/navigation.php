<?php

declare(strict_types=1);

/**
 * Sidebar navigation per role.
 */

return [
    'SUPER_ADMIN' => [
        ['title' => 'Dashboard', 'href' => '/admin'],
        ['title' => 'Bookings', 'href' => '/admin/bookings'],
        ['title' => 'Customers', 'href' => '/admin/customers'],
        ['title' => 'Employees', 'href' => '/admin/employees'],
        ['title' => 'Services', 'href' => '/admin/services'],
        ['title' => 'Branches', 'href' => '/admin/branches'],
        ['title' => 'Reports', 'href' => '/admin/reports'],
        ['title' => 'WhatsApp rebook', 'href' => '/admin/reminders'],
        ['title' => 'Notifications', 'href' => '/notifications'],
    ],
    'BRANCH_MANAGER' => [
        ['title' => 'Dashboard', 'href' => '/branch-manager'],
        ['title' => 'Bookings', 'href' => '/branch-manager/bookings'],
        ['title' => 'Customers', 'href' => '/branch-manager/customers'],
        ['title' => 'Staff', 'href' => '/branch-manager/staff'],
        ['title' => 'Reports', 'href' => '/branch-manager/reports'],
        ['title' => 'Notifications', 'href' => '/notifications'],
    ],
    'SERVICE_STAFF' => [
        ['title' => 'My Jobs', 'href' => '/staff'],
        ['title' => 'Attendance', 'href' => '/staff/attendance'],
        ['title' => 'Notifications', 'href' => '/notifications'],
        ['title' => 'Profile', 'href' => '/staff/profile'],
    ],
    'CUSTOMER' => [
        ['title' => 'Book Service', 'href' => '/book'],
        ['title' => 'My Bookings', 'href' => '/bookings'],
        ['title' => 'History', 'href' => '/history'],
        ['title' => 'Notifications', 'href' => '/notifications'],
        ['title' => 'Profile', 'href' => '/profile'],
    ],
];
