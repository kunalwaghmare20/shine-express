<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiResponse;
use App\Core\Database;
use App\Core\Request;

/**
 * Extended mobile platform endpoints from Android-app-development.md
 */
final class ApiPlatformController
{
    public function sendOtp(): void
    {
        $email = trim((string) Request::input('email'));
        $purpose = strtoupper((string) Request::input('purpose', 'REGISTER'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::error('Valid email required', 422);
        }
        if (!in_array($purpose, ['REGISTER', 'LOGIN', 'RESET'], true)) {
            $purpose = 'REGISTER';
        }

        $otp = (string) random_int(100000, 999999);
        $db = Database::connection();
        $db->prepare('DELETE FROM email_otps WHERE email = ? AND purpose = ?')->execute([$email, $purpose]);
        $db->prepare(
            'INSERT INTO email_otps (id, email, otp, purpose, expires_at) VALUES (?,?,?,?,DATE_ADD(NOW(), INTERVAL 15 MINUTE))'
        )->execute([generate_id(), $email, $otp, $purpose]);

        // Shared hosting without mailer: return OTP in debug for QA; production should email it.
        $payload = ['expiresInMinutes' => 15];
        if (filter_var(env_file('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            $payload['debugOtp'] = $otp;
        }

        ApiResponse::success($payload, 200, 'OTP sent');
    }

    public function verifyOtp(): void
    {
        $email = trim((string) Request::input('email'));
        $otp = trim((string) Request::input('otp'));
        $purpose = strtoupper((string) Request::input('purpose', 'REGISTER'));

        $stmt = Database::connection()->prepare(
            'SELECT * FROM email_otps WHERE email = ? AND purpose = ? AND otp = ? AND verified_at IS NULL
             AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$email, $purpose, $otp]);
        $row = $stmt->fetch();
        if ($row === false) {
            ApiResponse::error('Invalid or expired OTP', 422);
        }

        Database::connection()->prepare('UPDATE email_otps SET verified_at = NOW() WHERE id = ?')->execute([$row['id']]);
        ApiResponse::success(['verified' => true], 200, 'OTP verified');
    }

    public function forgotPassword(): void
    {
        $email = trim((string) Request::input('email'));
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            // Do not reveal whether email exists
            ApiResponse::success(null, 200, 'If the email exists, a reset link was sent');
        }

        $token = bin2hex(random_bytes(32));
        Database::connection()->prepare(
            'INSERT INTO password_resets (id, email, token, expires_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute([generate_id(), $email, $token]);

        $payload = [];
        if (filter_var(env_file('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            $payload['debugToken'] = $token;
        }
        ApiResponse::success($payload, 200, 'If the email exists, a reset link was sent');
    }

    public function resetPassword(): void
    {
        $token = (string) Request::input('token');
        $password = (string) Request::input('password');
        if (strlen($password) < 6) {
            ApiResponse::error('Password must be at least 6 characters', 422);
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if ($row === false) {
            ApiResponse::error('Invalid or expired reset token', 422);
        }

        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['email']]);
        Database::connection()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([$row['id']]);

        ApiResponse::success(null, 200, 'Password updated');
    }

    public function home(): void
    {
        $db = Database::connection();
        $categories = $db->query(
            'SELECT id, name, slug, icon, description FROM service_categories WHERE is_active = 1 ORDER BY sort_order'
        )->fetchAll();

        $featured = $db->query(
            'SELECT s.id, s.name, s.description, s.base_price AS basePrice, s.duration, c.name AS categoryName
             FROM services s JOIN service_categories c ON c.id = s.category_id
             WHERE s.is_active = 1 ORDER BY s.sort_order LIMIT 8'
        )->fetchAll();
        foreach ($featured as &$s) {
            $s['basePrice'] = (float) $s['basePrice'];
        }

        $offers = $db->query(
            'SELECT id, title, description, code, discount_percent AS discountPercent, discount_amount AS discountAmount
             FROM offers WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY created_at DESC LIMIT 10'
        )->fetchAll();

        $upcoming = [];
        $recent = [];
        if (ApiAuth::role() === 'CUSTOMER' && ApiAuth::customerId()) {
            $cid = ApiAuth::customerId();
            $u = $db->prepare(
                "SELECT b.id, b.booking_number AS bookingNumber, b.status, b.scheduled_date AS scheduledDate,
                        b.scheduled_time AS scheduledTime, s.name AS serviceName, b.total_amount AS totalAmount
                 FROM bookings b JOIN services s ON s.id = b.service_id
                 WHERE b.customer_id = ? AND b.status NOT IN ('COMPLETED','CANCELLED')
                 ORDER BY b.scheduled_date ASC LIMIT 5"
            );
            $u->execute([$cid]);
            $upcoming = $u->fetchAll();

            $r = $db->prepare(
                "SELECT b.id, b.booking_number AS bookingNumber, b.status, b.scheduled_date AS scheduledDate,
                        b.scheduled_time AS scheduledTime, s.name AS serviceName, b.total_amount AS totalAmount
                 FROM bookings b JOIN services s ON s.id = b.service_id
                 WHERE b.customer_id = ?
                 ORDER BY b.created_at DESC LIMIT 5"
            );
            $r->execute([$cid]);
            $recent = $r->fetchAll();
        }

        ApiResponse::success([
            'categories' => $categories,
            'featuredServices' => $featured,
            'offers' => $offers,
            'upcomingBookings' => $upcoming,
            'recentBookings' => $recent,
            'support' => [
                'phone' => env_file('SUPPORT_PHONE', '919673522737'),
                'whatsapp' => env_file('SUPPORT_WHATSAPP', '919673522737'),
            ],
            'payments' => (new \App\Services\PaymentService())->publicConfig(),
        ]);
    }

    public function search(): void
    {
        $q = '%' . trim((string) Request::input('q', '')) . '%';
        $stmt = Database::connection()->prepare(
            'SELECT s.id, s.name, s.description, s.base_price AS basePrice, s.duration, c.name AS categoryName
             FROM services s JOIN service_categories c ON c.id = s.category_id
             WHERE s.is_active = 1 AND (s.name LIKE ? OR s.description LIKE ? OR c.name LIKE ?)
             ORDER BY s.name LIMIT 30'
        );
        $stmt->execute([$q, $q, $q]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$s) {
            $s['basePrice'] = (float) $s['basePrice'];
        }
        ApiResponse::success($rows);
    }

    public function serviceDetail(string $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT s.*, c.name AS categoryName FROM services s
             JOIN service_categories c ON c.id = s.category_id WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if (!$service) {
            ApiResponse::error('Service not found', 404);
        }

        $items = $db->prepare(
            'SELECT id, name, description, price, duration FROM service_items WHERE service_id = ? AND is_active = 1 ORDER BY sort_order'
        );
        $items->execute([$id]);

        $faqs = $db->prepare(
            'SELECT id, question, answer FROM service_faqs WHERE service_id = ? OR service_id IS NULL ORDER BY sort_order'
        );
        $faqs->execute([$id]);

        $reviews = $db->prepare(
            'SELECT r.rating, r.comment, r.created_at AS createdAt, u.first_name AS firstName
             FROM reviews r
             JOIN bookings b ON b.id = r.booking_id
             JOIN customers c ON c.id = r.customer_id
             JOIN users u ON u.id = c.user_id
             WHERE b.service_id = ?
             ORDER BY r.created_at DESC LIMIT 20'
        );
        $reviews->execute([$id]);

        $avg = $db->prepare(
            'SELECT AVG(r.rating) FROM reviews r JOIN bookings b ON b.id = r.booking_id WHERE b.service_id = ?'
        );
        $avg->execute([$id]);

        ApiResponse::success([
            'id' => $service['id'],
            'name' => $service['name'],
            'description' => $service['description'],
            'basePrice' => (float) $service['base_price'],
            'duration' => (int) $service['duration'],
            'categoryName' => $service['categoryName'],
            'images' => json_decode((string) ($service['images'] ?? '[]'), true) ?: [],
            'items' => array_map(fn ($i) => [
                'id' => $i['id'],
                'name' => $i['name'],
                'description' => $i['description'],
                'price' => (float) $i['price'],
                'duration' => $i['duration'] !== null ? (int) $i['duration'] : null,
            ], $items->fetchAll()),
            'faqs' => $faqs->fetchAll(),
            'ratingAvg' => round((float) ($avg->fetchColumn() ?: 0), 1),
            'reviews' => $reviews->fetchAll(),
        ]);
    }

    public function createTicket(): void
    {
        $id = generate_id();
        Database::connection()->prepare(
            'INSERT INTO support_tickets (id, user_id, booking_id, subject, message) VALUES (?,?,?,?,?)'
        )->execute([
            $id,
            ApiAuth::id(),
            Request::input('bookingId'),
            Request::input('subject'),
            Request::input('message'),
        ]);
        ApiResponse::success(['id' => $id], 201, 'Ticket created');
    }

    public function tickets(): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, subject, message, status, booking_id AS bookingId, created_at AS createdAt
             FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([ApiAuth::id()]);
        ApiResponse::success($stmt->fetchAll());
    }

    public function loyalty(): void
    {
        $uid = ApiAuth::id();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM loyalty_accounts WHERE user_id = ?');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if ($row === false) {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $db->prepare('INSERT INTO loyalty_accounts (user_id, points, referral_code) VALUES (?,?,?)')
                ->execute([$uid, 0, $code]);
            $row = ['points' => 0, 'referral_code' => $code];
        }
        ApiResponse::success([
            'points' => (int) $row['points'],
            'referralCode' => $row['referral_code'],
        ]);
    }

    public function registerDevice(): void
    {
        $token = (string) Request::input('token');
        $platform = strtoupper((string) Request::input('platform', 'ANDROID'));
        if ($token === '') {
            ApiResponse::error('token required', 422);
        }
        if (!in_array($platform, ['ANDROID', 'IOS', 'WEB'], true)) {
            $platform = 'ANDROID';
        }
        $db = Database::connection();
        $db->prepare('DELETE FROM device_tokens WHERE token = ?')->execute([$token]);
        $db->prepare('INSERT INTO device_tokens (id, user_id, token, platform) VALUES (?,?,?,?)')
            ->execute([generate_id(), ApiAuth::id(), $token, $platform]);
        ApiResponse::success(null, 201, 'Device registered');
    }

    public function staffDashboard(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $db = Database::connection();
        $today = $db->prepare(
            "SELECT COUNT(*) FROM booking_assignments ba JOIN bookings b ON b.id = ba.booking_id
             WHERE ba.employee_id = ? AND b.scheduled_date = CURDATE()"
        );
        $today->execute([$emp['id']]);

        $upcoming = $db->prepare(
            "SELECT COUNT(*) FROM booking_assignments ba JOIN bookings b ON b.id = ba.booking_id
             WHERE ba.employee_id = ? AND b.scheduled_date > CURDATE() AND b.status NOT IN ('COMPLETED','CANCELLED')"
        );
        $upcoming->execute([$emp['id']]);

        $completed = $db->prepare(
            "SELECT COUNT(*) FROM booking_assignments ba JOIN bookings b ON b.id = ba.booking_id
             WHERE ba.employee_id = ? AND b.status = 'COMPLETED'"
        );
        $completed->execute([$emp['id']]);

        $att = $db->prepare('SELECT * FROM attendance WHERE employee_id = ? AND date = CURDATE() LIMIT 1');
        $att->execute([$emp['id']]);
        $attendance = $att->fetch();

        ApiResponse::success([
            'todayJobs' => (int) $today->fetchColumn(),
            'upcomingJobs' => (int) $upcoming->fetchColumn(),
            'completedJobs' => (int) $completed->fetchColumn(),
            'attendance' => $attendance ? [
                'status' => $attendance['status'],
                'checkIn' => $attendance['check_in'],
                'checkOut' => $attendance['check_out'],
            ] : null,
        ]);
    }

    public function applyLeave(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $id = generate_id();
        Database::connection()->prepare(
            'INSERT INTO leave_requests (id, employee_id, from_date, to_date, reason) VALUES (?,?,?,?,?)'
        )->execute([
            $id,
            $emp['id'],
            Request::input('fromDate'),
            Request::input('toDate'),
            Request::input('reason'),
        ]);
        ApiResponse::success(['id' => $id], 201, 'Leave applied');
    }

    public function leaves(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $stmt = Database::connection()->prepare(
            'SELECT id, from_date AS fromDate, to_date AS toDate, reason, status, created_at AS createdAt
             FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$emp['id']]);
        ApiResponse::success($stmt->fetchAll());
    }

    public function checkIn(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $db = Database::connection();
        $today = date('Y-m-d');
        $exists = $db->prepare('SELECT id FROM attendance WHERE employee_id = ? AND date = ?');
        $exists->execute([$emp['id'], $today]);
        if ($exists->fetch()) {
            ApiResponse::error('Already checked in', 422);
        }
        $db->prepare(
            'INSERT INTO attendance (id, employee_id, date, check_in, status, latitude, longitude)
             VALUES (?,?,?,NOW(),"PRESENT",?,?)'
        )->execute([
            generate_id(),
            $emp['id'],
            $today,
            Request::input('latitude'),
            Request::input('longitude'),
        ]);
        ApiResponse::success(null, 201, 'Checked in');
    }

    public function checkOut(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM attendance WHERE employee_id = ? AND date = CURDATE() LIMIT 1');
        $stmt->execute([$emp['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            ApiResponse::error('Check in first', 422);
        }
        $db->prepare('UPDATE attendance SET check_out = NOW() WHERE id = ?')->execute([$row['id']]);
        ApiResponse::success(null, 200, 'Checked out');
    }

    public function addJobNote(string $bookingId): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $id = generate_id();
        Database::connection()->prepare(
            'INSERT INTO job_notes (id, booking_id, employee_id, note) VALUES (?,?,?,?)'
        )->execute([$id, $bookingId, $emp['id'], Request::input('note')]);
        ApiResponse::success(['id' => $id], 201, 'Note added');
    }

    public function upsertChecklist(string $bookingId): void
    {
        $items = Request::input('items', []);
        if (!is_array($items)) {
            ApiResponse::error('items array required', 422);
        }
        $db = Database::connection();
        $db->prepare('DELETE FROM job_checklist_items WHERE booking_id = ?')->execute([$bookingId]);
        $ins = $db->prepare(
            'INSERT INTO job_checklist_items (id, booking_id, label, is_done) VALUES (?,?,?,?)'
        );
        foreach ($items as $item) {
            $ins->execute([
                generate_id(),
                $bookingId,
                $item['label'] ?? 'Item',
                !empty($item['isDone']) ? 1 : 0,
            ]);
        }
        ApiResponse::success(null, 200, 'Checklist saved');
    }

    public function rejectJob(string $bookingId): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }
        $reason = (string) Request::input('reason', 'Rejected by staff');
        $db = Database::connection();

        $assignment = $db->prepare(
            'SELECT id, rejected_at FROM booking_assignments WHERE booking_id = ? AND employee_id = ?'
        );
        $assignment->execute([$bookingId, $emp['id']]);
        $row = $assignment->fetch();
        if ($row === false) {
            ApiResponse::error('Job not assigned to you', 403);
        }
        if ($row['rejected_at'] !== null) {
            ApiResponse::error('You have already declined this job', 400);
        }

        $db->prepare(
            'UPDATE booking_assignments SET rejected_at = NOW(), rejection_reason = ? WHERE booking_id = ? AND employee_id = ?'
        )->execute([$reason, $bookingId, $emp['id']]);

        $remaining = $db->prepare(
            'SELECT COUNT(*) FROM booking_assignments WHERE booking_id = ? AND rejected_at IS NULL'
        );
        $remaining->execute([$bookingId]);
        $activeCount = (int) $remaining->fetchColumn();

        if ($activeCount === 0) {
            try {
                (new \App\Services\BookingService())->updateStatus(
                    $bookingId,
                    \App\Helpers\BookingStatus::REJECTED,
                    (string) ApiAuth::id(),
                    $reason
                );
            } catch (\Throwable $e) {
                ApiResponse::error($e->getMessage(), 400);
            }
            ApiResponse::success(null, 200, 'Job rejected — all assigned staff declined');
            return;
        }

        ApiResponse::success(
            ['remainingStaff' => $activeCount],
            200,
            'You declined this job. Other assigned staff can still accept it.'
        );
    }

    public function updateLocation(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }

        $latitude = Request::input('latitude');
        $longitude = Request::input('longitude');
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            ApiResponse::error('latitude and longitude are required', 422);
        }

        Database::connection()->prepare(
            'UPDATE employees SET current_latitude = ?, current_longitude = ?, location_updated_at = NOW(3) WHERE id = ?'
        )->execute([(float) $latitude, (float) $longitude, $emp['id']]);

        ApiResponse::success([
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ], 200, 'Location updated');
    }

    public function staffEarnings(): void
    {
        $emp = ApiAuth::employee();
        if (!$emp) {
            ApiResponse::error('Employee profile missing', 404);
        }

        $db = Database::connection();
        $employeeId = $emp['id'];
        $perJobBonus = (float) env_file('STAFF_PER_JOB_BONUS', 200);
        $baseSalary = (float) ($emp['salary'] ?? 0);

        $periods = [
            'today' => 'DATE(b.updated_at) = CURDATE()',
            'week' => 'b.updated_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)',
            'month' => 'YEAR(b.updated_at) = YEAR(CURDATE()) AND MONTH(b.updated_at) = MONTH(CURDATE())',
        ];

        $result = [
            'baseSalary' => $baseSalary,
            'perJobBonus' => $perJobBonus,
            'currency' => 'INR',
        ];

        foreach ($periods as $key => $where) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS completedJobs, COALESCE(SUM(b.total_amount), 0) AS jobRevenue
                 FROM booking_assignments ba
                 JOIN bookings b ON b.id = ba.booking_id
                 WHERE ba.employee_id = ? AND ba.rejected_at IS NULL AND b.status = 'COMPLETED'
                   AND {$where}"
            );
            $stmt->execute([$employeeId]);
            $row = $stmt->fetch();
            $jobs = (int) ($row['completedJobs'] ?? 0);
            $revenue = (float) ($row['jobRevenue'] ?? 0);
            $result[$key] = [
                'completedJobs' => $jobs,
                'jobRevenue' => $revenue,
                'estimatedEarnings' => round($jobs * $perJobBonus, 2),
            ];
        }

        $dailyBase = $baseSalary > 0 ? round($baseSalary / 26, 2) : 0.0;
        $result['dailyBaseEstimate'] = $dailyBase;
        $result['month']['estimatedTotal'] = round($result['month']['estimatedEarnings'] + $dailyBase * 26, 2);

        ApiResponse::success($result);
    }
}
