<?php
declare(strict_types=1);

class POSController extends Controller
{
    protected string $layout = 'app';

    public function terminal(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $db = Database::getInstance();
        
        // Get products with category info
        $stmt = $db->query(
            "SELECT p.product_id, p.product_name, p.product_code, p.selling_price, 
                    p.current_stock, p.category_id, c.category_name
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             WHERE p.status = 'active' AND p.current_stock > 0
             ORDER BY p.product_name ASC
             LIMIT 120"
        );
        
        // Get all active categories
        $categoriesStmt = $db->query(
            "SELECT category_id, category_name, icon
             FROM categories
             WHERE status = 'active'
             ORDER BY category_name ASC"
        );

        $this->render('pos/terminal', [
            'flash' => $this->getFlash(),
            'products' => $stmt->fetchAll(),
            'categories' => $categoriesStmt->fetchAll(),
            'csrf' => Auth::csrfGenerate(),
        ]);
    }

    public function products(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $q = trim((string)$this->get('q', ''));
        $db = Database::getInstance();

        $sql = "SELECT product_id, product_name, product_code, selling_price, current_stock
                FROM products
                WHERE status = 'active' AND current_stock > 0";
        $params = [];

        if ($q !== '') {
            $sql .= " AND (product_name LIKE ? OR product_code LIKE ?)";
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }

        $sql .= " ORDER BY product_name ASC LIMIT 40";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $items = array_map(static fn(array $row): array => [
            'id' => (int)$row['product_id'],
            'name' => $row['product_name'],
            'code' => $row['product_code'],
            'price' => (float)$row['selling_price'],
            'stock' => (int)$row['current_stock'],
        ], $stmt->fetchAll());

        $this->json(['success' => true, 'items' => $items]);
    }

    public function checkout(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $payload = $this->requestPayload();
        $items = $payload['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            $this->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }

        $paymentMethod = (string)($payload['payment_method'] ?? 'cash');
        $allowedPaymentMethods = ['cash', 'gcash', 'maya', 'card', 'split'];
        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            $this->json(['success' => false, 'message' => 'Invalid payment method.'], 422);
        }

        $discountAmount = max(0, (float)($payload['discount_amount'] ?? 0));
        $customerType = (string)($payload['customer_type'] ?? 'regular');
        if (!in_array($customerType, ['regular', 'senior', 'pwd'], true)) {
            $customerType = 'regular';
        }

        $customerId = isset($payload['customer_id']) ? (int)$payload['customer_id'] : null;
        if ($customerId !== null && $customerId <= 0) {
            $customerId = null;
        }

        $db = Database::getInstance();
        $user = $this->user();
        $cashierId = (int)$user['user_id'];
        $shiftId = $this->resolveOpenShiftId($db, $cashierId, $payload['shift_id'] ?? null);

        if ($shiftId <= 0) {
            $this->json(['success' => false, 'message' => 'No open shift found. Open a shift first.'], 422);
        }

        $productIds = [];
        $cartMap = [];
        foreach ($items as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['qty'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $productIds[] = $productId;
            $cartMap[$productId] = $qty;
        }

        $productIds = array_values(array_unique($productIds));
        if (count($productIds) === 0) {
            $this->json(['success' => false, 'message' => 'No valid cart items provided.'], 422);
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $db->prepare(
            "SELECT product_id, product_name, selling_price, avg_cost_price, cost_price, current_stock, is_vatable
             FROM products
             WHERE product_id IN ($placeholders) AND status = 'active'"
        );
        $stmt->execute($productIds);
        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $products[(int)$row['product_id']] = $row;
        }

        $subtotal = 0.0;
        foreach ($cartMap as $productId => $qty) {
            if (!isset($products[$productId])) {
                $this->json(['success' => false, 'message' => 'Product not found: #' . $productId], 422);
            }
            if ((int)$products[$productId]['current_stock'] < $qty) {
                $this->json([
                    'success' => false,
                    'message' => 'Insufficient stock for ' . $products[$productId]['product_name']
                ], 422);
            }
            $subtotal += (float)$products[$productId]['selling_price'] * $qty;
        }

        if ($discountAmount > $subtotal) {
            $discountAmount = $subtotal;
        }

        $totalAmount = round($subtotal - $discountAmount, 2);
        $amountTendered = (float)($payload['amount_tendered'] ?? $totalAmount);

        if ($paymentMethod === 'cash' && $amountTendered < $totalAmount) {
            $this->json(['success' => false, 'message' => 'Amount tendered is less than total due.'], 422);
        }

        $changeAmount = max(0, round($amountTendered - $totalAmount, 2));
        $isVatExempt = in_array($customerType, ['senior', 'pwd'], true) && SENIOR_PWD_VAT_EXEMPT;
        $vatAmount = $isVatExempt ? 0.00 : round($totalAmount - ($totalAmount / VAT_DIVISOR), 2);
        $vatableSales = $isVatExempt ? 0.00 : round($totalAmount, 2);
        $vatExemptSales = $isVatExempt ? round($totalAmount, 2) : 0.00;

        $discountType = $discountAmount > 0
            ? (in_array($customerType, ['senior', 'pwd'], true) ? 'senior_pwd' : 'manual')
            : 'none';

        $transactionNumber = $this->generateDocNumber($db, 'transactions', 'transaction_number', TXN_PREFIX);
        $orNumber = $this->generateDocNumber($db, 'transactions', 'or_number', OR_PREFIX);

        try {
            $db->beginTransaction();

            $insertTxn = $db->prepare(
                "INSERT INTO transactions (
                    transaction_number, or_number, branch_id, shift_id, cashier_id, customer_id,
                    customer_type, senior_id_number, vatable_sales, vat_exempt_sales, vat_amount,
                    subtotal, discount_type, discount_amount, total_amount, payment_method,
                    amount_tendered, change_amount, gcash_ref, maya_ref, card_approval_code,
                    points_earned, points_redeemed, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'completed', NOW()
                )"
            );

            $pointsEarned = $customerId ? (int)floor($totalAmount * LOYALTY_POINTS_PER_PESO) : 0;
            $pointsRedeemed = (int)($payload['points_redeemed'] ?? 0);

            $insertTxn->execute([
                $transactionNumber,
                $orNumber,
                BRANCH_ID,
                $shiftId,
                $cashierId,
                $customerId,
                $customerType,
                $payload['senior_id_number'] ?? null,
                $vatableSales,
                $vatExemptSales,
                $vatAmount,
                round($subtotal, 2),
                $discountType,
                round($discountAmount, 2),
                $totalAmount,
                $paymentMethod,
                round($amountTendered, 2),
                $changeAmount,
                $payload['gcash_ref'] ?? null,
                $payload['maya_ref'] ?? null,
                $payload['card_approval_code'] ?? null,
                $pointsEarned,
                $pointsRedeemed,
            ]);

            $transactionId = (int)$db->lastInsertId();

            $insertItem = $db->prepare(
                "INSERT INTO transaction_items (
                    transaction_id, product_id, batch_id, quantity, unit_price, cost_price,
                    is_vatable, discount_amount, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $updateProductStock = $db->prepare(
                "UPDATE products SET current_stock = current_stock - ? WHERE product_id = ?"
            );

            $updateBatch = $db->prepare(
                "UPDATE batches
                 SET qty_remaining = qty_remaining - ?,
                     status = CASE WHEN qty_remaining - ? <= 0 THEN 'depleted' ELSE status END
                 WHERE batch_id = ?"
            );

            $insertMovement = $db->prepare(
                "INSERT INTO stock_movements (
                    product_id, batch_id, branch_id, movement_type, quantity_in, quantity_out,
                    reference_type, reference_id, performed_by, notes, created_at
                ) VALUES (?, ?, ?, 'sale', 0, ?, 'transaction', ?, ?, ?, NOW())"
            );

            foreach ($cartMap as $productId => $qty) {
                $product = $products[$productId];
                $unitPrice = (float)$product['selling_price'];
                $costPrice = (float)($product['avg_cost_price'] > 0 ? $product['avg_cost_price'] : $product['cost_price']);
                $lineSubtotal = round($unitPrice * $qty, 2);

                $batchId = $this->consumeBatch($db, $productId, $qty, $updateBatch);

                $insertItem->execute([
                    $transactionId,
                    $productId,
                    $batchId,
                    $qty,
                    $unitPrice,
                    $costPrice,
                    (int)$product['is_vatable'],
                    0.00,
                    $lineSubtotal,
                ]);

                $updateProductStock->execute([$qty, $productId]);
                $insertMovement->execute([
                    $productId,
                    $batchId,
                    BRANCH_ID,
                    $qty,
                    $transactionId,
                    $cashierId,
                    'POS checkout',
                ]);
            }

            $this->applyCustomerPoints($db, $customerId, $transactionId, $pointsEarned, $pointsRedeemed, $totalAmount);
            $this->applyShiftSales($db, $shiftId, $paymentMethod, $totalAmount);

            $logger = new Logger();
            $logger->log('pos', 'checkout', $transactionId, null, [
                'transaction_number' => $transactionNumber,
                'total' => $totalAmount,
                'payment_method' => $paymentMethod,
                'items' => count($cartMap),
            ], 'POS checkout completed.');

            $db->commit();

            $this->json([
                'success' => true,
                'message' => 'Checkout successful.',
                'transaction_id' => $transactionId,
                'transaction_number' => $transactionNumber,
                'or_number' => $orNumber,
                'total' => $totalAmount,
                'change' => $changeAmount,
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()], 500);
        }
    }

    public function hold(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $payload = $this->requestPayload();
        $items = $payload['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            $this->json(['success' => false, 'message' => 'Cannot hold empty cart.'], 422);
        }

        $db = Database::getInstance();
        $cashierId = (int)$this->user()['user_id'];
        $shiftId = $this->resolveOpenShiftId($db, $cashierId, $payload['shift_id'] ?? null);
        if ($shiftId <= 0) {
            $this->json(['success' => false, 'message' => 'No open shift found.'], 422);
        }

        $label = trim((string)($payload['label'] ?? 'Hold ' . date('H:i:s')));
        $stmt = $db->prepare(
            "INSERT INTO held_transactions (cashier_id, shift_id, label, cart_json, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$cashierId, $shiftId, substr($label, 0, 60), json_encode($payload, JSON_UNESCAPED_UNICODE)]);

        $this->json(['success' => true, 'message' => 'Transaction held.', 'hold_id' => (int)$db->lastInsertId()]);
    }

    public function recalled(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $db = Database::getInstance();
        $cashierId = (int)$this->user()['user_id'];
        $stmt = $db->prepare(
            "SELECT hold_id, label, cart_json, created_at
             FROM held_transactions
             WHERE cashier_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$cashierId]);
        $rows = $stmt->fetchAll();
        $items = array_map(static function (array $row): array {
            return [
                'hold_id' => (int)$row['hold_id'],
                'label' => $row['label'],
                'created_at' => $row['created_at'],
                'cart' => json_decode((string)$row['cart_json'], true),
            ];
        }, $rows);

        $this->json(['success' => true, 'items' => $items]);
    }

    public function deleteHold(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $cashierId = (int)$this->user()['user_id'];
        $stmt = $db->prepare("DELETE FROM held_transactions WHERE hold_id = ? AND cashier_id = ?");
        $stmt->execute([(int)$id, $cashierId]);

        $this->json(['success' => true, 'message' => 'Held transaction removed.']);
    }

    public function reprint(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $db = Database::getInstance();

        $txnStmt = $db->prepare(
            "SELECT t.*, u.full_name AS cashier_name, c.full_name AS customer_name
             FROM transactions t
             JOIN users u ON u.user_id = t.cashier_id
             LEFT JOIN customers c ON c.customer_id = t.customer_id
             WHERE t.transaction_id = ?
             LIMIT 1"
        );
        $txnStmt->execute([(int)$id]);
        $txn = $txnStmt->fetch();
        if (!$txn) {
            $this->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        $itemStmt = $db->prepare(
            "SELECT ti.quantity, ti.unit_price, ti.subtotal, p.product_name
             FROM transaction_items ti
             JOIN products p ON p.product_id = ti.product_id
             WHERE ti.transaction_id = ?"
        );
        $itemStmt->execute([(int)$id]);

        $this->json([
            'success' => true,
            'transaction' => $txn,
            'items' => $itemStmt->fetchAll(),
        ]);
    }

    public function verifyPin(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $payload = $this->requestPayload();
        $managerId = (int)($payload['manager_id'] ?? 0);
        $pin = preg_replace('/\D+/', '', (string)($payload['pin'] ?? ''));

        if ($managerId <= 0 || strlen($pin) !== 6) {
            $this->json(['success' => false, 'message' => 'Invalid manager or PIN.'], 422);
        }

        $valid = Auth::verifyManagerPin($pin, $managerId);
        $this->json([
            'success' => $valid,
            'message' => $valid ? 'PIN verified.' : 'PIN verification failed.',
        ], $valid ? 200 : 422);
    }

    public function void(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $payload = $this->requestPayload();
        $reason = trim((string)($payload['reason'] ?? 'Void by manager'));
        $managerPinUsed = !empty($payload['manager_pin_used']) ? 1 : 0;

        $db = Database::getInstance();
        $txnStmt = $db->prepare("SELECT * FROM transactions WHERE transaction_id = ? LIMIT 1");
        $txnStmt->execute([(int)$id]);
        $txn = $txnStmt->fetch();

        if (!$txn) {
            $this->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }
        if ($txn['status'] !== 'completed') {
            $this->json(['success' => false, 'message' => 'Only completed transactions can be voided.'], 422);
        }

        $itemsStmt = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
        $itemsStmt->execute([(int)$id]);
        $items = $itemsStmt->fetchAll();

        try {
            $db->beginTransaction();

            $voidStmt = $db->prepare(
                "UPDATE transactions
                 SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = NOW()
                 WHERE transaction_id = ?"
            );
            $voidStmt->execute([$reason, (int)$this->user()['user_id'], (int)$id]);

            $updateProduct = $db->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_id = ?");
            $updateBatch = $db->prepare("UPDATE batches SET qty_remaining = qty_remaining + ?, status = 'active' WHERE batch_id = ?");
            $insertMovement = $db->prepare(
                "INSERT INTO stock_movements (
                    product_id, batch_id, branch_id, movement_type, quantity_in, quantity_out,
                    reference_type, reference_id, performed_by, notes, created_at
                ) VALUES (?, ?, ?, 'return', ?, 0, 'void', ?, ?, ?, NOW())"
            );

            foreach ($items as $item) {
                $qty = (int)$item['quantity'];
                $productId = (int)$item['product_id'];
                $batchId = isset($item['batch_id']) ? (int)$item['batch_id'] : null;

                $updateProduct->execute([$qty, $productId]);
                if ($batchId) {
                    $updateBatch->execute([$qty, $batchId]);
                }

                $insertMovement->execute([
                    $productId,
                    $batchId,
                    BRANCH_ID,
                    $qty,
                    (int)$id,
                    (int)$this->user()['user_id'],
                    'Transaction void restock',
                ]);
            }

            $shiftUpdateSql = "UPDATE shifts SET total_voids = total_voids + 1";
            if ($txn['payment_method'] === 'cash') {
                $shiftUpdateSql .= ", total_cash_sales = GREATEST(total_cash_sales - ?, 0)";
                $params = [(float)$txn['total_amount'], (int)$txn['shift_id']];
            } elseif (in_array($txn['payment_method'], ['gcash', 'maya'], true)) {
                $shiftUpdateSql .= ", total_ewallet_sales = GREATEST(total_ewallet_sales - ?, 0)";
                $params = [(float)$txn['total_amount'], (int)$txn['shift_id']];
            } elseif ($txn['payment_method'] === 'card') {
                $shiftUpdateSql .= ", total_card_sales = GREATEST(total_card_sales - ?, 0)";
                $params = [(float)$txn['total_amount'], (int)$txn['shift_id']];
            } else {
                $params = [(int)$txn['shift_id']];
            }
            $shiftUpdateSql .= " WHERE shift_id = ?";
            $shiftStmt = $db->prepare($shiftUpdateSql);
            $shiftStmt->execute($params);

            $voidLogStmt = $db->prepare(
                "INSERT INTO void_logs (transaction_id, voided_by, manager_pin_used, reason, items_json, voided_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $voidLogStmt->execute([
                (int)$id,
                (int)$this->user()['user_id'],
                $managerPinUsed,
                $reason,
                json_encode($items, JSON_UNESCAPED_UNICODE),
            ]);

            $logger = new Logger();
            $logger->log('pos', 'void', (int)$id, ['status' => 'completed'], ['status' => 'voided'], 'Transaction voided.');

            $db->commit();
            $this->json(['success' => true, 'message' => 'Transaction voided and stock restored.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['success' => false, 'message' => 'Void failed: ' . $e->getMessage()], 500);
        }
    }

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input');
        $json = $raw ? json_decode($raw, true) : null;
        if (is_array($json)) {
            return array_merge($_POST, $json);
        }
        return $_POST;
    }

    private function resolveOpenShiftId(PDO $db, int $cashierId, mixed $providedShiftId): int
    {
        $shiftId = (int)$providedShiftId;
        if ($shiftId > 0) {
            $stmt = $db->prepare("SELECT shift_id FROM shifts WHERE shift_id = ? AND status = 'open' LIMIT 1");
            $stmt->execute([$shiftId]);
            $row = $stmt->fetch();
            if ($row) {
                return (int)$row['shift_id'];
            }
        }

        $stmt = $db->prepare(
            "SELECT shift_id
             FROM shifts
             WHERE cashier_id = ? AND status = 'open'
             ORDER BY start_time DESC
             LIMIT 1"
        );
        $stmt->execute([$cashierId]);
        $row = $stmt->fetch();

        return $row ? (int)$row['shift_id'] : 0;
    }

    private function consumeBatch(PDO $db, int $productId, int $qty, PDOStatement $updateBatch): ?int
    {
        $stmt = $db->prepare(
            "SELECT batch_id, qty_remaining
             FROM batches
             WHERE product_id = ? AND status = 'active' AND qty_remaining > 0
             ORDER BY COALESCE(expiration_date, '9999-12-31') ASC, delivery_date ASC
             LIMIT 1"
        );
        $stmt->execute([$productId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return null;
        }

        $batchId = (int)$batch['batch_id'];
        $qtyToDeduct = min($qty, (int)$batch['qty_remaining']);
        if ($qtyToDeduct > 0) {
            $updateBatch->execute([$qtyToDeduct, $qtyToDeduct, $batchId]);
        }

        return $batchId;
    }

    private function applyCustomerPoints(
        PDO $db,
        ?int $customerId,
        int $transactionId,
        int $pointsEarned,
        int $pointsRedeemed,
        float $totalAmount
    ): void {
        if (!$customerId) {
            return;
        }

        $customerStmt = $db->prepare("SELECT points_balance, total_spend, total_visits FROM customers WHERE customer_id = ? LIMIT 1");
        $customerStmt->execute([$customerId]);
        $customer = $customerStmt->fetch();
        if (!$customer) {
            return;
        }

        $currentPoints = (int)$customer['points_balance'];
        $newPoints = max(0, $currentPoints + $pointsEarned - $pointsRedeemed);
        $newSpend = (float)$customer['total_spend'] + $totalAmount;
        $newVisits = (int)$customer['total_visits'] + 1;

        $tier = 'bronze';
        if ($newPoints >= LOYALTY_PLATINUM_THRESHOLD) {
            $tier = 'platinum';
        } elseif ($newPoints >= LOYALTY_GOLD_THRESHOLD) {
            $tier = 'gold';
        } elseif ($newPoints >= LOYALTY_SILVER_THRESHOLD) {
            $tier = 'silver';
        }

        $updateCustomer = $db->prepare(
            "UPDATE customers
             SET points_balance = ?, total_spend = ?, total_visits = ?, last_visit = NOW(), tier = ?
             WHERE customer_id = ?"
        );
        $updateCustomer->execute([$newPoints, round($newSpend, 2), $newVisits, $tier, $customerId]);

        if ($pointsEarned > 0) {
            $insertPoints = $db->prepare(
                "INSERT INTO loyalty_points (customer_id, transaction_id, points_type, points_amount, balance_after, notes, created_at)
                 VALUES (?, ?, 'earned', ?, ?, ?, NOW())"
            );
            $insertPoints->execute([$customerId, $transactionId, $pointsEarned, $newPoints, 'Points earned from checkout']);
        }

        if ($pointsRedeemed > 0) {
            $insertRedeem = $db->prepare(
                "INSERT INTO loyalty_points (customer_id, transaction_id, points_type, points_amount, balance_after, notes, created_at)
                 VALUES (?, ?, 'redeemed', ?, ?, ?, NOW())"
            );
            $insertRedeem->execute([$customerId, $transactionId, -$pointsRedeemed, $newPoints, 'Points redeemed during checkout']);
        }
    }

    private function applyShiftSales(PDO $db, int $shiftId, string $paymentMethod, float $totalAmount): void
    {
        if ($paymentMethod === 'cash') {
            $stmt = $db->prepare("UPDATE shifts SET total_cash_sales = total_cash_sales + ? WHERE shift_id = ?");
            $stmt->execute([$totalAmount, $shiftId]);
            return;
        }

        if (in_array($paymentMethod, ['gcash', 'maya', 'split'], true)) {
            $stmt = $db->prepare("UPDATE shifts SET total_ewallet_sales = total_ewallet_sales + ? WHERE shift_id = ?");
            $stmt->execute([$totalAmount, $shiftId]);
            return;
        }

        $stmt = $db->prepare("UPDATE shifts SET total_card_sales = total_card_sales + ? WHERE shift_id = ?");
        $stmt->execute([$totalAmount, $shiftId]);
    }

    private function generateDocNumber(PDO $db, string $table, string $column, string $prefix): string
    {
        $today = date('Ymd');
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn() + 1;

        return sprintf('%s-%s-%05d', $prefix, $today, $count);
    }
}
