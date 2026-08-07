<?php

declare(strict_types=1);

/**
 * Mobile REST API routes (/api/v1/...)
 * @var App\Core\Router $router
 */

use App\Controllers\Api\ApiAddressController;
use App\Controllers\Api\ApiAuthController;
use App\Controllers\Api\ApiBookingController;
use App\Controllers\Api\ApiCatalogController;
use App\Controllers\Api\ApiEmployeeJobController;
use App\Controllers\Api\ApiPlatformController;
use App\Middleware\ApiAuthMiddleware;

$apiAuth = [ApiAuthMiddleware::class];
$apiCustomer = [ApiAuthMiddleware::class, 'apiRole:CUSTOMER'];
$apiStaff = [ApiAuthMiddleware::class, 'apiRole:SERVICE_STAFF,BRANCH_MANAGER'];

$router->post('/api/v1/auth/register', [ApiAuthController::class, 'register']);
$router->post('/api/v1/auth/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/auth/otp/send', [ApiPlatformController::class, 'sendOtp']);
$router->post('/api/v1/auth/otp/verify', [ApiPlatformController::class, 'verifyOtp']);
$router->post('/api/v1/auth/forgot-password', [ApiPlatformController::class, 'forgotPassword']);
$router->post('/api/v1/auth/reset-password', [ApiPlatformController::class, 'resetPassword']);
$router->get('/api/v1/auth/me', [ApiAuthController::class, 'me'], $apiAuth);
$router->post('/api/v1/auth/logout', [ApiAuthController::class, 'logout'], $apiAuth);
$router->post('/api/v1/auth/profile', [ApiAuthController::class, 'updateProfile'], $apiAuth);
$router->post('/api/v1/devices', [ApiPlatformController::class, 'registerDevice'], $apiAuth);

$router->get('/api/v1/home', [ApiPlatformController::class, 'home'], $apiAuth);
$router->get('/api/v1/search', [ApiPlatformController::class, 'search'], $apiAuth);
$router->get('/api/v1/catalog', [ApiCatalogController::class, 'services'], $apiAuth);
$router->get('/api/v1/services/{id}', [ApiPlatformController::class, 'serviceDetail'], $apiAuth);

$router->get('/api/v1/addresses', [ApiAddressController::class, 'index'], $apiCustomer);
$router->post('/api/v1/addresses', [ApiAddressController::class, 'store'], $apiCustomer);
$router->post('/api/v1/addresses/{id}', [ApiAddressController::class, 'update'], $apiCustomer);
$router->post('/api/v1/addresses/{id}/delete', [ApiAddressController::class, 'destroy'], $apiCustomer);

$router->get('/api/v1/bookings', [ApiBookingController::class, 'index'], $apiCustomer);
$router->post('/api/v1/bookings', [ApiBookingController::class, 'store'], $apiCustomer);
$router->get('/api/v1/bookings/{id}', [ApiBookingController::class, 'show'], $apiCustomer);
$router->post('/api/v1/bookings/{id}/complete', [ApiBookingController::class, 'complete'], $apiCustomer);
$router->post('/api/v1/bookings/{id}/review', [ApiBookingController::class, 'review'], $apiCustomer);

$router->get('/api/v1/loyalty', [ApiPlatformController::class, 'loyalty'], $apiCustomer);
$router->get('/api/v1/support/tickets', [ApiPlatformController::class, 'tickets'], $apiAuth);
$router->post('/api/v1/support/tickets', [ApiPlatformController::class, 'createTicket'], $apiAuth);

$router->get('/api/v1/staff/dashboard', [ApiPlatformController::class, 'staffDashboard'], $apiStaff);
$router->post('/api/v1/staff/attendance/check-in', [ApiPlatformController::class, 'checkIn'], $apiStaff);
$router->post('/api/v1/staff/attendance/check-out', [ApiPlatformController::class, 'checkOut'], $apiStaff);
$router->get('/api/v1/staff/leaves', [ApiPlatformController::class, 'leaves'], $apiStaff);
$router->post('/api/v1/staff/leaves', [ApiPlatformController::class, 'applyLeave'], $apiStaff);

$router->get('/api/v1/jobs', [ApiEmployeeJobController::class, 'index'], $apiStaff);
$router->get('/api/v1/jobs/{id}', [ApiEmployeeJobController::class, 'show'], $apiStaff);
$router->post('/api/v1/jobs/{id}/accept', [ApiEmployeeJobController::class, 'accept'], $apiStaff);
$router->post('/api/v1/jobs/{id}/reject', [ApiPlatformController::class, 'rejectJob'], $apiStaff);
$router->post('/api/v1/jobs/{id}/start', [ApiEmployeeJobController::class, 'start'], $apiStaff);
$router->post('/api/v1/jobs/{id}/complete', [ApiEmployeeJobController::class, 'complete'], $apiStaff);
$router->post('/api/v1/jobs/{id}/photos', [ApiEmployeeJobController::class, 'uploadPhoto'], $apiStaff);
$router->post('/api/v1/jobs/{id}/notes', [ApiPlatformController::class, 'addJobNote'], $apiStaff);
$router->post('/api/v1/jobs/{id}/checklist', [ApiPlatformController::class, 'upsertChecklist'], $apiStaff);
