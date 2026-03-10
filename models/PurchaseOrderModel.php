<?php

class PurchaseOrderModel
{
    private PDO $db;
    private Logger $logger;
    private string $table = 'purchase_orders';

    private function mapStatusToDb(string $status): string
    {
        return match ($status) {
            'submitted' => 'pending_approval',
            'ordered' => 'sent',
            'received' => 'fully_received',
            'rejected' => 'cancelled',
            default => $status,
        };
    }
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Get all purchase orders with pagination
     */
    public function getAll($page = 1, $limit = 20, $search = '', $status = '', $supplier_id = 0)
    {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $limit;
        
        $where = 'WHERE 1=1 ';
        $params = [];
        
        if ($supplier_id > 0) {
            $where .= 'AND supplier_id = ? ';
            $params[] = $supplier_id;
        }
        
        if ($status !== '') {
            $where .= 'AND po.status = ? ';
            $params[] = $this->mapStatusToDb((string)$status);
        }
        
        if ($search !== '') {
            $where .= 'AND (po_number LIKE ? OR supplier_name LIKE ?) ';
            $search = "%{$search}%";
            $params[] = $search;
            $params[] = $search;
        }
        
        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} po 
                                    JOIN suppliers s ON s.supplier_id = po.supplier_id 
                                    {$where}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $stmt = $this->db->prepare(
            "SELECT po.*, s.supplier_name, s.contact_person, s.phone,
                    CASE
                        WHEN po.status = 'pending_approval' THEN 'submitted'
                        WHEN po.status = 'sent' THEN 'ordered'
                        WHEN po.status = 'fully_received' THEN 'received'
                        WHEN po.status = 'cancelled' THEN 'rejected'
                        ELSE po.status
                    END as status,
                    po.expected_delivery as expected_delivery_date,
                    po.created_at as po_date,
                    'Main Store' as delivery_location,
                    creator.full_name as requested_by,
                    CASE
                        WHEN po.status IN ('pending_approval','approved','sent','fully_received','cancelled') THEN po.updated_at
                        ELSE NULL
                    END as submitted_at,
                    CASE
                        WHEN po.status IN ('approved','sent','fully_received') THEN po.updated_at
                        ELSE NULL
                    END as approved_at,
                    CASE
                        WHEN po.status IN ('sent','fully_received') THEN po.updated_at
                        ELSE NULL
                    END as ordered_at,
                    po.actual_delivery as received_at,
                    CASE WHEN po.status = 'cancelled' THEN po.notes ELSE NULL END as rejection_reason,
                    COUNT(poi.item_id) as item_count,
                    COALESCE(SUM(poi.qty_ordered * poi.unit_cost), 0) as total_amount
             FROM {$this->table} po
             JOIN suppliers s ON s.supplier_id = po.supplier_id
             LEFT JOIN users creator ON creator.user_id = po.created_by
             LEFT JOIN po_items poi ON poi.po_id = po.po_id
             {$where}
             GROUP BY po.po_id
             ORDER BY po.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'items' => $items,
            'page' => $page,
            'pages' => (int)ceil($total / $limit),
            'limit' => $limit,
            'total' => $total
        ];
    }
    
    /**
     * Get purchase order by ID with line items
     */
    public function getById($po_id)
    {
        $stmt = $this->db->prepare(
                "SELECT po.*, s.supplier_name, s.contact_person, s.phone, s.email,
                    s.payment_terms, s.lead_time_days,
                    CASE
                        WHEN po.status = 'pending_approval' THEN 'submitted'
                        WHEN po.status = 'sent' THEN 'ordered'
                        WHEN po.status = 'fully_received' THEN 'received'
                        WHEN po.status = 'cancelled' THEN 'rejected'
                        ELSE po.status
                    END as status,
                    po.expected_delivery as expected_delivery_date,
                    po.created_at as po_date,
                    'Main Store' as delivery_location,
                    creator.full_name as requested_by,
                    CASE
                        WHEN po.status IN ('pending_approval','approved','sent','fully_received','cancelled') THEN po.updated_at
                        ELSE NULL
                    END as submitted_at,
                    CASE
                        WHEN po.status IN ('approved','sent','fully_received') THEN po.updated_at
                        ELSE NULL
                    END as approved_at,
                    CASE
                        WHEN po.status IN ('sent','fully_received') THEN po.updated_at
                        ELSE NULL
                    END as ordered_at,
                    po.actual_delivery as received_at,
                    CASE WHEN po.status = 'cancelled' THEN po.notes ELSE NULL END as rejection_reason
             FROM {$this->table} po
             JOIN suppliers s ON s.supplier_id = po.supplier_id
             LEFT JOIN users creator ON creator.user_id = po.created_by
             WHERE po.po_id = ? LIMIT 1"
        );
        $stmt->execute([(int)$po_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get PO items with product details
     */
    public function getItems($po_id)
    {
        $stmt = $this->db->prepare(
                "SELECT poi.*,
                    poi.item_id as po_item_id,
                    poi.qty_ordered as quantity,
                    poi.unit_cost as unit_price,
                    p.product_name, p.product_code, p.category_id,
                    (poi.qty_ordered * poi.unit_cost) as line_total
                 FROM po_items poi
             JOIN products p ON p.product_id = poi.product_id
             WHERE poi.po_id = ?
                 ORDER BY poi.item_id"
        );
        $stmt->execute([(int)$po_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new purchase order
     */
    public function create($data)
    {
        try {
            // Generate PO number
            $poNumber = $this->generatePONumber();
            
            // Insert PO header
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (
                    po_number, branch_id, supplier_id, created_by, status, expected_delivery, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $poNumber,
                BRANCH_ID,
                (int)$data['supplier_id'],
                (int)($_SESSION['user_id'] ?? 1),
                'draft',
                $data['expected_delivery_date'] ?? null,
                $data['notes'] ?? '',
            ]);
            
            $po_id = $this->db->lastInsertId();
            
            // Log
            $this->logger->log('purchase_orders', 'create', (int)$po_id, null,
                [
                    'po_number' => $poNumber,
                    'supplier_id' => (int)$data['supplier_id']
                ], 
                'Purchase order created');
            
            return [
                'success' => true,
                'id' => $po_id,
                'po_number' => $poNumber
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add item to purchase order
     */
    public function addItem($po_id, $product_id, $quantity, $unit_price)
    {
        try {
            // Check if item already exists
            $stmt = $this->db->prepare(
                 "SELECT item_id FROM po_items 
                 WHERE po_id = ? AND product_id = ? LIMIT 1"
            );
            $stmt->execute([(int)$po_id, (int)$product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing item
                $stmt = $this->db->prepare(
                    "UPDATE po_items 
                     SET qty_ordered = qty_ordered + ?, unit_cost = ?
                     WHERE item_id = ?"
                );
                $stmt->execute([(int)$quantity, (float)$unit_price, $existing['item_id']]);
            } else {
                // Insert new item
                $stmt = $this->db->prepare(
                    "INSERT INTO po_items (po_id, product_id, qty_ordered, unit_cost)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([(int)$po_id, (int)$product_id, (int)$quantity, (float)$unit_price]);
            }
            
            return [
                'success' => true,
                'message' => 'Item added to PO'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove item from purchase order
     */
    public function removeItem($po_item_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM po_items WHERE item_id = ?");
            $stmt->execute([(int)$po_item_id]);
            
            return [
                'success' => true,
                'message' => 'Item removed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update purchase order
     */
    public function update($po_id, $data)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                 SET expected_delivery = ?, notes = ?
                 WHERE po_id = ?"
            );
            
            $stmt->execute([
                $data['expected_delivery_date'] ?? null,
                $data['notes'] ?? '',
                (int)$po_id
            ]);
            
            $this->logger->log('purchase_orders', 'update', (int)$po_id, null, $data, 'PO updated');
            
            return [
                'success' => true,
                'message' => 'Purchase order updated'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Submit purchase order for approval (status: submitted)
     */
    public function submit($po_id)
    {
        try {
            // Verify has items
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM po_items WHERE po_id = ?");
            $stmt->execute([(int)$po_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$result['count'] === 0) {
                return [
                    'success' => false,
                    'error' => 'PO must have at least one item'
                ];
            }
            
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET status = 'pending_approval' WHERE po_id = ?"
            );
            $stmt->execute([(int)$po_id]);
            
            $this->logger->log('purchase_orders', 'submit', (int)$po_id, null, ['status' => 'submitted'], 'PO submitted for approval');
            
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Approve purchase order
     */
    public function approve($po_id, $approved_by = null)
    {
        try {
            $approved_by = $approved_by ?? $_SESSION['user_id'] ?? 1;
            
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                  SET status = 'approved', approved_by = ?
                 WHERE po_id = ?"
            );
            $stmt->execute([$approved_by, (int)$po_id]);
            
            $this->logger->log('purchase_orders', 'approve', (int)$po_id, null,
                ['approved_by' => $approved_by], 'PO approved');
            
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject purchase order
     */
    public function reject($po_id, $rejection_reason = '', $rejected_by = null)
    {
        try {
            $rejected_by = $rejected_by ?? $_SESSION['user_id'] ?? 1;
            
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                  SET status = 'cancelled', notes = CONCAT(COALESCE(notes, ''), CASE WHEN COALESCE(notes, '') = '' THEN '' ELSE '\n' END, 'Rejection reason: ', ?), approved_by = ?
                 WHERE po_id = ?"
            );
            $stmt->execute([$rejection_reason, $rejected_by, (int)$po_id]);
            
            $this->logger->log('purchase_orders', 'reject', (int)$po_id, null,
                ['rejected_by' => $rejected_by, 'reason' => $rejection_reason], 'PO rejected');
            
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mark as ordered/sent to supplier
     */
    public function markOrdered($po_id)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET status = 'sent' WHERE po_id = ?"
            );
            $stmt->execute([(int)$po_id]);
            
            $this->logger->log('purchase_orders', 'mark_ordered', (int)$po_id, null,
                ['status' => 'ordered'], 'PO marked as ordered');
            
            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mark as received (with receiving report)
     */
    public function markReceived($po_id, $received_date = null)
    {
        try {
            $po_id = (int)$po_id;
            $received_date = $received_date ?? date('Y-m-d H:i:s');
            $delivery_date = date('Y-m-d', strtotime($received_date));
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Get PO details
            $po = $this->getById($po_id);
            if (!$po) {
                throw new \Exception('Purchase order not found');
            }
            
            // Get all items in this PO
            $items = $this->getItems($po_id);
            if (empty($items)) {
                throw new \Exception('No items found in purchase order');
            }
            
            $total_items_received = 0;
            $total_qty_received = 0;
            
            // Process each item
            foreach ($items as $item) {
                $product_id = (int)$item['product_id'];
                $qty_ordered = (int)$item['qty_ordered'];
                $unit_cost = (float)$item['unit_cost'];
                
                // Generate batch number
                $batch_number = 'PO' . $po['po_number'] . '-P' . $product_id . '-' . date('Ymd');
                
                // Create batch record
                $stmt = $this->db->prepare(
                    "INSERT INTO batches (
                        product_id, po_id, batch_number, qty_received, qty_remaining,
                        delivery_date, cost_price, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
                );
                $stmt->execute([
                    $product_id,
                    $po_id,
                    $batch_number,
                    $qty_ordered,
                    $qty_ordered,
                    $delivery_date,
                    $unit_cost
                ]);
                
                $batch_id = (int)$this->db->lastInsertId();
                
                // Update product stock
                $stmt = $this->db->prepare(
                    "UPDATE products 
                     SET current_stock = current_stock + ?,
                         avg_cost_price = (
                             (current_stock * COALESCE(avg_cost_price, cost_price) + ? * ?) / 
                             NULLIF(current_stock + ?, 0)
                         ),
                         updated_at = NOW()
                     WHERE product_id = ?"
                );
                $stmt->execute([
                    $qty_ordered,
                    $qty_ordered,
                    $unit_cost,
                    $qty_ordered,
                    $product_id
                ]);
                
                // Create stock movement record
                $stmt = $this->db->prepare(
                    "INSERT INTO stock_movements (
                        product_id, batch_id, movement_type, quantity_in,
                        reference_type, reference_id, notes, created_at
                    ) VALUES (?, ?, 'delivery', ?, 'purchase_order', ?, ?, ?)"
                );
                $stmt->execute([
                    $product_id,
                    $batch_id,
                    $qty_ordered,
                    $po_id,
                    'PO ' . $po['po_number'] . ' received',
                    $received_date
                ]);
                
                $total_items_received++;
                $total_qty_received += $qty_ordered;
            }
            
            // Update PO status
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                 SET status = 'fully_received', 
                     actual_delivery = ?,
                     updated_at = NOW()
                 WHERE po_id = ?"
            );
            $stmt->execute([$received_date, $po_id]);
            
            // Log the action
            $this->logger->log('purchase_orders', 'mark_received', $po_id, null, [
                'received_at' => $received_date,
                'items_count' => $total_items_received,
                'total_qty' => $total_qty_received
            ], 'PO marked as received - stock updated');
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'success' => true,
                'items_received' => $total_items_received,
                'total_qty' => $total_qty_received
            ];
            
        } catch (\Exception $e) {
            // Rollback on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get purchase order summary/KPI stats
     */
    public function getSummary()
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total_pos,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                     SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                     SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as ordered,
                     SUM(CASE WHEN status = 'fully_received' THEN 1 ELSE 0 END) as received,
                     SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as rejected
             FROM {$this->table}"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get pending approvals
     */
    public function getPendingApprovals()
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, s.supplier_name,
                    CASE
                        WHEN po.status = 'pending_approval' THEN 'submitted'
                        WHEN po.status = 'sent' THEN 'ordered'
                        WHEN po.status = 'fully_received' THEN 'received'
                        WHEN po.status = 'cancelled' THEN 'rejected'
                        ELSE po.status
                    END as status,
                    po.expected_delivery as expected_delivery_date,
                    po.created_at as po_date,
                    'Main Store' as delivery_location,
                    creator.full_name as requested_by,
                    CASE
                        WHEN po.status IN ('pending_approval','approved','sent','fully_received','cancelled') THEN po.updated_at
                        ELSE NULL
                    END as submitted_at,
                    po.actual_delivery as received_at,
                    COUNT(poi.item_id) as item_count,
                    COALESCE(SUM(poi.qty_ordered * poi.unit_cost), 0) as total_amount
             FROM {$this->table} po
             JOIN suppliers s ON s.supplier_id = po.supplier_id
             LEFT JOIN users creator ON creator.user_id = po.created_by
             LEFT JOIN po_items poi ON poi.po_id = po.po_id
             WHERE po.status = 'pending_approval'
             GROUP BY po.po_id
             ORDER BY po.updated_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get overdue purchase orders
     */
    public function getOverdue()
    {
        $stmt = $this->db->prepare(
            "SELECT po.*, s.supplier_name,
                    CASE
                        WHEN po.status = 'pending_approval' THEN 'submitted'
                        WHEN po.status = 'sent' THEN 'ordered'
                        WHEN po.status = 'fully_received' THEN 'received'
                        WHEN po.status = 'cancelled' THEN 'rejected'
                        ELSE po.status
                    END as status,
                    po.expected_delivery as expected_delivery_date,
                    DATEDIFF(CURDATE(), po.expected_delivery) as days_overdue
             FROM {$this->table} po
             JOIN suppliers s ON s.supplier_id = po.supplier_id
             WHERE po.status IN ('approved', 'sent') 
             AND po.expected_delivery < CURDATE()
             ORDER BY po.expected_delivery ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search purchase orders
     */
    public function search($query)
    {
        $query = "%{$query}%";
        $stmt = $this->db->prepare(
            "SELECT po.*, s.supplier_name
                    ,CASE
                        WHEN po.status = 'pending_approval' THEN 'submitted'
                        WHEN po.status = 'sent' THEN 'ordered'
                        WHEN po.status = 'fully_received' THEN 'received'
                        WHEN po.status = 'cancelled' THEN 'rejected'
                        ELSE po.status
                    END as status
                    ,po.expected_delivery as expected_delivery_date
             FROM {$this->table} po
             JOIN suppliers s ON s.supplier_id = po.supplier_id
             WHERE po.po_number LIKE ? OR s.supplier_name LIKE ? OR po.notes LIKE ?
             ORDER BY po.created_at DESC
             LIMIT 25"
        );
        $stmt->execute([$query, $query, $query]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Queue sold products into supplier-grouped auto purchase orders.
     * Products are appended to the same open auto PO (draft/pending_approval)
     * until that PO is approved.
     */
    public function queueAutoReplenishmentFromSale(array $soldItems, int $createdBy): array
    {
        if (empty($soldItems)) {
            return ['queued_items' => 0, 'po_count' => 0, 'skipped_items' => 0];
        }

        $normalized = [];
        foreach ($soldItems as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['qty'] ?? 0);
            $unitCost = (float)($row['unit_cost'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            if (!isset($normalized[$productId])) {
                $normalized[$productId] = [
                    'product_id' => $productId,
                    'qty' => 0,
                    'unit_cost' => $unitCost,
                ];
            }

            $normalized[$productId]['qty'] += $qty;
            if ($unitCost > 0) {
                $normalized[$productId]['unit_cost'] = $unitCost;
            }
        }

        if (empty($normalized)) {
            return ['queued_items' => 0, 'po_count' => 0, 'skipped_items' => 0];
        }

        $productIds = array_keys($normalized);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $productStmt = $this->db->prepare(
            "SELECT p.product_id,
                    p.product_name,
                    p.primary_supplier_id,
                    p.cost_price,
                    COALESCE(
                        p.primary_supplier_id,
                        (
                            SELECT sp.supplier_id
                            FROM supplier_products sp
                            WHERE sp.product_id = p.product_id
                            ORDER BY sp.is_primary DESC, sp.sp_id ASC
                            LIMIT 1
                        ),
                        (
                            SELECT s.supplier_id
                            FROM suppliers s
                            WHERE s.status = 'active'
                            ORDER BY s.supplier_id ASC
                            LIMIT 1
                        )
                    ) AS resolved_supplier_id
             FROM products p
             WHERE p.product_id IN ($placeholders)"
        );
        $productStmt->execute($productIds);
        $products = [];
        foreach ($productStmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
            $products[(int)$product['product_id']] = $product;
        }

        $setPrimarySupplierStmt = $this->db->prepare(
            "UPDATE products SET primary_supplier_id = ? WHERE product_id = ? AND primary_supplier_id IS NULL"
        );

        $bySupplier = [];
        $skippedItems = 0;
        foreach ($normalized as $row) {
            $productId = (int)$row['product_id'];
            if (!isset($products[$productId])) {
                $skippedItems++;
                continue;
            }

            $product = $products[$productId];
            $supplierId = isset($product['resolved_supplier_id']) ? (int)$product['resolved_supplier_id'] : 0;
            if ($supplierId <= 0) {
                $skippedItems++;
                continue;
            }

            if (empty($product['primary_supplier_id'])) {
                $setPrimarySupplierStmt->execute([$supplierId, $productId]);
            }

            $cost = (float)$row['unit_cost'];
            if ($cost <= 0) {
                $cost = (float)($product['cost_price'] ?? 0);
            }
            if ($cost <= 0) {
                $cost = 1.00;
            }

            if (!isset($bySupplier[$supplierId])) {
                $bySupplier[$supplierId] = [];
            }

            $bySupplier[$supplierId][] = [
                'product_id' => $productId,
                'qty' => (int)$row['qty'],
                'unit_cost' => $cost,
            ];
        }

        if (empty($bySupplier)) {
            return ['queued_items' => 0, 'po_count' => 0, 'skipped_items' => $skippedItems];
        }

        $findAutoPoStmt = $this->db->prepare(
            "SELECT po_id
             FROM purchase_orders
             WHERE branch_id = ?
               AND supplier_id = ?
               AND status IN ('draft', 'pending_approval')
               AND notes LIKE ?
             ORDER BY po_id DESC
             LIMIT 1"
        );
        $createPoStmt = $this->db->prepare(
            "INSERT INTO purchase_orders (
                po_number, branch_id, supplier_id, created_by, status, expected_delivery, notes, created_at
             ) VALUES (?, ?, ?, ?, 'pending_approval', ?, ?, NOW())"
        );
        $upsertPoItemSelectStmt = $this->db->prepare(
            "SELECT item_id FROM po_items WHERE po_id = ? AND product_id = ? LIMIT 1"
        );
        $updatePoItemStmt = $this->db->prepare(
            "UPDATE po_items SET qty_ordered = qty_ordered + ?, unit_cost = ? WHERE item_id = ?"
        );
        $insertPoItemStmt = $this->db->prepare(
            "INSERT INTO po_items (po_id, product_id, qty_ordered, unit_cost) VALUES (?, ?, ?, ?)"
        );
        $updatePoTotalStmt = $this->db->prepare(
            "UPDATE purchase_orders po
             SET total_amount = (
                SELECT COALESCE(SUM(qty_ordered * unit_cost), 0)
                FROM po_items
                WHERE po_id = po.po_id
             )
             WHERE po.po_id = ?"
        );
        $supplierLeadStmt = $this->db->prepare(
            "SELECT lead_time_days FROM suppliers WHERE supplier_id = ? LIMIT 1"
        );

        $autoMarker = '[AUTO-REPLENISHMENT]';
        $poTouched = [];
        $queuedItems = 0;

        foreach ($bySupplier as $supplierId => $rows) {
            $findAutoPoStmt->execute([BRANCH_ID, (int)$supplierId, '%' . $autoMarker . '%']);
            $poId = (int)($findAutoPoStmt->fetchColumn() ?: 0);

            if ($poId <= 0) {
                $supplierLeadStmt->execute([(int)$supplierId]);
                $leadDays = (int)($supplierLeadStmt->fetchColumn() ?: 7);
                $expectedDelivery = date('Y-m-d', strtotime('+' . max(1, $leadDays) . ' days'));

                $poNumber = $this->generatePONumber();
                $createPoStmt->execute([
                    $poNumber,
                    BRANCH_ID,
                    (int)$supplierId,
                    max(1, $createdBy),
                    $expectedDelivery,
                    $autoMarker . ' Auto-generated from POS sales. Pending approval.',
                ]);
                $poId = (int)$this->db->lastInsertId();
            }

            foreach ($rows as $row) {
                $upsertPoItemSelectStmt->execute([$poId, (int)$row['product_id']]);
                $existingItemId = (int)($upsertPoItemSelectStmt->fetchColumn() ?: 0);

                if ($existingItemId > 0) {
                    $updatePoItemStmt->execute([(int)$row['qty'], (float)$row['unit_cost'], $existingItemId]);
                } else {
                    $insertPoItemStmt->execute([$poId, (int)$row['product_id'], (int)$row['qty'], (float)$row['unit_cost']]);
                }

                $queuedItems += (int)$row['qty'];
            }

            $updatePoTotalStmt->execute([$poId]);
            $poTouched[$poId] = true;
        }

        return [
            'queued_items' => $queuedItems,
            'po_count' => count($poTouched),
            'skipped_items' => $skippedItems,
        ];
    }
    
    /**
     * Generate unique PO number
     */
    private function generatePONumber()
    {
        $date = date('Ymd');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE po_number LIKE 'PO-{$date}%'"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $seq = str_pad((int)$result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return "PO-{$date}-{$seq}";
    }
}
