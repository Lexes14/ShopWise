<?php
/**
 * ShopWise AI - AI Recommendation Engine
 * 
 * Generates intelligent recommendations based on real-time business actions
 * Core Systems:
 * 1. Stock Replenishment Analysis
 * 2. Pricing Optimization
 * 3. Dead Stock Detection
 * 4. Supplier Performance Monitoring
 * 5. Product Association Analysis
 */

declare(strict_types=1);

class AIModel extends Model
{
    protected string $table = 'ai_recommendations';
    protected string $primaryKey = 'rec_id';
    
    /**
     * Analyze action and generate recommendations if needed
     * Called after key business actions
     */
    public function analyzeAction(string $module, string $action, ?int $recordId = null, mixed $data = null): void
    {
        try {
            // Route to appropriate analysis based on module/action
            match($module) {
                'pos' => $this->analyzeSale($action, $recordId, $data),
                'purchase_orders' => $this->analyzePurchaseOrder($action, $recordId, $data),
                'inventory' => $this->analyzeInventoryAction($action, $recordId, $data),
                'products' => $this->analyzeProductAction($action, $recordId, $data),
                'promotions' => $this->analyzePromotion($action, $recordId, $data),
                default => null
            };
        } catch (\Throwable $e) {
            // Silent fail - don't break business operations
            error_log("AI Analysis Failed: {$module}.{$action} - " . $e->getMessage());
        }
    }
    
    /**
     * Core System 1: Analyze Sales Transaction
     */
    private function analyzeSale(string $action, ?int $transactionId, mixed $data): void
    {
        if ($action !== 'checkout' || !$transactionId) {
            return;
        }
        
        // Get transaction items
        $items = $this->db->query(
            "SELECT ti.product_id, ti.quantity, p.current_stock, p.reorder_point,
                    p.product_name, p.avg_cost_price, p.selling_price
             FROM transaction_items ti
             JOIN products p ON p.product_id = ti.product_id
             WHERE ti.transaction_id = {$transactionId}"
        )->fetchAll();
        
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $currentStock = (int)$item['current_stock'];
            $reorderPoint = (int)$item['reorder_point'];
            
            // Check if stock is low after this sale
            if ($currentStock <= $reorderPoint) {
                $this->generateRestockRecommendation($productId, $currentStock, $item);
            }
            
            // Check profit margin
            $margin = $this->calculateMargin((float)$item['avg_cost_price'], (float)$item['selling_price']);
            if ($margin < 25) {
                $this->generatePricingRecommendation($productId, $margin, $item);
            }
        }
        
        // Analyze product associations (items bought together)
        $this->analyzeProductAssociations($transactionId);
    }
    
    /**
     * Core System 2: Analyze Purchase Order Actions
     */
    private function analyzePurchaseOrder(string $action, ?int $poId, mixed $data): void
    {
        if (!$poId) {
            return;
        }
        
        // When PO is received, check if delivery was on time
        if ($action === 'mark_received') {
            $po = $this->db->query(
                "SELECT po.*, s.supplier_name, s.lead_time_days,
                        DATEDIFF(po.actual_delivery, po.expected_delivery) as delay_days
                 FROM purchase_orders po
                 JOIN suppliers s ON s.supplier_id = po.supplier_id
                 WHERE po.po_id = {$poId}"
            )->fetch();
            
            if ($po) {
                $delayDays = (int)($po['delay_days'] ?? 0);
                if ($delayDays > 3) {
                    $this->generateSupplierPerformanceRecommendation($po);
                }
            }
        }
        
        // When PO is approved, check for bulk ordering opportunities
        if ($action === 'approve') {
            $this->checkBulkOrderingOpportunities($poId);
        }
    }
    
    /**
     * Core System 3: Analyze Inventory Actions
     */
    private function analyzeInventoryAction(string $action, ?int $recordId, mixed $data): void
    {
        // When stocktake is completed, identify dead stock
        if ($action === 'finalize_stocktake') {
            $this->identifyDeadStock();
        }
        
        // When adjustment is made, check why stock is being adjusted frequently
        if ($action === 'create_adjustment' && $recordId) {
            $this->checkFrequentAdjustments($recordId);
        }
    }
    
    /**
     * Core System 4: Analyze Product Actions
     */
    private function analyzeProductAction(string $action, ?int $productId, mixed $data): void
    {
        if (!$productId) {
            return;
        }
        
        // When price is changed, could analyze impact (future enhancement)
        // For now, skip - pricing recommendations come from sales analysis
    }
    
    /**
     * Core System 5: Analyze Promotion Performance
     */
    private function analyzePromotion(string $action, ?int $promoId, mixed $data): void
    {
        if ($action === 'end' && $promoId) {
            $this->evaluatePromotionEffectiveness($promoId);
        }
    }
    
    // ===================================================================
    // RECOMMENDATION GENERATORS
    // ===================================================================
    
    /**
     * Generate restock recommendation
     */
    private function generateRestockRecommendation(int $productId, int $currentStock, array $productData): void
    {
        // Skip if recent recommendation exists
        if ($this->hasRecentRecommendation($productId, 'restock', 24)) {
            return;
        }
        
        // Calculate velocity
        $velocity = $this->calculateSalesVelocity($productId, 30);
        if ($velocity < 0.1) {
            $velocity = 0.5; // Minimum assumption
        }
        
        $daysRemaining = $currentStock > 0 ? $currentStock / $velocity : 0;
        
        // Determine urgency
        $urgency = match(true) {
            $daysRemaining <= 3 => 'critical',
            $daysRemaining <= 7 => 'urgent',
            $daysRemaining <= 14 => 'normal',
            default => 'monitor'
        };
        
        // Calculate suggested order quantity
        $leadTime = 7; // Default
        $supplierLeadTime = $this->db->query(
            "SELECT lead_time_days FROM suppliers s
             JOIN products p ON p.primary_supplier_id = s.supplier_id
             WHERE p.product_id = {$productId}"
        )->fetchColumn();
        if ($supplierLeadTime) {
            $leadTime = (int)$supplierLeadTime;
        }
        
        $suggestedQty = (int)ceil($velocity * ($leadTime + 14) * 1.2 - $currentStock);
        
        // Confidence based on data quality
        $confidence = min(90.0, 50.0 + ($velocity > 1 ? 20.0 : 0) + (strlen((string)$supplierLeadTime) > 0 ? 20.0 : 0));
        
        $recommendation = sprintf(
            "Restock '%s' urgently. Current: %d units, Daily sales: %.1f, Days remaining: %.0f. Order %d units.",
            $productData['product_name'],
            $currentStock,
            $velocity,
            $daysRemaining,
            $suggestedQty
        );
        
        $reason = sprintf(
            "Stock level (%d units) is at or below reorder point. Sales velocity is %.2f units/day. At current rate, stock will run out in %.1f days.",
            $currentStock,
            $velocity,
            $daysRemaining
        );
        
        $this->insertRecommendation(
            'restock',
            $productId,
            $recommendation,
            $reason,
            $confidence,
            $urgency,
            $suggestedQty
        );
    }
    
    /**
     * Generate pricing optimization recommendation
     */
    private function generatePricingRecommendation(int $productId, float $currentMargin, array $productData): void
    {
        if ($this->hasRecentRecommendation($productId, 'pricing', 72)) {
            return;
        }
        
        $targetMargin = 25.0;
        $costPrice = (float)$productData['avg_cost_price'];
        $currentPrice = (float)$productData['selling_price'];
        
        // Calculate suggested price for target margin
        $suggestedPrice = $costPrice / (1 - ($targetMargin / 100));
        $priceIncrease = $suggestedPrice - $currentPrice;
        
        if ($priceIncrease < 0.50) {
            return; // Not worth the small increase
        }
        
        $recommendation = sprintf(
            "Consider increasing price of '%s' from ₱%.2f to ₱%.2f to achieve %d%% margin (currently %.1f%%).",
            $productData['product_name'],
            $currentPrice,
            $suggestedPrice,
            (int)$targetMargin,
            $currentMargin
        );
        
        $reason = sprintf(
            "Current margin is %.1f%%, below the target %d%%. Price adjustment would improve profitability by ₱%.2f per unit.",
            $currentMargin,
            (int)$targetMargin,
            $priceIncrease
        );
        
        $this->insertRecommendation(
            'pricing',
            $productId,
            $recommendation,
            $reason,
            75.0,
            'normal',
            $suggestedPrice
        );
    }
    
    /**
     * Generate supplier performance warning
     */
    private function generateSupplierPerformanceRecommendation(array $poData): void
    {
        $supplierId = (int)$poData['supplier_id'];
        $delayDays = (int)$poData['delay_days'];
        
        if ($this->hasRecentRecommendation($supplierId, 'supplier', 168, 'supplier_id')) {
            return;
        }
        
        // Check historical performance
        $avgDelay = $this->db->query(
            "SELECT AVG(DATEDIFF(actual_delivery, expected_delivery)) as avg_delay
             FROM purchase_orders
             WHERE supplier_id = {$supplierId}
               AND status = 'fully_received'
               AND actual_delivery IS NOT NULL
               AND expected_delivery IS NOT NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)"
        )->fetchColumn();
        
        $avgDelay = (float)($avgDelay ?? 0);
        
        $urgency = $delayDays > 7 || $avgDelay > 5 ? 'urgent' : 'normal';
        
        $recommendation = sprintf(
            "Supplier '%s' delivered PO #%s %d days late. Consider discussing delivery schedules or finding alternative suppliers.",
            $poData['supplier_name'],
            $poData['po_number'],
            $delayDays
        );
        
        $reason = sprintf(
            "Delivery was %d days late. Historical average delay: %.1f days over past 6 months. Consistent delays impact stock availability.",
            $delayDays,
            $avgDelay
        );
        
        $stmt = $this->db->prepare(
            "INSERT INTO ai_recommendations (
                rec_type, supplier_id, recommendation, reason,
                confidence_score, urgency, status, generated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        $stmt->execute([
            'supplier',
            $supplierId,
            $recommendation,
            $reason,
            80.0,
            $urgency
        ]);
    }
    
    /**
     * Identify dead stock (slow-moving items)
     */
    private function identifyDeadStock(): void
    {
        $deadStockItems = $this->db->query(
            "SELECT p.product_id, p.product_name, p.current_stock, 
                    p.cost_price, p.current_stock * p.cost_price as tied_capital,
                    COALESCE(SUM(ti.quantity), 0) as units_sold_90d
             FROM products p
             LEFT JOIN transaction_items ti ON ti.product_id = p.product_id
             LEFT JOIN transactions t ON t.transaction_id = ti.transaction_id
                AND t.status = 'completed'
                AND t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
             WHERE p.status = 'active'
               AND p.current_stock > 10
             GROUP BY p.product_id
             HAVING units_sold_90d = 0 OR units_sold_90d < 3
             ORDER BY tied_capital DESC
             LIMIT 20"
        )->fetchAll();
        
        foreach ($deadStockItems as $item) {
            $productId = (int)$item['product_id'];
            
            if ($this->hasRecentRecommendation($productId, 'dead_stock', 168)) {
                continue;
            }
            
            $tiedCapital = (float)$item['tied_capital'];
            $unitsSold = (int)$item['units_sold_90d'];
            $currentStock = (int)$item['current_stock'];
            
            $recommendation = sprintf(
                "Dead stock alert: '%s' has %d units (₱%.2f tied up) with only %d sales in 90 days. Consider promotion or clearance.",
                $item['product_name'],
                $currentStock,
                $tiedCapital,
                $unitsSold
            );
            
            $reason = sprintf(
                "Product has moved only %d units in 90 days with %d units in stock. Capital tied up: ₱%.2f. Implement clearance strategy to free up capital and shelf space.",
                $unitsSold,
                $currentStock,
                $tiedCapital
            );
            
            $this->insertRecommendation(
                'dead_stock',
                $productId,
                $recommendation,
                $reason,
                85.0,
                $tiedCapital > 5000 ? 'urgent' : 'normal',
                $tiedCapital
            );
        }
    }
    
    /**
     * Analyze products frequently bought together
     */
    private function analyzeProductAssociations(int $transactionId): void
    {
        // Get products in this transaction
        $productIds = $this->db->query(
            "SELECT product_id FROM transaction_items WHERE transaction_id = {$transactionId}"
        )->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($productIds) < 2) {
            return; // Need at least 2 products for association
        }
        
        // For each pair, check if they're frequently bought together
        foreach ($productIds as $idx => $productId) {
            foreach (array_slice($productIds, $idx + 1) as $relatedId) {
                $this->checkProductPairAssociation((int)$productId, (int)$relatedId);
            }
        }
    }
    
    /**
     * Check if two products are frequently bought together
     */
    private function checkProductPairAssociation(int $productId1, int $productId2): void
    {
        // Check if recommendation already exists
        $existing = $this->db->query(
            "SELECT rec_id FROM ai_recommendations
             WHERE rec_type = 'substitution'
               AND ((product_id = {$productId1} AND related_product_id = {$productId2})
                 OR (product_id = {$productId2} AND related_product_id = {$productId1}))
               AND status = 'pending'
               AND generated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetch();
        
        if ($existing) {
            return;
        }
        
        // Count how many times bought together in last 90 days
        $count = $this->db->query(
            "SELECT COUNT(DISTINCT t.transaction_id) as pair_count
             FROM transactions t
             JOIN transaction_items ti1 ON ti1.transaction_id = t.transaction_id
             JOIN transaction_items ti2 ON ti2.transaction_id = t.transaction_id
             WHERE t.status = 'completed'
               AND t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
               AND ti1.product_id = {$productId1}
               AND ti2.product_id = {$productId2}"
        )->fetchColumn();
        
        if ($count >= 5) { // At least 5 times in 90 days
            $products = $this->db->query(
                "SELECT product_id, product_name FROM products
                 WHERE product_id IN ({$productId1}, {$productId2})"
            )->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $recommendation = sprintf(
                "Product bundling opportunity: '%s' and '%s' are frequently bought together (%d times in 90 days). Consider creating a bundle promotion.",
                $products[$productId1],
                $products[$productId2],
                $count
            );
            
            $reason = sprintf(
                "These products appear together in %d transactions over 90 days, indicating strong purchase correlation. Bundle promotions can increase average transaction value.",
                $count
            );
            
            $stmt = $this->db->prepare(
                "INSERT INTO ai_recommendations (
                    rec_type, product_id, related_product_id, recommendation, reason,
                    confidence_score, urgency, status, generated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
            );
            
            $stmt->execute([
                'substitution',
                $productId1,
                $productId2,
                $recommendation,
                $reason,
                70.0 + min(20.0, $count * 2), // Higher count = higher confidence
                'normal'
            ]);
        }
    }
    
    /**
     * Check for frequent adjustments (possible theft/shrinkage)
     */
    private function checkFrequentAdjustments(int $adjustmentId): void
    {
        // Get adjustment details
        $adjustment = $this->db->query(
            "SELECT sa.*, p.product_name, p.product_id
             FROM stock_adjustments sa
             JOIN products p ON p.product_id = sa.product_id
             WHERE sa.adjustment_id = {$adjustmentId}"
        )->fetch();
        
        if (!$adjustment) {
            return;
        }
        
        $productId = (int)$adjustment['product_id'];
        
        // Count adjustments for this product in last 30 days
        $adjustmentCount = $this->db->query(
            "SELECT COUNT(*) FROM stock_adjustments
             WHERE product_id = {$productId}
               AND adjustment_type = 'decrease'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();
        
        if ($adjustmentCount >= 3) { // 3+ negative adjustments in 30 days
            if ($this->hasRecentRecommendation($productId, 'anomaly', 168)) {
                return;
            }
            
            $recommendation = sprintf(
                "Anomaly detected: '%s' has had %d negative stock adjustments in 30 days. Investigate for possible shrinkage, theft, or system errors.",
                $adjustment['product_name'],
                $adjustmentCount
            );
            
            $reason = sprintf(
                "Frequent negative adjustments (%d in 30 days) may indicate inventory control issues, theft, or data entry errors. Review security and processes.",
                $adjustmentCount
            );
            
            $this->insertRecommendation(
                'anomaly',
                $productId,
                $recommendation,
                $reason,
                80.0,
                'urgent',
                (float)$adjustmentCount
            );
        }
    }
    
    /**
     * Evaluate promotion effectiveness after it ends
     */
    private function evaluatePromotionEffectiveness(int $promoId): void
    {
        // Get promotion details and sales during promo period
        $promo = $this->db->query(
            "SELECT p.*, 
                    COALESCE(SUM(ti.quantity), 0) as units_sold,
                    COALESCE(SUM(ti.quantity * ti.unit_price), 0) as revenue,
                    COUNT(DISTINCT t.transaction_id) as transaction_count
             FROM promotions p
             LEFT JOIN transaction_items ti ON ti.promotion_id = p.promotion_id
             LEFT JOIN transactions t ON t.transaction_id = ti.transaction_id
                AND t.status = 'completed'
             WHERE p.promotion_id = {$promoId}
             GROUP BY p.promotion_id"
        )->fetch();
        
        if (!$promo) {
            return;
        }
        
        $unitsSold = (int)$promo['units_sold'];
        $revenue = (float)$promo['revenue'];
        $transactionCount = (int)$promo['transaction_count'];
        
        if ($unitsSold === 0) {
            // Promotion had no impact
            $recommendation = sprintf(
                "Promotion '%s' ended with no sales. Consider different products, better visibility, or more attractive discounts for future promotions.",
                $promo['promotion_name']
            );
            
            $reason = "Zero sales during promotion period suggests poor product selection, insufficient marketing, or uncompetitive pricing.";
            
            $this->insertRecommendation(
                'promotion',
                null,
                $recommendation,
                $reason,
                90.0,
                'normal',
                0
            );
        } elseif ($unitsSold >= 50 && $revenue >= 10000) {
            // Successful promotion - suggest repeat
            $recommendation = sprintf(
                "Promotion '%s' was highly successful (%d units sold, ₱%.2f revenue). Consider running similar promotions or making this permanent for high-velocity products.",
                $promo['promotion_name'],
                $unitsSold,
                $revenue
            );
            
            $reason = sprintf(
                "High performance metrics: %d units sold across %d transactions generating ₱%.2f in revenue. Repeat successful strategies.",
                $unitsSold,
                $transactionCount,
                $revenue
            );
            
            $this->insertRecommendation(
                'promotion',
                null,
                $recommendation,
                $reason,
                85.0,
                'normal',
                $revenue
            );
        }
    }
    
    /**
     * Check bulk ordering opportunities
     */
    private function checkBulkOrderingOpportunities(int $poId): void
    {
        $items = $this->db->query(
            "SELECT poi.*, p.product_name, p.product_id,
                    poi.qty_ordered * poi.unit_cost as line_total
             FROM po_items poi
             JOIN products p ON p.product_id = poi.product_id
             WHERE poi.po_id = {$poId}
             ORDER BY line_total DESC
             LIMIT 3"
        )->fetchAll();
        
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $qtyOrdered = (int)$item['qty_ordered'];
            $lineTotal = (float)$item['line_total'];
            
            // Check if there's potential for bulk discount
            if ($lineTotal >= 5000 && !$this->hasRecentRecommendation($productId, 'bulk_order', 168)) {
                $recommendation = sprintf(
                    "Bulk ordering opportunity: '%s' order value is ₱%.2f. Contact supplier about volume discounts for orders of %d+ units.",
                    $item['product_name'],
                    $lineTotal,
                    $qtyOrdered
                );
                
                $reason = sprintf(
                    "High-value order (₱%.2f) presents negotiating opportunity. Even 5%% discount would save ₱%.2f.",
                    $lineTotal,
                    $lineTotal * 0.05
                );
                
                $this->insertRecommendation(
                    'supplier',
                    $productId,
                    $recommendation,
                    $reason,
                    70.0,
                    'normal',
                    $lineTotal
                );
            }
        }
    }
    
    // ===================================================================
    // HELPER FUNCTIONS
    // ===================================================================
    
    /**
     * Calculate sales velocity (avg daily sales)
     */
    private function calculateSalesVelocity(int $productId, int $days): float
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(ti.quantity), 0) as total_sold,
                    DATEDIFF(CURDATE(), MIN(DATE(t.created_at))) + 1 as days_span
             FROM transaction_items ti
             JOIN transactions t ON t.transaction_id = ti.transaction_id
             WHERE ti.product_id = {$productId}
               AND t.status = 'completed'
               AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)"
        )->fetch();
        
        $totalSold = (float)$result['total_sold'];
        $daysSpan = max(1, (int)$result['days_span']);
        
        return $totalSold / $daysSpan;
    }
    
    /**
     * Calculate profit margin percentage
     */
    private function calculateMargin(float $cost, float $price): float
    {
        if ($price <= 0) {
            return 0;
        }
        
        return (($price - $cost) / $price) * 100;
    }
    
    /**
     * Check if recent recommendation exists
     */
    private function hasRecentRecommendation(int $id, string $type, int $hours, string $idField = 'product_id'): bool
    {
        $stmt = $this->db->query(
            "SELECT rec_id FROM ai_recommendations
             WHERE {$idField} = {$id}
               AND rec_type = '{$type}'
               AND status = 'pending'
               AND generated_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
             LIMIT 1"
        );
        
        return (bool)$stmt->fetch();
    }
    
    /**
     * Insert recommendation
     */
    private function insertRecommendation(
        string $type,
        ?int $productId,
        string $recommendation,
        string $reason,
        float $confidence,
        string $urgency,
        ?float $suggestedValue = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO ai_recommendations (
                rec_type, product_id, recommendation, reason,
                confidence_score, urgency, suggested_value, status, generated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        $stmt->execute([
            $type,
            $productId,
            $recommendation,
            $reason,
            $confidence,
            $urgency,
            $suggestedValue
        ]);
    }

    private function countPendingByType(string $type): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ai_recommendations WHERE rec_type = ? AND status = 'pending'"
        );
        $stmt->execute([$type]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Batch-generate pricing recommendations from low-margin products.
     */
    public function generatePricingRecommendations(int $limit = 30): int
    {
        $limit = max(1, min(200, $limit));

        $stmt = $this->db->prepare(
            "SELECT p.product_id, p.product_name,
                    COALESCE(NULLIF(p.avg_cost_price, 0), p.cost_price, 0) AS avg_cost_price,
                    p.selling_price,
                    COALESCE(SUM(CASE WHEN t.status = 'completed' THEN ti.quantity ELSE 0 END), 0) AS qty_sold_30d
             FROM products p
             LEFT JOIN transaction_items ti ON ti.product_id = p.product_id
             LEFT JOIN transactions t ON t.transaction_id = ti.transaction_id
                AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             WHERE p.status = 'active'
               AND p.selling_price > 0
             GROUP BY p.product_id, p.product_name, p.avg_cost_price, p.cost_price, p.selling_price
                 HAVING avg_cost_price > 0
                     AND ((p.selling_price - avg_cost_price) / p.selling_price) * 100 < 25
             ORDER BY qty_sold_30d DESC, p.product_name ASC
             LIMIT {$limit}"
        );
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $created = 0;
        foreach ($products as $product) {
            $price = (float)($product['selling_price'] ?? 0);
            $cost = (float)($product['avg_cost_price'] ?? 0);
            if ($price <= 0 || $cost <= 0) {
                continue;
            }

            $margin = $this->calculateMargin($cost, $price);
            $beforeCount = $this->countPendingByType('pricing');
            $this->generatePricingRecommendation((int)$product['product_id'], $margin, $product);
            $afterCount = $this->countPendingByType('pricing');

            if ($afterCount > $beforeCount) {
                $created += ($afterCount - $beforeCount);
            }
        }

        return $created;
    }
    
    /**
     * Get recommendations summary
     */
    public function getSummary(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                SUM(CASE WHEN urgency = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN urgency = 'urgent' THEN 1 ELSE 0 END) as urgent,
                AVG(confidence_score) as avg_confidence
             FROM ai_recommendations
             WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'active' => (int)($summary['pending'] ?? 0),
            'pending' => (int)($summary['pending'] ?? 0),
            'implemented' => (int)($summary['accepted'] ?? 0),
            'dismissed' => (int)($summary['dismissed'] ?? 0),
            'critical' => (int)($summary['critical'] ?? 0),
            'urgent' => (int)($summary['urgent'] ?? 0),
            'accuracy' => round((float)($summary['avg_confidence'] ?? 0), 1)
        ];
    }
    
    /**
     * Get recommendations by type
     */
    public function getByType(string $type, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, p.product_name, p.product_code,
                    s.supplier_name
             FROM ai_recommendations r
             LEFT JOIN products p ON p.product_id = r.product_id
             LEFT JOIN suppliers s ON s.supplier_id = r.supplier_id
             WHERE r.rec_type = ?
               AND r.status = 'pending'
             ORDER BY 
                CASE r.urgency
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                r.generated_at DESC
             LIMIT {$limit}"
        );
        
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
