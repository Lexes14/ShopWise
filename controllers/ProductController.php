<?php
declare(strict_types=1);

class ProductController extends ModuleController
{
    protected string $module = 'products';
    protected string $title = 'Product Management';
    private ProductModel $model;

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int)$this->get('page', 1);
        $search = trim((string)$this->get('search', ''));
        $category = (int)$this->get('category', 0);
        $supplier = (int)$this->get('supplier', 0);
        $status = trim((string)$this->get('status', ''));
        
        $data = $this->model->getAll($page, 50, $search, $category, $supplier, $status);
        $categories = $this->model->getCategories();
        $suppliers = $this->model->getSuppliers();
        $inventory = $this->model->getInventorySummary();
        
        $this->moduleIndex($data['records'], [
            'pagination' => [
                'page' => $data['page'],
                'pages' => $data['pages'],
                'total' => $data['total'],
            ],
            'categories' => $categories,
            'suppliers' => $suppliers,
            'inventory' => $inventory,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'supplier' => $supplier,
                'status' => $status,
            ],
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        
        $productId = (int)$id;
        $product = $this->model->getWithBatches($productId);
        
        if (!$product) {
            $this->error404('Product not found.');
        }
        
        $priceHistory = $this->model->getPriceHistory($productId, 10);
        
        $this->moduleSection('detail', [
            'product' => $product,
            'batches' => $product['batches'] ?? [],
            'priceHistory' => $priceHistory
        ]);
    }

    public function create(): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        $categories = $this->model->getCategories();
        $brands = $this->model->getBrands();
        $suppliers = $this->model->getSuppliers();
        
        $this->moduleSection('create', [
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers
        ]);
    }

    public function store(): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        Auth::csrfVerify();

        $productCode = trim((string)$this->post('product_code', ''));
        $productName = trim((string)$this->post('product_name', ''));
        $categoryId = (int)$this->post('category_id', 0);
        $sellingPrice = (float)$this->post('selling_price', 0);

        if ($productCode === '' || $productName === '' || $categoryId <= 0 || $sellingPrice <= 0) {
            $this->done('Product code, name, category, and selling price are required.', '/products/create');
        }

        $productId = $this->model->create([
            'product_code' => $productCode,
            'product_name' => $productName,
            'product_alias' => $this->post('product_alias', null),
            'category_id' => $categoryId,
            'brand_id' => (int)$this->post('brand_id', 0),
            'primary_supplier_id' => (int)$this->post('primary_supplier_id', 0),
            'secondary_supplier_id' => (int)$this->post('secondary_supplier_id', 0),
            'unit_of_measure' => $this->post('unit_of_measure', 'pc'),
            'storage_condition' => $this->post('storage_condition', 'dry'),
            'description' => $this->post('description', null),
            'is_vatable' => (int)$this->post('is_vatable', 1),
            'cost_price' => (float)$this->post('cost_price', 0),
            'selling_price' => $sellingPrice,
            'wholesale_price' => (float)$this->post('wholesale_price', 0),
            'min_selling_price' => (float)$this->post('min_selling_price', 0),
            'minimum_stock' => (int)$this->post('minimum_stock', 10),
            'reorder_point' => (int)$this->post('reorder_point', 20),
            'reorder_qty' => (int)$this->post('reorder_qty', 50),
            'maximum_stock' => (int)$this->post('maximum_stock', 500),
            'current_stock' => (int)$this->post('current_stock', 0),
        ], (int)$this->user()['user_id']);

        if (!$productId) {
            $this->done('Product code already exists.', '/products/create');
        }

        $logger = new Logger();
        $logger->log('products', 'create', $productId, null, ['product_code' => $productCode, 'product_name' => $productName], 'Product created.');

        $this->done('Product created successfully.', '/products');
    }

    public function edit(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        $product = $this->model->getById((int)$id);
        if (!$product) {
            $this->error404('Product not found.');
        }

        $categories = $this->model->getCategories();
        $brands = $this->model->getBrands();
        $suppliers = $this->model->getSuppliers();

        $this->moduleSection('edit', [
            'id' => (int)$id,
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth(['owner', 'manager', 'inventory_staff']);
        Auth::csrfVerify();

        $productId = (int)$id;
        $product = $this->model->getById($productId);
        if (!$product) {
            $this->done('Product not found.', '/products');
        }

        $this->model->update($productId, array_merge(
            [
                'product_name' => trim((string)$this->post('product_name', $product['product_name'])),
                'product_alias' => $this->post('product_alias', $product['product_alias']),
                'category_id' => (int)$this->post('category_id', $product['category_id']),
                'brand_id' => (int)$this->post('brand_id', $product['brand_id']),
                'primary_supplier_id' => (int)$this->post('primary_supplier_id', $product['primary_supplier_id']),
                'secondary_supplier_id' => (int)$this->post('secondary_supplier_id', $product['secondary_supplier_id']),
                'unit_of_measure' => $this->post('unit_of_measure', $product['unit_of_measure']),
                'storage_condition' => $this->post('storage_condition', $product['storage_condition']),
                'description' => $this->post('description', $product['description']),
                'is_vatable' => (int)$this->post('is_vatable', $product['is_vatable']),
                'cost_price' => (float)$this->post('cost_price', $product['cost_price']),
                'selling_price' => (float)$this->post('selling_price', $product['selling_price']),
                'wholesale_price' => (float)$this->post('wholesale_price', $product['wholesale_price']),
                'min_selling_price' => (float)$this->post('min_selling_price', $product['min_selling_price']),
                'minimum_stock' => (int)$this->post('minimum_stock', $product['minimum_stock']),
                'reorder_point' => (int)$this->post('reorder_point', $product['reorder_point']),
                'reorder_qty' => (int)$this->post('reorder_qty', $product['reorder_qty']),
                'maximum_stock' => (int)$this->post('maximum_stock', $product['maximum_stock']),
                'status' => $this->post('status', $product['status']),
                'price_change_reason' => $this->post('price_change_reason', 'Manual update'),
            ],
            ['changed_by' => (int)$this->user()['user_id']]
        ));

        $logger = new Logger();
        $logger->log('products', 'update', $productId, ['old' => $product], ['new_product_name' => $this->post('product_name')], 'Product updated.');

        $this->done('Product #' . $productId . ' updated.', '/products');
    }

    public function archive(string $id): void
    {
        $this->requireAuth(['owner', 'manager']);
        Auth::csrfVerify();
        $this->model->archive((int)$id);

        $logger = new Logger();
        $logger->log('products', 'archive', (int)$id, null, ['status' => 'inactive'], 'Product archived.');

        $this->done('Product #' . (int)$id . ' archived.', '/products');
    }

    public function batches(string $id): void
    {
        $this->requireAuth();
        $product = $this->model->getWithBatches((int)$id);
        if (!$product) {
            $this->error404('Product not found.');
        }
        
        $this->moduleSection('batches', [
            'id' => (int)$id,
            'product' => $product,
            'batches' => $product['batches'] ?? []
        ]);
    }

    public function search(): void
    {
        $this->requireAuth();
        $q = trim((string)$this->get('q', ''));
        if ($q === '') {
            $this->json(['items' => []]);
        }

        $items = array_map(static function (array $row): array {
            return [
                'product_id' => (int)$row['product_id'],
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'],
                'selling_price' => (float)$row['selling_price'],
                'cost_price' => (float)$row['cost_price'],
                'current_stock' => (int)$row['current_stock'],
                'id' => (int)$row['product_id'],
                'label' => $row['product_code'] . ' · ' . $row['product_name'] . ' (₱' . number_format((float)$row['selling_price'], 2) . ')',
                'stock' => (int)$row['current_stock'],
            ];
        }, $this->model->search($q, 20));

        $this->json(['items' => $items]);
    }

    public function priceHistory(string $id): void
    {
        $this->requireAuth();
        $product = $this->model->getById((int)$id);
        if (!$product) {
            $this->error404('Product not found.');
        }
        
        $history = $this->model->getPriceHistory((int)$id, 30);
        $this->moduleSection('price-history', [
            'id' => (int)$id,
            'product' => $product,
            'history' => $history
        ]);
    }
}
