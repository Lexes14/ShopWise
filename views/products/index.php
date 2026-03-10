<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Product Catalog</h5>
        <p class="text-muted small">Manage and view all products</p>
    </div>
    <?php if (can('products.create')): ?>
        <a href="<?= e(BASE_URL) ?>/products/create" class="btn btn-primary">+ Add Product</a>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-primary text-white rounded" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:24px;">📦</div>
                <div>
                    <div class="text-muted small">Total Products</div>
                    <div class="h5 mb-0"><?= e((string)($inventory['total_products'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-success text-white rounded" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:24px;">✓</div>
                <div>
                    <div class="text-muted small">In Stock</div>
                    <div class="h5 mb-0"><?= e((string)(($inventory['total_products'] ?? 0) - ($inventory['out_of_stock'] ?? 0) - ($inventory['low_stock'] ?? 0))) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-warning text-white rounded" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:24px;">⚠</div>
                <div>
                    <div class="text-muted small">Low Stock</div>
                    <div class="h5 mb-0"><?= e((string)($inventory['low_stock'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-danger text-white rounded" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:24px;">✗</div>
                <div>
                    <div class="text-muted small">Out of Stock</div>
                    <div class="h5 mb-0"><?= e((string)($inventory['out_of_stock'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/products" class="row g-3">
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Product name or code..." value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select">
                    <option value="0">All Categories</option>
                    <?php foreach (($categories ?? []) as $cat): ?>
                        <option value="<?= e((string)$cat['category_id']) ?>" <?php if (($filters['category'] ?? 0) == $cat['category_id']) echo 'selected'; ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label small">Supplier</label>
                <select name="supplier" class="form-select">
                    <option value="0">All Suppliers</option>
                    <?php foreach (($suppliers ?? []) as $sup): ?>
                        <option value="<?= e((string)$sup['supplier_id']) ?>" <?php if (($filters['supplier'] ?? 0) == $sup['supplier_id']) echo 'selected'; ?>>
                            <?= e($sup['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php if (($filters['status'] ?? '') === 'active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if (($filters['status'] ?? '') === 'inactive') echo 'selected'; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card card-soft">
    <?php if (!empty($records)): ?>
        <div class="table-w rap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">Code</th>
                        <th style="width: 22%;">Product Name</th>
                        <th style="width: 12%;">Category</th>
                        <th style="width: 14%;">Supplier</th>
                        <th style="width: 9%;" class="text-end">Cost</th>
                        <th style="width: 9%;" class="text-end">Price</th>
                        <th style="width: 10%;" class="text-end">Stock</th>
                        <th style="width: 14%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $product): ?>
                        <tr>
                            <td>
                                <code class="bg-light px-2 py-1 rounded"><?= e($product['product_code']) ?></code>
                            </td>
                            <td>
                                <div class="fw-500"><?= e($product['product_name']) ?></div>
                            </td>
                            <td>
                                <span class="badge text-bg-light"><?= e($product['category_name']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($product['primary_supplier_name'])): ?>
                                    <span class="text-muted small">🏢 <?= e($product['primary_supplier_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                ₱<?= e(number_format((float)$product['cost_price'], 2)) ?>
                            </td>
                            <td class="text-end fw-600">
                                ₱<?= e(number_format((float)$product['selling_price'], 2)) ?>
                            </td>
                            <td class="text-end">
                                <?php 
                                $stock = (int)$product['current_stock'];
                                $reorder = (int)$product['reorder_point'];
                                $stockClass = 'text-success';
                                if ($stock === 0) {
                                    $stockClass = 'text-danger fw-600';
                                } elseif ($stock <= $reorder) {
                                    $stockClass = 'text-warning fw-600';
                                }
                                ?>
                                <span class="<?= $stockClass ?>"><?= e((string)$stock) ?> units</span>
                            </td>
                            <td class="text-center">
                                <?php if (can('products.edit')): ?>
                                    <div class="btn-group" role="group">
                                        <a href="<?= e(BASE_URL) ?>/products/<?= e((string)$product['product_id']) ?>/edit" class="btn btn-sm btn-outline-primary" title="Edit">✎</a>
                                        <?php if (can('products.archive')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= e((string)$product['product_id']) ?>" title="Delete">🗑</button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Delete Modal (Owner/Manager only) -->
                                    <?php if (can('products.archive')): ?>
                                    <div class="modal fade" id="deleteModal<?= e((string)$product['product_id']) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Archive Product?</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Archive <strong><?= e($product['product_name']) ?></strong>? This cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="<?= e(BASE_URL) ?>/products/<?= e((string)$product['product_id']) ?>/archive" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Archive</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (($pagination['pages'] ?? 1) > 1): ?>
            <?php
            // Build filter query string
            $filterParams = [];
            if ($filters['search']) $filterParams[] = 'search=' . urlencode($filters['search']);
            if ($filters['category']) $filterParams[] = 'category=' . $filters['category'];
            if ($filters['supplier']) $filterParams[] = 'supplier=' . $filters['supplier'];
            if ($filters['status']) $filterParams[] = 'status=' . $filters['status'];
            $filterQuery = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';
            ?>
            <div class="card-footer d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/products?page=1<?= $filterQuery ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/products?page=<?= e((string)($pagination['page'] - 1)) ?><?= $filterQuery ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item active">
                            <span class="page-link">Page <?= e((string)$pagination['page']) ?> of <?= e((string)$pagination['pages']) ?></span>
                        </li>
                        
                        <?php if ($pagination['page'] < $pagination['pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/products?page=<?= e((string)($pagination['page'] + 1)) ?><?= $filterQuery ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/products?page=<?= e((string)$pagination['pages']) ?><?= $filterQuery ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">📭</div>
            <p class="text-muted">No products found. Create one to get started.</p>
            <?php if (can('products.create')): ?>
                <a href="<?= e(BASE_URL) ?>/products/create" class="btn btn-primary btn-sm">+ Add Product</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    
    .kpi-icon {
        flex-shrink: 0;
    }
    
    .table-wrap {
        overflow-x: auto;
    }
    
    .btn-group {
        display: flex;
        gap: 0.25rem;
    }
    
    .btn-group .btn {
        flex: 1;
    }
</style>
