<?php
$batches = $records ?? [];
$alerts = $extra['expiry_alerts'] ?? [];
?>

<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Expiring Batches</h5>
        <p class="text-muted small">Monitor and manage batches approaching expiration</p>
    </div>
</div>

<!-- Expiry Alert Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-danger">⚠ EXPIRED</div>
                <div class="h5"><?= ($alerts['expired'] ?? 0) ?></div>
                <div class="text-muted small">Already past expiry</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-danger">🔴 CRITICAL</div>
                <div class="h5"><?= ($alerts['critical'] ?? 0) ?></div>
                <div class="text-muted small">Expires in &lt;7 days</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-warning">🟠 URGENT</div>
                <div class="h5"><?= ($alerts['urgent'] ?? 0) ?></div>
                <div class="text-muted small">Expires in &lt;14 days</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-soft text-center">
            <div class="card-body">
                <div class="h4 mb-1 text-info">🟡 WARNING</div>
                <div class="h5"><?= ($alerts['warning'] ?? 0) ?></div>
                <div class="text-muted small">Expires in &lt;30 days</div>
            </div>
        </div>
    </div>
</div>

<!-- Expiring Batches Table -->
<div class="card card-soft">
    <?php if (!empty($batches)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Product</th>
                        <th style="width: 12%;">Batch #</th>
                        <th style="width: 10%;" class="text-end">Qty</th>
                        <th style="width: 12%;">Exp. Date</th>
                        <th style="width: 10%;" class="text-center">Days Left</th>
                        <th style="width: 15%;">Severity</th>
                        <th style="width: 20%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td>
                                <strong><?= e($batch['product_name']) ?></strong>
                                <div class="small text-muted">SKU: <?= e($batch['product_code']) ?></div>
                            </td>
                            <td>
                                <code><?= e((string)$batch['batch_number']) ?></code>
                            </td>
                            <td class="text-end">
                                <?= e((string)$batch['qty_remaining']) ?> units
                            </td>
                            <td>
                                <?php
                                $expDate = new DateTime($batch['expiration_date']);
                                echo $expDate->format('M d, Y');
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $daysLeft = (int)($batch['days_left'] ?? 0);
                                $dayClass = match(true) {
                                    $daysLeft < 0 => 'text-danger fw-700',
                                    $daysLeft < 7 => 'text-danger fw-700',
                                    $daysLeft < 14 => 'text-warning fw-700',
                                    $daysLeft < 30 => 'text-info fw-700',
                                    default => 'text-success'
                                };
                                ?>
                                <span class="<?= $dayClass ?>">
                                    <?php if ($daysLeft < 0): ?>
                                        EXPIRED (<?= abs($daysLeft) ?> days ago)
                                    <?php else: ?>
                                        <?= $daysLeft ?> days
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $severity = $batch['severity'] ?? '';
                                $badgeClass = match($severity) {
                                    'expired' => 'text-bg-danger',
                                    'critical' => 'text-bg-danger',
                                    'urgent' => 'text-bg-warning',
                                    'warning' => 'text-bg-info',
                                    default => 'text-bg-secondary'
                                };
                                $severityLabel = match($severity) {
                                    'expired' => 'EXPIRED ⚠',
                                    'critical' => 'CRITICAL 🔴',
                                    'urgent' => 'URGENT 🟠',
                                    'warning' => 'WARNING 🟡',
                                    default => 'UNKNOWN'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $severityLabel ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($daysLeft >= 0 && canAccess(['owner', 'manager'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#promotionModal<?= e((string)$batch['batch_id']) ?>">📢</button>
                                <?php endif; ?>
                                
                                <?php if ($daysLeft <= 14): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#discardModal<?= e((string)$batch['batch_id']) ?>">🗑</button>
                                <?php endif; ?>

                                <?php if ($daysLeft >= 0 && canAccess(['owner', 'manager'])): ?>
                                <!-- Mark as Sold Modal -->
                                <div class="modal fade" id="promotionModal<?= e((string)$batch['batch_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Create Promotion?</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Create a clearance promotion for batch <strong><?= e((string)$batch['batch_number']) ?></strong> to boost sales before expiry?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= e(BASE_URL) ?>/promotions/create?batch_id=<?= e((string)$batch['batch_id']) ?>" class="btn btn-sm btn-primary">Create Promotion</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Discard Modal -->
                                <div class="modal fade" id="discardModal<?= e((string)$batch['batch_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Discard Batch?</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Create waste adjustment for <strong><?= e((string)$batch['qty_remaining']) ?> units</strong> of batch <strong><?= e((string)$batch['batch_number']) ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="<?= e(BASE_URL) ?>/inventory/adjustments/submit" style="display:inline;">
                                                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                    <input type="hidden" name="product_id" value="<?= e((string)$batch['product_id']) ?>">
                                                    <input type="hidden" name="adjustment_type" value="removal">
                                                    <input type="hidden" name="quantity" value="<?= e((string)$batch['qty_remaining']) ?>">
                                                    <input type="hidden" name="reason" value="Expired batch discard - Batch #<?= e((string)$batch['batch_number']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Discard</button>
                                                </form>
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
    <?php else: ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3" style="font-size: 48px;">✓</div>
            <p class="text-muted">No expiring batches found!</p>
            <p class="small text-muted">All batches are well within their shelf life.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Stats -->
<div class="card card-soft mt-4">
    <div class="card-body">
        <h6 class="mb-3">Expiry Action Plan</h6>
        <ul class="mb-0 list-unstyled">
            <li class="mb-2"><strong class="text-danger">Expired:</strong> Immediately discard with waste adjustment</li>
            <li class="mb-2"><strong class="text-danger">Critical (&lt;7 days):</strong> Create clearance promotion or prepare for disposal</li>
            <li class="mb-2"><strong class="text-warning">Urgent (&lt;14 days):</strong> Increase shelf visibility and consider discounts</li>
            <li class="mb-2"><strong class="text-info">Warning (&lt;30 days):</strong> Monitor sales closely, plan promotional activities</li>
        </ul>
    </div>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
