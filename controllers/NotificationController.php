<?php
declare(strict_types=1);

class NotificationController extends ModuleController
{
    protected string $module = 'dashboard';
    protected string $title = 'Notifications';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        
        $user = $this->user();
        $userRole = $user['role_slug'] ?? 'guest';
        
        $stmt = $db->prepare(
            "SELECT notif_id, notif_type, title, message, link, is_read, created_at
             FROM notifications
             WHERE (
                 user_id = ? 
                 OR user_id IS NULL 
                 OR FIND_IN_SET(?, REPLACE(role_target, ' ', ''))
             )
             ORDER BY is_read ASC, created_at DESC
             LIMIT 50"
        );
        $stmt->execute([(int)$user['user_id'], $userRole]);
        
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'notifications']);
    }

    public function markRead(string $id): void
    {
        $this->requireAuth();
        Auth::csrfVerify();
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE notifications
             SET is_read = 1
             WHERE notif_id = ?"
        );
        $stmt->execute([(int)$id]);
        
        $this->done('Notification marked as read.', '/notifications');
    }

    public function markAllRead(): void
    {
        $this->requireAuth();
        Auth::csrfVerify();
        
        $db = Database::getInstance();
        $user = $this->user();
        $userRole = $user['role_slug'] ?? 'guest';
        
        $stmt = $db->prepare(
            "UPDATE notifications
             SET is_read = 1
             WHERE is_read = 0
               AND (
                   user_id = ? 
                   OR user_id IS NULL 
                   OR FIND_IN_SET(?, REPLACE(role_target, ' ', ''))
               )"
        );
        $stmt->execute([(int)$user['user_id'], $userRole]);
        
        $this->done('All notifications marked as read.', '/notifications');
    }

    public function delete(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM notifications WHERE notif_id = ?");
        $stmt->execute([(int)$id]);
        
        $logger = new Logger();
        $logger->log('notifications', 'delete', (int)$id, null, null, 'Notification deleted.');
        
        $this->done('Notification deleted.', '/notifications');
    }
}
