<?php
declare(strict_types=1);

class ShiftController extends ModuleController
{
    protected string $module = 'shifts';
    protected string $title = 'Shift Management';

    public function history(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier', 'bookkeeper']);
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT s.shift_id, s.shift_label, s.start_time, s.end_time, s.opening_cash, s.closing_cash,
                    s.expected_cash, s.cash_variance, s.status, u.full_name AS cashier_name
             FROM shifts s
             JOIN users u ON u.user_id = s.cashier_id
             ORDER BY s.start_time DESC
             LIMIT 150"
        );
        $this->moduleIndex($stmt->fetchAll(), ['section' => 'history']);
    }

    public function open(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY start_time DESC LIMIT 1");
        $stmt->execute([(int)$this->user()['user_id']]);
        $openShift = $stmt->fetch();

        $this->moduleSection('open', ['extra' => ['open_shift' => $openShift]]);
    }

    public function close(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY start_time DESC LIMIT 1");
        $stmt->execute([(int)$this->user()['user_id']]);
        $openShift = $stmt->fetch();

        $this->moduleSection('close', ['extra' => ['open_shift' => $openShift]]);
    }

    public function detail(string $id): void
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT s.*, u.full_name AS cashier_name, m.full_name AS manager_name
             FROM shifts s
             JOIN users u ON u.user_id = s.cashier_id
             LEFT JOIN users m ON m.user_id = s.manager_id
             WHERE s.shift_id = ?
             LIMIT 1"
        );
        $stmt->execute([(int)$id]);
        $shift = $stmt->fetch();

        if (!$shift) {
            $this->error404('Shift not found.');
        }

        $txStmt = $db->prepare(
            "SELECT transaction_number, total_amount, payment_method, created_at
             FROM transactions
             WHERE shift_id = ?
             ORDER BY created_at DESC"
        );
        $txStmt->execute([(int)$id]);

        $this->moduleSection('detail', ['extra' => [
            'id' => (int)$id,
            'shift' => $shift,
            'transactions' => $txStmt->fetchAll(),
        ]]);
    }

    public function start(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $cashierId = (int)$this->user()['user_id'];

        $existingStmt = $db->prepare("SELECT shift_id FROM shifts WHERE cashier_id = ? AND status = 'open' LIMIT 1");
        $existingStmt->execute([$cashierId]);
        if ($existingStmt->fetch()) {
            $this->done('You already have an open shift.', '/shifts/open');
        }

        $shiftLabel = (string)$this->post('shift_label', 'Morning');
        $allowedLabels = ['Morning', 'Afternoon', 'Evening', 'Graveyard', 'Custom'];
        if (!in_array($shiftLabel, $allowedLabels, true)) {
            $shiftLabel = 'Custom';
        }

        $customLabel = trim((string)$this->post('custom_label', ''));
        $openingCash = max(0, (float)$this->post('opening_cash', POS_SHIFT_FLOAT));

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO shifts (
                    branch_id, cashier_id, shift_label, custom_label, start_time,
                    opening_cash, total_cash_sales, total_ewallet_sales, total_card_sales,
                    total_voids, total_refunds, status
                ) VALUES (?, ?, ?, ?, NOW(), ?, 0, 0, 0, 0, 0, 'open')"
            );
            $stmt->execute([
                BRANCH_ID,
                $cashierId,
                $shiftLabel,
                $shiftLabel === 'Custom' ? substr($customLabel, 0, 50) : null,
                round($openingCash, 2),
            ]);

            $shiftId = (int)$db->lastInsertId();

            $cashStmt = $db->prepare(
                "INSERT INTO cash_movements (shift_id, cashier_id, movement_type, amount, notes, recorded_by, created_at)
                 VALUES (?, ?, 'open', ?, ?, ?, NOW())"
            );
            $cashStmt->execute([$shiftId, $cashierId, round($openingCash, 2), 'Opening cash float', $cashierId]);

            $logger = new Logger();
            $logger->log('shifts', 'start', $shiftId, null, ['opening_cash' => round($openingCash, 2)], 'Shift opened.');

            $db->commit();
            $this->done('Shift opened successfully.', '/shifts/open');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->done('Failed to open shift: ' . $e->getMessage(), '/shifts/open');
        }
    }

    public function end(): void
    {
        $this->requireAuth(['owner', 'manager', 'cashier']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $cashierId = (int)$this->user()['user_id'];
        $closingCash = max(0, (float)$this->post('closing_cash', 0));
        $denominationJson = $this->post('denomination_json', null);
        $notes = trim((string)$this->post('notes', ''));

        $shiftStmt = $db->prepare("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY start_time DESC LIMIT 1");
        $shiftStmt->execute([$cashierId]);
        $shift = $shiftStmt->fetch();
        if (!$shift) {
            $this->done('No open shift found.', '/shifts/close');
        }

        $shiftId = (int)$shift['shift_id'];
        $expectedCash = round((float)$shift['opening_cash'] + (float)$shift['total_cash_sales'], 2);
        $variance = round($closingCash - $expectedCash, 2);

        try {
            $db->beginTransaction();

            $updateStmt = $db->prepare(
                "UPDATE shifts
                 SET end_time = NOW(),
                     closing_cash = ?,
                     expected_cash = ?,
                     cash_variance = ?,
                     denomination_json = ?,
                     notes = ?,
                     status = 'closed'
                 WHERE shift_id = ?"
            );
            $updateStmt->execute([
                round($closingCash, 2),
                $expectedCash,
                $variance,
                $denominationJson ? (string)$denominationJson : null,
                $notes !== '' ? $notes : null,
                $shiftId,
            ]);

            $cashStmt = $db->prepare(
                "INSERT INTO cash_movements (shift_id, cashier_id, movement_type, amount, notes, recorded_by, created_at)
                 VALUES (?, ?, 'close', ?, ?, ?, NOW())"
            );
            $cashStmt->execute([$shiftId, $cashierId, round($closingCash, 2), 'Shift close', $cashierId]);

            $logger = new Logger();
            $logger->log('shifts', 'end', $shiftId, ['status' => 'open'], [
                'status' => 'closed',
                'closing_cash' => round($closingCash, 2),
                'variance' => $variance,
            ], 'Shift closed.');

            $db->commit();
            $this->done('Shift closed successfully.', '/shifts');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->done('Failed to close shift: ' . $e->getMessage(), '/shifts/close');
        }
    }

    public function verify(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $db = Database::getInstance();
        $managerId = (int)$this->user()['user_id'];
        $stmt = $db->prepare(
            "UPDATE shifts
             SET manager_verified = 1, manager_id = ?
             WHERE shift_id = ?"
        );
        $stmt->execute([$managerId, (int)$id]);

        $logger = new Logger();
        $logger->log('shifts', 'verify', (int)$id, null, ['manager_verified' => 1], 'Shift manager verification.');

        $this->done('Shift #' . (int)$id . ' verified.', '/shifts');
    }
}
