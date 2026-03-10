<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                      PRODUCT MODEL                                  ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * Centralized product data management and analytics
 */

declare(strict_types=1);

class ProductModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all products with pagination and filters
     */
    public function getAll(
        int $page = 1,
        int $limit = 50,
        string $search = '',
        int $categoryId = 0,
        int $supplierId = 0,
        string $status = ''
    ): array {
        $offset = ($page - 1) * $limit;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($search !== '') {
            $where[] = "(p.product_name LIKE ? OR p.product_code LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        if ($categoryId > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($supplierId > 0) {
            $where[] = "(p.primary_supplier_id = ? OR p.secondary_supplier_id = ?)";
            $params[] = $supplierId;
            $params[] = $supplierId;
        }
        
        if ($status !== '') {
            $where[] = "p.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM products p WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.selling_price, p.cost_price,
                    p.current_stock, p.reorder_point, p.status, c.category_name,
                    s.supplier_name as primary_supplier_name
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN suppliers s ON s.supplier_id = p.primary_supplier_id
             WHERE $whereClause
             ORDER BY p.product_name ASC
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
     * Get single product by ID with all details
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    /**
     * Get product with batch details
     */
    public function getWithBatches(int $id): ?array
    {
        $product = $this->getById($id);
        if (!$product) {
            return null;
        }
        
        $batchStmt = $this->db->prepare(
            "SELECT batch_id, batch_number as batch_code, qty_remaining as quantity_remaining, 
                    expiration_date as expiry_date, mfg_date as manufacturing_date,
                    cost_price as unit_cost, delivery_date as received_date, status
             FROM batches
             WHERE product_id = ?
             ORDER BY expiration_date ASC"
        );
        $batchStmt->execute([$id]);
        $product['batches'] = $batchStmt->fetchAll();
        
        return $product;
    }

    /**
     * Get product price history
     */
    public function getPriceHistory(int $id, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT field_changed, old_value, new_value, changed_by, reason, changed_at
             FROM price_history
             WHERE product_id = ?
             ORDER BY changed_at DESC
             LIMIT ?"
        );
        $stmt->execute([$id, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Create new product
     */
    public function create(array $data, int $createdBy): ?int
    {
        // Check if product code already exists
        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$data['product_code'] ?? '']);
        if ((int)$checkStmt->fetchColumn() > 0) {
            return null;
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO products (
                branch_id, product_code, product_name, product_alias, category_id, brand_id,
                primary_supplier_id, secondary_supplier_id, unit_of_measure, storage_condition,
                description, is_vatable, cost_price, avg_cost_price, selling_price, wholesale_price,
                min_selling_price, minimum_stock, reorder_point, reorder_qty, maximum_stock,
                current_stock, status, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, NOW(), NOW()
            )"
        );
        
        $stmt->execute([
            BRANCH_ID,
            $data['product_code'] ?? '',
            $data['product_name'] ?? '',
            $data['product_alias'] ?? null,
            (int)($data['category_id'] ?? 0),
            (int)($data['brand_id'] ?? 0) ?: null,
            (int)($data['primary_supplier_id'] ?? 0) ?: null,
            (int)($data['secondary_supplier_id'] ?? 0) ?: null,
            $data['unit_of_measure'] ?? 'pc',
            $data['storage_condition'] ?? 'dry',
            $data['description'] ?? null,
            (int)($data['is_vatable'] ?? 1),
            round((float)($data['cost_price'] ?? 0), 2),
            round((float)($data['cost_price'] ?? 0), 2),
            round((float)($data['selling_price'] ?? 0), 2),
            round((float)($data['wholesale_price'] ?? 0), 2),
            round((float)($data['min_selling_price'] ?? 0), 2),
            (int)($data['minimum_stock'] ?? 10),
            (int)($data['reorder_point'] ?? 20),
            (int)($data['reorder_qty'] ?? 50),
            (int)($data['maximum_stock'] ?? 500),
            (int)($data['current_stock'] ?? 0),
            'active',
            $createdBy,
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update product
     */
    public function update(int $id, array $data): bool
    {
        $current = $this->getById($id);
        if (!$current) {
            return false;
        }
        
        $stmt = $this->db->prepare(
            "UPDATE products
             SET product_name = ?, product_alias = ?, category_id = ?, brand_id = ?,
                 primary_supplier_id = ?, secondary_supplier_id = ?, unit_of_measure = ?,
                 storage_condition = ?, description = ?, is_vatable = ?, cost_price = ?,
                 selling_price = ?, wholesale_price = ?, min_selling_price = ?, minimum_stock = ?,
                 reorder_point = ?, reorder_qty = ?, maximum_stock = ?, status = ?, updated_at = NOW()
             WHERE product_id = ?"
        );
        
        $sellingPrice = round((float)($data['selling_price'] ?? $current['selling_price']), 2);
        $costPrice = round((float)($data['cost_price'] ?? $current['cost_price']), 2);
        
        $stmt->execute([
            $data['product_name'] ?? $current['product_name'],
            $data['product_alias'] ?? $current['product_alias'],
            (int)($data['category_id'] ?? $current['category_id']),
            (int)($data['brand_id'] ?? $current['brand_id']) ?: null,
            (int)($data['primary_supplier_id'] ?? $current['primary_supplier_id']) ?: null,
            (int)($data['secondary_supplier_id'] ?? $current['secondary_supplier_id']) ?: null,
            $data['unit_of_measure'] ?? $current['unit_of_measure'],
            $data['storage_condition'] ?? $current['storage_condition'],
            $data['description'] ?? $current['description'],
            (int)($data['is_vatable'] ?? $current['is_vatable']),
            $costPrice,
            $sellingPrice,
            round((float)($data['wholesale_price'] ?? $current['wholesale_price']), 2),
            round((float)($data['min_selling_price'] ?? $current['min_selling_price']), 2),
            (int)($data['minimum_stock'] ?? $current['minimum_stock']),
            (int)($data['reorder_point'] ?? $current['reorder_point']),
            (int)($data['reorder_qty'] ?? $current['reorder_qty']),
            (int)($data['maximum_stock'] ?? $current['maximum_stock']),
            $data['status'] ?? $current['status'],
            $id,
        ]);
        
        // Log price changes
        if ((float)$current['selling_price'] !== $sellingPrice) {
            $priceStmt = $this->db->prepare(
                "INSERT INTO price_history (product_id, field_changed, old_value, new_value, changed_by, reason, changed_at)
                 VALUES (?, 'selling_price', ?, ?, ?, ?, NOW())"
            );
            $priceStmt->execute([
                $id,
                round((float)$current['selling_price'], 2),
                $sellingPrice,
                (int)($data['changed_by'] ?? 0),
                $data['price_change_reason'] ?? 'Manual update',
            ]);
        }
        
        return true;
    }

    /**
     * Archive (soft delete) product
     */
    public function archive(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Search products by keyword (for AJAX)
     */
    public function search(string $query, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT product_id, product_code, product_name, selling_price, cost_price, current_stock
             FROM products
             WHERE status = 'active' AND (product_name LIKE ? OR product_code LIKE ?)
             ORDER BY product_name ASC
             LIMIT ?"
        );
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            "SELECT product_id, product_code, product_name, selling_price, current_stock, reorder_point
             FROM products
             WHERE category_id = ? AND status = 'active'
             ORDER BY product_name ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Get low stock products
     */
    public function getLowStock(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.current_stock, p.reorder_point,
                    c.category_name, (p.reorder_point - p.current_stock) as deficit
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             WHERE p.status = 'active' AND p.current_stock <= p.reorder_point
             ORDER BY p.current_stock ASC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get top selling products
     */
    public function getTopSellers(int $days = 30, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.selling_price,
                    SUM(t.quantity) as total_qty, SUM(t.subtotal) as total_revenue
             FROM products p
             JOIN transaction_items t ON t.product_id = p.product_id
             JOIN transactions txn ON txn.transaction_id = t.transaction_id
             WHERE txn.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND txn.branch_id = ?
             GROUP BY p.product_id
             ORDER BY total_qty DESC
             LIMIT ?"
        );
        $stmt->execute([$days, BRANCH_ID, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get product with full analytics
     */
    public function getWithAnalytics(int $id): ?array
    {
        $product = $this->getWithBatches($id);
        if (!$product) {
            return null;
        }
        
        // Sales data (last 30 days)
        $salesStmt = $this->db->prepare(
            "SELECT COUNT(*) as total_sold, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue
             FROM transaction_items ti
             JOIN transactions txn ON txn.transaction_id = ti.transaction_id
             WHERE ti.product_id = ?
               AND txn.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               AND txn.branch_id = ?"
        );
        $salesStmt->execute([$id, BRANCH_ID]);
        $product['sales_30d'] = $salesStmt->fetch();
        
        // Purchase history (last 10 batches)
        $purchaseStmt = $this->db->prepare(
            "SELECT b.delivery_date as received_date, b.batch_number, b.qty_received, b.cost_price as cost_price_per_unit
             FROM batches b
             WHERE b.product_id = ?
             ORDER BY b.delivery_date DESC
             LIMIT 10"
        );
        $purchaseStmt->execute([$id]);
        $product['recent_purchases'] = $purchaseStmt->fetchAll();
        
        return $product;
    }

    /**
     * Get all categories (for dropdowns)
     */
    public function getCategories(): array
    {
        $stmt = $this->db->query(
            "SELECT category_id, category_name FROM categories WHERE status='active' ORDER BY category_name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all brands (for dropdowns)
     */
    public function getBrands(): array
    {
        $stmt = $this->db->query(
            "SELECT brand_id, brand_name FROM brands WHERE status='active' ORDER BY brand_name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all suppliers (for dropdowns)
     */
    public function getSuppliers(): array
    {
        $stmt = $this->db->query(
            "SELECT supplier_id, supplier_name FROM suppliers WHERE status='active' ORDER BY supplier_name"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get product inventory summary
     */
    public function getInventorySummary(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN current_stock <= reorder_point THEN 1 ELSE 0 END) as low_stock,
                SUM(current_stock) as total_items,
                SUM(current_stock * cost_price) as total_value
             FROM products
             WHERE status = 'active'"
        );
        return $stmt->fetch() ?? [];
    }

    /**
     * Sync product inventory from batches
     */
    public function syncInventoryFromBatches(int $productId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products
             SET current_stock = (SELECT COALESCE(SUM(qty_remaining), 0) FROM batches WHERE product_id = ?)
             WHERE product_id = ?"
        );
        return $stmt->execute([$productId, $productId]);
    }
}
