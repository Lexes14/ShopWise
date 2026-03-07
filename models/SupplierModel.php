<?php
declare(strict_types=1);

class SupplierModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($page = 1, $limit = 20, $search = '', $status = 'active')
    {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 's.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(s.supplier_name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)';
            $searchLike = '%' . $search . '%';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM suppliers s {$whereSql}");
        $countStmt->execute($params);
        $total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $sql = "SELECT s.*,
                       COUNT(po.po_id) AS total_orders,
                       COALESCE(SUM(po.total_amount), 0) AS total_spent
                FROM suppliers s
                LEFT JOIN purchase_orders po ON po.supplier_id = s.supplier_id
                {$whereSql}
                GROUP BY s.supplier_id
                ORDER BY s.supplier_name ASC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($items as &$row) {
            $row = $this->normalizeSupplier($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    public function getById($supplierId)
    {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE supplier_id = ? LIMIT 1");
        $stmt->execute([(int)$supplierId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeSupplier($row) : null;
    }

    public function getWithHistory($supplierId, $limit = 10)
    {
        $supplier = $this->getById($supplierId);
        if (!$supplier) {
            return null;
        }

        $limit = max(1, (int)$limit);
        $stmt = $this->db->prepare(
            "SELECT
                po.po_id AS purchase_order_id,
                po.po_number,
                po.created_at AS order_date,
                po.status,
                po.total_amount,
                0 AS item_count
             FROM purchase_orders po
             WHERE po.supplier_id = ?
             ORDER BY po.created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute([(int)$supplierId]);
        $supplier['purchase_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $supplier;
    }

    public function create($data)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO suppliers (
                    supplier_name, contact_person, phone, email, address,
                    payment_terms, lead_time_days, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                trim((string)($data['supplier_name'] ?? '')),
                trim((string)($data['contact_person'] ?? '')),
                trim((string)($data['phone'] ?? '')),
                trim((string)($data['email'] ?? '')),
                trim((string)($data['address'] ?? '')),
                trim((string)($data['payment_terms'] ?? 'Net 30')),
                (int)($data['lead_time_days'] ?? 7),
                (string)($data['status'] ?? 'active'),
            ]);

            return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($supplierId, $data)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE suppliers
                 SET supplier_name = ?, contact_person = ?, email = ?, phone = ?,
                     address = ?, payment_terms = ?, status = ?, lead_time_days = ?
                 WHERE supplier_id = ?"
            );

            $stmt->execute([
                trim((string)($data['supplier_name'] ?? '')),
                trim((string)($data['contact_person'] ?? '')),
                trim((string)($data['email'] ?? '')),
                trim((string)($data['phone'] ?? '')),
                trim((string)($data['address'] ?? '')),
                trim((string)($data['payment_terms'] ?? 'Net 30')),
                (string)($data['status'] ?? 'active'),
                (int)($data['lead_time_days'] ?? 7),
                (int)$supplierId,
            ]);

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function archive($supplierId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE suppliers SET status = 'inactive' WHERE supplier_id = ?");
            $stmt->execute([(int)$supplierId]);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getPerformance($supplierId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(po.po_id) AS total_orders,
                COALESCE(SUM(po.total_amount), 0) AS total_spent,
                AVG(DATEDIFF(COALESCE(po.actual_delivery, po.created_at), po.created_at)) AS avg_delivery_days,
                0 AS avg_quality_rating
             FROM purchase_orders po
             WHERE po.supplier_id = ?"
        );
        $stmt->execute([(int)$supplierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSuppliedProducts($supplierId)
    {
        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_code, p.product_name, p.category_id,
                    p.cost_price AS unit_cost, p.current_stock
             FROM products p
             WHERE p.primary_supplier_id = ? OR p.secondary_supplier_id = ?
             ORDER BY p.product_name ASC"
        );
        $stmt->execute([(int)$supplierId, (int)$supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function search($query)
    {
        $stmt = $this->db->prepare(
            "SELECT supplier_id, supplier_name, email, phone
             FROM suppliers
             WHERE status = 'active' AND supplier_name LIKE ?
             ORDER BY supplier_name ASC
             LIMIT 20"
        );
        $stmt->execute(['%' . $query . '%']);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($items as &$row) {
            $row = $this->normalizeSupplier($row);
        }
        return $items;
    }

    public function getSummary()
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total_suppliers,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_suppliers,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_suppliers,
                SUM(CASE WHEN status = 'blacklisted' THEN 1 ELSE 0 END) AS archived_suppliers
             FROM suppliers"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function normalizeSupplier(array $row): array
    {
        $id = (int)($row['supplier_id'] ?? 0);
        $row['supplier_code'] = $row['supplier_code'] ?? sprintf('SUP-%04d', $id);
        $row['city'] = $row['city'] ?? '';
        $row['state'] = $row['state'] ?? '';
        $row['payment_method'] = $row['payment_method'] ?? 'bank_transfer';
        $row['bank_name'] = $row['bank_name'] ?? '';
        $row['account_number'] = $row['account_number'] ?? '';
        $row['tax_id'] = $row['tax_id'] ?? '';
        return $row;
    }
}
