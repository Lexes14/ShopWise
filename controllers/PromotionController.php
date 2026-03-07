<?php
declare(strict_types=1);

class PromotionController extends ModuleController
{
    protected string $module = 'promotions';
    protected string $title = 'Promotions';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT promo_id, promo_name, promo_type, status, start_datetime, end_datetime
             FROM promotions
             ORDER BY start_datetime DESC
             LIMIT 100"
        );
        $this->moduleIndex($stmt->fetchAll());
    }

    public function create(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();
        $products = $db->query("SELECT product_id, product_name FROM products WHERE status='active' ORDER BY product_name LIMIT 200")->fetchAll();
        $categories = $db->query("SELECT category_id, category_name FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();
        $this->moduleSection('create', ['extra' => ['products' => $products, 'categories' => $categories]]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        $db = Database::getInstance();
        $promoStmt = $db->prepare("SELECT * FROM promotions WHERE promo_id = ? LIMIT 1");
        $promoStmt->execute([(int)$id]);
        $promo = $promoStmt->fetch();
        if (!$promo) {
            $this->error404('Promotion not found.');
        }

        $products = $db->query("SELECT product_id, product_name FROM products WHERE status='active' ORDER BY product_name LIMIT 200")->fetchAll();
        $categories = $db->query("SELECT category_id, category_name FROM categories WHERE status='active' ORDER BY category_name")->fetchAll();

        $this->moduleSection('edit', ['extra' => [
            'id' => (int)$id,
            'promotion' => $promo,
            'products' => $products,
            'categories' => $categories,
        ]]);
    }

    public function store(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $name = trim((string)$this->post('promo_name', ''));
        $type = (string)$this->post('promo_type', 'price_discount');
        $applicableTo = (string)$this->post('applicable_to', 'all');
        if ($name === '') {
            $this->done('Promotion name is required.', '/promotions/create');
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO promotions (
                    promo_name, promo_type, description, discount_pct, discount_amount,
                    min_qty, free_qty, threshold_amount, threshold_discount,
                    start_datetime, end_datetime, applicable_to, created_by, status,
                    ai_generated, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())"
            );
            $stmt->execute([
                $name,
                $type,
                $this->post('description', null),
                $this->nullableDecimal($this->post('discount_pct', null)),
                $this->nullableDecimal($this->post('discount_amount', null)),
                $this->nullableInt($this->post('min_qty', null)),
                $this->nullableInt($this->post('free_qty', null)),
                $this->nullableDecimal($this->post('threshold_amount', null)),
                $this->nullableDecimal($this->post('threshold_discount', null)),
                $this->post('start_datetime', date('Y-m-d H:i:s')),
                $this->post('end_datetime', date('Y-m-d H:i:s', strtotime('+7 days'))),
                $applicableTo,
                (int)$this->user()['user_id'],
                $this->post('status', 'active'),
            ]);

            $promoId = (int)$db->lastInsertId();
            $this->syncPromotionTargets($db, $promoId, $applicableTo);

            $logger = new Logger();
            $logger->log('promotions', 'create', $promoId, null, ['promo_name' => $name], 'Promotion created.');

            $db->commit();
            $this->done('Promotion created.', '/promotions');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->done('Promotion create failed: ' . $e->getMessage(), '/promotions/create');
        }
    }

    public function update(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $promoId = (int)$id;
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "UPDATE promotions
                 SET promo_name = ?, promo_type = ?, description = ?, discount_pct = ?, discount_amount = ?,
                     min_qty = ?, free_qty = ?, threshold_amount = ?, threshold_discount = ?,
                     start_datetime = ?, end_datetime = ?, applicable_to = ?, status = ?
                 WHERE promo_id = ?"
            );
            $applicableTo = (string)$this->post('applicable_to', 'all');
            $stmt->execute([
                $this->post('promo_name', ''),
                $this->post('promo_type', 'price_discount'),
                $this->post('description', null),
                $this->nullableDecimal($this->post('discount_pct', null)),
                $this->nullableDecimal($this->post('discount_amount', null)),
                $this->nullableInt($this->post('min_qty', null)),
                $this->nullableInt($this->post('free_qty', null)),
                $this->nullableDecimal($this->post('threshold_amount', null)),
                $this->nullableDecimal($this->post('threshold_discount', null)),
                $this->post('start_datetime', date('Y-m-d H:i:s')),
                $this->post('end_datetime', date('Y-m-d H:i:s', strtotime('+7 days'))),
                $applicableTo,
                $this->post('status', 'active'),
                $promoId,
            ]);

            $this->syncPromotionTargets($db, $promoId, $applicableTo);

            $logger = new Logger();
            $logger->log('promotions', 'update', $promoId, null, ['status' => $this->post('status', 'active')], 'Promotion updated.');

            $db->commit();
            $this->done('Promotion #' . $promoId . ' updated.', '/promotions');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->done('Promotion update failed: ' . $e->getMessage(), '/promotions/' . $promoId . '/edit');
        }
    }

    public function archive(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE promotions SET status = 'inactive' WHERE promo_id = ?");
        $stmt->execute([(int)$id]);

        $logger = new Logger();
        $logger->log('promotions', 'archive', (int)$id, null, ['status' => 'inactive'], 'Promotion archived.');

        $this->done('Promotion #' . (int)$id . ' archived.', '/promotions');
    }

    private function syncPromotionTargets(PDO $db, int $promoId, string $applicableTo): void
    {
        $db->prepare("DELETE FROM promotion_products WHERE promo_id = ?")->execute([$promoId]);
        $db->prepare("DELETE FROM promotion_categories WHERE promo_id = ?")->execute([$promoId]);

        if ($applicableTo === 'product') {
            $products = $this->post('product_ids', []);
            if (!is_array($products)) {
                $products = [];
            }
            $stmt = $db->prepare("INSERT IGNORE INTO promotion_products (promo_id, product_id) VALUES (?, ?)");
            foreach ($products as $productId) {
                $pid = (int)$productId;
                if ($pid > 0) {
                    $stmt->execute([$promoId, $pid]);
                }
            }
        }

        if ($applicableTo === 'category') {
            $categories = $this->post('category_ids', []);
            if (!is_array($categories)) {
                $categories = [];
            }
            $stmt = $db->prepare("INSERT IGNORE INTO promotion_categories (promo_id, category_id) VALUES (?, ?)");
            foreach ($categories as $categoryId) {
                $cid = (int)$categoryId;
                if ($cid > 0) {
                    $stmt->execute([$promoId, $cid]);
                }
            }
        }
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return round((float)$value, 2);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}
