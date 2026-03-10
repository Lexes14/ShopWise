<?php
declare(strict_types=1);

class InventoryController extends ModuleController
{
    protected string $module = 'inventory';
    protected string $title = 'Inventory Management';
    private InventoryModel $model;

    public function __construct()
    {
        $this->model = new InventoryModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int)$this->get('page', 1);
        $search = trim((string)$this->get('search', ''));
        $status = trim((string)$this->get('status', ''));
        
        $data = $this->model->getInventory($page, 50, $search, $status);
        $summary = $this->model->getInventorySummary();
        $expiryAlerts = $this->model->getExpiryAlerts();
        
        $this->moduleIndex($data['records'], [
            'pagination' => [
                'page' => $data['page'],
                'pages' => $data['pages'],
                'total' => $data['total'],
            ],
            'summary' => $summary,
            'expiry_alerts' => $expiryAlerts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function adjustments(): void
    {
        $this->requireAuth();
        
        $page = (int)$this->get('page', 1);
        $status = trim((string)$this->get('status', ''));
        
        $data = $this->model->getAdjustments($page, 50, $status);
        
        $this->moduleIndex($data['records'], [
            'section' => 'adjustments',
            'pagination' => [
                'page' => $data['page'],
                'pages' => $data['pages'],
                'total' => $data['total'],
            ],
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function submitAdjustment(): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        Auth::csrfVerify();

        $productId = (int)$this->post('product_id', 0);
        $qty = (int)$this->post('quantity', 0);
        $type = (string)$this->post('adjustment_type', 'correction');
        $reason = trim((string)$this->post('reason', ''));

        if ($productId <= 0 || $qty === 0 || $reason === '') {
            $this->done('Product, quantity, and reason are required.', '/inventory/adjustments');
        }

        $adjustmentId = $this->model->createAdjustment(
            $productId,
            $type,
            $qty,
            $reason,
            (int)$this->user()['user_id']
        );

        if (!$adjustmentId) {
            $this->done('Failed to submit adjustment.', '/inventory/adjustments');
        }

        $logger = new Logger();
        $logger->log('inventory', 'submit_adjustment', $adjustmentId, null, [
            'product_id' => $productId,
            'quantity' => $qty,
            'type' => $type,
        ], 'Stock adjustment submitted.');

        $this->done('Stock adjustment submitted for approval.', '/inventory/adjustments');
    }

    public function approveAdjustment(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        if ($this->model->approveAdjustment((int)$id, (int)$this->user()['user_id'])) {
            $logger = new Logger();
            $logger->log('inventory', 'approve_adjustment', (int)$id, null, [], 'Stock adjustment approved.');
            $this->done('Adjustment #' . (int)$id . ' approved.', '/inventory/adjustments');
        } else {
            $this->done('Failed to approve adjustment.', '/inventory/adjustments');
        }
    }

    public function rejectAdjustment(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        if ($this->model->rejectAdjustment((int)$id, (int)$this->user()['user_id'])) {
            $logger = new Logger();
            $logger->log('inventory', 'reject_adjustment', (int)$id, null, [], 'Stock adjustment rejected.');
            $this->done('Adjustment #' . (int)$id . ' rejected.', '/inventory/adjustments');
        } else {
            $this->done('Failed to reject adjustment.', '/inventory/adjustments');
        }
    }

    public function expiring(): void
    {
        $this->requireAuth();
        
        $expiringBatches = $this->model->getExpiringBatches(30, 100);
        $expiryAlerts = $this->model->getExpiryAlerts();
        
        $this->moduleIndex($expiringBatches, [
            'section' => 'expiring',
            'expiry_alerts' => $expiryAlerts,
        ]);
    }

    public function checkExpiryAlerts(): void
    {
        $this->requireAuth();
        $alerts = $this->model->getExpiryAlerts();
        $this->json($alerts);
    }

    public function aging(): void
    {
        $this->requireAuth();
        
        $agingProducts = $this->model->getAgingProducts(90, 100);
        
        $this->moduleIndex($agingProducts, [
            'section' => 'aging',
        ]);
    }

    public function stocktake(): void
    {
        $this->requireAuth();
        
        $stocktakesData = $this->model->getStocktakes();
        
        $this->moduleIndex([], [
            'section' => 'stocktake',
            'activeStocktakes' => $stocktakesData['active'] ?? [],
            'completedStocktakes' => $stocktakesData['completed'] ?? [],
        ]);
    }

    public function createStocktake(): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        Auth::csrfVerify();

        $stocktakeId = $this->model->createStocktake((int)$this->user()['user_id']);

        if (!$stocktakeId) {
            $this->done('Failed to create stocktake.', '/inventory/stocktake');
        }

        $logger = new Logger();
        $logger->log('inventory', 'create_stocktake', $stocktakeId, null, [], 'Stocktake session created.');

        $this->done('Stocktake #' . $stocktakeId . ' created.', '/inventory/stocktake');
    }

    public function recordCount(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        Auth::csrfVerify();

        $stocktakeId = (int)$id;
        $redirect = '/inventory/stocktake/' . $stocktakeId . '/count';
        $respondJson = $this->isAjax();
        $productId = (int)$this->post('product_id', 0);
        $countedQty = (int)$this->post('counted_quantity', 0);

        if ($productId <= 0 || $countedQty < 0) {
            if ($respondJson) {
                $this->json(['success' => false, 'message' => 'Invalid product or quantity.'], 400);
            }
            $this->done('Invalid product or quantity.', $redirect, 'danger');
            return;
        }

        $currentUserId = (int)($this->user()['user_id'] ?? 0);

        if ($this->model->recordStocktakeCount($stocktakeId, $productId, $countedQty, $currentUserId > 0 ? $currentUserId : null)) {
            if ($respondJson) {
                $this->json(['success' => true, 'message' => 'Count recorded.']);
            }
            $this->done('Count recorded.', $redirect, 'success');
            return;
        }

        if ($respondJson) {
            $this->json(['success' => false, 'message' => 'Failed to record count.'], 500);
        }
        $this->done('Failed to record count.', $redirect, 'danger');
    }

    public function finalizeStocktake(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();

        $currentUserId = (int)($this->user()['user_id'] ?? 0);

        if ($this->model->finalizeStocktake((int)$id, $currentUserId > 0 ? $currentUserId : null)) {
            $logger = new Logger();
            $logger->log('inventory', 'finalize_stocktake', (int)$id, null, [], 'Stocktake finalized.');
            $this->done('Stocktake #' . (int)$id . ' finalized.', '/inventory/stocktake');
        } else {
            $this->done('Failed to finalize stocktake.', '/inventory/stocktake');
        }
    }

    public function countingPage(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        
        $stocktakeId = (int)$id;
        $items = $this->model->getStocktakeItems($stocktakeId);
        
        if (empty($items)) {
            $this->done('Stocktake not found or has no products.', '/inventory/stocktake');
            return;
        }
        
        // Count stated vs pending
        $countedCount = count(array_filter($items, fn($item) => $item['status'] === 'counted'));
        $totalCount = count($items);
        
        $this->render('inventory/stocktake-counting', [
            'stocktakeId' => $stocktakeId,
            'items' => $items,
            'countedCount' => $countedCount,
            'totalCount' => $totalCount,
            'progressPercent' => $totalCount > 0 ? round(($countedCount / $totalCount) * 100) : 0,
            'csrf' => Auth::csrfGenerate(),
        ]);
    }

    public function shelves(): void
    {
        $this->requireAuth();
        
        $shelves = $this->model->getShelves(100);
        
        $this->moduleIndex($shelves, [
            'section' => 'shelves',
        ]);
    }
}
