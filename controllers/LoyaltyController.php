<?php
declare(strict_types=1);

class LoyaltyController extends ModuleController
{
    protected string $module = 'loyalty';
    protected string $title = 'Loyalty';

    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT customer_id, full_name, phone, points_balance, tier, status
             FROM customers
             ORDER BY points_balance DESC, full_name ASC
             LIMIT 100"
        );
        $this->moduleIndex($stmt->fetchAll());
    }

    public function detail(string $id): void
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $customerStmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ? LIMIT 1");
        $customerStmt->execute([(int)$id]);
        $customer = $customerStmt->fetch();
        if (!$customer) {
            $this->error404('Customer not found.');
        }

        $pointsStmt = $db->prepare(
            "SELECT points_type, points_amount, balance_after, notes, created_at
             FROM loyalty_points
             WHERE customer_id = ?
             ORDER BY created_at DESC
             LIMIT 100"
        );
        $pointsStmt->execute([(int)$id]);

        $this->moduleSection('detail', ['extra' => [
            'id' => (int)$id,
            'customer' => $customer,
            'points_history' => $pointsStmt->fetchAll(),
        ]]);
    }

    public function register(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $fullName = trim((string)$this->post('full_name', ''));
        $phone = trim((string)$this->post('phone', ''));
        if ($fullName === '' || $phone === '') {
            $this->done('Full name and phone are required.', '/loyalty');
        }

        $db = Database::getInstance();
        $exists = $db->prepare("SELECT COUNT(*) FROM customers WHERE phone = ?");
        $exists->execute([$phone]);
        if ((int)$exists->fetchColumn() > 0) {
            $this->done('Phone number is already registered.', '/loyalty');
        }

        $stmt = $db->prepare(
            "INSERT INTO customers (
                full_name, phone, email, birthday, address, tier,
                points_balance, total_spend, total_visits, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'bronze', 0, 0, 0, 'active', NOW())"
        );
        $stmt->execute([
            $fullName,
            $phone,
            $this->post('email', null),
            $this->post('birthday', null),
            $this->post('address', null),
        ]);

        $customerId = (int)$db->lastInsertId();
        $logger = new Logger();
        $logger->log('loyalty', 'register', $customerId, null, ['full_name' => $fullName, 'phone' => $phone], 'Loyalty customer registered.');

        $this->done('Customer registered successfully.', '/loyalty/' . $customerId);
    }

    public function search(): void
    {
        $this->requireAuth();
        $q = trim((string)$this->get('q', ''));
        if ($q === '') {
            $this->json(['items' => []]);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT customer_id, full_name, phone, points_balance
             FROM customers
             WHERE full_name LIKE ? OR phone LIKE ?
             ORDER BY full_name ASC
             LIMIT 20"
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);

        $items = array_map(static fn(array $row): array => [
            'id' => (int)$row['customer_id'],
            'label' => $row['full_name'] . ' · ' . $row['phone'] . ' (' . (int)$row['points_balance'] . ' pts)',
        ], $stmt->fetchAll());

        $this->json(['items' => $items]);
    }
}
