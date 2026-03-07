<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1"><?= e($supplier['supplier_name'] ?? 'Supplier') ?></h5>
        <p class="text-muted small">Supplier Details & Performance Metrics</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (canAccess(['owner', 'manager'])): ?>
            <a href="<?= e(BASE_URL) ?>/suppliers/<?= e((string)$supplier['supplier_id']) ?>/edit" class="btn btn-primary">Edit</a>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>/suppliers" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<!-- Supplier Information -->
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
                        <small class="text-muted">Supplier Code</small>
                        <div class="fw-600"><?= e($supplier['supplier_code'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $badgeClass = match($supplier['status'] ?? '') {
                                'active' => 'text-bg-success',
                                'inactive' => 'text-bg-warning',
                                'archived' => 'text-bg-secondary',
                                default => 'text-bg-light'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($supplier['status'] ?? '')) ?></span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Contact Person</small>
                        <div class="fw-600"><?= e($supplier['contact_person'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Phone</small>
                        <div><a href="tel:<?= e($supplier['phone'] ?? '') ?>"><?= e($supplier['phone'] ?? '-') ?></a></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Email</small>
                        <div><a href="mailto:<?= e($supplier['email'] ?? '') ?>"><?= e($supplier['email'] ?? '-') ?></a></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Address</small>
                        <div><?= e($supplier['address'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Terms -->
        <div class="card card-soft mt-3">
            <div class="card-header">
                <h6 class="mb-0">Payment Terms</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted">Payment Terms</small>
                        <div class="fw-600"><?= e(ucfirst($supplier['payment_terms'] ?? '-')) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Payment Method</small>
                        <div class="fw-600"><?= e(ucfirst(str_replace('_', ' ', $supplier['payment_method'] ?? '-'))) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Bank Name</small>
                        <div><?= e($supplier['bank_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Account Number</small>
                        <div class="font-monospace"><?= e($supplier['account_number'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Tax ID</small>
                        <div><?= e($supplier['tax_id'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Lead Time</small>
                        <div><?= (int)($supplier['lead_time_days'] ?? 0) ?> days</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Summary -->
    <div class="col-lg-4">
        <?php if (isset($performance) && $performance): ?>
            <div class="card card-soft">
                <div class="card-header">
                    <h6 class="mb-0">Performance</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total Orders</small>
                        <div class="h5">
                            <?= (int)($performance['total_orders'] ?? 0) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Total Spent</small>
                        <div class="h5">
                            ₦<?= number_format((float)($performance['total_spent'] ?? 0), 2) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Avg Delivery Days</small>
                        <div class="h5">
                            <?= round((float)($performance['avg_delivery_days'] ?? 0), 1) ?> days
                        </div>
                    </div>
                    
                    <div>
                        <small class="text-muted">Quality Rating</small>
                        <div class="h5">
                            <?php
                            $rating = (float)($performance['avg_quality_rating'] ?? 0);
                            echo $rating > 0 ? round($rating, 2) . ' / 5' : 'No ratings';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card card-soft mt-3">
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted">Created:</span>
                    <div><?= (new DateTime($supplier['created_at'] ?? 'now'))->format('M d, Y') ?></div>
                </div>
                <?php if (isset($supplier['updated_at'])): ?>
                    <div>
                        <span class="text-muted">Last Updated:</span>
                        <div><?= (new DateTime($supplier['updated_at']))->format('M d, Y') ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Purchase History -->
<?php if (!empty($supplier['purchase_history'])): ?>
    <div class="card card-soft">
        <div class="card-header">
            <h6 class="mb-0">Recent Purchase Orders</h6>
        </div>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">PO Number</th>
                        <th style="width: 20%;">Order Date</th>
                        <th style="width: 15%;">Items</th>
                        <th style="width: 15%;" class="text-end">Amount</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($supplier['purchase_history'], 0, 10) as $po): ?>
                        <tr>
                            <td>
                                <code>#<?= e((string)$po['purchase_order_id']) ?></code>
                            </td>
                            <td>
                                <?= (new DateTime($po['order_date'] ?? 'now'))->format('M d, Y') ?>
                            </td>
                            <td>
                                <?= (int)($po['item_count'] ?? 0) ?> items
                            </td>
                            <td class="text-end">
                                ₦<?= number_format((float)($po['total_amount'] ?? 0), 2) ?>
                            </td>
                            <td>
                                <?php
                                $poStatus = $po['status'] ?? '';
                                $badgeClass = match($poStatus) {
                                    'pending' => 'text-bg-warning',
                                    'confirmed' => 'text-bg-info',
                                    'delivered' => 'text-bg-success',
                                    'cancelled' => 'text-bg-danger',
                                    default => 'text-bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($poStatus)) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= e(BASE_URL) ?>/purchase-orders/<?= e((string)$po['purchase_order_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if (count($supplier['purchase_history']) > 10): ?>
        <div class="text-center mt-3">
            <a href="<?= e(BASE_URL) ?>/purchase-orders?supplier_id=<?= e((string)$supplier['supplier_id']) ?>" class="btn btn-sm btn-outline-primary">View All Purchase Orders</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Supplied Products -->
<?php if (!empty($products)): ?>
    <div class="card card-soft mt-4">
        <div class="card-header">
            <h6 class="mb-0">Supplied Products</h6>
        </div>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Product</th>
                        <th style="width: 20%;">Category</th>
                        <th style="width: 15%;">Unit Cost</th>
                        <th style="width: 15%;">Effective Date</th>
                        <th style="width: 20%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($products, 0, 15) as $product): ?>
                        <tr>
                            <td>
                                <strong><?= e($product['product_name'] ?? '') ?></strong>
                                <div class="small text-muted">SKU: <?= e($product['product_code'] ?? '') ?></div>
                            </td>
                            <td>
                                <?= e($product['category_name'] ?? '-') ?>
                            </td>
                            <td class="text-end">
                                ₦<?= number_format((float)($product['unit_cost'] ?? 0), 2) ?>
                            </td>
                            <td>
                                <?= (new DateTime($product['effective_date'] ?? 'now'))->format('M d, Y') ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= e(BASE_URL) ?>/products/<?= e((string)$product['product_id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
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
