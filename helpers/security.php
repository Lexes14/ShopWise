<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║               SHOPWISE AI — SECURITY HELPER FUNCTIONS               ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Provides security functions: sanitization, password hashing, token generation.
 */

declare(strict_types=1);

/**
 * Sanitize string input
 * 
 * @param string $input
 * @return string
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize array of inputs
 * 
 * @param array $input
 * @return array
 */
function sanitizeAll(array $input): array
{
    $sanitized = [];
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeAll($value);
        } else {
            $sanitized[$key] = sanitize((string) $value);
        }
    }
    return $sanitized;
}

/**
 * Generate random token
 * 
 * @param int $length
 * @return string
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Hash password using bcrypt
 * 
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Hash PIN using bcrypt
 * 
 * @param string $pin
 * @return string
 */
function hashPin(string $pin): string
{
    return password_hash($pin, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify PIN against hash
 * 
 * @param string $pin
 * @param string $hash
 * @return bool
 */
function verifyPin(string $pin, string $hash): bool
{
    return password_verify($pin, $hash);
}

/**
 * Check if uploaded file is valid image type
 * 
 * @param string $mimeType
 * @return bool
 */
function isValidImageType(string $mimeType): bool
{
    $allowedTypes = ALLOWED_IMAGE_TYPES ?? [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    return in_array($mimeType, $allowedTypes);
}

/**
 * Generate URL-friendly slug from text
 * 
 * @param string $text
 * @return string
 */
function generateSlug(string $text): string
{
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace non-alphanumeric with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Escape output for HTML
 * 
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for HTML attributes
 * 
 * @param mixed $value
 * @return string
 */
function attr($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for JavaScript
 * 
 * @param mixed $value
 * @return string
 */
function js($value): string
{
    if ($value === null) {
        return '';
    }
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Generate CSRF token
 * 
 * @return string
 */
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(CSRF_TOKEN_LENGTH ?? 32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF hidden input field
 * 
 * @return string
 */
function csrfField(): string
{
    return '<input type="hidden" name="_token" value="' . attr(csrfToken()) . '">';
}

/**
 * Validate CSRF token
 * 
 * @param string $token
 * @return bool
 */
function validateCsrfToken(string $token): bool
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate random password
 * 
 * @param int $length
 * @return string
 */
function generatePassword(int $length = 12): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $max)];
    }
    
    return $password;
}

/**
 * Validate password strength
 * 
 * @param string $password
 * @return array [valid => bool, errors => array]
 */
function validatePasswordStrength(string $password): array
{
    $errors = [];
    $minLength = PASSWORD_MIN_LENGTH ?? 8;
    
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least $minLength characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Clean filename for safe storage
 * 
 * @param string $filename
 * @return string
 */
function sanitizeFilename(string $filename): string
{
    // Remove any path components
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
        $filename = $basename . '.' . $extension;
    }
    
    return $filename;
}

/**
 * Get client IP address
 * 
 * @return string
 */
function getClientIp(): string
{
    $ip = '0.0.0.0';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get user agent string
 * 
 * @return string
 */
function getUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Check if request is from localhost
 * 
 * @return bool
 */
function isLocalhost(): bool
{
    $ip = getClientIp();
    return in_array($ip, ['127.0.0.1', '::1', 'localhost']);
}

/**
 * Mask sensitive data for display (e.g., credit cards, passwords)
 * 
 * @param string $data
 * @param int $visibleChars
 * @param string $maskChar
 * @return string
 */
function maskSensitiveData(string $data, int $visibleChars = 4, string $maskChar = '*'): string
{
    $length = strlen($data);
    
    if ($length <= $visibleChars) {
        return str_repeat($maskChar, $length);
    }
    
    $masked = str_repeat($maskChar, $length - $visibleChars);
    $visible = substr($data, -$visibleChars);
    
    return $masked . $visible;
}

/**
 * Rate limit check (simple in-memory implementation)
 * 
 * @param string $key Unique identifier (e.g., user ID + action)
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $decaySeconds Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $key = 'rl_' . md5($key);
    
    // Initialize or cleanup old attempts
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'reset_at' => $now + $decaySeconds
        ];
    }
    
    // Reset if time window has passed
    if ($now >= $_SESSION['rate_limit'][$key]['reset_at']) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'reset_at' => $now + $decaySeconds
        ];
    }
    
    // Check if limit exceeded
    if ($_SESSION['rate_limit'][$key]['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$key]['attempts']++;
    
    return true;
}
