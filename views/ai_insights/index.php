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

<!-- Recent Recommendations -->
<div class="card card-soft mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">🤖 Recent AI Recommendations</h6>
        <span class="badge bg-primary"><?= count($records) ?> Active</span>
    </div>
    <div class="card-body">
        <?php if (empty($records)): ?>
            <div class="text-center py-5">
                <div class="text-muted mb-3">
                    <svg width="80" height="80" fill="currentColor" class="opacity-25">
                        <circle cx="40" cy="40" r="35" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path d="M 25 40 L 35 50 L 55 30" stroke="currentColor" stroke-width="3" fill="none"/>
                    </svg>
                </div>
                <p class="text-muted">No pending recommendations at the moment.</p>
                <p class="text-muted small">AI analyzes your business activities in real-time. Recommendations will appear as actions occur.</p>
                <form method="POST" action="<?= e(BASE_URL) ?>/ai-insights/generate" class="mt-3">
                    <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Generate Stock Recommendations</button>
                </form>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Recommendation</th>
                            <th>Product</th>
                            <th>Urgency</th>
                            <th>Confidence</th>
                            <th>Generated</th>
                            <th width="100">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'restock' => '📦',
                                        'pricing' => '💰',
                                        'dead_stock' => '⚠️',
                                        'supplier' => '🚚',
                                        'substitution' => '🛍️',
                                        'promotion' => '🎉',
                                        'anomaly' => '🔴',
                                        'bulk_order' => '📊'
                                    ];
                                    $icon = $typeIcons[$rec['rec_type']] ?? '💡';
                                    $typeLabel = ucfirst(str_replace('_', ' ', $rec['rec_type']));
                                    ?>
                                    <span title="<?= e($typeLabel) ?>"><?= $icon ?></span>
                                    <small class="text-muted d-block"><?= e($typeLabel) ?></small>
                                </td>
                                <td>
                                    <div class="fw-600" style="max-width: 400px;">
                                        <?= e(substr($rec['recommendation'], 0, 120)) ?>
                                        <?= strlen($rec['recommendation']) > 120 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($rec['product_name'])): ?>
                                        <small class="text-muted"><?= e($rec['product_name']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $urgencyBadges = [
                                        'critical' => 'bg-danger',
                                        'urgent' => 'bg-warning',
                                        'normal' => 'bg-info',
                                        'monitor' => 'bg-secondary'
                                    ];
                                    $badgeClass = $urgencyBadges[$rec['urgency']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= e(ucfirst($rec['urgency'])) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= number_format($rec['confidence_score'], 0) ?>%</small>
                                </td>
                                <td>
                                    <small class="text-muted"><?= e(date('M d, H:i', strtotime($rec['generated_at']))) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="<?= e(BASE_URL) ?>/ai-insights/<?= intval($rec['rec_id']) ?>/accept" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            <button type="submit" class="btn btn-success btn-sm" title="Accept">✓</button>
                                        </form>
                                        <form method="POST" action="<?= e(BASE_URL) ?>/ai-insights/<?= intval($rec['rec_id']) ?>/dismiss" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= e($csrf ?? csrfToken()) ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Dismiss">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>