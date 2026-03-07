<?php
declare(strict_types=1);

/**
 * DashboardModel — Dashboard Analytics & KPI Retrieval
 *
 * Aggregates sales data, inventory insights, and system alerts for
 * dashboard display. All queries are optimized with proper indexing.
 */

class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get today's sales statistics.
     *
     * @return array ['total_sales' => float, 'transaction_count' => int, 'avg_sale' => float, 'cash_sales' => float, 'digital_sales' => float]
     */
    public function getTodayStats(): array
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));

        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as transaction_count,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_sale,
                SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_method != 'cash' THEN total_amount ELSE 0 END) as digital_sales
             FROM transactions
             WHERE DATE(created_at) = ? AND branch_id = ? AND status = 'completed'"
        );
        $stmt->execute([$today, BRANCH_ID]);
        
        $row = $stmt->fetch() ?: [];

        return [
            'total_sales' => (float)($row['total_sales'] ?? 0),
            'transaction_count' => (int)($row['transaction_count'] ?? 0),
            'avg_sale' => (float)($row['avg_sale'] ?? 0),
            'cash_sales' => (float)($row['cash_sales'] ?? 0),
            'digital_sales' => (float)($row['digital_sales'] ?? 0),
        ];
    }

    /**
     * Get sales data for last 14 days (current vs previous period).
     *
     * @return array ['current' => [...], 'previous' => [...], 'labels' => [...]]
     */
    public function getLast14DaysSales(): array
    {
        $currentStart = date('Y-m-d', strtotime('-13 days'));
        $currentEnd = date('Y-m-d');
        $previousStart = date('Y-m-d', strtotime('-27 days'));
        $previousEnd = date('Y-m-d', strtotime('-14 days'));

        // Current period
        $stmt = $this->db->prepare(
            "SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as sales
             FROM transactions
             WHERE DATE(created_at) BETWEEN ? AND ? AND branch_id = ? AND status = 'completed'
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
        $stmt->execute([$currentStart, $currentEnd, BRANCH_ID]);
        $currentData = $stmt->fetchAll();

        // Previous period
        $stmt = $this->db->prepare(
            "SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as sales
             FROM transactions
             WHERE DATE(created_at) BETWEEN ? AND ? AND branch_id = ? AND status = 'completed'
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
        $stmt->execute([$previousStart, $previousEnd, BRANCH_ID]);
        $previousData = $stmt->fetchAll();

        // Generate labels for all 14 days in current period
        $labels = [];
        $currentSales = [];
        $previousSales = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M d', strtotime($date));

            // Find current sales
            $currentValue = 0;
            foreach ($currentData as $record) {
                if ($record['date'] === $date) {
                    $currentValue = (float)$record['sales'];
                    break;
                }
            }
            $currentSales[] = $currentValue;

            // Find previous sales
            $prevDate = date('Y-m-d', strtotime($date . ' - 14 days'));
            $prevValue = 0;
            foreach ($previousData as $record) {
                if ($record['date'] === $prevDate) {
                    $prevValue = (float)$record['sales'];
                    break;
                }
            }
            $previousSales[] = $prevValue;
        }

        return [
            'current' => $currentSales,
            'previous' => $previousSales,
            'labels' => $labels,
        ];
    }

    /**
     * Get top 5 selling products today.
     *
     * @return array [['product_id' => int, 'name' => string, 'qty_sold' => int, 'revenue' => float], ...]
     */
    public function getTopProductsToday(): array
    {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT 
                p.product_id,
                p.product_name as name,
                SUM(ti.quantity) as qty_sold,
                SUM(ti.quantity * ti.unit_price) as revenue
             FROM transaction_items ti
             JOIN products p ON ti.product_id = p.product_id
             JOIN transactions t ON ti.transaction_id = t.transaction_id
             WHERE DATE(t.created_at) = ? AND t.branch_id = ? AND t.status = 'completed'
             GROUP BY p.product_id, p.product_name
             ORDER BY revenue DESC
             LIMIT 5"
        );
        $stmt->execute([$today, BRANCH_ID]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get sales breakdown by product category.
     *
     * @return array [['category' => string, 'sales' => float], ...]
     */
    public function getSalesByCategory(): array
    {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category,
                SUM(ti.quantity * ti.unit_price) as sales
             FROM transaction_items ti
             JOIN products p ON ti.product_id = p.product_id
             LEFT JOIN categories c ON p.category_id = c.category_id
             JOIN transactions t ON ti.transaction_id = t.transaction_id
             WHERE DATE(t.created_at) = ? AND t.branch_id = ? AND t.status = 'completed'
             GROUP BY c.category_id
             ORDER BY sales DESC"
        );
        $stmt->execute([$today, BRANCH_ID]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get hourly sales data for the last 7 days (for heatmap).
     *
     * @return array ['labels' => [...], 'datasets' => [...]]
     */
    public function getHourlySalesData(): array
    {
        $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
        $today = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT 
                DATE(created_at) as date,
                HOUR(created_at) as hour,
                SUM(total_amount) as sales
             FROM transactions
             WHERE DATE(created_at) BETWEEN ? AND ? AND branch_id = ? AND status = 'completed'
             GROUP BY DATE(created_at), HOUR(created_at)
             ORDER BY date ASC, hour ASC"
        );
        $stmt->execute([$sevenDaysAgo, $today, BRANCH_ID]);
        $data = $stmt->fetchAll();

        // Generate hour labels
        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        // Group by date and build datasets
        $datasets = [];
        $currentDate = null;
        $hourlyData = [];

        foreach ($data as $record) {
            if ($currentDate !== $record['date']) {
                if ($currentDate !== null) {
                    // Finalize previous date's dataset
                    $datasets[] = [
                        'label' => date('D, M d', strtotime($currentDate)),
                        'data' => array_values($hourlyData)
                    ];
                }
                $currentDate = $record['date'];
                $hourlyData = array_fill(0, 24, 0);
            }

            $hourlyData[$record['hour']] = (float)$record['sales'];
        }

        if ($currentDate !== null) {
            $datasets[] = [
                'label' => date('D, M d', strtotime($currentDate)),
                'data' => array_values($hourlyData)
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Get active alerts (expiring products, low stock, pending approvals).
     *
     * @return array [['type' => string, 'severity' => string, 'message' => string, 'count' => int], ...]
     */
    public function getActiveAlerts(): array
    {
        $alerts = [];

        // Expiring products
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count
             FROM batches
             WHERE expiration_date BETWEEN DATE_ADD(NOW(), INTERVAL 1 DAY) 
                                  AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                   AND qty_remaining > 0
                   AND status = 'active'"
        );
        $stmt->execute();
        $expiring = $stmt->fetch()['count'] ?? 0;

        if ($expiring > 0) {
            $alerts[] = [
                'type' => 'expiring',
                'severity' => 'warning',
                'message' => "$expiring batch(es) expiring in 7 days",
                'count' => $expiring,
                'icon' => '⏰'
            ];
        }

        // Low stock products
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count
             FROM products p
               WHERE p.current_stock < p.reorder_point AND p.status = 'active' AND p.branch_id = ?"
        );
        $stmt->execute([BRANCH_ID]);
        $lowStock = $stmt->fetch()['count'] ?? 0;

        if ($lowStock > 0) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'message' => "$lowStock product(s) below reorder level",
                'count' => $lowStock,
                'icon' => '📉'
            ];
        }

        // Pending stock adjustments
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count
             FROM stock_adjustments
             WHERE status = 'pending' AND branch_id = ?"
        );
        $stmt->execute([BRANCH_ID]);
        $pending = $stmt->fetch()['count'] ?? 0;

        if ($pending > 0) {
            $alerts[] = [
                'type' => 'pending_adjustments',
                'severity' => 'info',
                'message' => "$pending stock adjustment(s) pending approval",
                'count' => $pending,
                'icon' => '✓'
            ];
        }

        // Pending purchase orders
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count
             FROM purchase_orders
             WHERE status = 'pending_approval' AND branch_id = ?"
        );
        $stmt->execute([BRANCH_ID]);
        $pendingPO = $stmt->fetch()['count'] ?? 0;

        if ($pendingPO > 0) {
            $alerts[] = [
                'type' => 'pending_po',
                'severity' => 'info',
                'message' => "$pendingPO purchase order(s) awaiting approval",
                'count' => $pendingPO,
                'icon' => '📦'
            ];
        }

        return $alerts;
    }

    /**
     * Get pending actions for the logged-in user.
     *
     * @param int $userId
     * @return array [['action' => string, 'count' => int], ...]
     */
    public function getPendingActions(int $userId): array
    {
        $actions = [];

        // Get user's role
        $stmt = $this->db->prepare(
            "SELECT r.role_slug
             FROM users u
             JOIN roles r ON r.role_id = u.role_id
             WHERE u.user_id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return $actions;
        }

        $roleSlug = $user['role_slug'];

        // Manager-level actions
        if (in_array($roleSlug, ['manager', 'bookkeeper', 'supervisor'], true)) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM stock_adjustments WHERE status = 'pending' AND branch_id = ?"
            );
            $stmt->execute([BRANCH_ID]);
            $adjustments = $stmt->fetch()['count'] ?? 0;
            
            if ($adjustments > 0) {
                $actions[] = ['action' => 'Review Stock Adjustments', 'count' => $adjustments, 'icon' => '✓'];
            }

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'pending_approval' AND branch_id = ?"
            );
            $stmt->execute([BRANCH_ID]);
            $pos = $stmt->fetch()['count'] ?? 0;
            
            if ($pos > 0) {
                $actions[] = ['action' => 'Approve Purchase Orders', 'count' => $pos, 'icon' => '📦'];
            }
        }

        // Cashier actions
        if ($roleSlug === 'cashier') {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM held_transactions WHERE cashier_id = ?"
            );
            $stmt->execute([$userId]);
            $held = $stmt->fetch()['count'] ?? 0;
            
            if ($held > 0) {
                $actions[] = ['action' => 'Recall Held Transactions', 'count' => $held, 'icon' => '🔄'];
            }
        }

        return $actions;
    }

    /**
     * Get top suppliers by purchase amount (last 30 days).
     *
     * @return array [['supplier_id' => int, 'name' => string, 'amount' => float], ...]
     */
    public function getTopSuppliers(): array
    {
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

        $stmt = $this->db->prepare(
            "SELECT 
                s.supplier_id,
                s.supplier_name as name,
                SUM(po.total_amount) as amount
             FROM purchase_orders po
             JOIN suppliers s ON po.supplier_id = s.supplier_id
             WHERE DATE(po.created_at) >= ? AND po.branch_id = ?
             GROUP BY s.supplier_id, s.supplier_name
             ORDER BY amount DESC
             LIMIT 5"
        );
        $stmt->execute([$thirtyDaysAgo, BRANCH_ID]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get inventory summary (stock ok, low, out of stock counts).
     *
     * @return array ['stock_ok' => int, 'stock_low' => int, 'stock_out' => int]
     */
    public function getInventorySummary(): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                SUM(CASE WHEN current_stock > reorder_point THEN 1 ELSE 0 END) as stock_ok,
                SUM(CASE WHEN current_stock > 0 AND current_stock <= reorder_point THEN 1 ELSE 0 END) as stock_low,
                SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as stock_out
             FROM products
             WHERE status = 'active' AND branch_id = ?"
        );
        $stmt->execute([BRANCH_ID]);
        
        $row = $stmt->fetch() ?: [];

        return [
            'stock_ok' => (int)($row['stock_ok'] ?? 0),
            'stock_low' => (int)($row['stock_low'] ?? 0),
            'stock_out' => (int)($row['stock_out'] ?? 0),
        ];
    }

    /**
     * Get recent transactions for activity feed.
     *
     * @param int $limit Default 5
     * @return array [['type' => string, 'message' => string, 'timestamp' => string], ...]
     */
    public function getRecentActivity(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
              "SELECT t.transaction_id, t.cashier_id, u.full_name, t.total_amount, t.created_at
             FROM transactions t
               JOIN users u ON t.cashier_id = u.user_id
             WHERE t.branch_id = ?
               ORDER BY t.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([BRANCH_ID, $limit]);
        $transactions = $stmt->fetchAll();

        $activity = [];
        foreach ($transactions as $txn) {
            $activity[] = [
                'type' => 'sale',
                'message' => $txn['full_name'] . ' completed a sale of ₱' . number_format((float)$txn['total_amount'], 2),
                'timestamp' => $txn['created_at'],
                'icon' => '💰'
            ];
        }

        return $activity;
    }
}
