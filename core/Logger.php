<?php
/**
 * ShopWise AI - Audit Logger
 *
 * Records all significant user actions to the audit_logs table.
 * Captures who did what, when, from where, and what changed.
 * Wrapped in try/catch at every call - NEVER throws or breaks calling code.
 *
 * @package ShopWiseAI\Core
 */

declare(strict_types=1);

class Logger
{
    /** @var PDO Database connection */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Write an audit log entry.
     *
     * @param string     $module       Module name (e.g. 'products', 'pos', 'users')
     * @param string     $action       Action performed (e.g. 'create', 'update', 'delete', 'login')
     * @param int|null   $recordId     Primary key of the affected record
     * @param mixed      $oldValue     Previous state (will be JSON-encoded)
     * @param mixed      $newValue     New state (will be JSON-encoded)
     * @param string     $description  Human-readable description of the action
     * @return void
     */
    public function log(
        string  $module,
        string  $action,
        ?int    $recordId    = null,
        mixed   $oldValue    = null,
        mixed   $newValue    = null,
        string  $description = ''
    ): void {
        try {
            $user      = Auth::user();
            $userId    = $user['user_id'] ?? null;
            $branchId  = $user['branch_id'] ?? BRANCH_ID;
            $ip        = $this->getIpAddress();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Encode old/new values as JSON
            $oldJson = $oldValue !== null
                ? json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : null;

            $newJson = $newValue !== null
                ? json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : null;

            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs
                    (user_id, branch_id, module, action, record_id,
                     old_value, new_value, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $userId,
                $branchId,
                substr($module, 0, 50),
                substr($action, 0, 80),
                $recordId,
                $oldJson,
                $newJson,
                substr($description, 0, 1000),
                $ip,
                substr($userAgent, 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Log failure to file - never propagate
            $this->writeToFile(
                "[AUDIT_LOG_FAIL] {$module}.{$action} - " . $e->getMessage()
            );
        }
    }

    /**
     * Log a system event (no user context required).
     *
     * @param string $event    Event description
     * @param string $details  Optional additional details
     */
    public function system(string $event, string $details = ''): void
    {
        $this->log('system', $event, null, null, null, $details);
    }

    /**
     * Get the real client IP address.
     * Handles proxies and load balancers.
     *
     * @return string
     */
    private function getIpAddress(): string
    {
        $candidates = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For may contain comma-separated list: take first
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Write a failure message to the flat-file log.
     * Used when DB logging fails (e.g. DB is down).
     *
     * @param string $message
     */
    private function writeToFile(string $message): void
    {
        $logFile = LOG_PATH . 'audit_errors.log';
        $line    = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}