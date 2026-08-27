<?php

declare(strict_types=1);

/**
 * Application routes.
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\BranchController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\EmployeeController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\NotificationController;
use App\Controllers\ProfileController;
use App\Controllers\ReminderController;
use App\Controllers\ReportController;
use App\Controllers\ServiceController;
use App\Controllers\PushBroadcastController;
use App\Controllers\WhatsAppBroadcastController;
use App\Controllers\WhatsAppSettingsController;
use App\Middleware\AuthMiddleware;

$auth = [AuthMiddleware::class];
$admin = [AuthMiddleware::class, 'role:SUPER_ADMIN'];
$manager = [AuthMiddleware::class, 'role:BRANCH_MANAGER'];
$adminOrManager = [AuthMiddleware::class, 'role:SUPER_ADMIN,BRANCH_MANAGER'];
$staff = [AuthMiddleware::class, 'role:SERVICE_STAFF'];
$customer = [AuthMiddleware::class, 'role:CUSTOMER'];

$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], $auth);

// Admin
$router->get('/admin', [DashboardController::class, 'admin'], $admin);
$router->get('/admin/bookings', [BookingController::class, 'index'], $admin);
$router->get('/admin/bookings/create', [BookingController::class, 'createForm'], $admin);
$router->post('/admin/bookings', [BookingController::class, 'store'], $admin);
$router->get('/admin/bookings/{id}', [BookingController::class, 'show'], $admin);
$router->post('/admin/bookings/{id}/status', [BookingController::class, 'updateStatus'], $admin);
$router->post('/admin/bookings/{id}/assign', [BookingController::class, 'assign'], $admin);
$router->get('/admin/customers', [CustomerController::class, 'index'], $admin);
$router->get('/admin/customers/create', [CustomerController::class, 'createForm'], $admin);
$router->post('/admin/customers', [CustomerController::class, 'store'], $admin);
$router->get('/admin/customers/{id}/edit', [CustomerController::class, 'editForm'], $admin);
$router->post('/admin/customers/{id}', [CustomerController::class, 'update'], $admin);
$router->get('/admin/customers/{id}', [CustomerController::class, 'show'], $admin);
$router->get('/admin/employees', [EmployeeController::class, 'index'], $admin);
$router->get('/admin/employees/create', [EmployeeController::class, 'createForm'], $admin);
$router->post('/admin/employees', [EmployeeController::class, 'store'], $admin);
$router->get('/admin/employees/{id}/edit', [EmployeeController::class, 'editForm'], $admin);
$router->post('/admin/employees/{id}', [EmployeeController::class, 'update'], $admin);
$router->get('/admin/services', [ServiceController::class, 'index'], $admin);
$router->get('/admin/services/create', [ServiceController::class, 'createForm'], $admin);
$router->post('/admin/services', [ServiceController::class, 'store'], $admin);
$router->get('/admin/services/{id}', [ServiceController::class, 'show'], $admin);
$router->get('/admin/services/{id}/edit', [ServiceController::class, 'editForm'], $admin);
$router->post('/admin/services/{id}', [ServiceController::class, 'update'], $admin);
$router->post('/admin/services/{id}/items', [ServiceController::class, 'storeItem'], $admin);
$router->get('/admin/services/{id}/items/{itemId}/edit', [ServiceController::class, 'editItemForm'], $admin);
$router->post('/admin/services/{id}/items/{itemId}', [ServiceController::class, 'updateItem'], $admin);
$router->post('/admin/services/{id}/items/{itemId}/delete', [ServiceController::class, 'deleteItem'], $admin);
$router->get('/admin/branches', [BranchController::class, 'index'], $admin);
$router->get('/admin/branches/create', [BranchController::class, 'createForm'], $admin);
$router->post('/admin/branches', [BranchController::class, 'store'], $admin);
$router->get('/admin/branches/{id}/edit', [BranchController::class, 'editForm'], $admin);
$router->post('/admin/branches/{id}', [BranchController::class, 'update'], $admin);
$router->get('/admin/reports', [ReportController::class, 'index'], $admin);
$router->get('/admin/reminders', [ReminderController::class, 'index'], $admin);
$router->post('/admin/reminders/run', [ReminderController::class, 'run'], $admin);
$router->get('/admin/whatsapp-settings', [WhatsAppSettingsController::class, 'index'], $admin);
$router->post('/admin/whatsapp-settings', [WhatsAppSettingsController::class, 'save'], $admin);
$router->get('/admin/whatsapp-broadcast', [WhatsAppBroadcastController::class, 'index'], $admin);
$router->get('/admin/whatsapp-broadcast/preview', [WhatsAppBroadcastController::class, 'preview'], $admin);
$router->post('/admin/whatsapp-broadcast/preview', [WhatsAppBroadcastController::class, 'previewForm'], $admin);
$router->post('/admin/whatsapp-broadcast/templates', [WhatsAppBroadcastController::class, 'saveTemplate'], $admin);
$router->post('/admin/whatsapp-broadcast/templates/delete', [WhatsAppBroadcastController::class, 'deleteTemplate'], $admin);
$router->post('/admin/whatsapp-broadcast/send', [WhatsAppBroadcastController::class, 'send'], $admin);
$router->get('/admin/push-broadcast', [PushBroadcastController::class, 'index'], $admin);
$router->get('/admin/push-broadcast/preview', [PushBroadcastController::class, 'preview'], $admin);
$router->post('/admin/push-broadcast/preview', [PushBroadcastController::class, 'previewForm'], $admin);
$router->post('/admin/push-broadcast/templates', [PushBroadcastController::class, 'saveTemplate'], $admin);
$router->post('/admin/push-broadcast/templates/delete', [PushBroadcastController::class, 'deleteTemplate'], $admin);
$router->post('/admin/push-broadcast/send', [PushBroadcastController::class, 'send'], $admin);

// Branch manager
$router->get('/branch-manager', [DashboardController::class, 'branchManager'], $manager);
$router->get('/branch-manager/bookings', [BookingController::class, 'index'], $manager);
$router->get('/branch-manager/bookings/create', [BookingController::class, 'createForm'], $manager);
$router->post('/branch-manager/bookings', [BookingController::class, 'store'], $manager);
$router->get('/branch-manager/bookings/{id}', [BookingController::class, 'show'], $manager);
$router->post('/branch-manager/bookings/{id}/status', [BookingController::class, 'updateStatus'], $manager);
$router->post('/branch-manager/bookings/{id}/assign', [BookingController::class, 'assign'], $manager);
$router->get('/branch-manager/customers', [CustomerController::class, 'index'], $manager);
$router->get('/branch-manager/customers/{id}/edit', [CustomerController::class, 'editForm'], $manager);
$router->post('/branch-manager/customers/{id}', [CustomerController::class, 'update'], $manager);
$router->get('/branch-manager/customers/{id}', [CustomerController::class, 'show'], $manager);
$router->get('/branch-manager/staff', [EmployeeController::class, 'index'], $manager);
$router->get('/branch-manager/employees/create', [EmployeeController::class, 'createForm'], $manager);
$router->post('/branch-manager/employees', [EmployeeController::class, 'store'], $manager);
$router->get('/branch-manager/employees/{id}/edit', [EmployeeController::class, 'editForm'], $manager);
$router->post('/branch-manager/employees/{id}', [EmployeeController::class, 'update'], $manager);
$router->get('/branch-manager/reports', [ReportController::class, 'index'], $manager);

// Staff (home is /staff → jobs; role_home points here after login)
$router->get('/staff', [BookingController::class, 'staffJobs'], $staff);
$router->get('/staff/jobs', [BookingController::class, 'staffJobs'], $staff);
$router->get('/staff/bookings/{id}', [BookingController::class, 'show'], $staff);
$router->post('/staff/bookings/{id}/status', [BookingController::class, 'updateStatus'], $staff);
$router->get('/staff/attendance', [ProfileController::class, 'attendance'], $staff);
$router->post('/staff/attendance/check-in', [ProfileController::class, 'checkIn'], $staff);
$router->get('/staff/profile', [ProfileController::class, 'staff'], $staff);

// Customer
$router->get('/book', [BookingController::class, 'bookForm'], $customer);
$router->post('/book', [BookingController::class, 'bookStore'], $customer);
$router->get('/bookings', [BookingController::class, 'myBookings'], $customer);
$router->get('/bookings/{id}', [BookingController::class, 'show'], $customer);
$router->post('/bookings/{id}/status', [BookingController::class, 'updateStatus'], $customer);
$router->get('/history', [BookingController::class, 'history'], $customer);
$router->get('/profile', [ProfileController::class, 'customer'], $customer);
$router->post('/profile/addresses', [ProfileController::class, 'addAddress'], $customer);

// Shared
$router->get('/notifications', [NotificationController::class, 'index'], $auth);
$router->post('/notifications/read-all', [NotificationController::class, 'markAll'], $auth);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], $auth);
