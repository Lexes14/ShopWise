<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Physical Stocktake</h5>
        <p class="text-muted small">Conduct and manage physical inventory counts</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStocktakeModal">+ New Stocktake</button>
</div>

<!-- Active Stocktakes -->
<?php if (!empty($activeStocktakes)): ?>
    <h6 class="mb-3 text-muted">Active Stocktakes</h6>
    <div class="row g-3 mb-4">
        <?php foreach ($activeStocktakes as $stocktake): ?>
            <?php
            $totalProducts = (int)($stocktake['total_products'] ?? 0);
            $countedProducts = (int)($stocktake['counted_products'] ?? 0);
            $progressPercent = $totalProducts > 0 ? round(($countedProducts / $totalProducts) * 100) : 0;
            ?>
            <div class="col-md-6">
                <div class="card card-soft">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1"><?= e($stocktake['location']) ?> - <?= e($stocktake['created_by']) ?></h6>
                                <small class="text-muted">Started <?= (new DateTime($stocktake['created_at']))->format('M d, Y H:i') ?></small>
                            </div>
                            <span class="badge text-bg-warning">IN PROGRESS</span>
                        </div>
                        
                        <!-- Progress -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <small>Progress</small>
                                <small class="fw-600"><?= $countedProducts ?> / <?= $totalProducts ?> products</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $progressPercent ?>% complete</small>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <small class="text-muted">Remaining</small>
                                <div class="h6 mb-0"><?= ($totalProducts - $countedProducts) ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Est. Time</small>
                                <div class="h6 mb-0">~<?= max(1, ceil(($totalProducts - $countedProducts) / 20)) ?> min</div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="d-grid gap-2">
                            <a href="<?= e(BASE_URL) ?>/inventory/stocktake/<?= e((string)$stocktake['stocktake_id']) ?>/count" class="btn btn-sm btn-primary">Continue Counting</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#finalizeModal<?= e((string)$stocktake['stocktake_id']) ?>">Finalize & Close</button>
                            
                            <!-- Finalize Modal -->
                            <div class="modal fade" id="finalizeModal<?= e((string)$stocktake['stocktake_id']) ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">Finalize Stocktake?</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted mb-3">Finalize this stocktake and calculate variances?</p>
                                            <p class="small text-danger">This will create adjustment records for any discrepancies and cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" action="<?= e(BASE_URL) ?>/inventory/stocktake/<?= e((string)$stocktake['stocktake_id']) ?>/finalize" style="display:inline;">
                                                <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Finalize</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Completed Stocktakes -->
<h6 class="mb-3 text-muted">Completed Stocktakes</h6>
<div class="card card-soft">
    <?php if (!empty($completedStocktakes)): ?>
        <div class="table-wrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Location</th>
                        <th style="width: 15%;">Completed By</th>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 12%;">Products</th>
                        <th style="width: 12%;">Variances</th>
                        <th style="width: 15%;" class="text-center">Variance %</th>
                        <th style="width: 15%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedStocktakes as $stocktake): ?>
                        <?php
                        $totalProducts = (int)($stocktake['total_products'] ?? 0);
                        $variances = (int)($stocktake['variance_count'] ?? 0);
                        $variancePercent = $totalProducts > 0 ? round(($variances / $totalProducts) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($stocktake['location']) ?></strong>
                            </td>
                            <td>
                                <?= e($stocktake['completed_by']) ?>
                            </td>
                            <td>
                                <?= (new DateTime($stocktake['completed_at']))->format('M d, Y H:i') ?>
                            </td>
                            <td class="text-center">
                                <?= $totalProducts ?>
                            </td>
                            <td class="text-center">
                                <?php if ($variances > 0): ?>
                                    <span class="badge text-bg-warning"><?= $variances ?></span>
                                <?php else: ?>
                                    <span class="text-success">✓ None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($variancePercent > 5): ?>
                                    <span class="text-danger fw-600"><?= $variancePercent ?>%</span>
                                <?php elseif ($variancePercent > 0): ?>
                                    <span class="text-warning fw-600"><?= $variancePercent ?>%</span>
                                <?php else: ?>
                                    <span class="text-success">0%</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= e((string)$stocktake['stocktake_id']) ?>">View</button>
                                
                                <!-- Detail Modal -->
                                <div class="modal fade" id="detailModal<?= e((string)$stocktake['stocktake_id']) ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Stocktake Report - <?= e($stocktake['location']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">Completed By</small>
                                                        <div><?= e($stocktake['completed_by']) ?></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">Completion Date</small>
                                                        <div><?= (new DateTime($stocktake['completed_at']))->format('M d, Y H:i') ?></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <small class="text-muted">Total Products</small>
                                                        <div class="h5"><?= $totalProducts ?></div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <small class="text-muted">Products with Variance</small>
                                                        <div class="h5 <?= $variances > 0 ? 'text-warning' : 'text-success' ?>"><?= $variances ?></div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <small class="text-muted">Variance Rate</small>
                                                        <div class="h5 <?= $variancePercent > 5 ? 'text-danger' : ($variancePercent > 0 ? 'text-warning' : 'text-success') ?>"><?= $variancePercent ?>%</div>
                                                    </div>
                                                </div>
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
            <div class="text-muted mb-3" style="font-size: 48px;">📊</div>
            <p class="text-muted">No completed stocktakes yet.</p>
        </div>
    <?php endif; ?>
</div>

<!-- New Stocktake Modal -->
<div class="modal fade" id="newStocktakeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Stocktake</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= e(BASE_URL) ?>/inventory/stocktake/create">
                <div class="modal-body">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Location/Section *</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Main Store, Warehouse A, Section 1" required>
                        <small class="text-muted">Which location is being counted?</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-600">Scope *</label>
                        <select name="scope" class="form-select" required>
                            <option value="">Select scope</option>
                            <option value="all">All Products (Full Inventory)</option>
                            <option value="category">By Category</option>
                            <option value="section">By Section/Location</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info small">
                        <strong>ℹ️ Tip:</strong> Full inventory stocktakes take longer but provide complete accuracy. Partial stocktakes by category are useful for quarterly audits.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Stocktake</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .table-wrap {
        overflow-x: auto;
    }
</style>
