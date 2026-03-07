<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Stock Adjustments</h5>
        <p class="text-muted small">Request and approve inventory adjustments</p>
    </div>
    <?php if (can('inventory.submitAdjustment')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdjustmentModal">+ New Adjustment</button>
    <?php endif; ?>
</div>

<!-- Adjustment Status Badges -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-warning">⏳</div>
                <div class="h5">Pending</div>
                <div class="text-muted small">
                    <?php
                    $pending = 0;
                    foreach ($records as $r) {
                        if (($r['status'] ?? '') === 'pending') $pending++;
                    }
                    echo $pending;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-success">✓</div>
                <div class="h5">Approved</div>
                <div class="text-muted small">
                    <?php
                    $approved = 0;
                    foreach ($records as $r) {
                        if (($r['status'] ?? '') === 'approved') $approved++;
                    }
                    echo $approved;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-danger">✗</div>
                <div class="h5">Rejected</div>
                <div class="text-muted small">
                    <?php
                    $rejected = 0;
                    foreach ($records as $r) {
                        if (($r['status'] ?? '') === 'rejected') $rejected++;
                    }
                    echo $rejected;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/inventory/adjustments" class="row g-3">
            <div class="col-sm-6 col-md-6">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php if (($filters['status'] ?? '') === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="approved" <?php if (($filters['status'] ?? '') === 'approved') echo 'selected'; ?>>Approved</option>
                    <option value="rejected" <?php if (($filters['status'] ?? '') === 'rejected') echo 'selected'; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-6">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Adjustments Table -->
<div class="card card-soft">
    <?php if (!empty($records)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 25%;">Product</th>
                        <th style="width: 12%;">Type</th>
                        <th style="width: 10%;" class="text-end">Qty</th>
                        <th style="width: 15%;">Reason</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 20%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $adj): ?>
                        <tr>
                            <td>
                                <code>#<?= e((string)$adj['adjustment_id']) ?></code>
                            </td>
                            <td>
                                <?= e($adj['product_name']) ?>
                                <div class="small text-muted">By: <?= e($adj['requested_by']) ?></div>
                            </td>
                            <td>
                                <span class="badge text-bg-info"><?= e(ucfirst((string)$adj['adjustment_type'])) ?></span>
                            </td>
                            <td class="text-end fw-600">
                                <?php
                                $qty = (int)$adj['quantity'];
                                $sign = $qty > 0 ? '+' : '';
                                ?>
                                <span class="<?= $qty > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= e($sign) ?><?= e((string)$qty) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= e(substr($adj['reason'], 0, 40)) ?><?php if (strlen($adj['reason']) > 40) echo '...'; ?></small>
                            </td>
                            <td>
                                <?php
                                $status = $adj['status'] ?? '';
                                $badgeClass = match($status) {
                                    'pending' => 'text-bg-warning',
                                    'approved' => 'text-bg-success',
                                    'rejected' => 'text-bg-danger',
                                    default => 'text-bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($status)) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($status === 'pending' && can('inventory.approveAdjustment')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= e((string)$adj['adjustment_id']) ?>">✓</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= e((string)$adj['adjustment_id']) ?>">✗</button>
                                    
                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?= e((string)$adj['adjustment_id']) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Approve Adjustment?</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Approve adjustment of <strong><?= e((string)$qty) ?> units</strong> for <strong><?= e($adj['product_name']) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="<?= e(BASE_URL) ?>/inventory/<?= e((string)$adj['adjustment_id']) ?>/approve" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?= e((string)$adj['adjustment_id']) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Reject Adjustment?</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Reject this adjustment for <strong><?= e($adj['product_name']) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="<?= e(BASE_URL) ?>/inventory/<?= e((string)$adj['adjustment_id']) ?>/reject" style="display:inline;">
                                                        <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">📭</div>
            <p class="text-muted">No adjustments found.</p>
        </div>
    <?php endif; ?>
</div>

<!-- New Adjustment Modal -->
<?php if (can('inventory.submitAdjustment')): ?>
<div class="modal fade" id="newAdjustmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Stock Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= e(BASE_URL) ?>/inventory/adjustments/submit">
                <div class="modal-body">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Product *</label>
                        <input type="text" class="form-control" id="productSearch" placeholder="Search product...">
                        <input type="hidden" name="product_id" id="productId">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Adjustment Type *</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="">Select type</option>
                            <option value="addition">Addition (In)</option>
                            <option value="removal">Removal (Out)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Quantity *</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Reason *</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Damage, expiration, theft, correction, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>

<script>
// Product search functionality
document.getElementById('productSearch').addEventListener('input', function(e) {
    let query = e.target.value.trim();
    if (query.length < 2) return;
    
    fetch('<?= e(BASE_URL) ?>/products/search?q=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            // Simple implementation - you can enhance this with a dropdown
            if (data.items && data.items.length > 0) {
                let item = data.items[0];
                document.getElementById('productId').value = item.id;
            }
        });
});
</script>
