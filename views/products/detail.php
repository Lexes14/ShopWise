<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1"><?= e($product['product_name'] ?? 'Product') ?></h5>
        <p class="text-muted small">Product Details & Inventory Information</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (can('products.edit')): ?>
            <a href="<?= e(BASE_URL) ?>/products/<?= e((string)($product['product_id'] ?? '')) ?>/edit" class="btn btn-primary">Edit</a>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>/products" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<!-- Product Information -->
<div class="row g-3 mb-4">
    <!-- Basic Info -->
    <div class="col-lg-8">
        <div class="card card-soft">
            <div class="card-header">
                <h6 class="mb-0">Basic Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted">Product Code</small>
                        <div class="fw-600 font-monospace"><?= e($product['product_code'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $badgeClass = match($product['status'] ?? '') {
                                'active' => 'text-bg-success',
                                'inactive' => 'text-bg-warning',
                                'discontinued' => 'text-bg-danger',
                                default => 'text-bg-light'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($product['status'] ?? '')) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Category</small>
                        <div class="fw-600"><?= e($product['category_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Brand</small>
                        <div><?= e($product['brand_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Unit of Measure</small>
                        <div><?= e(strtoupper($product['unit_of_measure'] ?? 'pc')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Storage Condition</small>
                        <div><?= e(ucfirst($product['storage_condition'] ?? 'dry')) ?></div>
                    </div>
                    <?php if (!empty($product['description'])): ?>
                    <div class="col-12">
                        <small class="text-muted">Description</small>
                        <div><?= e($product['description']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Pricing Information -->
        <div class="card card-soft mt-3">
            <div class="card-header">
                <h6 class="mb-0">Pricing</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Cost Price</small>
                        <div class="fw-600">₱<?= number_format((float)($product['cost_price'] ?? 0), 2) ?></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Selling Price</small>
                        <div class="fw-600 text-success">₱<?= number_format((float)($product['selling_price'] ?? 0), 2) ?></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Wholesale Price</small>
                        <div>₱<?= number_format((float)($product['wholesale_price'] ?? 0), 2) ?></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Min Selling Price</small>
                        <div>₱<?= number_format((float)($product['min_selling_price'] ?? 0), 2) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">VAT Status</small>
                        <div>
                            <?php if (!empty($product['is_vatable'])): ?>
                                <span class="badge text-bg-info">Vatable</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">VAT Exempt</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Markup</small>
                        <div>
                            <?php
                            $costPrice = (float)($product['cost_price'] ?? 0);
                            $sellingPrice = (float)($product['selling_price'] ?? 0);
                            $markup = $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice * 100) : 0;
                            ?>
                            <?= number_format($markup, 2) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Levels -->
        <div class="card card-soft mt-3">
            <div class="card-header">
                <h6 class="mb-0">Stock Levels</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Current Stock</small>
                        <div class="h5 mb-0">
                            <?php
                            $currentStock = (int)($product['current_stock'] ?? 0);
                            $reorderPoint = (int)($product['reorder_point'] ?? 0);
                            $stockClass = $currentStock <= $reorderPoint ? 'text-danger' : 'text-success';
                            ?>
                            <span class="<?= $stockClass ?>"><?= $currentStock ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Minimum Stock</small>
                        <div class="fw-600"><?= (int)($product['minimum_stock'] ?? 0) ?></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Reorder Point</small>
                        <div class="fw-600"><?= (int)($product['reorder_point'] ?? 0) ?></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted">Maximum Stock</small>
                        <div class="fw-600"><?= (int)($product['maximum_stock'] ?? 0) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Reorder Quantity</small>
                        <div><?= (int)($product['reorder_qty'] ?? 0) ?> units</div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Stock Value</small>
                        <div class="fw-600">₱<?= number_format($currentStock * (float)($product['cost_price'] ?? 0), 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="col-lg-4">
        <div class="card card-soft">
            <div class="card-header">
                <h6 class="mb-0">Quick Stats</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Stock Status</small>
                    <div class="h6">
                        <?php
                        $currentStock = (int)($product['current_stock'] ?? 0);
                        $reorderPoint = (int)($product['reorder_point'] ?? 0);
                        $minimumStock = (int)($product['minimum_stock'] ?? 0);
                        
                        if ($currentStock <= 0) {
                            echo '<span class="badge text-bg-danger">OUT OF STOCK</span>';
                        } elseif ($currentStock <= $minimumStock) {
                            echo '<span class="badge text-bg-danger">CRITICAL LOW</span>';
                        } elseif ($currentStock <= $reorderPoint) {
                            echo '<span class="badge text-bg-warning">LOW STOCK</span>';
                        } else {
                            echo '<span class="badge text-bg-success">IN STOCK</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Total Batches</small>
                    <div class="h5">
                        <?= count($batches ?? []) ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Stock Value</small>
                    <div class="h5">
                        ₱<?= number_format($currentStock * (float)($product['cost_price'] ?? 0), 2) ?>
                    </div>
                </div>
                
                <div>
                    <small class="text-muted">Last Updated</small>
                    <div class="small">
                        <?= !empty($product['updated_at']) ? (new DateTime($product['updated_at']))->format('M d, Y h:i A') : 'N/A' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card card-soft mt-3">
            <div class="card-header">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= e(BASE_URL) ?>/products/<?= e((string)($product['product_id'] ?? '')) ?>/batches" class="btn btn-sm btn-outline-primary">View Batches</a>
                <a href="<?= e(BASE_URL) ?>/products/<?= e((string)($product['product_id'] ?? '')) ?>/price-history" class="btn btn-sm btn-outline-primary">Price History</a>
                <a href="<?= e(BASE_URL) ?>/inventory/adjustments/create?product_id=<?= e((string)($product['product_id'] ?? '')) ?>" class="btn btn-sm btn-outline-secondary">Stock Adjustment</a>
            </div>
        </div>
    </div>
</div>

<!-- Product Batches -->
<?php if (!empty($batches)): ?>
    <div class="card card-soft mt-4">
        <div class="card-header">
            <h6 class="mb-0">Product Batches (FEFO)</h6>
        </div>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Batch Code</th>
                        <th style="width: 15%;">Manufacturing</th>
                        <th style="width: 15%;">Expiry Date</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 15%;">Unit Cost</th>
                        <th style="width: 20%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td>
                                <code><?= e($batch['batch_code'] ?? '') ?></code>
                            </td>
                            <td>
                                <?= !empty($batch['manufacturing_date']) ? (new DateTime($batch['manufacturing_date']))->format('M d, Y') : '-' ?>
                            </td>
                            <td>
                                <?php
                                $expiryDate = !empty($batch['expiry_date']) ? new DateTime($batch['expiry_date']) : null;
                                $now = new DateTime();
                                $daysToExpiry = $expiryDate ? $now->diff($expiryDate)->days : 0;
                                $isExpired = $expiryDate && $expiryDate < $now;
                                ?>
                                <?php if ($expiryDate): ?>
                                    <span class="<?= $isExpired ? 'text-danger' : ($daysToExpiry <= 30 ? 'text-warning' : '') ?>">
                                        <?= $expiryDate->format('M d, Y') ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int)($batch['quantity_remaining'] ?? 0) ?>
                            </td>
                            <td>
                                ₱<?= number_format((float)($batch['unit_cost'] ?? 0), 2) ?>
                            </td>
                            <td>
                                <?php
                                if ($isExpired) {
                                    echo '<span class="badge text-bg-danger">Expired</span>';
                                } elseif ($daysToExpiry <= 7) {
                                    echo '<span class="badge text-bg-danger">Expiring Soon</span>';
                                } elseif ($daysToExpiry <= 30) {
                                    echo '<span class="badge text-bg-warning">Near Expiry</span>';
                                } else {
                                    echo '<span class="badge text-bg-success">Active</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Price History -->
<?php if (!empty($priceHistory)): ?>
    <div class="card card-soft mt-4">
        <div class="card-header">
            <h6 class="mb-0">Recent Price Changes</h6>
        </div>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Date</th>
                        <th style="width: 20%;">Old Price</th>
                        <th style="width: 20%;">New Price</th>
                        <th style="width: 35%;">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($priceHistory as $history): ?>
                        <tr>
                            <td>
                                <?= !empty($history['changed_at']) ? (new DateTime($history['changed_at']))->format('M d, Y h:i A') : '-' ?>
                            </td>
                            <td>
                                ₱<?= number_format((float)($history['old_price'] ?? 0), 2) ?>
                            </td>
                            <td>
                                ₱<?= number_format((float)($history['new_price'] ?? 0), 2) ?>
                                <?php
                                $oldPrice = (float)($history['old_price'] ?? 0);
                                $newPrice = (float)($history['new_price'] ?? 0);
                                if ($oldPrice > 0) {
                                    $change = (($newPrice - $oldPrice) / $oldPrice) * 100;
                                    $changeClass = $change > 0 ? 'text-success' : 'text-danger';
                                    echo '<small class="' . $changeClass . '">(' . ($change > 0 ? '+' : '') . number_format($change, 2) . '%)</small>';
                                }
                                ?>
                            </td>
                            <td>
                                <small><?= e($history['reason'] ?? '-') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
