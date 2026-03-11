<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">📈 Sales Report</h5>
        <p class="text-muted small">Daily sales performance with date range filtering</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card card-soft mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(BASE_URL) ?>/reports/sales" class="row g-3 align-items-end">
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
                <a href="<?= e(BASE_URL) ?>/reports/sales" class="btn btn-outline-secondary">Reset</a>
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
                <small class="text-muted">Total Transactions</small>
                <h4 class="mb-0 mt-1"><?= number_format($extra['summary']['total_transactions'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Gross Sales</small>
                <h4 class="mb-0 mt-1 text-success">₱<?= number_format($extra['summary']['total_gross_sales'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Total Discounts</small>
                <h4 class="mb-0 mt-1 text-warning">₱<?= number_format($extra['summary']['total_discounts'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Net Sales</small>
                <h4 class="mb-0 mt-1 text-primary">₱<?= number_format($extra['summary']['total_net_sales'] ?? 0, 2) ?></h4>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sales Data Table -->
<div class="card card-soft">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Daily Sales Breakdown</h6>
        <div class="btn-group btn-group-sm">
            <a href="<?= e(BASE_URL) ?>/reports/export/sales-csv?start_date=<?= e($extra['filters']['start_date'] ?? '') ?>&end_date=<?= e($extra['filters']['end_date'] ?? '') ?>" 
               class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export CSV
            </a>
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
                <p class="text-muted">No sales data found for the selected date range.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Transactions</th>
                            <th class="text-end">Gross Sales</th>
                            <th class="text-end">Discounts</th>
                            <th class="text-end">VAT Collected</th>
                            <th class="text-end">Net Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td>
                                    <span class="fw-600"><?= e(date('M d, Y', strtotime($row['report_date']))) ?></span>
                                    <small class="text-muted d-block"><?= e(date('l', strtotime($row['report_date']))) ?></small>
                                </td>
                                <td class="text-end"><?= number_format($row['transactions']) ?></td>
                                <td class="text-end text-success fw-600">₱<?= number_format($row['gross_sales'], 2) ?></td>
                                <td class="text-end text-warning">₱<?= number_format($row['discounts'], 2) ?></td>
                                <td class="text-end text-info">₱<?= number_format($row['vat_collected'], 2) ?></td>
                                <td class="text-end text-primary fw-600">₱<?= number_format($row['net_sales'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (isset($extra['summary'])): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th>TOTAL</th>
                            <th class="text-end"><?= number_format($extra['summary']['total_transactions']) ?></th>
                            <th class="text-end text-success">₱<?= number_format($extra['summary']['total_gross_sales'], 2) ?></th>
                            <th class="text-end text-warning">₱<?= number_format($extra['summary']['total_discounts'], 2) ?></th>
                            <th class="text-end text-info">₱<?= number_format($extra['summary']['total_vat'], 2) ?></th>
                            <th class="text-end text-primary">₱<?= number_format($extra['summary']['total_net_sales'], 2) ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<?php if (!empty($records)): ?>
<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">📊 Quick Statistics</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Average Daily Sales:</span>
                    <span class="fw-600">₱<?= number_format(($extra['summary']['total_gross_sales'] ?? 0) / max(1, count($records)), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Average Transaction Value:</span>
                    <span class="fw-600">₱<?= number_format(($extra['summary']['total_gross_sales'] ?? 0) / max(1, $extra['summary']['total_transactions'] ?? 1), 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Discount Rate:</span>
                    <span class="fw-600"><?= number_format((($extra['summary']['total_discounts'] ?? 0) / max(1, $extra['summary']['total_gross_sales'] ?? 1)) * 100, 2) ?>%</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-3">📅 Date Range Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">From:</span>
                    <span class="fw-600"><?= e(date('M d, Y', strtotime($extra['filters']['start_date']))) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">To:</span>
                    <span class="fw-600"><?= e(date('M d, Y', strtotime($extra['filters']['end_date']))) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Days in Range:</span>
                    <span class="fw-600"><?= count($records) ?> days</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
