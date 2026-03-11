<?php
declare(strict_types=1);

class ReportController extends ModuleController
{
    protected string $module = 'reports';
    protected string $title = 'Reports';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $summary = [
            'today_sales' => (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE DATE(created_at)=CURDATE() AND status='completed'")->fetchColumn(),
            'month_sales' => (float)$db->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND status='completed'")->fetchColumn(),
            'low_stock' => (int)$db->query("SELECT COUNT(*) FROM products WHERE current_stock <= reorder_point AND status='active'")->fetchColumn(),
            'pending_ai' => (int)$db->query("SELECT COUNT(*) FROM ai_recommendations WHERE status='pending'")->fetchColumn(),
        ];
        $this->moduleSection('index', ['extra' => ['summary' => $summary]]);
    }

    public function sales(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        
        // Get date range from request
        $startDate = trim((string)$this->get('start_date', date('Y-m-d', strtotime('-30 days'))));
        $endDate = trim((string)$this->get('end_date', date('Y-m-d')));
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }
        
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS report_date, 
                    COUNT(*) AS transactions, 
                    SUM(total_amount) AS gross_sales,
                    SUM(discount_amount) AS discounts, 
                    SUM(vat_amount) AS vat_collected,
                    SUM(total_amount - discount_amount) AS net_sales
             FROM transactions
             WHERE status = 'completed' 
               AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY report_date DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        $records = $stmt->fetchAll();
        
        // Calculate summary totals
        $summary = [
            'total_transactions' => array_sum(array_column($records, 'transactions')),
            'total_gross_sales' => array_sum(array_column($records, 'gross_sales')),
            'total_discounts' => array_sum(array_column($records, 'discounts')),
            'total_vat' => array_sum(array_column($records, 'vat_collected')),
            'total_net_sales' => array_sum(array_column($records, 'net_sales')),
        ];
        
        $this->moduleSection('sales', [
            'records' => $records,
            'extra' => [
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => $summary,
            ]
        ]);
    }

    public function customerTransactions(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();

        $customerId = (int)$this->get('customer_id', 0);
        $startDate = trim((string)$this->get('start_date', date('Y-m-d', strtotime('-30 days'))));
        $endDate = trim((string)$this->get('end_date', date('Y-m-d')));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }

        $where = [
            "t.status = 'completed'",
            'DATE(t.created_at) BETWEEN ? AND ?',
        ];
        $params = [$startDate, $endDate];

        if ($customerId > 0) {
            $where[] = 't.customer_id = ?';
            $params[] = $customerId;
        }

        $sql =
            "SELECT t.transaction_id, t.transaction_number, t.or_number, t.created_at,
                    COALESCE(c.full_name, 'Walk-in Customer') AS customer_name,
                    COALESCE(c.phone, '-') AS customer_phone,
                    t.customer_type, t.payment_method, t.total_amount
             FROM transactions t
             LEFT JOIN customers c ON c.customer_id = t.customer_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY t.created_at DESC
             LIMIT 300";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $customerStmt = $db->query(
            "SELECT customer_id, full_name, phone
             FROM customers
             WHERE status = 'active'
             ORDER BY full_name ASC
             LIMIT 300"
        );

        $summary = [
            'transactions' => count($records),
            'total_sales' => (float)array_sum(array_map(static fn(array $row): float => (float)$row['total_amount'], $records)),
        ];

        $this->moduleSection('customer_transactions', ['extra' => [
            'records' => $records,
            'customers' => $customerStmt->fetchAll(),
            'filters' => [
                'customer_id' => $customerId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => $summary,
        ]]);
    }

    public function profit(): void
    {
        $this->requireAuth(['owner', 'manager', 'bookkeeper']);
        $db = Database::getInstance();
        
        // Get date range from request
        $startDate = trim((string)$this->get('start_date', date('Y-m-d', strtotime('-30 days'))));
        $endDate = trim((string)$this->get('end_date', date('Y-m-d')));
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }
        
        $stmt = $db->prepare(
            "SELECT DATE(t.created_at) AS report_date,
                    COUNT(DISTINCT t.transaction_id) AS transactions,
                    SUM(ti.subtotal) AS revenue,
                    SUM(ti.cost_price * ti.quantity) AS cogs,
                    SUM(ti.subtotal - (ti.cost_price * ti.quantity)) AS gross_profit,
                    ROUND((SUM(ti.subtotal - (ti.cost_price * ti.quantity)) / NULLIF(SUM(ti.subtotal), 0)) * 100, 2) AS margin_percent
             FROM transaction_items ti
             JOIN transactions t ON t.transaction_id = ti.transaction_id
             WHERE t.status = 'completed' 
               AND DATE(t.created_at) BETWEEN ? AND ?
             GROUP BY DATE(t.created_at)
             ORDER BY report_date DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        $records = $stmt->fetchAll();
        
        // Calculate summary totals
        $totalRevenue = array_sum(array_column($records, 'revenue'));
        $totalCogs = array_sum(array_column($records, 'cogs'));
        $totalGrossProfit = array_sum(array_column($records, 'gross_profit'));
        
        $summary = [
            'total_transactions' => array_sum(array_column($records, 'transactions')),
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_gross_profit' => $totalGrossProfit,
            'avg_margin_percent' => $totalRevenue > 0 ? round(($totalGrossProfit / $totalRevenue) * 100, 2) : 0,
        ];
        
        $this->moduleSection('profit', [
            'records' => $records,
            'extra' => [
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => $summary,
            ]
        ]);
    }

    public function inventory(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT product_code, product_name, current_stock, reorder_point, minimum_stock,
                    (current_stock * cost_price) AS stock_value
             FROM products
             WHERE status='active'
             ORDER BY current_stock ASC"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'inventory']);
    }

    public function shrinkage(): void
    {
        $this->requireAuth(['owner', 'manager', 'bookkeeper']);
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT sa.adjustment_id, p.product_name, sa.adjustment_type, sa.quantity, sa.reason, sa.requested_at
             FROM stock_adjustments sa
             JOIN products p ON p.product_id = sa.product_id
             WHERE sa.status='approved' AND sa.quantity < 0
             ORDER BY sa.requested_at DESC"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'shrinkage']);
    }

    public function cashier(): void
    {
        $this->requireAuth(['owner', 'manager', 'security', 'bookkeeper']);
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT u.full_name AS cashier_name,
                    COUNT(t.transaction_id) AS total_txn,
                    COALESCE(SUM(t.total_amount),0) AS total_sales,
                    COALESCE(AVG(t.total_amount),0) AS avg_basket
             FROM users u
             LEFT JOIN transactions t ON t.cashier_id = u.user_id
                 AND t.status='completed'
                 AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             WHERE u.role_id = (SELECT role_id FROM roles WHERE role_slug='cashier' LIMIT 1)
             GROUP BY u.user_id, u.full_name
             ORDER BY total_sales DESC"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'cashier']);
    }

    public function supplier(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT s.supplier_name,
                    COUNT(po.po_id) AS total_pos,
                    COALESCE(SUM(po.total_amount),0) AS po_value,
                    SUM(CASE WHEN po.status='fully_received' THEN 1 ELSE 0 END) AS fully_received
             FROM suppliers s
             LEFT JOIN purchase_orders po ON po.supplier_id = s.supplier_id
             GROUP BY s.supplier_id, s.supplier_name
             ORDER BY po_value DESC"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'supplier']);
    }

    public function aiAccuracy(): void
    {
        $this->requireAuth(['owner', 'manager', 'bookkeeper']);
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT rec_type,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) AS accepted,
                    SUM(CASE WHEN status='dismissed' THEN 1 ELSE 0 END) AS dismissed,
                    ROUND(AVG(confidence_score),2) AS avg_confidence
             FROM ai_recommendations
             GROUP BY rec_type
             ORDER BY total DESC"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'ai-accuracy']);
    }

    public function custom(): void
    {
        $this->requireAuth();
        $this->moduleSection('custom', ['extra' => [
            'message' => 'Custom report builder is available in this section.'
        ]]);
    }

    /**
     * Export sales report to CSV
     */
    public function exportSalesCsv(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS report_date, COUNT(*) AS transactions, 
                    SUM(total_amount) AS gross_sales,
                    SUM(discount_amount) AS discounts, SUM(vat_amount) AS vat_collected,
                    SUM(total_amount - discount_amount - vat_amount) AS net_sales
             FROM transactions
             WHERE status = 'completed' 
               AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY report_date DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = 'sales_report_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Date', 'Transactions', 'Gross Sales', 'Discounts', 'VAT Collected', 'Net Sales']);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['report_date'],
                $row['transactions'],
                number_format((float)$row['gross_sales'], 2, '.', ''),
                number_format((float)$row['discounts'], 2, '.', ''),
                number_format((float)$row['vat_collected'], 2, '.', ''),
                number_format((float)$row['net_sales'], 2, '.', ''),
            ]);
        }
        
        fclose($output);
        
        $logger = new Logger();
        $logger->log('reports', 'export_sales_csv', null, null, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], 'Sales report exported to CSV.');
        
        exit;
    }

    /**
     * Export profit report to CSV
     */
    public function exportProfitCsv(): void
    {
        $this->requireAuth(['owner', 'manager', 'bookkeeper']);
        $db = Database::getInstance();
        
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        $stmt = $db->prepare(
            "SELECT DATE(t.created_at) AS report_date,
                    SUM(ti.subtotal) AS revenue,
                    SUM(ti.cost_price * ti.quantity) AS cogs,
                    SUM(ti.subtotal - (ti.cost_price * ti.quantity)) AS gross_profit,
                    ROUND((SUM(ti.subtotal - (ti.cost_price * ti.quantity)) / SUM(ti.subtotal)) * 100, 2) AS margin_pct
             FROM transaction_items ti
             JOIN transactions t ON t.transaction_id = ti.transaction_id
             WHERE t.status = 'completed' 
               AND DATE(t.created_at) BETWEEN ? AND ?
             GROUP BY DATE(t.created_at)
             ORDER BY report_date DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = 'profit_report_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Date', 'Revenue', 'COGS', 'Gross Profit', 'Margin %']);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['report_date'],
                number_format((float)$row['revenue'], 2, '.', ''),
                number_format((float)$row['cogs'], 2, '.', ''),
                number_format((float)$row['gross_profit'], 2, '.', ''),
                number_format((float)$row['margin_pct'], 2, '.', ''),
            ]);
        }
        
        fclose($output);
        
        $logger = new Logger();
        $logger->log('reports', 'export_profit_csv', null, null, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], 'Profit report exported to CSV.');
        
        exit;
    }

    /**
     * Export inventory report to CSV
     */
    public function exportInventoryCsv(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "SELECT product_code, product_name, current_stock, reorder_point, minimum_stock,
                    cost_price, selling_price,
                    (current_stock * cost_price) AS stock_value
             FROM products
             WHERE status='active'
             ORDER BY product_name"
        );
        $data = $stmt->fetchAll();
        
        $filename = 'inventory_report_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Product Code', 'Product Name', 'Current Stock', 'Reorder Point', 
                          'Minimum Stock', 'Cost Price', 'Selling Price', 'Stock Value']);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['product_code'],
                $row['product_name'],
                $row['current_stock'],
                $row['reorder_point'],
                $row['minimum_stock'],
                number_format((float)$row['cost_price'], 2, '.', ''),
                number_format((float)$row['selling_price'], 2, '.', ''),
                number_format((float)$row['stock_value'], 2, '.', ''),
            ]);
        }
        
        fclose($output);
        
        $logger = new Logger();
        $logger->log('reports', 'export_inventory_csv', null, null, [], 'Inventory report exported to CSV.');
        
        exit;
    }

    /**
     * Export report to Excel format (simple HTML table that Excel can open)
     */
    public function exportSalesExcel(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS report_date, COUNT(*) AS transactions, 
                    SUM(total_amount) AS gross_sales,
                    SUM(discount_amount) AS discounts, SUM(vat_amount) AS vat_collected,
                    SUM(total_amount - discount_amount - vat_amount) AS net_sales
             FROM transactions
             WHERE status = 'completed' 
               AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY report_date DESC"
        );
        $stmt->execute([$startDate, $endDate]);
        $data = $stmt->fetchAll();
        
        $filename = 'sales_report_' . date('Ymd_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo '<?xml version="1.0"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Worksheet ss:Name="Sales Report">';
        echo '<Table>';
        
        // Header row
        echo '<Row>';
        echo '<Cell><Data ss:Type="String">Date</Data></Cell>';
        echo '<Cell><Data ss:Type="String">Transactions</Data></Cell>';
        echo '<Cell><Data ss:Type="String">Gross Sales</Data></Cell>';
        echo '<Cell><Data ss:Type="String">Discounts</Data></Cell>';
        echo '<Cell><Data ss:Type="String">VAT Collected</Data></Cell>';
        echo '<Cell><Data ss:Type="String">Net Sales</Data></Cell>';
        echo '</Row>';
        
        // Data rows
        foreach ($data as $row) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['report_date']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $row['transactions'] . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . number_format((float)$row['gross_sales'], 2, '.', '') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . number_format((float)$row['discounts'], 2, '.', '') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . number_format((float)$row['vat_collected'], 2, '.', '') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . number_format((float)$row['net_sales'], 2, '.', '') . '</Data></Cell>';
            echo '</Row>';
        }
        
        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';
        
        $logger = new Logger();
        $logger->log('reports', 'export_sales_excel', null, null, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], 'Sales report exported to Excel.');
        
        exit;
    }
}
