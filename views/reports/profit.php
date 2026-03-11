<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">💵 Profit & Loss Report</h5>
        <p class="text-muted small">Revenue, Cost of Goods Sold (COGS), and profit margins</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/reports/profit" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" 
                       value="<?= e($extra['filters']['start_date'] ?? date('Y-m-d', strtotime('-30 days'))) ?>" 
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" 
                       value="<?= e($extra['filters']['end_date'] ?? date('Y-m-d')) ?>" 
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Generate Report
                </button>
                <a href="<?= e(BASE_URL) ?>/reports/profit" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<?php if (isset($extra['summary'])): ?>
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Total Revenue</small>
                <h4 class="mb-0 mt-1 text-primary">₱<?= number_format($extra['summary']['total_revenue'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Cost of Goods Sold</small>
                <h4 class="mb-0 mt-1 text-danger">₱<?= number_format($extra['summary']['total_cogs'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Gross Profit</small>
                <h4 class="mb-0 mt-1 text-success">₱<?= number_format($extra['summary']['total_gross_profit'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Profit Margin</small>
                <h4 class="mb-0 mt-1 text-info"><?= number_format($extra['summary']['avg_margin_percent'] ?? 0, 2) ?>%</h4>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Profit & Loss Table -->
<div class="card card-soft">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Daily Profit & Loss Breakdown</h6>
        <div class="btn-group btn-group-sm">
            <a href="<?= e(BASE_URL) ?>/reports/export/profit-csv?start_date=<?= e($extra['filters']['start_date'] ?? '') ?>&end_date=<?= e($extra['filters']['end_date'] ?? '') ?>" 
               class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($records)): ?>
            <div class="text-center py-5">
                <div class="text-muted mb-3">
                    <svg width="80" height="80" fill="currentColor" class="opacity-25">
                        <rect x="10" y="20" width="60" height="50" stroke="currentColor" stroke-width="2" fill="none"/>
                        <line x1="20" y1="35" x2="60" y2="35" stroke="currentColor" stroke-width="2"/>
                        <line x1="20" y1="50" x2="60" y2="50" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <p class="text-muted">No profit data found for the selected date range.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">COGS</th>
                            <th class="text-end">Gross Profit</th>
                            <th class="text-end">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <?php 
                            $marginPercent = (float)($row['margin_percent'] ?? 0);
                            $marginClass = $marginPercent >= 30 ? 'text-success' : ($marginPercent >= 20 ? 'text-info' : 'text-warning');
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-600"><?= e(date('M d, Y', strtotime($row['report_date']))) ?></span>
                                    <small class="text-muted d-block"><?= e(date('l', strtotime($row['report_date']))) ?></small>
                                </td>
                                <td class="text-end"><?= number_format($row['transactions']) ?></td>
                                <td class="text-end text-primary fw-600">₱<?= number_format($row['revenue'], 2) ?></td>
                                <td class="text-end text-danger">₱<?= number_format($row['cogs'], 2) ?></td>
                                <td class="text-end text-success fw-600">₱<?= number_format($row['gross_profit'], 2) ?></td>
                                <td class="text-end <?= $marginClass ?> fw-600"><?= number_format($marginPercent, 2) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (isset($extra['summary'])): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th>TOTAL</th>
                            <th class="text-end"><?= number_format($extra['summary']['total_transactions']) ?></th>
                            <th class="text-end text-primary">₱<?= number_format($extra['summary']['total_revenue'], 2) ?></th>
                            <th class="text-end text-danger">₱<?= number_format($extra['summary']['total_cogs'], 2) ?></th>
                            <th class="text-end text-success">₱<?= number_format($extra['summary']['total_gross_profit'], 2) ?></th>
                            <th class="text-end text-info"><?= number_format($extra['summary']['avg_margin_percent'], 2) ?>%</th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional Insights -->
<?php if (!empty($records)): ?>
<div class="row g-3 mt-3">
    <div class="col-md-4">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">📊 Financial Metrics</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Average Daily Revenue:</span>
                    <span class="fw-600">₱<?= number_format(($extra['summary']['total_revenue'] ?? 0) / max(1, count($records)), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Average Daily Profit:</span>
                    <span class="fw-600">₱<?= number_format(($extra['summary']['total_gross_profit'] ?? 0) / max(1, count($records)), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">COGS Ratio:</span>
                    <span class="fw-600"><?= number_format((($extra['summary']['total_cogs'] ?? 0) / max(1, $extra['summary']['total_revenue'] ?? 1)) * 100, 2) ?>%</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">📅 Period Analysis</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">From:</span>
                    <span class="fw-600"><?= e(date('M d, Y', strtotime($extra['filters']['start_date']))) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">To:</span>
                    <span class="fw-600"><?= e(date('M d, Y', strtotime($extra['filters']['end_date']))) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Trading Days:</span>
                    <span class="fw-600"><?= count($records) ?> days</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">💡 Performance Indicators</h6>
                <?php 
                $margin = $extra['summary']['avg_margin_percent'] ?? 0;
                $profitability = $margin >= 30 ? 'Excellent' : ($margin >= 20 ? 'Good' : ($margin >= 15 ? 'Fair' : 'Needs Improvement'));
                $profitColor = $margin >= 30 ? 'success' : ($margin >= 20 ? 'info' : ($margin >= 15 ? 'warning' : 'danger'));
                ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Profitability:</span>
                    <span class="badge bg-<?= $profitColor ?>"><?= $profitability ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Margin Rating:</span>
                    <span class="fw-600 text-<?= $profitColor ?>"><?= number_format($margin, 2) ?>%</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Transactions:</span>
                    <span class="fw-600"><?= number_format($extra['summary']['total_transactions']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Margin Explanation -->
<div class="alert alert-info mt-3">
    <strong>💡 Understanding Profit Margins:</strong><br>
    <small>
        • <strong>30%+</strong> = Excellent (Healthy business with good pricing)<br>
        • <strong>20-29%</strong> = Good (Solid profitability, competitive pricing)<br>
        • <strong>15-19%</strong> = Fair (Acceptable but could improve)<br>
        • <strong>&lt;15%</strong> = Needs Improvement (Review pricing or reduce costs)
    </small>
</div>
<?php endif; ?>
