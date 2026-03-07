<?php

/**
 * ShopWise AI - Authentication & Session Manager
 *
 * Handles all authentication concerns: password/PIN login, session management,
 * CSRF token generation/verification, role guards, and account lockout.
 *
 * @package ShopWiseAI\Core
 */

class Auth
{
    /**
     * Attempt password-based login.
     *
     * Verifies credentials, checks lockout status, records login attempt,
     * regenerates session on success.
     *
     * @param string $username  Submitted username
     * @param string $password  Submitted plain-text password
     * @return array ['success'=>bool, 'message'=>string, 'user'=>array|null]
     */
    public static function login(string $username, string $password): array
    {
        $db = Database::getInstance();

        // Fetch user by username
        $stmt = $db->prepare(
            "SELECT u.*, r.role_slug, r.role_name
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             WHERE u.username = ? AND u.status != 'inactive'
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Record failed attempt helper
        $recordFail = function (string $reason) use ($db, $username, $user): void {
            // Increment failed_logins if user exists
            if ($user) {
                $db->prepare(
                    "UPDATE users SET failed_logins = failed_logins + 1,
                     locked_until = IF(failed_logins + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), locked_until)
                     WHERE user_id = ?"
                )->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION, $user['user_id']]);
            }
            // Log the attempt
            $db->prepare(
                "INSERT INTO login_logs (user_id, username_attempted, ip_address, success, failure_reason, attempted_at)
                 VALUES (?, ?, ?, 0, ?, NOW())"
            )->execute([$user['user_id'] ?? null, $username, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $reason]);
        };

        if (!$user) {
            $recordFail('user_not_found');
            return ['success' => false, 'message' => 'Invalid username or password.', 'user' => null];
        }

        // Check if account is locked
        if ($user['status'] === 'locked') {
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                return [
                    'success' => false,
                    'message' => "Account is locked. Try again in {$minutesLeft} minute(s).",
                    'user'    => null
                ];
            } else {
                // Lockout expired - unlock
                $db->prepare("UPDATE users SET status='active', failed_logins=0, locked_until=NULL WHERE user_id=?")
                   ->execute([$user['user_id']]);
                $user['status'] = 'active';
            }
        }

        // Check failed attempts and enforce lockout
        if ((int)$user['failed_logins'] >= MAX_LOGIN_ATTEMPTS) {
            $db->prepare(
                "UPDATE users SET status='locked', locked_until=DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE user_id=?"
            )->execute([LOCKOUT_DURATION, $user['user_id']]);

            $recordFail('max_attempts_exceeded');
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Account locked for ' . (LOCKOUT_DURATION / 60) . ' minutes.',
                'user'    => null
            ];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $recordFail('wrong_password');
            $remaining = MAX_LOGIN_ATTEMPTS - ((int)$user['failed_logins'] + 1);
            $msg = $remaining > 0
                ? "Invalid password. {$remaining} attempt(s) remaining."
                : "Invalid password. Account will be locked on next failure.";
            return ['success' => false, 'message' => $msg, 'user' => null];
        }

        // Success - reset failed logins, update last_login
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db->prepare(
            "UPDATE users SET failed_logins=0, locked_until=NULL, last_login=NOW(), last_ip=? WHERE user_id=?"
        )->execute([$ip, $user['user_id']]);

        // Log success
        $db->prepare(
            "INSERT INTO login_logs (user_id, username_attempted, ip_address, success, attempted_at)
             VALUES (?, ?, ?, 1, NOW())"
        )->execute([$user['user_id'], $username, $ip]);

        // Regenerate session to prevent fixation
        session_regenerate_id(true);

        // Populate session
        self::setSession($user);

        return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
    }
    /**
     * Attempt PIN-based login (for cashiers).
     *
     * @param string $pin        6-digit PIN (plain text)
     * @param int    $cashierId  User ID to authenticate
     * @return array ['success'=>bool, 'message'=>string, 'user'=>array|null]
     */
    public static function loginByPin(string $pin, int $cashierId): array
    {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT u.*, r.role_slug, r.role_name
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             WHERE u.user_id = ? AND u.status = 'active' AND u.role_id IN (
                 SELECT role_id FROM roles WHERE role_slug = 'cashier'
             )
             LIMIT 1"
        );
        $stmt->execute([$cashierId]);
        $user = $stmt->fetch();

        if (!$user || empty($user['pin_hash'])) {
            return ['success' => false, 'message' => 'Invalid cashier or PIN not set.', 'user' => null];
        }

        if (!password_verify($pin, $user['pin_hash'])) {
            return ['success' => false, 'message' => 'Incorrect PIN.', 'user' => null];
        }

        // Update last login
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db->prepare("UPDATE users SET last_login=NOW(), last_ip=? WHERE user_id=?")
           ->execute([$ip, $user['user_id']]);

        session_regenerate_id(true);
        self::setSession($user);

        return ['success' => true, 'message' => 'PIN login successful.', 'user' => $user];
    }

    /**
     * Verify a manager PIN for POS authorization (void, discount, etc.).
     * Does NOT change the current session.
     *
     * @param string $pin       PIN to verify
     * @param int    $userId    User ID to verify against
     * @return bool
     */
    public static function verifyManagerPin(string $pin, int $userId): bool
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT pin_hash FROM users WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || empty($row['pin_hash'])) {
            return false;
        }

        return password_verify($pin, $row['pin_hash']);
    }

    /**
     * Log the current user out.
     * Logs to audit, destroys session, redirects to login.
     *
     * @return void
     */
    public static function logout(): void
    {
        $user = self::user();

        if ($user) {
            // Log logout to audit
            try {
                $logger = new Logger();
                $logger->log('auth', 'logout', $user['user_id'], null, null, 'User logged out.');
            } catch (Throwable) {
                // Never break on logger failure
            }
        }

        // Destroy session completely
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Ensure the current request is authenticated.
     * Redirects to login if not. Optionally enforces role.
     *
     * @param array $roles  Allowed role slugs. Empty = any role.
     */
    public static function guard(array $roles = []): void
    {
        if (!self::isLoggedIn()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session expired. Please log in.']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if (!empty($roles)) {
            $user = self::user();
            if (!in_array($user['role_slug'], $roles, true)) {
                http_response_code(403);
                $errorFile = VIEW_PATH . 'errors/403.php';
                if (file_exists($errorFile)) {
                    include $errorFile;
                } else {
                    echo '<h1>403 Forbidden</h1>';
                }
                exit;
            }
        }

        self::checkSessionTimeout();
    }

    /**
     * Check if a user is currently logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['sw_user']['user_id']);
    }

    /**
     * Get the current authenticated user from session.
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        return $_SESSION['sw_user'] ?? null;
    }

    /**
     * Generate and store a new CSRF token.
     *
     * @return string  The generated token
     */
    public static function csrfGenerate(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the submitted CSRF token.
     * Terminates with 403 on mismatch.
     *
     * @return void
     */
    public static function csrfVerify(): void
    {
        $submitted = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored    = $_SESSION['csrf_token'] ?? '';

        if (empty($submitted) || empty($stored) || !hash_equals($stored, $submitted)) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
            } else {
                $errorFile = VIEW_PATH . 'errors/403.php';
                if (file_exists($errorFile)) {
                    include $errorFile;
                } else {
                    echo '<h1>403 - Forbidden: CSRF token mismatch</h1>';
                }
            }
            exit;
        }

        // Rotate token after successful verification
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));

        // Expose the refreshed token for AJAX clients so subsequent requests stay valid
        header('X-CSRF-TOKEN: ' . $_SESSION['csrf_token']);
    }

    /**
     * Check if the session has exceeded the timeout period.
     * Shows a warning at SESSION_WARNING seconds remaining.
     * Auto-logs out at timeout.
     *
     * @return void
     */
    public static function checkSessionTimeout(): void
    {
        if (!self::isLoggedIn()) {
            return;
        }

        $lastActivity = $_SESSION['sw_last_activity'] ?? time();
        $elapsed      = time() - $lastActivity;

        if ($elapsed >= SESSION_TIMEOUT) {
            self::logout();
            header('Location: ' . BASE_URL . '/login?reason=timeout');
            exit;
        }

        // Update last activity time
        $_SESSION['sw_last_activity'] = time();
    }

    /**
     * Store authenticated user data in the session.
     *
     * @param array $user  User row from DB (with role_slug, role_name joined)
     */
    private static function setSession(array $user): void
    {
        $_SESSION['sw_user'] = [
            'user_id'    => (int)$user['user_id'],
            'username'   => $user['username'],
            'full_name'  => $user['full_name'],
            'email'      => $user['email'] ?? '',
            'role_id'    => (int)$user['role_id'],
            'role_slug'  => $user['role_slug'],
            'role_name'  => $user['role_name'],
            'branch_id'  => (int)($user['branch_id'] ?? BRANCH_ID),
            'avatar_path'=> $user['avatar_path'] ?? null,
        ];

        $_SESSION['sw_last_activity'] = time();
        $_SESSION['sw_login_time']    = time();
    }
}
