<?php
declare(strict_types=1);

class AuthController extends Controller
{
    protected string $layout = 'auth';

    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect($this->getRedirectPath(Auth::user()['role_slug'] ?? null));
        }

        $reason = $this->get('reason', '');
        $message = null;

        if ($reason === 'timeout') {
            $message = 'Your session expired. Please sign in again.';
        }

        $this->render('auth/login', [
            'flash' => $this->getFlash(),
            'sessionMessage' => $message,
            'csrf' => Auth::csrfGenerate(),
            'baseUrl' => BASE_URL,
        ], 'auth');
    }

    public function login(): void
    {
        Auth::csrfVerify();

        $username = trim((string)$this->post('username', ''));
        $password = (string)$this->post('password', '');

        if ($username === '' || $password === '') {
            $this->flash('Username and password are required.', 'danger');
            $this->redirect('/login');
        }

        $result = Auth::login($username, $password);

        if (!$result['success']) {
            $this->flash($result['message'], 'danger');
            $this->redirect('/login');
        }

        try {
            $logger = new Logger();
            $logger->log('auth', 'login', (int)$result['user']['user_id'], null, null, 'User logged in successfully.');
        } catch (Throwable) {
        }

        $this->flash('Welcome back, ' . ($result['user']['full_name'] ?? 'User') . '!', 'success');
        $this->redirect($this->getRedirectPath($result['user']['role_slug'] ?? null));
    }

    public function loginPin(): void
    {
        Auth::csrfVerify();

        $pin = preg_replace('/\D+/', '', (string)$this->post('pin', ''));
        $cashierId = (int)$this->post('cashier_id', 0);
        $username = trim((string)$this->post('username', ''));

        if ($cashierId <= 0 && $username !== '') {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.user_id
                 FROM users u
                 JOIN roles r ON r.role_id = u.role_id
                 WHERE u.username = ? AND u.status = 'active' AND r.role_slug = 'cashier'
                 LIMIT 1"
            );
            $stmt->execute([$username]);
            $row = $stmt->fetch();
            $cashierId = (int)($row['user_id'] ?? 0);
        }

        if ($cashierId <= 0 || strlen($pin) !== 6) {
            $this->respondPinFailure('Invalid cashier account or PIN format.');
            return;
        }

        $result = Auth::loginByPin($pin, $cashierId);

        if (!$result['success']) {
            $this->respondPinFailure($result['message']);
            return;
        }

        $redirect = $this->getRedirectPath($result['user']['role_slug'] ?? null);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'PIN login successful.',
                'redirect' => BASE_URL . $redirect,
            ]);
        }

        $this->flash('PIN login successful.', 'success');
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        Auth::csrfVerify();
        Auth::logout();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->flash('You have been logged out.', 'info');
        $this->redirect('/login');
    }

    private function respondPinFailure(string $message): void
    {
        if ($this->isAjax()) {
            $this->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        $this->flash($message, 'danger');
        $this->redirect('/login');
    }

    private function getRedirectPath(?string $roleSlug): string
    {
        return match ($roleSlug) {
            'cashier' => '/pos/terminal',
            'bookkeeper' => '/reports',
            default => '/dashboard',
        };
    }
}
