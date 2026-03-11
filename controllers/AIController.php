<?php
declare(strict_types=1);

class AIController extends ModuleController
{
    protected string $module = 'ai_insights';
    protected string $title = 'AI Insights';
    private AIModel $aiModel;

    public function __construct()
    {
        $this->aiModel = new AIModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        
        // Get summary statistics
        $summary = $this->aiModel->getSummary();
        
        // Get recent recommendations
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT r.rec_id, r.rec_type, r.recommendation, r.confidence_score, 
                    r.urgency, r.status, r.generated_at, p.product_name
             FROM ai_recommendations r
             LEFT JOIN products p ON p.product_id = r.product_id
             WHERE r.status = 'pending'
             ORDER BY 
                CASE r.urgency
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                r.generated_at DESC
             LIMIT 50"
        );
        
        $this->moduleIndex($stmt->fetchAll(), [
            'extra' => [
                'summary' => $summary
            ]
        ]);
    }

    public function generate(): void
    {
        $this->requireAuth(['owner', 'manager', 'purchasing_officer']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        
        // Get AI settings
        $safetyMultiplier = 1.5; // Default
        $settingStmt = $db->query(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'ai_safety_multiplier'"
        );
        $setting = $settingStmt->fetch();
        if ($setting) {
            $safetyMultiplier = (float)$setting['setting_value'];
        }

        // Find products that need restocking
        $productsStmt = $db->query(
            "SELECT p.product_id, p.product_name, p.current_stock, p.reorder_point, 
                    p.reorder_qty, p.minimum_stock, p.maximum_stock,
                    s.lead_time_days
             FROM products p
             LEFT JOIN suppliers s ON s.supplier_id = p.primary_supplier_id
             WHERE p.status='active' AND p.current_stock <= p.reorder_point
             ORDER BY p.current_stock ASC
             LIMIT 20"
        );
        $products = $productsStmt->fetchAll();

        if (empty($products)) {
            $this->done('No low-stock products found for AI generation.', '/ai-insights');
        }

        $recommendationsCreated = 0;

        foreach ($products as $product) {
            $productId = (int)$product['product_id'];
            
            // Calculate sales velocity (avg daily sales over last 30 days)
            $salesStmt = $db->prepare(
                "SELECT 
                    COALESCE(SUM(ti.quantity), 0) AS total_sold,
                    COUNT(DISTINCT DATE(t.created_at)) AS days_with_sales,
                    DATEDIFF(CURDATE(), MIN(DATE(t.created_at))) + 1 AS days_span
                 FROM transaction_items ti
                 JOIN transactions t ON t.transaction_id = ti.transaction_id
                 WHERE ti.product_id = ?
                   AND t.status = 'completed'
                   AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            );
            $salesStmt->execute([$productId]);
            $salesData = $salesStmt->fetch();
            
            $totalSold = (float)$salesData['total_sold'];
            $daysSpan = max(1, (int)$salesData['days_span']);
            $avgDailySales = $totalSold / $daysSpan;
            
            // If no recent sales, use a minimum assumption
            if ($avgDailySales < 0.1) {
                $avgDailySales = 0.5; // Assume at least 0.5 units per day
            }
            
            // Calculate days of stock remaining
            $currentStock = (int)$product['current_stock'];
            $daysOfStockRemaining = $currentStock > 0 ? $currentStock / $avgDailySales : 0;
            
            // Determine urgency
            $urgency = 'low';
            if ($daysOfStockRemaining <= 3) {
                $urgency = 'critical';
            } elseif ($daysOfStockRemaining <= 7) {
                $urgency = 'urgent';
            } elseif ($daysOfStockRemaining <= 14) {
                $urgency = 'medium';
            }
            
            // Calculate recommended order quantity
            $leadTime = max(1, (int)($product['lead_time_days'] ?? 7));
            $safetyStock = (int)ceil($avgDailySales * $leadTime * $safetyMultiplier);
            $maxStock = (int)$product['maximum_stock'] ?: 999999;
            
            // Order enough to cover: (lead time + review period) * daily sales + safety stock
            $reviewPeriod = 14; // 2 weeks between orders
            $suggestedQty = (int)ceil(($avgDailySales * ($leadTime + $reviewPeriod)) + $safetyStock - $currentStock);
            
            // Respect maximum stock level
            if (($currentStock + $suggestedQty) > $maxStock) {
                $suggestedQty = $maxStock - $currentStock;
            }
            
            // Ensure minimum order of reorder_qty
            $suggestedQty = max($suggestedQty, (int)$product['reorder_qty']);
            
            // Calculate confidence score based on data quality
            $confidenceScore = 50.0; // Base confidence
            if ($totalSold > 0) {
                $confidenceScore += 20.0; // Has sales data
            }
            if ($daysSpan >= 20) {
                $confidenceScore += 15.0; // Good data span
            }
            if ($avgDailySales > 1) {
                $confidenceScore += 15.0; // Decent velocity
            }
            $confidenceScore = min(95.0, $confidenceScore);
            
            // Check if recommendation already exists
            $checkStmt = $db->prepare(
                "SELECT rec_id FROM ai_recommendations
                 WHERE product_id = ? 
                   AND rec_type = 'restock'
                   AND status = 'pending'
                   AND generated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $checkStmt->execute([$productId]);
            
            if ($checkStmt->fetch()) {
                continue; // Skip if recent recommendation exists
            }
            
            // Create recommendation
            $recommendation = sprintf(
                'Restock %s by %d units. Current: %d, Avg Daily Sales: %.2f, Days Remaining: %.1f',
                $product['product_name'],
                $suggestedQty,
                $currentStock,
                $avgDailySales,
                $daysOfStockRemaining
            );
            
            $reason = sprintf(
                'Sales velocity analysis: %.2f units/day over %d days. Lead time: %d days. Safety multiplier: %.1fx',
                $avgDailySales,
                $daysSpan,
                $leadTime,
                $safetyMultiplier
            );
            
            $formula = 'suggested_qty=ceil((avg_daily_sales*(lead_time+review_period))+safety_stock-current_stock)';
            
            $stmt = $db->prepare(
                "INSERT INTO ai_recommendations (
                    rec_type, product_id, recommendation, reason, formula_used,
                    confidence_score, urgency, suggested_value, status, generated_at
                ) VALUES (
                    'restock', ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
                )"
            );
            $stmt->execute([
                $productId,
                $recommendation,
                $reason,
                $formula,
                $confidenceScore,
                $urgency,
                $suggestedQty,
            ]);
            
            $recommendationsCreated++;
        }

        $logger = new Logger();
        $logger->log('ai_insights', 'generate', null, null, [
            'recommendations_created' => $recommendationsCreated
        ], 'AI recommendations generated with sales velocity analysis.');

        $this->done(
            sprintf('AI generation completed. %d recommendations created.', $recommendationsCreated),
            '/ai-insights'
        );
    }

    public function demand(): void
    {
        $this->requireAuth();
        
        $recommendations = $this->aiModel->getByType('restock', 100);
        
        $this->moduleSection('demand', [
            'records' => $recommendations,
            'extra' => [
                'subtitle' => 'Demand Forecasting & Restock Recommendations',
            ],
        ]);
    }

    public function pricing(): void
    {
        $this->requireAuth();
        
        $recommendations = $this->aiModel->getByType('pricing', 100);
        if (empty($recommendations)) {
            $this->aiModel->generatePricingRecommendations(30);
            $recommendations = $this->aiModel->getByType('pricing', 100);
        }
        
        $this->moduleSection('pricing', [
            'records' => $recommendations,
            'extra' => [
                'subtitle' => 'Price Optimization Suggestions',
            ],
        ]);
    }

    public function stock(): void
    {
        $this->requireAuth();
        
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT r.*, p.product_name, p.product_code
             FROM ai_recommendations r
             LEFT JOIN products p ON p.product_id = r.product_id
             WHERE r.rec_type IN ('restock', 'dead_stock')
               AND r.status = 'pending'
             ORDER BY 
                CASE r.urgency
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                r.generated_at DESC
             LIMIT 100"
        );
        
        $this->moduleSection('stock', [
            'records' => $stmt->fetchAll(),
            'extra' => [
                'subtitle' => 'Stock Optimization & Dead Stock Alerts',
            ],
        ]);
    }

    public function bundling(): void
    {
        $this->requireAuth();
        
        $recommendations = $this->aiModel->getByType('substitution', 100);
        
        $this->moduleSection('bundling', [
            'records' => $recommendations,
            'extra' => [
                'subtitle' => 'Product Bundling Opportunities',
            ],
        ]);
    }

    public function anomalies(): void
    {
        $this->requireAuth();
        
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT r.*, p.product_name, p.product_code
             FROM ai_recommendations r
             LEFT JOIN products p ON p.product_id = r.product_id
             WHERE r.rec_type IN ('anomaly', 'dead_stock')
               AND r.status = 'pending'
             ORDER BY 
                CASE r.urgency
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                r.generated_at DESC
             LIMIT 100"
        );

        $this->moduleSection('anomalies', [
            'records' => $stmt->fetchAll(),
            'extra' => [
                'subtitle' => 'Anomaly Detection & Alerts',
            ],
        ]);
    }

    public function segments(): void
    {
        $this->requireAuth();
        
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT r.*, s.supplier_name
             FROM ai_recommendations r
             LEFT JOIN suppliers s ON s.supplier_id = r.supplier_id
             WHERE r.rec_type = 'supplier'
               AND r.status = 'pending'
             ORDER BY 
                CASE r.urgency
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                r.generated_at DESC
             LIMIT 100"
        );

        $this->moduleSection('segments', [
            'records' => $stmt->fetchAll(),
            'extra' => [
                'subtitle' => 'Supplier Performance & Recommendations',
            ],
        ]);
    }

    public function accept(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'purchasing_officer', 'inventory_staff']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $userId = (int)$this->user()['user_id'];
        $recId = (int)$id;

        try {
            $db->beginTransaction();

            $recStmt = $db->prepare(
                "SELECT *
                 FROM ai_recommendations
                 WHERE rec_id = ? AND status = 'pending'
                 LIMIT 1"
            );
            $recStmt->execute([$recId]);
            $recommendation = $recStmt->fetch(PDO::FETCH_ASSOC);

            if (!$recommendation) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $this->done('Recommendation not found or already processed.', '/ai-insights', 'warning');
                return;
            }

            $actionResult = $this->executeRecommendationAction($db, $recommendation, $userId);

            $stmt = $db->prepare(
                "UPDATE ai_recommendations
                 SET status = 'accepted', acted_on_by = ?, acted_on_at = NOW()
                 WHERE rec_id = ? AND status = 'pending'"
            );
            $stmt->execute([$userId, $recId]);

            $logger = new Logger();
            $logger->log(
                'ai_insights',
                'accept',
                $recId,
                ['status' => 'pending'],
                [
                    'status' => 'accepted',
                    'rec_type' => $recommendation['rec_type'] ?? null,
                    'auto_action' => $actionResult,
                ],
                'AI recommendation accepted with automatic action execution.'
            );

            $db->commit();

            $message = 'AI recommendation #' . $recId . ' accepted.';
            if (!empty($actionResult['message'])) {
                $message .= ' ' . $actionResult['message'];
            }
            $this->done($message, '/ai-insights');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->done('Failed to accept recommendation: ' . $e->getMessage(), '/ai-insights');
        }
    }

    private function executeRecommendationAction(PDO $db, array $recommendation, int $userId): array
    {
        $recType = (string)($recommendation['rec_type'] ?? '');
        $productId = (int)($recommendation['product_id'] ?? 0);

        if ($recType === 'restock') {
            if ($productId <= 0) {
                return ['action' => 'skipped', 'message' => 'No product linked for auto-PO.'];
            }

            $qty = max(1, (int)round((float)($recommendation['suggested_value'] ?? 0)));
            $prodStmt = $db->prepare(
                "SELECT product_id,
                        COALESCE(NULLIF(avg_cost_price, 0), cost_price, 0) AS unit_cost
                 FROM products
                 WHERE product_id = ?
                 LIMIT 1"
            );
            $prodStmt->execute([$productId]);
            $product = $prodStmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return ['action' => 'skipped', 'message' => 'Product not found for auto-PO.'];
            }

            $unitCost = (float)($product['unit_cost'] ?? 0);
            if ($unitCost <= 0) {
                $unitCost = 1.00;
            }

            $poModel = new PurchaseOrderModel();
            $result = $poModel->queueAutoReplenishmentFromSale([
                [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                ],
            ], $userId);

            if ((int)($result['queued_items'] ?? 0) > 0) {
                return [
                    'action' => 'purchase_order',
                    'message' => 'Auto PO queued for approval.',
                    'meta' => $result,
                ];
            }

            return ['action' => 'skipped', 'message' => 'No PO changes were needed.'];
        }

        if (in_array($recType, ['dead_stock', 'promotion', 'substitution'], true)) {
            if ($productId <= 0) {
                return ['action' => 'skipped', 'message' => 'No product linked for promo creation.'];
            }

            $promoPct = match ($recType) {
                'dead_stock' => 15.0,
                'substitution' => 8.0,
                default => 10.0,
            };

            $dupStmt = $db->prepare(
                "SELECT p.promo_id
                 FROM promotions p
                 JOIN promotion_products pp ON pp.promo_id = p.promo_id
                 WHERE p.ai_generated = 1
                   AND p.status = 'active'
                   AND p.promo_type = 'price_discount'
                   AND pp.product_id = ?
                   AND NOW() BETWEEN p.start_datetime AND p.end_datetime
                 ORDER BY p.promo_id DESC
                 LIMIT 1"
            );
            $dupStmt->execute([$productId]);
            $existingPromoId = (int)($dupStmt->fetchColumn() ?: 0);
            if ($existingPromoId > 0) {
                return [
                    'action' => 'skipped',
                    'message' => 'Active AI promo already exists for this item.',
                    'meta' => ['promo_id' => $existingPromoId],
                ];
            }

            $nameStmt = $db->prepare("SELECT product_name FROM products WHERE product_id = ? LIMIT 1");
            $nameStmt->execute([$productId]);
            $productName = (string)($nameStmt->fetchColumn() ?: ('Product #' . $productId));

            $promoStmt = $db->prepare(
                "INSERT INTO promotions (
                    promo_name, promo_type, description, discount_pct, discount_amount,
                    min_qty, free_qty, threshold_amount, threshold_discount,
                    start_datetime, end_datetime, applicable_to, created_by, status,
                    ai_generated, created_at
                ) VALUES (?, 'price_discount', ?, ?, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 MINUTE), DATE_ADD(NOW(), INTERVAL 14 DAY), 'product', ?, 'active', 1, NOW())"
            );

            $promoName = 'AI Auto Promo: ' . $productName;
            $promoDescription = 'Auto-created from AI recommendation #' . (int)$recommendation['rec_id'] . ' (' . $recType . ').';
            $promoStmt->execute([$promoName, $promoDescription, $promoPct, $userId]);
            $promoId = (int)$db->lastInsertId();

            $targetStmt = $db->prepare("INSERT IGNORE INTO promotion_products (promo_id, product_id) VALUES (?, ?)");
            $targetStmt->execute([$promoId, $productId]);

            $relatedProductId = (int)($recommendation['related_product_id'] ?? 0);
            if ($recType === 'substitution' && $relatedProductId > 0) {
                $targetStmt->execute([$promoId, $relatedProductId]);
            }

            return [
                'action' => 'promotion',
                'message' => 'Auto promotion created (' . rtrim(rtrim(number_format($promoPct, 2), '0'), '.') . '% off).',
                'meta' => ['promo_id' => $promoId],
            ];
        }

        if ($recType === 'pricing') {
            if ($productId <= 0) {
                return ['action' => 'skipped', 'message' => 'No product linked for auto price update.'];
            }

            $suggestedPrice = round((float)($recommendation['suggested_value'] ?? 0), 2);
            if ($suggestedPrice <= 0) {
                return ['action' => 'skipped', 'message' => 'Suggested price is missing.'];
            }

            $curStmt = $db->prepare("SELECT selling_price FROM products WHERE product_id = ? LIMIT 1");
            $curStmt->execute([$productId]);
            $currentPrice = (float)($curStmt->fetchColumn() ?: 0);
            if ($currentPrice <= 0 || abs($currentPrice - $suggestedPrice) < 0.01) {
                return ['action' => 'skipped', 'message' => 'No price change needed.'];
            }

            $upStmt = $db->prepare("UPDATE products SET selling_price = ?, updated_at = NOW() WHERE product_id = ?");
            $upStmt->execute([$suggestedPrice, $productId]);

            return [
                'action' => 'price_update',
                'message' => 'Product price updated automatically.',
                'meta' => ['old_price' => $currentPrice, 'new_price' => $suggestedPrice],
            ];
        }

        return ['action' => 'none', 'message' => 'No automatic action configured for this recommendation type.'];
    }

    public function dismiss(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'purchasing_officer', 'inventory_staff']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE ai_recommendations
             SET status = 'dismissed', acted_on_by = ?, acted_on_at = NOW()
             WHERE rec_id = ? AND status = 'pending'"
        );
        $stmt->execute([(int)$this->user()['user_id'], (int)$id]);

        $logger = new Logger();
        $logger->log('ai_insights', 'dismiss', (int)$id, ['status' => 'pending'], ['status' => 'dismissed'], 'AI recommendation dismissed.');

        $this->done('AI recommendation #' . (int)$id . ' dismissed.', '/ai-insights');
    }

    public function feedback(string $id): void
    {
        $this->requireAuth();
        Auth::csrfVerify();

        $rating = (string)$this->post('rating', 'partially_helpful');
        if (!in_array($rating, ['helpful', 'not_helpful', 'partially_helpful'], true)) {
            $rating = 'partially_helpful';
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO ai_feedback (rec_id, user_id, rating, comment, submitted_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            (int)$id,
            (int)$this->user()['user_id'],
            $rating,
            $this->post('comment', null),
        ]);

        $this->done('Feedback submitted for recommendation #' . (int)$id . '.', '/ai-insights');
    }
}
