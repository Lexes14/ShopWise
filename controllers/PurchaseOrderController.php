<?php
declare(strict_types=1);

class PurchaseOrderController extends ModuleController
{
    protected string $module = 'purchase_orders';
    protected string $title = 'Purchase Orders';
    private PurchaseOrderModel $poModel;
    private SupplierModel $supplierModel;
    private ProductModel $productModel;
    
    public function __construct()
    {
        $this->poModel = new PurchaseOrderModel();
        $this->supplierModel = new SupplierModel();
        $this->productModel = new ProductModel();
    }
    
    /**
     * List all purchase orders with filters
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int)$this->get('page', 1);
        $search = $this->get('search', '');
        $status = $this->get('status', '');
        $supplier_id = (int)$this->get('supplier_id', 0);
        
        $result = $this->poModel->getAll($page, 20, $search, $status, $supplier_id);
        $summary = $this->poModel->getSummary();
        $overdue = $this->poModel->getOverdue();
        
        $this->moduleIndex($result['items'], [
            'pagination' => [
                'page' => $result['page'],
                'pages' => $result['pages'],
                'total' => $result['total']
            ],
            'extra' => [
                'summary' => $summary,
                'overdue' => count($overdue),
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'supplier_id' => $supplier_id
                ]
            ]
        ]);
    }
    
    /**
     * Show purchase order detail
     */
    public function show(string $id): void
    {
        $this->requireAuth();
        
        $po_id = (int)$id;
        $po = $this->poModel->getById($po_id);
        
        if (!$po) {
            $this->error404('Purchase order not found.');
        }
        
        $items = $this->poModel->getItems($po_id);
        
        $this->moduleSection('show', [
            'po' => $po,
            'items' => $items
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $suppliers = $this->supplierModel->search('');
        $this->moduleSection('create', [
            'suppliers' => $suppliers
        ]);
    }
    
    /**
     * Store new purchase order
     */
    public function store(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $data = [
            'supplier_id' => (int)$this->post('supplier_id', 0),
            'po_date' => $this->post('po_date', date('Y-m-d')),
            'expected_delivery_date' => $this->post('expected_delivery_date', null),
            'delivery_location' => $this->post('delivery_location', ''),
            'notes' => $this->post('notes', '')
        ];
        
        if ($data['supplier_id'] <= 0) {
            $this->done('Supplier is required.', '/purchase-orders/create');
        }
        
        $result = $this->poModel->create($data);
        
        if ($result['success']) {
            $this->done("PO {$result['po_number']} created successfully.", '/purchase-orders/' . $result['id'] . '/edit');
        } else {
            $this->done($result['error'] ?? 'Failed to create PO.', '/purchase-orders/create');
        }
    }
    
    /**
     * Show edit form (for adding items)
     */
    public function edit(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        
        $po_id = (int)$id;
        $po = $this->poModel->getById($po_id);
        
        if (!$po) {
            $this->error404('Purchase order not found.');
        }
        
        $items = $this->poModel->getItems($po_id);
        $products = $this->productModel->search('');
        
        $this->moduleSection('edit', [
            'id' => $po_id,
            'po' => $po,
            'items' => $items,
            'products' => $products
        ]);
    }
    
    /**
     * Update purchase order details
     */
    public function update(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $data = [
            'expected_delivery_date' => $this->post('expected_delivery_date', null),
            'delivery_location' => $this->post('delivery_location', ''),
            'notes' => $this->post('notes', '')
        ];
        
        $result = $this->poModel->update($po_id, $data);
        
        if ($result['success']) {
            $this->done('Purchase order updated.', '/purchase-orders/' . $po_id . '/edit');
        } else {
            $this->done($result['error'] ?? 'Failed to update PO.', '/purchase-orders/' . $po_id . '/edit');
        }
    }
    
    /**
     * Add item to PO (AJAX)
     */
    public function addItem(): void
    {
        $this->requireAuth(['owner', 'manager']);
        
        $po_id = (int)$this->post('po_id', 0);
        $product_id = (int)$this->post('product_id', 0);
        $quantity = (int)$this->post('quantity', 0);
        $unit_price = (float)$this->post('unit_price', 0);
        
        if ($po_id <= 0 || $product_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        $result = $this->poModel->addItem($po_id, $product_id, $quantity, $unit_price);
        $this->json($result);
    }
    
    /**
     * Remove item from PO
     */
    public function removeItem(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_item_id = (int)$id;
        $result = $this->poModel->removeItem($po_item_id);
        
        if ($result['success']) {
            $this->done('Item removed.', $_SERVER['HTTP_REFERER'] ?? '/purchase-orders');
        } else {
            $this->done('Failed to remove item.', $_SERVER['HTTP_REFERER'] ?? '/purchase-orders');
        }
    }
    
    /**
     * Submit PO for approval
     */
    public function submit(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $result = $this->poModel->submit($po_id);
        
        if ($result['success']) {
            $this->done('PO submitted for approval.', '/purchase-orders/' . $po_id);
        } else {
            $this->done($result['error'] ?? 'Failed to submit PO.', '/purchase-orders/' . $po_id);
        }
    }
    
    /**
     * Approve purchase order
     */
    public function approve(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $result = $this->poModel->approve($po_id);
        
        if ($result['success']) {
            $this->done('PO approved.', '/purchase-orders/' . $po_id);
        } else {
            $this->done('Failed to approve PO.', '/purchase-orders/' . $po_id);
        }
    }
    
    /**
     * Reject purchase order
     */
    public function reject(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $reason = $this->post('reason', '');
        $result = $this->poModel->reject($po_id, $reason);
        
        if ($result['success']) {
            $this->done('PO rejected.', '/purchase-orders/' . $po_id);
        } else {
            $this->done('Failed to reject PO.', '/purchase-orders/' . $po_id);
        }
    }
    
    /**
     * Mark as ordered/sent to supplier
     */
    public function markOrdered(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $result = $this->poModel->markOrdered($po_id);
        
        if ($result['success']) {
            $this->done('PO marked as ordered.', '/purchase-orders/' . $po_id);
        } else {
            $this->done('Failed to mark PO as ordered.', '/purchase-orders/' . $po_id);
        }
    }
    
    /**
     * Mark as received
     */
    public function markReceived(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $po_id = (int)$id;
        $received_date = $this->post('received_date', null);
        $result = $this->poModel->markReceived($po_id, $received_date);
        
        if ($result['success']) {
            $this->done('PO marked as received.', '/purchase-orders/' . $po_id);
        } else {
            $this->done('Failed to mark PO as received.', '/purchase-orders/' . $po_id);
        }
    }
    
    /**
     * Search POs (AJAX)
     */
    public function search(): void
    {
        $this->requireAuth();
        
        $query = $this->get('q', '');
        
        if (strlen($query) < 2) {
            $this->json(['items' => []]);
            return;
        }
        
        $pos = $this->poModel->search($query);
        
        $items = array_map(function($po) {
            return [
                'id' => $po['po_id'],
                'number' => $po['po_number'],
                'supplier' => $po['supplier_name'],
                'status' => $po['status']
            ];
        }, $pos);
        
        $this->json(['items' => $items]);
    }
}
