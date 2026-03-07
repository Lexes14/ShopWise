<?php
declare(strict_types=1);

class UserController extends ModuleController
{
    protected string $module = 'users';
    protected string $title = 'User Management';

    public function index(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT u.user_id, u.full_name, u.username, u.status, r.role_name
             FROM users u
             JOIN roles r ON r.role_id = u.role_id
             ORDER BY u.full_name ASC"
        );
        $this->moduleIndex($stmt->fetchAll());
    }

    public function create(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();
        $roles = $db->query("SELECT role_id, role_name, description FROM roles ORDER BY role_id")->fetchAll();
        $this->moduleSection('create', ['extra' => ['roles' => $roles]]);
    }
    public function edit(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();
        $userStmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $userStmt->execute([(int)$id]);
        $targetUser = $userStmt->fetch();
        if (!$targetUser) {
            $this->error404('User not found.');
        }

        $roles = $db->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll();
        $this->moduleSection('edit', ['extra' => ['id' => (int)$id, 'target_user' => $targetUser, 'roles' => $roles]]);
    }

    public function store(): void
    {
        $this->requireAuth(['owner']);
        Auth::csrfVerify();

        $fullName = trim((string)$this->post('full_name', ''));
        $username = trim((string)$this->post('username', ''));
        $password = (string)$this->post('password', '');
        $roleId = (int)$this->post('role_id', 0);

        if ($fullName === '' || $username === '' || $password === '' || $roleId <= 0) {
            $this->done('Full name, username, password, and role are required.', '/users/create');
        }

        $db = Database::getInstance();
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check->execute([$username]);
        if ((int)$check->fetchColumn() > 0) {
            $this->done('Username already exists.', '/users/create');
        }

        $pin = preg_replace('/\D+/', '', (string)$this->post('pin', ''));
        $pinHash = strlen($pin) === 6 ? hashPin($pin) : null;

        $stmt = $db->prepare(
            "INSERT INTO users (
                role_id, branch_id, full_name, username, password_hash, pin_hash,
                email, phone, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())"
        );
        $stmt->execute([
            $roleId,
            BRANCH_ID,
            $fullName,
            $username,
            hashPassword($password),
            $pinHash,
            $this->post('email', null),
            $this->post('phone', null),
        ]);

        $newUserId = (int)$db->lastInsertId();
        $logger = new Logger();
        $logger->log('users', 'create', $newUserId, null, ['username' => $username], 'User created.');

        $this->done('User created successfully.', '/users');
    }

    public function update(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $targetId = (int)$id;
        $stmt = $db->prepare(
            "UPDATE users
             SET role_id = ?, full_name = ?, email = ?, phone = ?, status = ?, updated_at = NOW()
             WHERE user_id = ?"
        );
        $stmt->execute([
            (int)$this->post('role_id', 0),
            $this->post('full_name', ''),
            $this->post('email', null),
            $this->post('phone', null),
            $this->post('status', 'active'),
            $targetId,
        ]);

        $pin = preg_replace('/\D+/', '', (string)$this->post('pin', ''));
        if (strlen($pin) === 6) {
            $pinStmt = $db->prepare("UPDATE users SET pin_hash = ? WHERE user_id = ?");
            $pinStmt->execute([hashPin($pin), $targetId]);
        }

        $logger = new Logger();
        $logger->log('users', 'update', $targetId, null, ['status' => $this->post('status', 'active')], 'User updated.');

        $this->done('User #' . $targetId . ' updated.', '/users');
    }

    public function deactivate(string $id): void
    {
        $this->requireAuth(['owner']);
        Auth::csrfVerify();

        $targetId = (int)$id;
        if ($targetId === (int)$this->user()['user_id']) {
            $this->done('You cannot deactivate your own account.', '/users');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$targetId]);

        $logger = new Logger();
        $logger->log('users', 'deactivate', $targetId, null, ['status' => 'inactive'], 'User deactivated.');

        $this->done('User #' . $targetId . ' deactivated.', '/users');
    }

    public function resetPassword(string $id): void
    {
        $this->requireAuth(['owner']);
        Auth::csrfVerify();

        $newPassword = (string)$this->post('new_password', '');
        if ($newPassword === '') {
            $newPassword = generatePassword(10);
        }

        $targetId = (int)$id;
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE users
             SET password_hash = ?, failed_logins = 0, locked_until = NULL,
                 status = IF(status='locked','active',status), updated_at = NOW()
             WHERE user_id = ?"
        );
        $stmt->execute([hashPassword($newPassword), $targetId]);

        $logger = new Logger();
        $logger->log('users', 'reset_password', $targetId, null, ['password_reset' => true], 'Password reset by owner.');

        $this->flash('Password reset complete. New password: ' . $newPassword, 'warning');
        $this->redirect('/users');
    }
}
