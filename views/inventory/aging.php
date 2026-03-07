<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Aging Products</h5>
        <p class="text-muted small">Products with no recent sales activity</p>
    </div>
</div>

<!-- Aging Analysis -->
<div class="alert alert-info mb-4">
    <strong>📊 No Sales Analysis:</strong> Products listed have not been sold in the last 90 days. These represent tied-up capital and storage costs. Consider clearance promotions, bundling, or archiving.
</div>

<!-- Aging Products Table -->
<div class="card card-soft">
    <?php if (!empty($products)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Product</th>
                        <th style="width: 12%;">Category</th>
                        <th style="width: 12%;">Current Stock</th>
                        <th style="width: 12%;">Unit Cost</th>
                        <th style="width: 12%;">Tied-Up Value</th>
                        <th style="width: 15%;">Last Sale</th>
                        <th style="width: 20%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stockQty = (int)$product['current_stock'];
                        $unitCost = (float)($product['cost_price'] ?? 0);
                        $tiedUpValue = $stockQty * $unitCost;
                        $lastSaleDate = new DateTime($product['last_sale_date'] ?? 'now');
                        $daysSinceSale = (new DateTime())->diff($lastSaleDate)->days;
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($product['product_name']) ?></strong>
                                <div class="small text-muted">SKU: <?= e($product['product_code']) ?></div>
                            </td>
                            <td>
                                <span class="badge text-bg-secondary"><?= e($product['category_name']) ?></span>
                            </td>
                            <td class="text-end">
                                <strong><?= e((string)$stockQty) ?></strong> units
                            </td>
                            <td class="text-end">
                                ₦<?= number_format($unitCost, 2) ?>
                            </td>
                            <td class="text-end">
                                <strong class="text-danger">₦<?= number_format($tiedUpValue, 2) ?></strong>
                            </td>
                            <td>
                                <?= $lastSaleDate->format('M d, Y') ?>
                                <div class="small text-muted"><?= $daysSinceSale ?> days ago</div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#promotionModal<?= e((string)$product['product_id']) ?>" title="Create Clearance Promotion">📢</button>
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#bundleModal<?= e((string)$product['product_id']) ?>" title="Bundle with Other Products">📦</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#archiveModal<?= e((string)$product['product_id']) ?>" title="Archive Product">📋</button>
                                
                                <!-- Create Promotion Modal -->
                                <div class="modal fade" id="promotionModal<?= e((string)$product['product_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Create Clearance Promotion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="<?= e(BASE_URL) ?>/promotions/store">
                                                <div class="modal-body">
                                                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                    <input type="hidden" name="product_id" value="<?= e((string)$product['product_id']) ?>">
                                                    <input type="hidden" name="promotion_type" value="clearance">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-600">Promotion Name *</label>
                                                        <input type="text" name="promotion_name" class="form-control" placeholder="e.g., Clearance Sale - <?= e($product['product_name']) ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-600">Discount Percentage *</label>
                                                        <div class="input-group">
                                                            <input type="number" name="discount_percentage" class="form-control" min="1" max="100" required>
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-600">Duration (Days) *</label>
                                                        <input type="number" name="duration_days" class="form-control" min="1" value="30" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Create Promotion</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bundle Modal -->
                                <div class="modal fade" id="bundleModal<?= e((string)$product['product_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Create Bundle Promotion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-muted">Bundling aging products with popular items can increase sales. Open promotions module to create a bundle offer including this product with complementary items.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= e(BASE_URL) ?>/promotions/create?type=bundle&product_id=<?= e((string)$product['product_id']) ?>" class="btn btn-primary">Open Promotions</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Archive Modal -->
                                <div class="modal fade" id="archiveModal<?= e((string)$product['product_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Archive Product?</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-muted">Archive <strong><?= e($product['product_name']) ?></strong>?</p>
                                                <p class="small text-danger">Note: Product must have zero stock before archiving.</p>
                                                <?php if ($stockQty > 0): ?>
                                                    <div class="alert alert-warning mb-0">⚠ Current stock: <strong><?= $stockQty ?> units</strong></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <?php if ($stockQty === 0): ?>
                                                    <form method="POST" action="<?= e(BASE_URL) ?>/products/<?= e((string)$product['product_id']) ?>/archive" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning">Archive</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-warning" disabled>Archive (Clear Stock First)</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="card-footer bg-light">
            <div class="row text-center">
                <div class="col-sm-4">
                    <div class="small text-muted">Total Aging Products</div>
                    <div class="h5 mb-0"><?= count($products) ?></div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-muted">Total Units</div>
                    <div class="h5 mb-0"><?= array_sum(array_map(fn($p) => (int)$p['current_stock'], $products)) ?></div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-muted">Total Tied-Up Value</div>
                    <div class="h5 mb-0 text-danger">
                        ₦<?= number_format(
                            array_sum(array_map(
                                fn($p) => ((int)$p['current_stock'] * (float)($p['cost_price'] ?? 0)),
                                $products
                            )),
                            2
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">✓</div>
            <p class="text-muted">No aging products found!</p>
            <p class="small text-muted">All products are selling regularly.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
