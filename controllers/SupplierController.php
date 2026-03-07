<?php
declare(strict_types=1);

class SupplierController extends ModuleController
{
    protected string $module = 'suppliers';
    protected string $title = 'Supplier Management';
    private SupplierModel $model;
    
    public function __construct()
    {
        $this->model = new SupplierModel();
    }
    
    /**
     * List all suppliers with pagination
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int)$this->get('page', 1);
        $search = $this->get('search', '');
        $status = $this->get('status', '');
        
        $result = $this->model->getAll($page, 20, $search, $status);
        $summary = $this->model->getSummary();
        
        $this->moduleIndex($result['items'], [
            'pagination' => [
                'current' => $result['page'],
                'total' => $result['pages']
            ],
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'status' => $status
            ],
            'suppliers' => $result['items']
        ]);
    }
    
    /**
     * Show supplier details
     */
    public function show(string $id): void
    {
        $this->requireAuth();
        
        $supplierId = (int)$id;
        $supplier = $this->model->getWithHistory($supplierId);
        
        if (!$supplier) {
            $this->error404('Supplier not found.');
        }
        
        $performance = $this->model->getPerformance($supplierId);
        $products = $this->model->getSuppliedProducts($supplierId);
        
        $this->moduleSection('detail', [
            'supplier' => $supplier,
            'performance' => $performance,
            'products' => $products
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(): void
    {
        $this->requireAuth(['owner', 'manager']);
        $this->moduleSection('create');
    }
    
    /**
     * Store new supplier
     */
    public function store(): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $data = [
            'supplier_code' => $this->post('supplier_code', 'SUP-' . strtoupper(substr(md5((string)time()), 0, 6))),
            'supplier_name' => trim((string)$this->post('supplier_name', '')),
            'contact_person' => $this->post('contact_person', ''),
            'email' => $this->post('email', ''),
            'phone' => $this->post('phone', ''),
            'address' => $this->post('address', ''),
            'payment_terms' => $this->post('payment_terms', 'Net 30'),
            'tax_id' => $this->post('tax_id', ''),
            'status' => 'active'
        ];
        
        if (empty($data['supplier_name'])) {
            $this->done('Supplier name is required.', '/suppliers/create');
        }
        
        $result = $this->model->create($data);
        
        if ($result['success']) {
            $this->done('Supplier created successfully.', '/suppliers/' . $result['id']);
        } else {
            $this->done($result['error'] ?? 'Failed to create supplier.', '/suppliers/create');
        }
    }
    
    /**
     * Show edit form
     */
    public function edit(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        
        $supplierId = (int)$id;
        $supplier = $this->model->getById($supplierId);
        
        if (!$supplier) {
            $this->error404('Supplier not found.');
        }
        
        $this->moduleSection('edit', [
            'id' => $supplierId,
            'supplier' => $supplier
        ]);
    }
    
    /**
     * Update supplier
     */
    public function update(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $supplierId = (int)$id;
        $supplier = $this->model->getById($supplierId);
        
        if (!$supplier) {
            $this->done('Supplier not found.', '/suppliers');
        }
        
        $data = [
            'supplier_name' => trim((string)$this->post('supplier_name', $supplier['supplier_name'])),
            'contact_person' => $this->post('contact_person', $supplier['contact_person']),
            'email' => $this->post('email', $supplier['email']),
            'phone' => $this->post('phone', $supplier['phone']),
            'address' => $this->post('address', $supplier['address']),
            'payment_terms' => $this->post('payment_terms', $supplier['payment_terms']),
            'tax_id' => $this->post('tax_id', $supplier['tax_id'])
        ];
        
        $result = $this->model->update($supplierId, $data);
        
        if ($result['success']) {
            $this->done('Supplier updated successfully.', '/suppliers/' . $supplierId);
        } else {
            $this->done($result['error'] ?? 'Failed to update supplier.', '/suppliers/' . $supplierId . '/edit');
        }
    }
    
    /**
     * Archive supplier
     */
    public function archive(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        
        $supplierId = (int)$id;
        $result = $this->model->archive($supplierId);
        
        if ($result['success']) {
            $this->done('Supplier archived successfully.', '/suppliers');
        } else {
            $this->done($result['error'] ?? 'Failed to archive supplier.', '/suppliers');
        }
    }
    
    /**
     * Search suppliers (AJAX)
     */
    public function search(): void
    {
        $this->requireAuth();
        
        $query = $this->get('q', '');
        
        if (strlen($query) < 2) {
            $this->json(['items' => []]);
            return;
        }
        
        $suppliers = $this->model->search($query);
        
        $items = array_map(function($s) {
            return [
                'id' => $s['supplier_id'],
                'name' => $s['supplier_name'],
                'code' => $s['supplier_code'],
                'email' => $s['email'],
                'phone' => $s['phone']
            ];
        }, $suppliers);
        
        $this->json(['items' => $items]);
    }
}
