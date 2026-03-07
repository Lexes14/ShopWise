<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e(APP_NAME) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(ASSET_URL) ?>/css/app.css">
</head>
<body>
<?php
$currentUser = Auth::user() ?? [];
$currentRole = (string)($currentUser['role_slug'] ?? '');
$userName = $currentUser['full_name'] ?? 'Guest';
$userInitials = strtoupper(substr($userName, 0, 1));
$roleName = $currentUser['role_name'] ?? 'User';

// Navigation structure with icons and grouping
$navSections = [
    [
        'label' => 'Main',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/dashboard', 'permission' => 'dashboard.index'],
            ['icon' => '🛍️', 'label' => 'POS Terminal', 'url' => '/pos/terminal', 'permission' => 'pos.terminal'],
        ]
    ],
    [
        'label' => 'Inventory',
        'items' => [
            ['icon' => '📦', 'label' => 'Products', 'url' => '/products', 'permission' => 'products.index'],
            ['icon' => '📋', 'label' => 'Inventory', 'url' => '/inventory', 'permission' => 'inventory.index'],
            ['icon' => '🏢', 'label' => 'Suppliers', 'url' => '/suppliers', 'permission' => 'suppliers.index'],
            ['icon' => '📄', 'label' => 'Purchase Orders', 'url' => '/purchase-orders', 'permission' => 'purchase_orders.index'],
        ]
    ],
    [
        'label' => 'Operations',
        'items' => [
            ['icon' => '⏰', 'label' => 'Shifts', 'url' => '/shifts', 'permission' => 'shifts.history'],
            ['icon' => '🎁', 'label' => 'Loyalty', 'url' => '/loyalty', 'permission' => 'loyalty.index'],
            ['icon' => '🤖', 'label' => 'AI Insights', 'url' => '/ai-insights', 'permission' => 'ai_insights.index'],
            ['icon' => '🏷️', 'label' => 'Promotions', 'url' => '/promotions', 'permission' => 'promotions.index'],
        ]
    ],
    [
        'label' => 'System',
        'items' => [
            ['icon' => '📈', 'label' => 'Reports', 'url' => '/reports', 'permission' => 'reports.index'],
            ['icon' => '👥', 'label' => 'Users', 'url' => '/users', 'permission' => 'users.index'],
            ['icon' => '📜', 'label' => 'Audit Log', 'url' => '/audit', 'permission' => 'audit.index'],
            ['icon' => '💾', 'label' => 'Backup', 'url' => '/backup', 'permission' => 'backup.index'],
            ['icon' => '⚙️', 'label' => 'Settings', 'url' => '/settings', 'permission' => 'settings.index'],
        ]
    ]
];

$uriPath = strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '/';
$activePath = str_replace('/ShopWise_ai', '', $uriPath);
if ($activePath === '' || $activePath[0] !== '/') {
    $activePath = '/' . ltrim($activePath, '/');
}

// Get current page title from URL
$pageTitle = 'Dashboard';
foreach ($navSections as $section) {
    foreach ($section['items'] as $item) {
        if (str_starts_with($activePath, $item['url'])) {
            $pageTitle = $item['label'];
            break 2;
        }
    }
}
?>

<!-- APP SHELL -->
<div class="sw-app-shell">
    
    <!-- SIDEBAR -->
    <aside class="sw-sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sw-sidebar-brand">
            <div class="sw-sidebar-brand-logo">
                <span>SW</span>
            </div>
            <div class="sw-sidebar-brand-text"><?= e(APP_NAME) ?></div>
        </div>
        
        <!-- Navigation -->
        <nav class="sw-sidebar-nav">
            <?php foreach ($navSections as $section): ?>
                <?php 
                // Check if at least one item in section is accessible
                $hasAccessibleItem = false;
                foreach ($section['items'] as $item) {
                    if (!function_exists('hasPermission') || $currentRole === '' || hasPermission($currentRole, $item['permission'])) {
                        $hasAccessibleItem = true;
                        break;
                    }
                }
                if (!$hasAccessibleItem) continue;
                ?>
                
                <div class="sw-nav-section">
                    <div class="sw-nav-section-label"><?= e($section['label']) ?></div>
                    <?php foreach ($section['items'] as $item): ?>
                        <?php
                        if (function_exists('hasPermission') && $currentRole !== '' && !hasPermission($currentRole, $item['permission'])) {
                            continue;
                        }
                        $isActive = str_starts_with($activePath, $item['url']);
                        ?>
                        <a class="sw-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e(BASE_URL) . e($item['url']) ?>">
                            <span class="sw-nav-icon"><?= $item['icon'] ?></span>
                            <span class="sw-nav-label"><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>
        
        <!-- User Section -->
        <div class="sw-sidebar-user">
            <div class="sw-user-avatar"><?= e($userInitials) ?></div>
            <div class="sw-user-info">
                <div class="sw-user-name"><?= e($userName) ?></div>
                <div class="sw-user-role"><?= e($roleName) ?></div>
            </div>
            <form method="POST" action="<?= e(BASE_URL) ?>/logout" class="m-0">
                <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                <button class="sw-logout-btn" type="submit" title="Logout">
                    🚪
                </button>
            </form>
        </div>
    </aside>
    
    <!-- MAIN CONTENT -->
    <main class="sw-main">
        <!-- Topbar -->
        <header class="sw-topbar">
            <div class="sw-topbar-left">
                <button type="button" class="sw-sidebar-toggle" id="sidebarToggle">
                    <span class="sw-toggle-icon">☰</span>
                </button>
                <div class="sw-page-title-container">
                    <h1 class="sw-page-title"><?= e($pageTitle) ?></h1>
                    <div class="sw-breadcrumb">
                        <span>ShopWise AI</span>
                        <span class="sw-breadcrumb-sep">›</span>
                        <span><?= e($pageTitle) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="sw-topbar-right">
                <!-- Quick Search (Optional) -->
                <div class="sw-topbar-search">
                    <span class="sw-search-icon">🔍</span>
                    <input type="text" placeholder="Search..." class="sw-search-input" id="globalSearch">
                </div>
                
                <!-- Notifications -->
                <div class="sw-notification-bell" style="display: none;">
                    <span>🔔</span>
                    <span class="sw-notification-badge">3</span>
                </div>
                
                <!-- User Dropdown -->
                <div class="sw-user-dropdown">
                    <div class="sw-topbar-user">
                        <div class="sw-topbar-avatar"><?= e($userInitials) ?></div>
                        <div class="sw-topbar-user-info">
                            <div class="sw-topbar-user-name"><?= e($userName) ?></div>
                            <div class="sw-topbar-user-role"><?= e($roleName) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <section class="sw-content">
            <?= $content ?>
        </section>
        
        <!-- Footer -->
        <footer class="sw-footer">
            <div class="sw-footer-content">
                <span>© <?= date('Y') ?> ShopWise AI v5.0</span>
                <span class="sw-footer-sep">•</span>
                <span>Made with ❤️ in the Philippines</span>
            </div>
        </footer>
    </main>
</div>

<!-- Scripts -->
<script>
// Sidebar Toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const appShell = document.querySelector('.sw-app-shell');

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
        sidebar.classList.toggle('sw-sidebar-collapsed');
        appShell.classList.toggle('sw-sidebar-hidden');
    });
}

// Global Search (can be enhanced)
const globalSearch = document.getElementById('globalSearch');
if (globalSearch) {
    globalSearch.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        // Add global search functionality here
        console.log('Searching for:', searchTerm);
    });
}

// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(event) {
    if (window.innerWidth < 992) {
        const isClickInsideSidebar = sidebar?.contains(event.target);
        const isClickOnToggle = sidebarToggle?.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && !sidebar?.classList.contains('sw-sidebar-collapsed')) {
            sidebar?.classList.add('sw-sidebar-collapsed');
            appShell?.classList.add('sw-sidebar-hidden');
        }
    }
});

// Flash message auto-dismiss
setTimeout(function() {
    document.querySelectorAll('.sw-flash').forEach(function(flash) {
        flash.style.opacity = '0';
        setTimeout(function() {
            flash.remove();
        }, 300);
    });
}, 5000);
</script>
<script src="<?= e(ASSET_URL) ?>/js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
