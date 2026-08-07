<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
        );
        $stmt->execute([Auth::id()]);
        $this->view('notifications/index', [
            'title' => 'Notifications',
            'items' => $stmt->fetchAll(),
            'user' => Auth::user(),
        ], 'layouts/dashboard');
    }

    public function markRead(string $id): void
    {
        Database::connection()->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW(3) WHERE id = ? AND user_id = ?'
        )->execute([$id, Auth::id()]);
        $this->redirect('/notifications');
    }

    public function markAll(): void
    {
        Database::connection()->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW(3) WHERE user_id = ? AND is_read = 0'
        )->execute([Auth::id()]);
        flash_success('All marked as read');
        $this->redirect('/notifications');
    }
}
