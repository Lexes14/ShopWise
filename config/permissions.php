<?php
/**
 * ShopWise AI — Role-Based Access Control (RBAC) Configuration
 *
 * Defines which modules and actions each role can access.
 * Used by Auth::guard() and the sidebar navigation renderer.
 *
 * Permission keys map directly to controller/action paths.
 * Format: 'module.action' or 'module.*' for full access.
 *
 * @package ShopWiseAI\Config
 */

declare(strict_types=1);

$PERMISSIONS = [

    // ─────────────────────────────────────────────────────────────────────────
    // OWNER — Full system access. No restrictions.
    // ─────────────────────────────────────────────────────────────────────────
    'owner' => [
        'dashboard.*',
        'products.*',
        'inventory.*',
        'suppliers.*',
        'purchase_orders.*',
        'pos.*',
        'shifts.*',
        'ai_insights.*',
        'reports.*',
        'promotions.*',
        'loyalty.*',
        'users.*',
        'users.resetPassword',  // Explicit for clarity
        'audit.*',
        'backup.*',
        'settings.*',
        'incidents.*',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // MANAGER — Full operational access; cannot manage users or backup.
    // ─────────────────────────────────────────────────────────────────────────
    'manager' => [
        'dashboard.*',
        'products.*',
        'inventory.*',
        'suppliers.*',
        'purchase_orders.*',
        'pos.*',
        'shifts.*',
        'ai_insights.*',
        'reports.*',
        'promotions.*',
        'loyalty.*',
        'users.index',
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'audit.index',
        'audit.detail',
        'incidents.*',
        'settings.index',
        'settings.updateStore',
        'settings.updateTax',
        'settings.updatePOS',
        'settings.updateAI',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // INVENTORY STAFF — Manages stock, products, adjustments. No POS, no finance.
    // ─────────────────────────────────────────────────────────────────────────
    'inventory_staff' => [
        'dashboard.index',
        'dashboard.chartData',
        'products.*',
        'inventory.*',
        'suppliers.index',
        'suppliers.view',
        'purchase_orders.index',
        'purchase_orders.view',
        'purchase_orders.receive',
        'ai_insights.index',
        'ai_insights.demand',
        'ai_insights.pricing',
        'ai_insights.stock',
        'ai_insights.bundling',
        'ai_insights.anomalies',
        'ai_insights.segments',
        'ai_insights.accept',
        'ai_insights.dismiss',
        'reports.inventory',
        'reports.expiry',
        'reports.shrinkage',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // CASHIER — POS terminal only. Can view own shift. No back-office access.
    // ─────────────────────────────────────────────────────────────────────────
    'cashier' => [
        'pos.*',
        'shifts.history',
        'shifts.open',
        'shifts.close',
        'shifts.myHistory',
        'shifts.detail',
        'loyalty.lookup',
        'loyalty.earn',
        'loyalty.redeem',
        'products.search',  // AJAX search for POS
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // PURCHASING OFFICER — Manages suppliers and purchase orders. No POS, no staff.
    // ─────────────────────────────────────────────────────────────────────────
    'purchasing_officer' => [
        'dashboard.index',
        'dashboard.chartData',
        'products.index',
        'products.view',
        'products.search',
        'inventory.index',
        'inventory.lowStock',
        'inventory.expiring',
        'suppliers.*',
        'purchase_orders.*',
        'ai_insights.index',
        'ai_insights.demand',
        'ai_insights.pricing',
        'ai_insights.stock',
        'ai_insights.bundling',
        'ai_insights.anomalies',
        'ai_insights.segments',
        'ai_insights.restock',
        'reports.supplier',
        'reports.inventory',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // SECURITY — Read-only access to monitor transactions and incidents.
    // ─────────────────────────────────────────────────────────────────────────
    'security' => [
        'dashboard.index',
        'pos.view',
        'reports.cashier',
        'audit.index',
        'audit.detail',
        'incidents.*',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // BOOKKEEPER — Financial reports, transactions, no operational changes.
    // ─────────────────────────────────────────────────────────────────────────
    'bookkeeper' => [
        'dashboard.index',
        'dashboard.chartData',
        'shifts.history',
        'shifts.detail',
        'reports.*',
        'audit.index',
        'audit.detail',
        'suppliers.index',
        'suppliers.view',
        'purchase_orders.index',
        'purchase_orders.view',
    ],
];

/**
 * Check if a given role has permission for a specific module.action.
 *
 * @param string $role       User's role slug (e.g. 'cashier')
 * @param string $permission Permission key (e.g. 'products.create')
 * @return bool
 */
function hasPermission(string $role, string $permission): bool
{
    global $PERMISSIONS;

    if (!isset($PERMISSIONS[$role])) {
        return false;
    }

    $perms = $PERMISSIONS[$role];

    // Check exact match
    if (in_array($permission, $perms, true)) {
        return true;
    }

    // Check wildcard match (e.g. 'products.*' grants 'products.create')
    $parts = explode('.', $permission, 2);
    if (count($parts) === 2 && in_array($parts[0] . '.*', $perms, true)) {
        return true;
    }

    // Owners always have full access
    if ($role === 'owner') {
        return true;
    }

    return false;
}

/**
 * Get all navigation items the given role can see.
 * Used by the sidebar renderer in the layout.
 *
 * @param string $role
 * @return array List of accessible module slugs
 */
function getAccessibleModules(string $role): array
{
    global $PERMISSIONS;

    if (!isset($PERMISSIONS[$role])) {
        return [];
    }

    $modules = [];
    foreach ($PERMISSIONS[$role] as $perm) {
        $module = explode('.', $perm)[0];
        if (!in_array($module, $modules)) {
            $modules[] = $module;
        }
    }

    return $modules;
}