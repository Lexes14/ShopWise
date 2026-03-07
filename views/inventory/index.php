<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Inventory Overview</h5>
        <p class="text-muted small">Monitor stock levels and manage inventory</p>
    </div>
    <div class="btn-group">
        <a href="<?= e(BASE_URL) ?>/inventory/adjustments" class="btn btn-outline-primary btn-sm">📝 Adjustments</a>
        <a href="<?= e(BASE_URL) ?>/inventory/expiring" class="btn btn-outline-warning btn-sm">⚠ Expiring</a>
        <a href="<?= e(BASE_URL) ?>/inventory/stocktake" class="btn btn-outline-info btn-sm">📊 Stocktake</a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:48px; height:48px; background:#1A3C5E; color:white; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:24px;">📦</div>
                <div>
                    <div class="text-muted small">Total Products</div>
                    <div class="h5 mb-0"><?= e((string)($summary['total_products'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:48px; height:48px; background:#28A745; color:white; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:24px;">✓</div>
                <div>
                    <div class="text-muted small">In Stock</div>
                    <div class="h5 mb-0"><?= e((string)(($summary['total_products'] ?? 0) - ($summary['out_of_stock'] ?? 0) - ($summary['low_stock'] ?? 0))) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:48px; height:48px; background:#FFC107; color:white; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:24px;">⚠</div>
                <div>
                    <div class="text-muted small">Low Stock</div>
                    <div class="h5 mb-0"><?= e((string)($summary['low_stock'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:48px; height:48px; background:#DC3545; color:white; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:24px;">✗</div>
                <div>
                    <div class="text-muted small">Out of Stock</div>
                    <div class="h5 mb-0"><?= e((string)($summary['out_of_stock'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expiry Alerts -->
<?php if (($expiry_alerts['critical'] ?? 0) > 0 || ($expiry_alerts['urgent'] ?? 0) > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
        <div style="font-size: 24px;">⚠️</div>
        <div class="flex-grow-1">
            <strong>Expiry Alert:</strong>
            <?php
            $alerts = [];
            if ($expiry_alerts['expired'] > 0) $alerts[] = $expiry_alerts['expired'] . ' expired';
            if ($expiry_alerts['critical'] > 0) $alerts[] = $expiry_alerts['critical'] . ' expiring in 7 days';
            if ($expiry_alerts['urgent'] > 0) $alerts[] = $expiry_alerts['urgent'] . ' expiring in 14 days';
            echo implode(', ', $alerts);
            ?>
        </div>
        <a href="<?= e(BASE_URL) ?>/inventory/expiring" class="btn btn-sm btn-warning">View Details</a>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/inventory" class="row g-3">
            <div class="col-sm-6 col-md-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Product name or code..." value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label small">Stock Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="ok" <?php if (($filters['status'] ?? '') === 'ok') echo 'selected'; ?>>In Stock</option>
                    <option value="low" <?php if (($filters['status'] ?? '') === 'low') echo 'selected'; ?>>Low Stock</option>
                    <option value="out" <?php if (($filters['status'] ?? '') === 'out') echo 'selected'; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card card-soft">
    <?php if (!empty($records)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Code</th>
                        <th style="width: 30%;">Product Name</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 12%;" class="text-end">Current Stock</th>
                        <th style="width: 12%;" class="text-end">Reorder Point</th>
                        <th style="width: 16%;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $item): ?>
                        <tr>
                            <td>
                                <code class="bg-light px-2 py-1 rounded"><?= e($item['product_code']) ?></code>
                            </td>
                            <td>
                                <?php if (can('products.edit')): ?>
                                    <a href="<?= e(BASE_URL) ?>/products/<?= e((string)$item['product_id']) ?>/edit" class="text-decoration-none fw-500">
                                        <?= e($item['product_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="fw-500">
                                        <?= e($item['product_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge text-bg-light"><?= e($item['category_name']) ?></span>
                            </td>
                            <td class="text-end fw-600">
                                <?php
                                $stock = (int)$item['current_stock'];
                                $displayStock = $stock;
                                if ($item['batch_count'] > 0) {
                                    $displayStock .= ' (' . (int)$item['batch_count'] . ' batches)';
                                }
                                ?>
                                <?= e((string)$displayStock) ?>
                            </td>
                            <td class="text-end">
                                <?= e((string)$item['reorder_point']) ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $stock = (int)$item['current_stock'];
                                $reorder = (int)$item['reorder_point'];
                                
                                if ($stock === 0) {
                                    $statusBadge = '<span class="badge text-bg-danger">Out of Stock</span>';
                                    $statusIcon = '✗';
                                } elseif ($stock <= $reorder) {
                                    $statusBadge = '<span class="badge text-bg-warning text-dark">Low Stock</span>';
                                    $statusIcon = '⚠';
                                } else {
                                    $statusBadge = '<span class="badge text-bg-success">OK</span>';
                                    $statusIcon = '✓';
                                }
                                ?>
                                <div><?= $statusBadge ?></div>
                                <?php if ($item['next_expiry']): ?>
                                    <small class="text-muted d-block mt-1">Expires: <?= e(date('M d, Y', strtotime($item['next_expiry']))) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (($pagination['pages'] ?? 1) > 1): ?>
            <div class="card-footer d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/inventory?page=1<?php if ($filters['search']) echo '&search=' . urlencode($filters['search']); if ($filters['status']) echo '&status=' . $filters['status']; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/inventory?page=<?= e((string)($pagination['page'] - 1)) ?><?php if ($filters['search']) echo '&search=' . urlencode($filters['search']); if ($filters['status']) echo '&status=' . $filters['status']; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item active">
                            <span class="page-link">Page <?= e((string)$pagination['page']) ?> of <?= e((string)$pagination['pages']) ?></span>
                        </li>
                        
                        <?php if ($pagination['page'] < $pagination['pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/inventory?page=<?= e((string)($pagination['page'] + 1)) ?><?php if ($filters['search']) echo '&search=' . urlencode($filters['search']); if ($filters['status']) echo '&status=' . $filters['status']; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(BASE_URL) ?>/inventory?page=<?= e((string)$pagination['pages']) ?><?php if ($filters['search']) echo '&search=' . urlencode($filters['search']); if ($filters['status']) echo '&status=' . $filters['status']; ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">📭</div>
            <p class="text-muted">No inventory records found matching your filters.</p>
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
    
    .table-wrap {
        overflow-x: auto;
    }
    
    .btn-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
</style>
