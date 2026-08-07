<?php

declare(strict_types=1);

/**
 * Role-Based Access Control — mirrors the original Shine Express roles.
 */

return [
    'roles' => [
        'SUPER_ADMIN',
        'BRANCH_MANAGER',
        'SERVICE_STAFF',
        'CUSTOMER',
    ],

    'permissions' => [
        'manage:company',
        'manage:branches',
        'manage:all_employees',
        'manage:branch_employees',
        'view:employees',
        'manage:all_customers',
        'view:customers',
        'manage:services',
        'manage:pricing',
        'manage:all_bookings',
        'manage:branch_bookings',
        'view:assigned_jobs',
        'update:job_status',
        'create:booking',
        'cancel:own_booking',
        'manage:payments',
        'view:invoices',
        'download:invoice',
        'view:all_reports',
        'view:branch_reports',
        'manage:settings',
    ],

    'role_permissions' => [
        'SUPER_ADMIN' => '*', // all permissions

        'BRANCH_MANAGER' => [
            'manage:branch_bookings',
            'manage:branch_employees',
            'view:customers',
            'view:branch_reports',
            'update:job_status',
            'view:employees',
        ],

        'SERVICE_STAFF' => [
            'view:assigned_jobs',
            'update:job_status',
        ],

        'CUSTOMER' => [
            'create:booking',
            'cancel:own_booking',
            'view:invoices',
            'download:invoice',
        ],
    ],

    'role_home' => [
        'SUPER_ADMIN' => '/admin',
        'BRANCH_MANAGER' => '/branch-manager',
        'SERVICE_STAFF' => '/staff/jobs',
        'CUSTOMER' => '/book',
    ],
];
