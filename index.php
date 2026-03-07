<?php
/**
 * ShopWise AI — Application Entry Point
 * 
 * All HTTP requests are routed through this file via .htaccess rewrite rules.
 * Defines constants, starts session, loads all classes, and dispatches the router.
 *
 * @package ShopWiseAI
 * @version 5.0
 */

declare(strict_types=1);

// ── Path Constants ──────────────────────────────────────────────────────────
define('ROOT_PATH',    __DIR__ . DIRECTORY_SEPARATOR);
define('CONFIG_PATH',  ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('CORE_PATH',    ROOT_PATH . 'core'   . DIRECTORY_SEPARATOR);
define('MODEL_PATH',   ROOT_PATH . 'models' . DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH', ROOT_PATH . 'controllers' . DIRECTORY_SEPARATOR);
define('VIEW_PATH',    ROOT_PATH . 'views'  . DIRECTORY_SEPARATOR);
define('VIEWS_PATH',   VIEW_PATH);
define('HELPER_PATH',  ROOT_PATH . 'helpers'. DIRECTORY_SEPARATOR);
define('ASSET_PATH',   ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH',  ROOT_PATH . 'uploads'. DIRECTORY_SEPARATOR);
define('LOG_PATH',     ROOT_PATH . 'logs'   . DIRECTORY_SEPARATOR);
define('BACKUP_PATH',  ROOT_PATH . 'backups'. DIRECTORY_SEPARATOR);
define('EXPORT_PATH',  ROOT_PATH . 'exports'. DIRECTORY_SEPARATOR);
define('DB_PATH',      ROOT_PATH . 'database'. DIRECTORY_SEPARATOR);

// ── Session Configuration ────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   // set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

// ── Load Configuration ───────────────────────────────────────────────────────
require_once CONFIG_PATH . 'app.php';
require_once CONFIG_PATH . 'database.php';
require_once CONFIG_PATH . 'permissions.php';

// ── Autoloader ───────────────────────────────────────────────────────────────
/**
 * Simple file-based class autoloader.
 * Scans core/, helpers/, models/, controllers/ directories
 * and requires matching .php files.
 */
function shopwiseAutoload(string $className): void
{
    $searchPaths = [
        CORE_PATH,
        HELPER_PATH,
        MODEL_PATH,
        CONTROLLER_PATH,
    ];

    foreach ($searchPaths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('shopwiseAutoload');

// ── Load Helpers (procedural files — not classes) ────────────────────────────
require_once HELPER_PATH . 'format.php';
require_once HELPER_PATH . 'security.php';
require_once HELPER_PATH . 'pagination.php';
require_once HELPER_PATH . 'auth.php';
require_once HELPER_PATH . 'ui.php';

// ── Error Handling ───────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    // Log errors to file in production
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . 'php_errors.log');
}

set_exception_handler(function (Throwable $e): void {
    if (APP_ENV === 'development') {
        echo '<pre style="background:#1e1e1e;color:#f8f8f2;padding:2rem;font-family:monospace;">';
        echo '<strong style="color:#ff6b6b;">Fatal Error:</strong> ' . htmlspecialchars($e->getMessage()) . "\n";
        echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (line ' . $e->getLine() . ")\n\n";
        echo '<strong>Stack Trace:</strong>' . "\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        // Log the error
        error_log('[' . date('Y-m-d H:i:s') . '] Exception: ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL,
            3,
            LOG_PATH . 'app_errors.log'
        );
        // Show friendly error page
        http_response_code(500);
        if (file_exists(VIEW_PATH . 'errors/500.php')) {
            include VIEW_PATH . 'errors/500.php';
        } else {
            echo '<h1>500 — Internal Server Error</h1>';
        }
    }
    exit;
});

// ── Dispatch Router ──────────────────────────────────────────────────────────
$router = new Router();
$router->dispatch();