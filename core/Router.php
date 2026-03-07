<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                    SHOPWISE AI — ROUTER CLASS                       ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Simple pattern-based router with named parameters.
 * All routes are registered at the bottom of this file.
 */

declare(strict_types=1);

class Router
{
    private static array $routes = [];
    private static string $basePath = '/ShopWise_ai';
    
    /**
     * Register a GET route
     */
    public static function get(string $pattern, string $controller, string $method): void
    {
        self::$routes[] = [
            'method' => 'GET',
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $method
        ];
    }
    
    /**
     * Register a POST route
     */
    public static function post(string $pattern, string $controller, string $method): void
    {
        self::$routes[] = [
            'method' => 'POST',
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $method
        ];
    }
    
    /**
     * Register routes that accept both GET and POST
     */
    public static function any(string $pattern, string $controller, string $method): void
    {
        self::get($pattern, $controller, $method);
        self::post($pattern, $controller, $method);
    }
    
    /**
     * Dispatch the current request to the appropriate controller
     */
    public static function dispatch(): void
    {
        self::registerRoutes();
        
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $requestUri = strtok($requestUri, '?');
        
        // Remove base path
        $requestUri = str_replace(self::$basePath, '', $requestUri);

        // Normalize direct index entrypoint access
        if ($requestUri === '/index.php' || $requestUri === 'index.php') {
            $requestUri = '/';
        }
        
        // Ensure leading slash
        if (empty($requestUri) || $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        // Normalize trailing slash (except root) so /path and /path/ both match
        if ($requestUri !== '/' && str_ends_with($requestUri, '/')) {
            $requestUri = rtrim($requestUri, '/');
        }
        
        // Try to match route
        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
            
            $pattern = self::convertPatternToRegex($route['pattern']);
            
            if (preg_match($pattern, $requestUri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Instantiate controller and call method
                $controllerName = $route['controller'];
                $actionName = $route['action'];
                
                if (!class_exists($controllerName)) {
                    self::error404();
                    return;
                }

                if (!self::authorize($controllerName, $actionName, $route['pattern'])) {
                    return;
                }
                
                $controller = new $controllerName();
                
                if (!method_exists($controller, $actionName)) {
                    self::error404();
                    return;
                }
                
                // Call controller method with parameters
                call_user_func_array([$controller, $actionName], $params);
                return;
            }
        }
        
        // No route matched
        self::error404();
    }

    private static function authorize(string $controllerName, string $actionName, string $pattern): bool
    {
        $publicAuthActions = [
            'AuthController::showLogin',
            'AuthController::login',
            'AuthController::loginPin',
            'AuthController::logout',
        ];

        $signature = $controllerName . '::' . $actionName;
        if (in_array($signature, $publicAuthActions, true)) {
            return true;
        }

        if (!Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if (!function_exists('hasPermission')) {
            return true;
        }

        $user = Auth::user();
        $role = (string)($user['role_slug'] ?? '');
        if ($role === '') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $module = self::resolveModuleSlug($controllerName, $pattern);
        $permission = $module . '.' . $actionName;

        if (!hasPermission($role, $permission)) {
            http_response_code(403);
            if (file_exists(VIEWS_PATH . 'errors/403.php')) {
                $errorMessage = 'You do not have permission to access this module action.';
                include VIEWS_PATH . 'errors/403.php';
            } else {
                echo '<h1>403 - Forbidden</h1>';
            }
            exit;
        }

        return true;
    }

    private static function resolveModuleSlug(string $controllerName, string $pattern): string
    {
        $map = [
            'DashboardController' => 'dashboard',
            'NotificationController' => 'dashboard',
            'ProductController' => 'products',
            'InventoryController' => 'inventory',
            'SupplierController' => 'suppliers',
            'PurchaseOrderController' => 'purchase_orders',
            'POSController' => 'pos',
            'ShiftController' => 'shifts',
            'AIController' => 'ai_insights',
            'ReportController' => 'reports',
            'PromotionController' => 'promotions',
            'LoyaltyController' => 'loyalty',
            'UserController' => 'users',
            'AuditController' => 'audit',
            'BackupController' => 'backup',
            'SettingsController' => 'settings',
            'AuthController' => 'auth',
        ];

        if (isset($map[$controllerName])) {
            return $map[$controllerName];
        }

        $trimmed = trim($pattern, '/');
        $first = explode('/', $trimmed)[0] ?? 'dashboard';
        return str_replace('-', '_', $first);
    }
    
    /**
     * Convert route pattern to regex
     * Supports :param named parameters
     */
    private static function convertPatternToRegex(string $pattern): string
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        
        // Convert :param to named capture groups
        $pattern = preg_replace('/:([\w]+)/', '(?P<$1>[^\/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Show 404 error page
     */
    private static function error404(): void
    {
        http_response_code(404);
        if (file_exists(VIEWS_PATH . 'errors/404.php')) {
            include VIEWS_PATH . 'errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
        exit;
    }
    
    /**
     * Generate URL from route pattern
     */
    public static function url(string $path): string
    {
        return BASE_URL . $path;
    }
    
    /**
     * Register all application routes
     */
    private static function registerRoutes(): void
    {
        // ═══════════════════════════════════════════════════════════════════
        // AUTHENTICATION
        // ═══════════════════════════════════════════════════════════════════
        self::get('/', 'AuthController', 'showLogin');
        self::get('/login', 'AuthController', 'showLogin');
        self::post('/login', 'AuthController', 'login');
        self::post('/login/pin', 'AuthController', 'loginPin');
        self::post('/logout', 'AuthController', 'logout');
        
        // ═══════════════════════════════════════════════════════════════════
        // DASHBOARD
        // ═══════════════════════════════════════════════════════════════════
        self::get('/dashboard', 'DashboardController', 'index');
        self::get('/dashboard/chart-data', 'DashboardController', 'chartData');
        
        // ═══════════════════════════════════════════════════════════════════
        // NOTIFICATIONS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/notifications', 'NotificationController', 'index');
        self::post('/notifications/:id/mark-read', 'NotificationController', 'markRead');
        self::post('/notifications/mark-all-read', 'NotificationController', 'markAllRead');
        self::post('/notifications/:id/delete', 'NotificationController', 'delete');
        
        // ═══════════════════════════════════════════════════════════════════
        // PRODUCTS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/products', 'ProductController', 'index');
        self::get('/products/create', 'ProductController', 'create');
        self::get('/products/search', 'ProductController', 'search');
        self::post('/products', 'ProductController', 'store');
        self::get('/products/:id', 'ProductController', 'show');
        self::get('/products/:id/edit', 'ProductController', 'edit');
        self::post('/products/:id/update', 'ProductController', 'update');
        self::post('/products/:id/archive', 'ProductController', 'archive');
        self::get('/products/:id/batches', 'ProductController', 'batches');
        self::get('/products/:id/price-history', 'ProductController', 'priceHistory');
        
        // ═══════════════════════════════════════════════════════════════════
        // INVENTORY
        // ═══════════════════════════════════════════════════════════════════
        self::get('/inventory', 'InventoryController', 'index');
        self::get('/inventory/adjustments', 'InventoryController', 'adjustments');
        self::post('/inventory/adjustments/submit', 'InventoryController', 'submitAdjustment');
        self::post('/inventory/adjustments/:id/approve', 'InventoryController', 'approveAdjustment');
        self::post('/inventory/adjustments/:id/reject', 'InventoryController', 'rejectAdjustment');
        self::get('/inventory/expiring', 'InventoryController', 'expiring');
        self::post('/inventory/expiring/check', 'InventoryController', 'checkExpiryAlerts');
        self::get('/inventory/aging', 'InventoryController', 'aging');
        self::get('/inventory/stocktake', 'InventoryController', 'stocktake');
        self::post('/inventory/stocktake/create', 'InventoryController', 'createStocktake');
        self::post('/inventory/stocktake/:id/record', 'InventoryController', 'recordCount');
        self::post('/inventory/stocktake/:id/finalize', 'InventoryController', 'finalizeStocktake');
        self::get('/inventory/shelves', 'InventoryController', 'shelves');
        
        // ═══════════════════════════════════════════════════════════════════
        // SUPPLIERS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/suppliers', 'SupplierController', 'index');
        self::get('/suppliers/create', 'SupplierController', 'create');
        self::post('/suppliers', 'SupplierController', 'store');
        self::get('/suppliers/:id', 'SupplierController', 'show');
        self::get('/suppliers/:id/edit', 'SupplierController', 'edit');
        self::post('/suppliers/:id/update', 'SupplierController', 'update');
        self::post('/suppliers/:id/archive', 'SupplierController', 'archive');
        self::get('/suppliers/search', 'SupplierController', 'search');
        
        // ═══════════════════════════════════════════════════════════════════
        // PURCHASE ORDERS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/purchase-orders', 'PurchaseOrderController', 'index');
        self::get('/purchase-orders/create', 'PurchaseOrderController', 'create');
        self::post('/purchase-orders', 'PurchaseOrderController', 'store');
        self::post('/purchase-orders/store', 'PurchaseOrderController', 'store');
        self::get('/purchase-orders/:id', 'PurchaseOrderController', 'show');
        self::get('/purchase-orders/:id/edit', 'PurchaseOrderController', 'edit');
        self::post('/purchase-orders/:id/update', 'PurchaseOrderController', 'update');
        self::post('/purchase-orders/add-item', 'PurchaseOrderController', 'addItem');
        self::post('/purchase-orders/items/:id/remove', 'PurchaseOrderController', 'removeItem');
        self::post('/purchase-orders/:id/submit', 'PurchaseOrderController', 'submit');
        self::post('/purchase-orders/:id/approve', 'PurchaseOrderController', 'approve');
        self::post('/purchase-orders/:id/reject', 'PurchaseOrderController', 'reject');
        self::post('/purchase-orders/:id/mark-ordered', 'PurchaseOrderController', 'markOrdered');
        self::post('/purchase-orders/:id/mark-received', 'PurchaseOrderController', 'markReceived');
        self::get('/purchase-orders/search', 'PurchaseOrderController', 'search');
        
        // ═══════════════════════════════════════════════════════════════════
        // POS TERMINAL
        // ═══════════════════════════════════════════════════════════════════
        self::get('/pos/terminal', 'POSController', 'terminal');
        self::get('/pos/products', 'POSController', 'products');
        self::post('/pos/checkout', 'POSController', 'checkout');
        self::post('/pos/hold', 'POSController', 'hold');
        self::get('/pos/held', 'POSController', 'recalled');
        self::post('/pos/hold/:id/delete', 'POSController', 'deleteHold');
        self::get('/pos/receipt/:id', 'POSController', 'reprint');
        self::post('/pos/verify-pin', 'POSController', 'verifyPin');
        self::post('/pos/void/:id', 'POSController', 'void');
        
        // ═══════════════════════════════════════════════════════════════════
        // SHIFTS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/shifts', 'ShiftController', 'history');
        self::get('/shifts/open', 'ShiftController', 'open');
        self::post('/shifts/start', 'ShiftController', 'start');
        self::get('/shifts/close', 'ShiftController', 'close');
        self::post('/shifts/end', 'ShiftController', 'end');
        self::get('/shifts/:id', 'ShiftController', 'detail');
        self::post('/shifts/:id/verify', 'ShiftController', 'verify');
        
        // ═══════════════════════════════════════════════════════════════════
        // AI INSIGHTS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/ai-insights', 'AIController', 'index');
        self::get('/ai-insights/demand', 'AIController', 'demand');
        self::get('/ai-insights/pricing', 'AIController', 'pricing');
        self::get('/ai-insights/stock', 'AIController', 'stock');
        self::get('/ai-insights/bundling', 'AIController', 'bundling');
        self::get('/ai-insights/anomalies', 'AIController', 'anomalies');
        self::get('/ai-insights/segments', 'AIController', 'segments');
        self::post('/ai-insights/generate', 'AIController', 'generate');
        self::post('/ai-insights/:id/accept', 'AIController', 'accept');
        self::post('/ai-insights/:id/dismiss', 'AIController', 'dismiss');
        self::post('/ai-insights/:id/feedback', 'AIController', 'feedback');
        
        // ═══════════════════════════════════════════════════════════════════
        // REPORTS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/reports', 'ReportController', 'index');
        self::get('/reports/sales', 'ReportController', 'sales');
        self::get('/reports/profit', 'ReportController', 'profit');
        self::get('/reports/inventory', 'ReportController', 'inventory');
        self::get('/reports/shrinkage', 'ReportController', 'shrinkage');
        self::get('/reports/cashier', 'ReportController', 'cashier');
        self::get('/reports/supplier', 'ReportController', 'supplier');
        self::get('/reports/ai-accuracy', 'ReportController', 'aiAccuracy');
        self::get('/reports/custom', 'ReportController', 'custom');
        self::get('/reports/export/sales-csv', 'ReportController', 'exportSalesCsv');
        self::get('/reports/export/profit-csv', 'ReportController', 'exportProfitCsv');
        self::get('/reports/export/inventory-csv', 'ReportController', 'exportInventoryCsv');
        self::get('/reports/export/sales-excel', 'ReportController', 'exportSalesExcel');
        
        // ═══════════════════════════════════════════════════════════════════
        // PROMOTIONS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/promotions', 'PromotionController', 'index');
        self::get('/promotions/create', 'PromotionController', 'create');
        self::post('/promotions', 'PromotionController', 'store');
        self::get('/promotions/:id/edit', 'PromotionController', 'edit');
        self::post('/promotions/:id/update', 'PromotionController', 'update');
        self::post('/promotions/:id/archive', 'PromotionController', 'archive');
        
        // ═══════════════════════════════════════════════════════════════════
        // LOYALTY
        // ═══════════════════════════════════════════════════════════════════
        self::get('/loyalty', 'LoyaltyController', 'index');
        self::post('/loyalty/register', 'LoyaltyController', 'register');
        self::get('/loyalty/:id', 'LoyaltyController', 'detail');
        self::get('/loyalty/search', 'LoyaltyController', 'search');
        
        // ═══════════════════════════════════════════════════════════════════
        // USER MANAGEMENT
        // ═══════════════════════════════════════════════════════════════════
        self::get('/users', 'UserController', 'index');
        self::get('/users/create', 'UserController', 'create');
        self::post('/users', 'UserController', 'store');
        self::get('/users/:id/edit', 'UserController', 'edit');
        self::post('/users/:id/update', 'UserController', 'update');
        self::post('/users/:id/deactivate', 'UserController', 'deactivate');
        self::post('/users/:id/reset-password', 'UserController', 'resetPassword');
        
        // ═══════════════════════════════════════════════════════════════════
        // AUDIT LOGS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/audit', 'AuditController', 'index');
        self::get('/audit/:id/detail', 'AuditController', 'detail');
        self::get('/audit/export', 'AuditController', 'exportCsv');
        
        // ═══════════════════════════════════════════════════════════════════
        // BACKUP
        // ═══════════════════════════════════════════════════════════════════
        self::get('/backup', 'BackupController', 'index');
        self::post('/backup/create', 'BackupController', 'create');
        self::get('/backup/:filename/download', 'BackupController', 'download');
        self::post('/backup/restore', 'BackupController', 'restore');
        
        // ═══════════════════════════════════════════════════════════════════
        // SETTINGS
        // ═══════════════════════════════════════════════════════════════════
        self::get('/settings', 'SettingsController', 'index');
        self::post('/settings/store', 'SettingsController', 'updateStore');
        self::post('/settings/tax', 'SettingsController', 'updateTax');
        self::post('/settings/pos', 'SettingsController', 'updatePOS');
        self::post('/settings/ai', 'SettingsController', 'updateAI');
    }
}
