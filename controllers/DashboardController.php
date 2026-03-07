<?php
declare(strict_types=1);

/**
 * DashboardController — Main dashboard with analytics and KPIs
 *
 * Displays today's sales, trend analysis, inventory status, alerts,
 * and pending actions per user role.
 */
class DashboardController extends Controller
{
    protected string $layout = 'app';

    /**
     * Main dashboard with analytics
     */
    public function index(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        $model = new DashboardModel();

        // Gather all dashboard data
        $todayStats = $model->getTodayStats();
        $salesTrendData = $model->getLast14DaysSales();
        $topProducts = $model->getTopProductsToday();
        $salesByCategory = $model->getSalesByCategory();
        $hourlyData = $model->getHourlySalesData();
        $alerts = $model->getActiveAlerts();
        $inventorySummary = $model->getInventorySummary();
        $pendingActions = $model->getPendingActions($user['user_id']);

        // Format chart data for rendering
        $chartData = [
            'salesTrend' => $salesTrendData,
            'topProducts' => $topProducts,
            'byCategory' => $salesByCategory,
            'hourly' => $hourlyData,
        ];

        $this->render('dashboard/index', [
            'flash' => $this->getFlash(),
            'user' => $user,
            'todayStats' => $todayStats,
            'chartData' => $chartData,
            'alerts' => $alerts,
            'inventorySummary' => $inventorySummary,
            'pendingActions' => $pendingActions,
        ]);
    }
}
