<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">Reports & Analytics</h5>
        <p class="text-muted small">View business intelligence reports and insights</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted">Today's Sales</small>
                        <h4 class="mb-0 mt-1">₦<?= number_format($extra['summary']['today_sales'] ?? 0, 2) ?></h4>
                    </div>
                    <div class="icon-box bg-primary-soft">📊</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted">Month Sales</small>
                        <h4 class="mb-0 mt-1">₦<?= number_format($extra['summary']['month_sales'] ?? 0, 2) ?></h4>
                    </div>
                    <div class="icon-box bg-success-soft">💰</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted">Low Stock Items</small>
                        <h4 class="mb-0 mt-1"><?= intval($extra['summary']['low_stock'] ?? 0) ?></h4>
                    </div>
                    <div class="icon-box bg-warning-soft">⚠️</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted">Pending AI Insights</small>
                        <h4 class="mb-0 mt-1"><?= intval($extra['summary']['pending_ai'] ?? 0) ?></h4>
                    </div>
                    <div class="icon-box bg-info-soft">🤖</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Categories -->
<div class="row g-3">
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-primary-soft" style="font-size: 2rem;">📈</div>
                    <div>
                        <h6 class="mb-1">Sales Report</h6>
                        <small class="text-muted">Daily and monthly sales data</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/sales" class="btn btn-sm btn-outline-primary w-100">View Report</a>
            </div>
        </div>
    </div>

    <?php $currentRole = (string)($user['role_slug'] ?? ''); ?>
    <?php if (in_array($currentRole, ['owner', 'manager'], true)): ?>
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-info-soft" style="font-size: 2rem;">🧾</div>
                    <div>
                        <h6 class="mb-1">Customer Transactions</h6>
                        <small class="text-muted">Per-customer receipt history</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/customer-transactions" class="btn btn-sm btn-outline-info w-100">View Report</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-success-soft" style="font-size: 2rem;">💵</div>
                    <div>
                        <h6 class="mb-1">Profit & Loss</h6>
                        <small class="text-muted">Revenue, COGS, and margins</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/profit" class="btn btn-sm btn-outline-success w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-info-soft" style="font-size: 2rem;">📦</div>
                    <div>
                        <h6 class="mb-1">Inventory Report</h6>
                        <small class="text-muted">Stock levels and valuation</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/inventory" class="btn btn-sm btn-outline-info w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-danger-soft" style="font-size: 2rem;">📉</div>
                    <div>
                        <h6 class="mb-1">Shrinkage Report</h6>
                        <small class="text-muted">Stock adjustments and losses</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/shrinkage" class="btn btn-sm btn-outline-danger w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-warning-soft" style="font-size: 2rem;">👤</div>
                    <div>
                        <h6 class="mb-1">Cashier Performance</h6>
                        <small class="text-muted">Sales by cashier</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/cashier" class="btn btn-sm btn-outline-warning w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft card-hover">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="icon-box bg-secondary-soft" style="font-size: 2rem;">📄</div>
                    <div>
                        <h6 class="mb-1">Custom Reports</h6>
                        <small class="text-muted">Build your own reports</small>
                    </div>
                </div>
                <a href="<?= e(BASE_URL) ?>/reports/custom" class="btn btn-sm btn-outline-secondary w-100">Build Report</a>
            </div>
        </div>
    </div>
</div>

<style>
    .card-hover:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        transition: all 0.2s ease;
    }
    
    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .bg-primary-soft {
        background: rgba(26, 60, 94, 0.1);
    }
    
    .bg-success-soft {
        background: rgba(40, 167, 69, 0.1);
    }
    
    .bg-info-soft {
        background: rgba(23, 162, 184, 0.1);
    }
    
    .bg-warning-soft {
        background: rgba(232, 160, 32, 0.1);
    }
    
    .bg-danger-soft {
        background: rgba(220, 53, 69, 0.1);
    }
    
    .bg-secondary-soft {
        background: rgba(108, 117, 125, 0.1);
    }
</style>
