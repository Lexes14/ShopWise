<div class="page-header gap-3 mb-4">
    <div>
        <h5 class="mb-1">AI Insights & Recommendations</h5>
        <p class="text-muted small">Machine learning powered business insights</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Active Insights</small>
                <h4 class="mb-0 mt-1"><?= intval($extra['summary']['active'] ?? 12) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Pending Review</small>
                <h4 class="mb-0 mt-1"><?= intval($extra['summary']['pending'] ?? 5) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Implemented</small>
                <h4 class="mb-0 mt-1"><?= intval($extra['summary']['implemented'] ?? 48) ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <small class="text-muted">Accuracy Score</small>
                <h4 class="mb-0 mt-1"><?= number_format($extra['summary']['accuracy'] ?? 85, 1) ?>%</h4>
            </div>
        </div>
    </div>
</div>

<!-- Insights Categories -->
<div class="row g-3">
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">📈 Demand Forecasting</h6>
                <p class="text-muted small mb-3">AI predicts which products will be in high demand based on historical sales patterns.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/demand" class="btn btn-sm btn-outline-primary">View Forecast</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">💰 Price Optimization</h6>
                <p class="text-muted small mb-3">Recommends optimal prices to maximize revenue while staying competitive.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/pricing" class="btn btn-sm btn-outline-success">View Prices</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">📦 Stock Optimization</h6>
                <p class="text-muted small mb-3">Suggests optimal reorder points to minimize stockouts and overstocking.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/stock" class="btn btn-sm btn-outline-info">View Stock</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">🛍️ Product Bundling</h6>
                <p class="text-muted small mb-3">Identifies products frequently bought together for bundle promotions.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/bundling" class="btn btn-sm btn-outline-warning">View Bundles</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">⚠️ Anomaly Detection</h6>
                <p class="text-muted small mb-3">Alerts you to unusual sales patterns, potential theft, or data errors.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/anomalies" class="btn btn-sm btn-outline-danger">View Alerts</a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="mb-2">👥 Customer Segmentation</h6>
                <p class="text-muted small mb-3">Group customers by behavior for targeted marketing and loyalty programs.</p>
                <a href="<?= e(BASE_URL) ?>/ai-insights/segments" class="btn btn-sm btn-outline-secondary">View Segments</a>
            </div>
        </div>
    </div>
</div>