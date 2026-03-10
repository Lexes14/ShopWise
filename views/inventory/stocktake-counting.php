<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">📊 Stocktake - Recording Counts</h5>
        <p class="text-muted small">Record physical count for each product</p>
    </div>
    <a href="<?= e(BASE_URL) ?>/inventory/stocktake" class="btn btn-outline-secondary btn-sm">← Back to Stocktakes</a>
</div>

<!-- Progress Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Progress</small>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: <?= e((string)$progressPercent) ?>%"></div>
                </div>
                <div class="h6 mb-0"><?= e((string)$countedCount) ?> / <?= e((string)$totalCount) ?> counted (<?= e((string)$progressPercent) ?>%)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Remaining</small>
                <div class="h6 mb-0 text-warning fw-600"><?= e((string)($totalCount - $countedCount)) ?> products</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Est. Time</small>
                <div class="h6 mb-0">~<?= e((string)max(1, ceil(($totalCount - $countedCount) / 20))) ?> min</div>
            </div>
        </div>
    </div>
</div>

<!-- Counting Interface -->
<div class="card card-soft">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Products</h6>
        <small class="text-muted">Scroll to find products</small>
    </div>
    <div class="card-body p-0">
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 35%;">Product Name</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 15%;" class="text-end">System Stock</th>
                        <th style="width: 15%;" class="text-end">Your Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $isCounted = $item['status'] === 'counted';
                        $countedQty = $isCounted ? (int)$item['counted_qty'] : '';
                        ?>
                        <tr class="<?= $isCounted ? 'opacity-50' : '' ?>">
                            <td>
                                <code class="bg-light px-2 py-1 rounded"><?= e($item['product_code']) ?></code>
                            </td>
                            <td>
                                <div class="fw-500"><?= e($item['product_name']) ?></div>
                                <small class="text-muted"><?= e($item['category_name']) ?></small>
                            </td>
                            <td>
                                <span class="badge text-bg-light"><?= e($item['category_name']) ?></span>
                            </td>
                            <td class="text-end fw-600">
                                <?= e((string)$item['current_stock']) ?>
                            </td>
                            <td>
                                <form method="POST" action="<?= e(BASE_URL) ?>/inventory/stocktake/<?= e((string)$stocktakeId) ?>/record" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="product_id" value="<?= e((string)$item['product_id']) ?>">
                                    
                                    <input 
                                        type="number" 
                                        name="counted_quantity" 
                                        class="form-control form-control-sm" 
                                        style="max-width: 100px;"
                                        value="<?= $countedQty ?>"
                                        placeholder="0"
                                        required
                                    >
                                    <button 
                                        type="submit" 
                                        class="btn btn-sm <?= $isCounted ? 'btn-outline-success' : 'btn-primary' ?>"
                                        title="<?= $isCounted ? 'Update count' : 'Record count' ?>"
                                    >
                                        <?= $isCounted ? '✓ Update' : 'Record' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Finalize Section -->
<?php if ($countedCount === $totalCount): ?>
    <div class="alert alert-success mt-4 d-flex align-items-center gap-3">
        <div style="font-size: 24px;">✓</div>
        <div>
            <strong>Counting Complete!</strong>
            <p class="mb-0 mt-1 text-muted">All products have been counted. You can now finalize this stocktake.</p>
        </div>
        <div class="ms-auto">
            <a href="<?= e(BASE_URL) ?>/inventory/stocktake" class="btn btn-success btn-sm">Go to Finalize</a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info mt-4 d-flex align-items-center gap-3">
        <div style="font-size: 24px;">ℹ️</div>
        <div>
            <strong>Still counting...</strong>
            <p class="mb-0 mt-1 text-muted"><?= e((string)($totalCount - $countedCount)) ?> product(s) remaining. Keep recording counts below.</p>
        </div>
    </div>
<?php endif; ?>
