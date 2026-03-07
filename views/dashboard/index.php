<?php
// Page Header using new UI helper
pageHeader(
    'Dashboard',
    'Welcome back, ' . e($user['full_name'] ?? 'User') . ' • ' . date('l, M d, Y'),
    []
);
?>

<!-- FLASH MESSAGES -->
<?php if (!empty($flash)): ?>
    <div class="sw-flash sw-flash-<?= e($flash['type']) ?>" style="margin-bottom: 24px;">
        <span class="sw-flash-message"><?= e($flash['message']) ?></span>
        <button class="sw-flash-close" onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>

<!-- KPI CARDS GRID (4 COLUMNS) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 32px;">
    <!-- KPI Card: Today's Sales -->
    <div class="sw-kpi-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--sw-text-muted);">
                Today's Sales
            </div>
            <div style="width: 40px; height: 40px; background: var(--sw-primary-light); border-radius: var(--sw-radius-md); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                💰
            </div>
        </div>
        <div style="font-family: var(--sw-font-display); font-size: 28px; font-weight: 700; color: var(--sw-text); margin-bottom: 8px;">
            <?= e(peso($todayStats['total_sales'] ?? 0)) ?>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--sw-text-muted);">
            <span style="color: var(--sw-success);">↑ <?= e(number_format($todayStats['transaction_count'] ?? 0)) ?> transactions</span>
        </div>
    </div>

    <!-- KPI Card: Avg Sale -->
    <div class="sw-kpi-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--sw-text-muted);">
                Average Sale
            </div>
            <div style="width: 40px; height: 40px; background: var(--sw-accent-light); border-radius: var(--sw-radius-md); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                📊
            </div>
        </div>
        <div style="font-family: var(--sw-font-display); font-size: 28px; font-weight: 700; color: var(--sw-text); margin-bottom: 8px;">
            <?= e(peso($todayStats['avg_sale'] ?? 0)) ?>
        </div>
        <div style="font-size: 12px; color: var(--sw-text-muted);">
            <?= $todayStats['transaction_count'] > 0 ? 'Per transaction' : 'No sales yet' ?>
        </div>
    </div>

    <!-- KPI Card: Cash vs Digital -->
    <div class="sw-kpi-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--sw-text-muted);">
                Payment Methods
            </div>
            <div style="width: 40px; height: 40px; background: var(--sw-info-light); border-radius: var(--sw-radius-md); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                💳
            </div>
        </div>
        <div style="display: flex; gap: 16px; margin-bottom: 8px;">
            <div>
                <div style="font-size: 12px; color: var(--sw-text-muted); margin-bottom: 4px;">Cash</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--sw-primary);">
                    <?= e(peso($todayStats['cash_sales'] ?? 0)) ?>
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--sw-text-muted); margin-bottom: 4px;">Digital</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--sw-accent);">
                    <?= e(peso($todayStats['digital_sales'] ?? 0)) ?>
                </div>
            </div>
        </div>
        <div style="font-size: 12px; color: var(--sw-text-muted);">
            <?php 
                $total = $todayStats['cash_sales'] + $todayStats['digital_sales'];
                $cashPct = $total > 0 ? round(($todayStats['cash_sales'] / $total) * 100) : 0;
            ?>
            <?= $cashPct ?>% cash
        </div>
    </div>

    <!-- KPI Card: Inventory Status -->
    <div class="sw-kpi-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--sw-text-muted);">
                Inventory
            </div>
            <div style="width: 40px; height: 40px; background: var(--sw-warning-light); border-radius: var(--sw-radius-md); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                📦
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
            <div style="text-align: center; flex: 1;">
                <div style="font-size: 18px; font-weight: 700; color: var(--sw-success);">
                    <?= e($inventorySummary['stock_ok'] ?? 0) ?>
                </div>
                <div style="font-size: 11px; color: var(--sw-text-muted);">OK</div>
            </div>
            <div style="text-align: center; flex: 1;">
                <div style="font-size: 18px; font-weight: 700; color: var(--sw-warning);">
                    <?= e($inventorySummary['stock_low'] ?? 0) ?>
                </div>
                <div style="font-size: 11px; color: var(--sw-text-muted);">Low</div>
            </div>
            <div style="text-align: center; flex: 1;">
                <div style="font-size: 18px; font-weight: 700; color: var(--sw-danger);">
                    <?= e($inventorySummary['stock_out'] ?? 0) ?>
                </div>
                <div style="font-size: 11px; color: var(--sw-text-muted);">Out</div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT GRID -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px;">
    <!-- Sales Trend Chart -->
    <div style="background: var(--sw-surface); border: 1.5px solid var(--sw-border); border-radius: var(--sw-radius-lg); padding: 24px;">
        <h3 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; margin-bottom: 20px; margin-top: 0;">
            Sales Trend (14 Days)
        </h3>
        <canvas id="salesTrendChart" style="max-height: 300px;"></canvas>
    </div>

    <!-- Top Products Bar Chart -->
    <div style="background: var(--sw-surface); border: 1.5px solid var(--sw-border); border-radius: var(--sw-radius-lg); padding: 24px;">
        <h3 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; margin-bottom: 20px; margin-top: 0;">
            Top Products Today
        </h3>
        <canvas id="topProductsChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<!-- ALERT FEED & CATEGORY BREAKDOWN -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px;">
    <!-- Alert Feed -->
    <div style="background: var(--sw-surface); border: 1.5px solid var(--sw-border); border-radius: var(--sw-radius-lg); padding: 24px;">
        <h3 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; margin-bottom: 20px; margin-top: 0;">
            Active Alerts
        </h3>
        <?php if (empty($alerts)): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--sw-text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">✓</div>
                <div style="font-size: 14px;">All systems operational</div>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($alerts as $alert): ?>
                    <div style="background: var(--sw-surface2); border-left: 4px solid <?= $alert['severity'] === 'warning' ? 'var(--sw-warning)' : 'var(--sw-info)' ?>; padding: 12px 16px; border-radius: var(--sw-radius-md);">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <div style="font-size: 20px; line-height: 1;">
                                <?= e($alert['icon']) ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 600; color: var(--sw-text);">
                                    <?= e($alert['message']) ?>
                                </div>
                            </div>
                            <div style="background: <?= $alert['severity'] === 'warning' ? 'var(--sw-warning-light)' : 'var(--sw-info-light)' ?>; color: <?= $alert['severity'] === 'warning' ? 'var(--sw-warning)' : 'var(--sw-info)' ?>; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?= e($alert['count']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sales by Category Donut -->
    <div style="background: var(--sw-surface); border: 1.5px solid var(--sw-border); border-radius: var(--sw-radius-lg); padding: 24px;">
        <h3 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; margin-bottom: 20px; margin-top: 0;">
            Sales by Category
        </h3>
        <canvas id="categorySalesChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<!-- HOURLY SALES HEATMAP -->
<div style="background: var(--sw-surface); border: 1.5px solid var(--sw-border); border-radius: var(--sw-radius-lg); padding: 24px; margin-bottom: 32px;">
    <h3 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; margin-bottom: 20px; margin-top: 0;">
        Hourly Sales (7 Days)
    </h3>
    <canvas id="hourlySalesChart" style="max-height: 250px;"></canvas>
</div>

<!-- PENDING ACTIONS -->
<?php if (!empty($pendingActions)): ?>
    <div style="background: var(--sw-accent-light); border-left: 4px solid var(--sw-accent); border-radius: var(--sw-radius-lg); padding: 20px; margin-bottom: 32px;">
        <h4 style="font-family: var(--sw-font-display); font-size: 16px; font-weight: 700; color: var(--sw-accent-dark); margin-top: 0; margin-bottom: 16px;">
            📋 Pending Actions
        </h4>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($pendingActions as $action): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(232, 160, 32, 0.1); padding: 12px 16px; border-radius: var(--sw-radius-md);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="font-size: 20px;"><?= e($action['icon']) ?></div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--sw-text);">
                            <?= e($action['action']) ?>
                        </div>
                    </div>
                    <a href="#" style="background: var(--sw-accent); color: #fff; padding: 6px 16px; border-radius: var(--sw-radius-md); text-decoration: none; font-size: 13px; font-weight: 600;">
                        <?= e($action['count']) ?> pending
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- CHART.JS INITIALIZATION -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sales Trend Chart (14 days)
        const salesTrendData = <?= json_encode($chartData['salesTrend'] ?? []) ?>;
        if (salesTrendData && document.getElementById('salesTrendChart')) {
            window.initSalesLineChart(
                'salesTrendChart',
                salesTrendData.labels || [],
                salesTrendData.current || [],
                salesTrendData.previous || []
            );
        }

        // Top Products Bar Chart
        const topProductsData = <?= json_encode($chartData['topProducts'] ?? []) ?>;
        if (topProductsData && document.getElementById('topProductsChart')) {
            const labels = topProductsData.map(p => p.name);
            const data = topProductsData.map(p => p.revenue);
            window.initTopProductsBar('topProductsChart', labels, data);
        }

        // Category Sales Donut
        const categoryData = <?= json_encode($chartData['byCategory'] ?? []) ?>;
        if (categoryData && document.getElementById('categorySalesChart')) {
            const labels = categoryData.map(c => c.category);
            const data = categoryData.map(c => c.sales);
            window.initCategoryDonut('categorySalesChart', labels, data);
        }

        // Hourly Sales Heatmap
        const hourlyData = <?= json_encode($chartData['hourly'] ?? []) ?>;
        if (hourlyData && document.getElementById('hourlySalesChart')) {
            window.initHourlySalesChart('hourlySalesChart', hourlyData.labels || [], hourlyData.datasets || []);
        }
    });
</script>

<style>
    @media (max-width: 1024px) {
        [style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
