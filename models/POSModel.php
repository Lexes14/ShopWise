<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║                    SHOPWISE AI — POS MODEL                          ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Complete POS business logic per blueprint specification
 * Handles: product loading, checkout processing, FEFO batch consumption,
 * OR/TXN number generation, stock deduction, VAT calculation, void processing
 */

declare(strict_types=1);

class POSModel extends Model
{
    protected string $table = 'transactions';
    protected string $primaryKey = 'transaction_id';

    /**
     * Get products by category with stock and promotion data
     */
    public function getProductsByCategory(string $category = 'all', string $search = ''): array
    {
        $sql = "SELECT 
                    p.product_id, p.product_code, p.product_name, p.product_alias,
                    p.selling_price, p.is_vatable, p.current_stock,
                    p.image_path, p.minimum_stock,
                    c.category_name, c.icon AS category_icon
                FROM products p
                JOIN categories c ON c.category_id = p.category_id
                WHERE p.status = 'active'";
        
        $params = [];
        
        if ($category !== 'all') {
            $sql .= " AND c.category_name = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ? OR p.product_alias LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY p.product_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Apply active promotions
        foreach ($products as &$product) {
            $promo = $this->getActivePromotion((int)$product['product_id']);
            if ($promo) {
                $product['promo_price'] = $promo['discounted_price'];
                $product['promo_name'] = $promo['promo_name'];
            }
        }
        
        return $products;
    }

    /**
     * Get active promotion for a product
     */
    private function getActivePromotion(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT pr.promo_id, pr.promo_name, pr.promo_type, 
                    pr.discount_pct, pr.discount_amount
             FROM promotions pr
             JOIN promotion_products pp ON pp.promo_id = pr.promo_id
             WHERE pp.product_id = ?
               AND pr.status = 'active'
               AND NOW() BETWEEN pr.start_datetime AND pr.end_datetime
             ORDER BY pr.discount_pct DESC
             LIMIT 1"
        );
        $stmt->execute([$productId]);
        $promo = $stmt->fetch();
        
        if (!$promo) {
            return null;
        }
        
        // Would need product price here to calculate
        return $promo;
    }

    /**
     * Process complete checkout transaction
     * Returns: ['success' => bool, 'transaction_id' => int, 'or_number' => string, 'receipt_data' => array]
     */
    public function processCheckout(array $cartItems, array $paymentData, int $shiftId, int $cashierId): array
    {
        try {
            $this->db->beginTransaction();
            
            // 1. Generate OR and TXN numbers (with row-level lock)
            $orNumber = $this->generateORNumber();
            $txnNumber = $this->generateTXNNumber();
            
            // 2. Calculate totals
            $subtotal = 0;
            $vatableSales = 0;
            $vatExemptSales = 0;
            
            foreach ($cartItems as $item) {
                $itemSubtotal = $item['price'] * $item['qty'];
                $subtotal += $itemSubtotal;
                
                if ($item['is_vatable']) {
                    $vatableSales += $itemSubtotal;
                } else {
                    $vatExemptSales += $itemSubtotal;
                }
            }
            
            // 3. Calculate VAT (VAT-inclusive formula)
            $vatAmount = round($vatableSales - ($vatableSales / 1.12), 2);
            
            // 4. Apply customer discount
            $discountAmount = 0;
            $discountType = 'none';
            if (in_array($paymentData['customer_type'] ?? 'regular', ['senior', 'pwd'])) {
                $discountAmount = round($subtotal * 0.20, 2);
                $discountType = 'senior_pwd';
            }
            
            // 5. Calculate grand total
            $grandTotal = $subtotal - $discountAmount;
            
            // 6. Insert transaction record
            $stmt = $this->db->prepare(
                "INSERT INTO transactions (
                    transaction_number, or_number, branch_id, shift_id, cashier_id,
                    customer_id, customer_type, senior_id_number,
                    vatable_sales, vat_exempt_sales, vat_amount,
                    subtotal, discount_type, discount_amount, total_amount,
                    payment_method, amount_tendered, change_amount,
                    gcash_ref, maya_ref, card_approval_code,
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    'completed', NOW()
                )"
            );
            
            $stmt->execute([
                $txnNumber,
                $orNumber,
                BRANCH_ID,
                $shiftId,
                $cashierId,
                $paymentData['customer_id'] ?? null,
                $paymentData['customer_type'] ?? 'regular',
                $paymentData['senior_id_number'] ?? null,
                $vatableSales,
                $vatExemptSales,
                $vatAmount,
                $subtotal,
                $discountType,
                $discountAmount,
                $grandTotal,
                $paymentData['payment_method'],
                $paymentData['amount_tendered'],
                $paymentData['amount_tendered'] - $grandTotal,
                $paymentData['gcash_ref'] ?? null,
                $paymentData['maya_ref'] ?? null,
                $paymentData['card_approval_code'] ?? null,
            ]);
            
            $transactionId = (int)$this->db->lastInsertId();
            
            // 7. Process each cart item
            foreach ($cartItems as $item) {
                // Get next batch (FEFO)
                $batch = $this->getNextBatchForSale((int)$item['product_id'], (int)$item['qty']);
                
                if (!$batch) {
                    throw new Exception("Insufficient stock for product ID {$item['product_id']}");
                }
                
                // Deduct from batch
                $this->deductFromBatch((int)$batch['batch_id'], (int)$item['qty']);
                
                // Update product stock
                $this->updateProductStock((int)$item['product_id'], -(int)$item['qty']);
                
                // Insert transaction item
                $stmt = $this->db->prepare(
                    "INSERT INTO transaction_items (
                        transaction_id, product_id, batch_id, quantity,
                        unit_price, cost_price, is_vatable, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $transactionId,
                    $item['product_id'],
                    $batch['batch_id'],
                    $item['qty'],
                    $item['price'],
                    $batch['cost_price'],
                    $item['is_vatable'],
                    $item['price'] * $item['qty']
                ]);
                
                // Record stock movement
                $this->recordStockMovement(
                    (int)$item['product_id'],
                    (int)$batch['batch_id'],
                    'sale',
                    0,
                    (int)$item['qty'],
                    'transactions',
                    $transactionId,
                    $cashierId
                );
            }
            
            // 8. Update shift totals
            $this->updateShiftTotals($shiftId, $grandTotal, $paymentData['payment_method']);
            
            // 9. Award loyalty points if customer provided
            if (!empty($paymentData['customer_id'])) {
                $this->awardLoyaltyPoints((int)$paymentData['customer_id'], $grandTotal, $transactionId);
            }
            
            $this->db->commit();
            
            // 10. GetReceipt data
            $receiptData = $this->getReceiptData($transactionId);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'or_number' => $orNumber,
                'receipt_data' => $receiptData
            ];
            
        } catch (Exception $e) {
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
     * Generate OR Number with row-level lock (sequential per day)
     */
    private function generateORNumber(): string
    {
        $today = date('Ymd');
        $prefix = 'OR-' . $today . '-';
        
        // Lock the last OR number for today
        $stmt = $this->db->prepare(
            "SELECT or_number 
             FROM transactions 
             WHERE or_number LIKE ? 
             ORDER BY transaction_id DESC 
             LIMIT 1 
             FOR UPDATE"
        );
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetch();
        
        if ($last) {
            $lastNum = (int)substr($last['or_number'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return $prefix . str_pad((string)$newNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Transaction Number
     */
    private function generateTXNNumber(): string
    {
        $today = date('Ymd');
        $prefix = 'TXN-' . $today . '-';
        
        $stmt = $this->db->prepare(
            "SELECT transaction_number 
             FROM transactions 
             WHERE transaction_number LIKE ? 
             ORDER BY transaction_id DESC 
             LIMIT 1 
             FOR UPDATE"
        );
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetch();
        
        if ($last) {
            $lastNum = (int)substr($last['transaction_number'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return $prefix . str_pad((string)$newNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get next batch for sale (FEFO - First Expiry First Out)
     */
    private function getNextBatchForSale(int $productId, int $qtyNeeded): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT batch_id, batch_number, qty_remaining, cost_price, expiration_date
             FROM batches
             WHERE product_id = ?
               AND status = 'active'
               AND qty_remaining >= ?
             ORDER BY 
                CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END,
                expiration_date ASC,
                batch_id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$productId, $qtyNeeded]);
        $batch = $stmt->fetch();
        
        return $batch ?: null;
    }

    /**
     * Deduct quantity from batch
     */
    private function deductFromBatch(int $batchId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE batches 
             SET qty_remaining = qty_remaining - ?
             WHERE batch_id = ?"
        );
        $stmt->execute([$qty, $batchId]);
        
        // Check if depleted
        $stmt = $this->db->prepare(
            "UPDATE batches 
             SET status = 'depleted'
             WHERE batch_id = ? AND qty_remaining = 0"
        );
        $stmt->execute([$batchId]);
        
        return true;
    }

    /**
     * Update product stock
     */
    private function updateProductStock(int $productId, int $delta): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products 
             SET current_stock = current_stock + ?
             WHERE product_id = ?"
        );
        return $stmt->execute([$delta, $productId]);
    }

    /**
     * Record stock movement
     */
    private function recordStockMovement(
        int $productId,
        int $batchId,
        string $type,
        int $qtyIn,
        int $qtyOut,
        string $refType,
        int $refId,
        int $performedBy
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_movements (
                product_id, batch_id, branch_id, movement_type,
                quantity_in, quantity_out, reference_type, reference_id,
                performed_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $productId, $batchId, BRANCH_ID, $type,
            $qtyIn, $qtyOut, $refType, $refId, $performedBy
        ]);
    }

    /**
     * Update shift totals
     */
    private function updateShiftTotals(int $shiftId, float $amount, string $paymentMethod): void
    {
        $field = match($paymentMethod) {
            'cash' => 'total_cash_sales',
            'gcash', 'maya' => 'total_ewallet_sales',
            'card' => 'total_card_sales',
            default => 'total_cash_sales'
        };
        
        $stmt = $this->db->prepare(
            "UPDATE shifts 
             SET $field = $field + ?
             WHERE shift_id = ?"
        );
        $stmt->execute([$amount, $shiftId]);
    }

    /**
     * Award loyalty points
     */
    private function awardLoyaltyPoints(int $customerId, float $totalSpent, int $transactionId): void
    {
        $points = (int)floor($totalSpent / 10); // ₱10 = 1 point
        
        if ($points > 0) {
            $stmt = $this->db->prepare(
                "UPDATE customers 
                 SET points_balance = points_balance + ?,
                     total_spend = total_spend + ?,
                     total_visits = total_visits + 1
                 WHERE customer_id = ?"
            );
            $stmt->execute([$points, $totalSpent, $customerId]);
            
            // Get new balance
            $stmt = $this->db->prepare(
                "SELECT points_balance FROM customers WHERE customer_id = ?"
            );
            $stmt->execute([$customerId]);
            $newBalance = (int)$stmt->fetchColumn();
            
            // Record loyalty transaction
            $stmt = $this->db->prepare(
                "INSERT INTO loyalty_points (
                    customer_id, transaction_id, points_type, points_amount,
                    balance_after, notes, created_at
                ) VALUES (?, ?, 'earned', ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $customerId,
                $transactionId,
                $points,
                $newBalance,
                "Earned from transaction"
            ]);
        }
    }

    /**
     * Get receipt data
     */
    public function getReceiptData(int $transactionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, u.full_name AS cashier_name, c.full_name AS customer_name
             FROM transactions t
             JOIN users u ON u.user_id = t.cashier_id
             LEFT JOIN customers c ON c.customer_id = t.customer_id
             WHERE t.transaction_id = ?"
        );
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            return [];
        }
        
        $stmt = $this->db->prepare(
            "SELECT ti.*, p.product_name
             FROM transaction_items ti
             JOIN products p ON p.product_id = ti.product_id
             WHERE ti.transaction_id = ?"
        );
        $stmt->execute([$transactionId]);
        $items = $stmt->fetchAll();
        
        return [
            'transaction' => $transaction,
            'items' => $items
        ];
    }

    /**
     * Hold transaction (save cart for later)
     */
    public function holdTransaction(array $cartItems, int $shiftId, int $cashierId, string $label): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO held_transactions (cashier_id, shift_id, label, cart_json, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $cashierId,
            $shiftId,
            $label,
            json_encode($cartItems)
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get held transactions
     */
    public function getHeldTransactions(int $cashierId, int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hold_id, label, cart_json, created_at
             FROM held_transactions
             WHERE cashier_id = ? AND shift_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$cashierId, $shiftId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete held transaction
     */
    public function deleteHeldTransaction(int $holdId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM held_transactions WHERE hold_id = ?");
        return $stmt->execute([$holdId]);
    }

    /**
     * Void transaction (restore stock)
     */
    public function voidTransaction(int $transactionId, string $reason, int $voidedBy): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Get transaction items
            $stmt = $this->db->prepare(
                "SELECT ti.*, t.shift_id, t.total_amount, t.payment_method
                 FROM transaction_items ti
                 JOIN transactions t ON t.transaction_id = ti.transaction_id
                 WHERE ti.transaction_id = ?"
            );
            $stmt->execute([$transactionId]);
            $items = $stmt->fetchAll();
            
            if (empty($items)) {
                throw new Exception("Transaction not found");
            }
            
            $transaction = $items[0]; // Get txn data from first item
            
            // Restore stock for each item
            foreach ($items as $item) {
                // Restore batch quantity
                $stmt = $this->db->prepare(
                    "UPDATE batches 
                     SET qty_remaining = qty_remaining + ?,
                         status = 'active'
                     WHERE batch_id = ?"
                );
                $stmt->execute([$item['quantity'], $item['batch_id']]);
                
                // Restore product stock
                $this->updateProductStock((int)$item['product_id'], (int)$item['quantity']);
                
                // Record reverse stock movement
                $this->recordStockMovement(
                    (int)$item['product_id'],
                    (int)$item['batch_id'],
                    'return',
                    (int)$item['quantity'],
                    0,
                    'void',
                    $transactionId,
                    $voidedBy
                );
            }
            
            // Update transaction status
            $stmt = $this->db->prepare(
                "UPDATE transactions 
                 SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = NOW()
                 WHERE transaction_id = ?"
            );
            $stmt->execute([$reason, $voidedBy, $transactionId]);
            
            // Update shift totals (reverse)
            $field = match($transaction['payment_method']) {
                'cash' => 'total_cash_sales',
                'gcash', 'maya' => 'total_ewallet_sales',
                'card' => 'total_card_sales',
                default => 'total_cash_sales'
            };
            
            $stmt = $this->db->prepare(
                "UPDATE shifts 
                 SET $field = $field - ?,
                     total_voids = total_voids + 1
                 WHERE shift_id = ?"
            );
            $stmt->execute([$transaction['total_amount'], $transaction['shift_id']]);
            
            // Log void
            $stmt = $this->db->prepare(
                "INSERT INTO void_logs (
                    transaction_id, voided_by, reason, items_json, voided_at
                ) VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $transactionId,
                $voidedBy,
                $reason,
                json_encode($items)
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
