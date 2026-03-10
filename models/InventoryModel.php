<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                      INVENTORY MODEL                                ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * Inventory management, stock tracking, batch expiry, and adjustments
 */

declare(strict_types=1);

class InventoryModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get inventory overview with pagination and filters
     */
    public function getInventory(
        int $page = 1,
        int $limit = 50,
        string $search = '',
        string $status = ''
    ): array {
        $offset = ($page - 1) * $limit;
        
        $where = ['p.status = "active"'];
        $params = [];
        
        if ($search !== '') {
            $where[] = "(p.product_name LIKE ? OR p.product_code LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        if ($status !== '') {
            if ($status === 'low') {
                $where[] = "p.current_stock <= p.reorder_point";
            } elseif ($status === 'out') {
                $where[] = "p.current_stock = 0";
            } elseif ($status === 'ok') {
                $where[] = "p.current_stock > p.reorder_point";
            }
        }
        
        $whereClause = implode(' AND ', $where);
        
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products p WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.current_stock, 
                    p.reorder_point, p.minimum_stock, p.maximum_stock, p.cost_price, p.selling_price,
                    c.category_name,
                    (SELECT COUNT(*) FROM batches WHERE product_id = p.product_id AND status='active') as batch_count,
                    (SELECT COALESCE(MIN(expiration_date), NULL) FROM batches WHERE product_id = p.product_id AND status='active') as next_expiry,
                    CASE 
                        WHEN (SELECT COALESCE(MIN(expiration_date), NULL) FROM batches WHERE product_id = p.product_id AND status='active') IS NULL THEN NULL
                        ELSE DATEDIFF((SELECT COALESCE(MIN(expiration_date), NULL) FROM batches WHERE product_id = p.product_id AND status='active'), CURDATE())
                    END as days_to_expiry
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             WHERE $whereClause
             ORDER BY CASE 
                        WHEN p.current_stock <= p.reorder_point THEN 1
                        WHEN p.current_stock = 0 THEN 0
                        ELSE 2
                      END ASC, p.product_name ASC
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return [
            'records' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * Get batches for a product with stock info
     */
    public function getProductBatches(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.batch_id, b.batch_number, b.qty_received, b.qty_remaining, 
                    b.delivery_date as received_date, b.expiration_date, b.cost_price as cost_price_per_unit, b.status,
                    DATEDIFF(b.expiration_date, CURDATE()) as days_until_expiry
             FROM batches b
             WHERE b.product_id = ? AND b.status = 'active'
             ORDER BY b.expiration_date ASC"
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get expiring batches (within 30 days)
     */
    public function getExpiringBatches(int $days = 30, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.batch_id, b.batch_number, p.product_id, p.product_code, p.product_name, 
                    b.qty_remaining, b.expiration_date,
                    DATEDIFF(b.expiration_date, CURDATE()) as days_left,
                    CASE 
                        WHEN DATEDIFF(b.expiration_date, CURDATE()) < 0 THEN 'expired'
                        WHEN DATEDIFF(b.expiration_date, CURDATE()) <= 7 THEN 'critical'
                        WHEN DATEDIFF(b.expiration_date, CURDATE()) <= 14 THEN 'urgent'
                        ELSE 'warning'
                    END as severity
             FROM batches b
             JOIN products p ON p.product_id = b.product_id
             WHERE b.status = 'active'
               AND b.expiration_date IS NOT NULL
               AND DATEDIFF(b.expiration_date, CURDATE()) <= ?
             ORDER BY b.expiration_date ASC
             LIMIT ?"
        );
                $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get aging products (no sales in X days)
     */
    public function getAgingProducts(int $days = 90, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.current_stock, 
                    p.reorder_point, p.cost_price, c.category_name,
                    COALESCE(MAX(txn.created_at), p.created_at) as last_sale_date,
                    DATEDIFF(CURDATE(), COALESCE(MAX(txn.created_at), p.created_at)) as days_no_sale,
                                        COUNT(t.item_id) as total_sales_30d
             FROM products p
             LEFT JOIN transaction_items t ON t.product_id = p.product_id
             LEFT JOIN transactions txn ON txn.transaction_id = t.transaction_id
               AND txn.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             JOIN categories c ON c.category_id = p.category_id
             WHERE p.status = 'active'
             GROUP BY p.product_id
             HAVING DATEDIFF(CURDATE(), COALESCE(MAX(txn.created_at), p.created_at)) > ?
             ORDER BY days_no_sale DESC
             LIMIT ?"
        );
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get stock adjustments with pagination
     */
    public function getAdjustments(
        int $page = 1,
        int $limit = 50,
        string $status = ''
    ): array {
        $offset = ($page - 1) * $limit;
        $where = ['1=1'];
        $params = [];
        
        if ($status !== '') {
            $where[] = "sa.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM stock_adjustments sa WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        $stmt = $this->db->prepare(
            "SELECT sa.adjustment_id, sa.adjustment_type, p.product_name, sa.quantity, sa.status,
                    sa.reason, u.full_name as requested_by, sa.requested_at, sa.actioned_at as approved_at,
                    COALESCE(u2.full_name, 'Pending') as approved_by
             FROM stock_adjustments sa
             JOIN products p ON p.product_id = sa.product_id
             JOIN users u ON u.user_id = sa.requested_by
             LEFT JOIN users u2 ON u2.user_id = sa.approved_by
             WHERE $whereClause
             ORDER BY sa.requested_at DESC
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return [
            'records' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * Create stock adjustment request
     */
    public function createAdjustment(
        int $productId,
        string $type,
        int $quantity,
        string $reason,
        int $requestedBy
    ): ?int {
        if ($type === 'addition') {
            $type = 'correction';
            $quantity = abs($quantity);
        } elseif ($type === 'removal') {
            $type = 'correction';
            $quantity = -abs($quantity);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO stock_adjustments (product_id, adjustment_type, quantity, reason, requested_by, status, requested_at)
             VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        if ($stmt->execute([$productId, $type, $quantity, $reason, $requestedBy])) {
            return (int)$this->db->lastInsertId();
        }
        
        return null;
    }

    /**
     * Approve stock adjustment
     */
    public function approveAdjustment(int $adjustmentId, int $approvedBy): bool
    {
        $stmt = $this->db->prepare(
            "SELECT sa.product_id, sa.adjustment_type, sa.quantity 
             FROM stock_adjustments sa
             WHERE sa.adjustment_id = ?"
        );
        $stmt->execute([$adjustmentId]);
        $adjustment = $stmt->fetch();
        
        if (!$adjustment) {
            return false;
        }
        
        // Update stock
        $updateStmt = $this->db->prepare(
            "UPDATE products
             SET current_stock = GREATEST(0, current_stock + ?)
             WHERE product_id = ?"
        );
        $updateStmt->execute([$adjustment['quantity'], $adjustment['product_id']]);
        
        // Update adjustment status
        $approveStmt = $this->db->prepare(
            "UPDATE stock_adjustments SET status = 'approved', approved_by = ?, actioned_at = NOW() WHERE adjustment_id = ?"
        );
        
        return $approveStmt->execute([$approvedBy, $adjustmentId]);
    }

    /**
     * Reject stock adjustment
     */
    public function rejectAdjustment(int $adjustmentId, int $rejectedBy): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_adjustments SET status = 'rejected', approved_by = ?, actioned_at = NOW() WHERE adjustment_id = ?"
        );
        return $stmt->execute([$rejectedBy, $adjustmentId]);
    }

    /**
     * Create stocktake batch
     */
    public function createStocktake(int $createdBy): ?int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stocktake_sessions (branch_id, session_type, started_by, status, started_at)
             VALUES (?, 'full', ?, 'in_progress', NOW())"
        );
        
        if ($stmt->execute([BRANCH_ID, $createdBy])) {
            return (int)$this->db->lastInsertId();
        }
        
        return null;
    }

    /**
     * Get active stocktakes
     */
    public function getStocktakes(string $status = ''): array
    {
        $where = ['st.branch_id = ?'];
        $params = [BRANCH_ID];
        
        if ($status !== '') {
            $where[] = "st.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->prepare(
            "SELECT st.session_id as stocktake_id,
                    st.status,
                    st.started_at as created_at,
                    st.completed_at,
                    'Main Store' as location,
                    starter.full_name as created_by,
                    COALESCE(completer.full_name, starter.full_name) as completed_by,
                    COUNT(si.item_id) as total_products,
                    SUM(CASE WHEN si.counted_qty IS NOT NULL THEN 1 ELSE 0 END) as counted_products,
                    SUM(CASE WHEN si.counted_qty IS NOT NULL AND si.counted_qty <> si.expected_qty THEN 1 ELSE 0 END) as variance_count
             FROM stocktake_sessions st
             LEFT JOIN users starter ON starter.user_id = st.started_by
             LEFT JOIN users completer ON completer.user_id = st.completed_by
             LEFT JOIN stocktake_items si ON si.session_id = st.session_id
             WHERE $whereClause
             GROUP BY st.session_id
             ORDER BY st.started_at DESC"
        );
        $stmt->execute($params);

        $rows = $stmt->fetchAll() ?: [];
        return [
            'active' => array_values(array_filter($rows, fn($row) => ($row['status'] ?? '') === 'in_progress')),
            'completed' => array_values(array_filter($rows, fn($row) => ($row['status'] ?? '') === 'completed')),
        ];
    }

    /**
     * Record stocktake count for a product
     */
    public function recordStocktakeCount(int $stocktakeId, int $productId, int $countedQty, ?int $countedBy = null): bool
    {
        $sessionStmt = $this->db->prepare(
            "SELECT session_id FROM stocktake_sessions WHERE session_id = ? AND status = 'in_progress' LIMIT 1"
        );
        $sessionStmt->execute([$stocktakeId]);
        if (!$sessionStmt->fetchColumn()) {
            return false;
        }

        $expectedStmt = $this->db->prepare("SELECT current_stock FROM products WHERE product_id = ? LIMIT 1");
        $expectedStmt->execute([$productId]);
        $expectedQty = (int)($expectedStmt->fetchColumn() ?: 0);

        $countedBy = $countedBy !== null && $countedBy > 0 ? $countedBy : null;

        $updateStmt = $this->db->prepare(
            "UPDATE stocktake_items
             SET counted_qty = ?, counted_by = ?, counted_at = NOW()
             WHERE session_id = ? AND product_id = ?"
        );
        $updateStmt->execute([$countedQty, $countedBy, $stocktakeId, $productId]);

        if ($updateStmt->rowCount() > 0) {
            return true;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO stocktake_items (session_id, product_id, expected_qty, counted_qty, counted_by, counted_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        return $stmt->execute([$stocktakeId, $productId, $expectedQty, $countedQty, $countedBy]);
    }

    /**
     * Finalize stocktake and update inventory
     */
    public function finalizeStocktake(int $stocktakeId, ?int $completedBy = null): bool
    {
        // Get all stocktake items
        $itemStmt = $this->db->prepare(
            "SELECT si.product_id, si.counted_qty as counted_quantity, p.current_stock
             FROM stocktake_items si
             JOIN products p ON p.product_id = si.product_id
             WHERE si.session_id = ?"
        );
        $itemStmt->execute([$stocktakeId]);
        $items = $itemStmt->fetchAll();
        
        // Record variance adjustments
        foreach ($items as $item) {
            $variance = $item['counted_quantity'] - $item['current_stock'];
            if ($variance !== 0) {
                $type = $variance > 0 ? 'addition' : 'removal';
                $reason = 'Stocktake variance for stocktake #' . $stocktakeId;
                $this->createAdjustment(
                    $item['product_id'],
                    $type,
                    abs($variance),
                    $reason,
                    0  // System user
                );
            }
        }
        
        // Update stocktake status
        $completedBy = $completedBy !== null && $completedBy > 0 ? $completedBy : null;

        $updateStmt = $this->db->prepare(
            "UPDATE stocktake_sessions
             SET status = 'completed', completed_at = NOW(), completed_by = ?
             WHERE session_id = ?"
        );
        return $updateStmt->execute([$completedBy, $stocktakeId]);
    }

    /**
     * Get products for stocktake counting
     */
    public function getStocktakeItems(int $stocktakeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.current_stock,
                    c.category_name,
                    si.item_id, si.counted_qty,
                    CASE WHEN si.counted_qty IS NOT NULL THEN 'counted' ELSE 'pending' END as status
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN stocktake_items si ON si.session_id = ? AND si.product_id = p.product_id
             WHERE p.status = 'active'
             ORDER BY CASE WHEN si.counted_qty IS NOT NULL THEN 1 ELSE 0 END ASC, p.product_name ASC"
        );
        $stmt->execute([$stocktakeId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get shelf locations
     */
    public function getShelves(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT sl.shelf_id,
                    sl.shelf_name as shelf_code,
                    COALESCE(sl.aisle, '-') as aisle,
                    COALESCE(sl.position, '-') as `row`,
                    '-' as `column`,
                    sl.status,
                    COUNT(sa.product_id) as product_count
             FROM shelves sl
             LEFT JOIN shelf_assignments sa ON sa.shelf_id = sl.shelf_id
             WHERE sl.branch_id = ?
             GROUP BY sl.shelf_id
             ORDER BY sl.aisle, sl.shelf_name
             LIMIT ?"
        );
        $stmt->execute([BRANCH_ID, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Assign product to shelf
     */
    public function assignProductToShelf(int $shelfId, int $productId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shelf_assignments (shelf_id, product_id, qty_placed, assigned_by, assigned_at)
             VALUES (?, ?, 1, ?, NOW())
             ON DUPLICATE KEY UPDATE assigned_by = VALUES(assigned_by), assigned_at = NOW()"
        );
        return $stmt->execute([$shelfId, $productId, (int)($_SESSION['user_id'] ?? 0)]);
    }

    /**
     * Get inventory summary statistics
     */
    public function getInventorySummary(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN current_stock <= reorder_point THEN 1 ELSE 0 END) as low_stock,
                SUM(current_stock) as total_units,
                SUM(current_stock * cost_price) as inventory_value,
                AVG(current_stock) as avg_stock
             FROM products
             WHERE status = 'active' AND branch_id = ?"
        );
        $stmt->execute([BRANCH_ID]);
        return $stmt->fetch() ?? [];
    }

    /**
     * Get expiry alerts
     */
    public function getExpiryAlerts(): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) < 0 THEN 1 ELSE 0 END) as expired_count,
                SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 0 AND 7 THEN 1 ELSE 0 END) as expiring_critical,
                SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as expiring_urgent,
                SUM(CASE WHEN DATEDIFF(expiration_date, CURDATE()) BETWEEN 15 AND 30 THEN 1 ELSE 0 END) as expiring_warning
             FROM batches
             WHERE status = 'active' AND expiration_date IS NOT NULL"
        );
        $stmt->execute();
        $alerts = $stmt->fetch();
        
        return [
            'expired' => (int)($alerts['expired_count'] ?? 0),
            'critical' => (int)($alerts['expiring_critical'] ?? 0),
            'urgent' => (int)($alerts['expiring_urgent'] ?? 0),
            'warning' => (int)($alerts['expiring_warning'] ?? 0),
        ];
    }

    /**
     * Get pending adjustments count
     */
    public function getPendingAdjustmentsCount(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM stock_adjustments WHERE status = 'pending'"
        );
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get inventory valuation by category
     */
    public function getInventoryByCategory(): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.category_name, 
                    COUNT(p.product_id) as product_count,
                    SUM(p.current_stock) as total_units,
                    SUM(p.current_stock * p.cost_price) as category_value
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             WHERE p.status = 'active' AND p.branch_id = ?
             GROUP BY c.category_id
             ORDER BY category_value DESC"
        );
        $stmt->execute([BRANCH_ID]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate waste/loss from expired batches
     */
    public function getExpiredBatchLoss(): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                SUM(qty_remaining) as expired_units,
                SUM(qty_remaining * cost_price) as loss_value,
                COUNT(*) as batch_count
             FROM batches
             WHERE status = 'active' AND expiration_date < CURDATE()"
        );
        $stmt->execute();
        $result = $stmt->fetch() ?? [];
        return [
            'expired_units' => (int)($result['expired_units'] ?? 0),
            'loss_value' => (float)($result['loss_value'] ?? 0),
            'batch_count' => (int)($result['batch_count'] ?? 0),
        ];
    }
}
