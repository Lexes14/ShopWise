<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                   SHOPWISE AI — BASE CONTROLLER                     ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * All controllers extend this base class.
 * Provides common functionality: view rendering, redirects, auth guards, etc.
 */

declare(strict_types=1);

class Controller
{
    protected array $data = [];
    protected string $layout = 'app';
    
    /**
     * Render a view with layout
     */
    protected function render(string $view, array $data = [], string $layout = null): void
    {
        $layout = $layout ?? $this->layout;
        $this->data = array_merge($this->data, $data);
        
        // Extract data to variables
        extract($this->data);
        
        // Start output buffering for content
        ob_start();
        
        $viewFile = VIEWS_PATH . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: $view");
        }
        
        include $viewFile;
        
        // Get content
        $content = ob_get_clean();
        
        // Render layout with content
        $layoutFile = VIEWS_PATH . 'layouts/' . $layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout file not found: $layout");
        }
        
        include $layoutFile;
    }
    
    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = BASE_URL . $url;
        }
        
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Set flash message in session
     */
    protected function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Get flash message and clear it
     */
    protected function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Check if user is authenticated
     */
    protected function requireAuth(array $allowedRoles = []): void
    {
        if (!Auth::isLoggedIn()) {
            $this->flash('Please login to continue.', 'warning');
            $this->redirect('/login');
        }
        
        if (!empty($allowedRoles)) {
            try {
                Auth::guard($allowedRoles);
            } catch (Exception $e) {
                $this->error403();
            }
        }
    }
    
    /**
     * Verify CSRF token
     */
    protected function verifyCsrf(): void
    {
        try {
            Auth::csrfVerify();
        } catch (Exception $e) {
            $this->error403('Invalid security token. Please refresh and try again.');
        }
    }
    
    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get POST data
     */
    protected function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get uploaded file
     */
    protected function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Validate input
     */
    protected function validate(array $data, array $rules): array
    {
        return Validator::validate($data, $rules);
    }
    
    /**
     * Show 403 error
     */
    protected function error403(string $message = 'Access Denied'): void
    {
        http_response_code(403);
        $this->data['errorMessage'] = $message;
        
        if (file_exists(VIEWS_PATH . 'errors/403.php')) {
            $this->render('errors/403', $this->data, 'error');
        } else {
            echo '<h1>403 - Forbidden</h1><p>' . htmlspecialchars($message) . '</p>';
        }
        exit;
    }
    
    /**
     * Show 404 error
     */
    protected function error404(string $message = 'Page Not Found'): void
    {
        http_response_code(404);
        $this->data['errorMessage'] = $message;
        
        if (file_exists(VIEWS_PATH . 'errors/404.php')) {
            $this->render('errors/404', $this->data, 'error');
        } else {
            echo '<h1>404 - Not Found</h1><p>' . htmlspecialchars($message) . '</p>';
        }
        exit;
    }
    
    /**
     * Handle file upload
     */
    protected function handleUpload(array $file, string $destination, array $allowedTypes = [], int $maxSize = 0): ?string
    {
        // Validate upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Check file type
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        // Check file size
        $maxSize = $maxSize ?: UPLOAD_MAX_SIZE_MB * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return null;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $fullPath = $destination . $filename;
        
        // Create directory if not exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return $filename;
        }
        
        return null;
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get current user
     */
    protected function user(): ?array
    {
        return Auth::user();
    }
    
    /**
     * Get current user ID
     */
    protected function userId(): ?int
    {
        $user = Auth::user();
        return $user['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    protected function userRole(): ?string
    {
        $user = Auth::user();
        return $user['role_slug'] ?? null;
    }
}
