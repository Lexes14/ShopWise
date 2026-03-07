/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║              SHOPWISE AI — CHARTS v5.0                              ║
 * ║         Chart.js 4 visualization library                             ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 * 
 * Requires: Chart.js 4.x from CDN
 */

(() => {
    'use strict';

    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Include Chart.js 4.x CDN in your layout.');
        return;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SHARED COLOR PALETTE (MATCHING CSS DESIGN SYSTEM)
    // ═══════════════════════════════════════════════════════════════════════

    const colors = {
        primary: '#1A3C5E',
        primaryLight: '#EBF2FA',
        accent: '#E8A020',
        accentLight: '#FEF3DC',
        success: '#16A34A',
        successLight: '#DCFCE7',
        warning: '#D97706',
        warningLight: '#FEF3C7',
        danger: '#DC2626',
        dangerLight: '#FEE2E2',
        info: '#0284C7',
        infoLight: '#E0F2FE',
        gray: '#64748B',
        grayLight: '#F1F5F9'
    };

    const chartColors = [
        colors.primary,
        colors.accent,
        colors.success,
        colors.info,
        colors.warning,
        colors.danger,
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F59E0B'
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SHARED CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════

    const sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 16,
                    font: {
                        family: 'DM Sans, sans-serif',
                        size: 12,
                        weight: 600
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    family: 'Syne, sans-serif',
                    size: 13,
                    weight: 700
                },
                bodyFont: {
                    family: 'DM Sans, sans-serif',
                    size: 12
                },
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        
                        const value = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                        
                        // Format as peso if it looks like money
                        if (value > 100) {
                            label += '₱' + value.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        } else {
                            label += value.toLocaleString('en-PH');
                        }
                        
                        return label;
                    }
                }
            }
        }
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 1: SALES LINE CHART (Dual-line with gradient)
    // ═══════════════════════════════════════════════════════════════════════

    window.initSalesLineChart = (canvasId, labels, currentData, previousData) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const ctx = canvas.getContext('2d');
        
        // Current period gradient
        const gradientCurrent = ctx.createLinearGradient(0, 0, 0, 400);
        gradientCurrent.addColorStop(0, colors.primary + '40');
        gradientCurrent.addColorStop(1, colors.primary + '00');
        
        // Previous period gradient
        const gradientPrevious = ctx.createLinearGradient(0, 0, 0, 400);
        gradientPrevious.addColorStop(0, colors.gray + '20');
        gradientPrevious.addColorStop(1, colors.gray + '00');

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current Period',
                        data: currentData,
                        borderColor: colors.primary,
                        backgroundColor: gradientCurrent,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Previous Period',
                        data: previousData,
                        borderColor: colors.gray,
                        backgroundColor: gradientPrevious,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 2: TOP PRODUCTS BAR (Horizontal amber bars)
    // ═══════════════════════════════════════════════════════════════════════

    window.initTopProductsBar = (canvasId, labels, data) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: data,
                    backgroundColor: colors.accent,
                    borderRadius: 6,
                    barThickness: 32
                }]
            },
            options: {
                ...sharedOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 3: CATEGORY DONUT (Multi-color with center text)
    // ═══════════════════════════════════════════════════════════════════════

    window.initCategoryDonut = (canvasId, labels, data) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: chartColors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                ...sharedOptions,
                cutout: '70%',
                plugins: {
                    ...sharedOptions.plugins,
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 4: INVENTORY CATEGORY BAR (Grouped bars)
    // ═══════════════════════════════════════════════════════════════════════

    window.initInventoryCategoryBar = (canvasId, labels, stockOkData, stockLowData, stockOutData) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Stock OK',
                        data: stockOkData,
                        backgroundColor: colors.success,
                        borderRadius: 6
                    },
                    {
                        label: 'Stock Low',
                        data: stockLowData,
                        backgroundColor: colors.warning,
                        borderRadius: 6
                    },
                    {
                        label: 'Out of Stock',
                        data: stockOutData,
                        backgroundColor: colors.danger,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: false
                    },
                    x: {
                        stacked: false
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 5: HOURLY SALES HEATMAP (7-day hourly)
    // ═══════════════════════════════════════════════════════════════════════

    window.initHourlySalesChart = (canvasId, labels, datasets) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const dayColors = [
            colors.primary,
            colors.accent,
            colors.success,
            colors.info,
            colors.warning,
            colors.danger,
            '#8B5CF6'
        ];

        const chartDatasets = datasets.map((dataset, index) => ({
            label: dataset.label,
            data: dataset.data,
            borderColor: dayColors[index % dayColors.length],
            backgroundColor: dayColors[index % dayColors.length] + '20',
            borderWidth: 2,
            fill: false,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5
        }));

        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels, // ['12am', '1am', '2am', ...]
                datasets: chartDatasets
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 6: SUPPLIER RADAR (Scorecard with 5 metrics)
    // ═══════════════════════════════════════════════════════════════════════

    window.initSupplierRadar = (canvasId, labels, data) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'radar',
            data: {
                labels: labels, // ['Quality', 'Delivery', 'Price', 'Service', 'Reliability']
                datasets: [{
                    label: 'Supplier Score',
                    data: data, // [8, 9, 7, 8, 9]
                    backgroundColor: colors.primary + '30',
                    borderColor: colors.primary,
                    borderWidth: 2,
                    pointBackgroundColor: colors.accent,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...sharedOptions,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 7: CASHIER PERFORMANCE BAR (Comparison)
    // ═══════════════════════════════════════════════════════════════════════

    window.initCashierBar = (canvasId, labels, salesData, transactionsData) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Sales (₱)',
                        data: salesData,
                        backgroundColor: colors.primary,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Transactions',
                        data: transactionsData,
                        backgroundColor: colors.accent,
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // CHART 8: PROFIT TREND (Stacked area)
    // ═══════════════════════════════════════════════════════════════════════

    window.initProfitTrend = (canvasId, labels, revenueData, costData, profitData) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: colors.success,
                        backgroundColor: colors.successLight,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Cost',
                        data: costData,
                        borderColor: colors.danger,
                        backgroundColor: colors.dangerLight,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Profit',
                        data: profitData,
                        borderColor: colors.primary,
                        backgroundColor: colors.primaryLight,
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                ...sharedOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    }
                }
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════════════
    // REFRESH CHART METHOD
    // ═══════════════════════════════════════════════════════════════════════

    window.refreshChart = (chartInstance, newLabels, newDatasets) => {
        if (!chartInstance) return;
        
        chartInstance.data.labels = newLabels;
        
        if (Array.isArray(newDatasets)) {
            chartInstance.data.datasets.forEach((dataset, index) => {
                if (newDatasets[index]) {
                    dataset.data = newDatasets[index];
                }
            });
        } else {
            chartInstance.data.datasets[0].data = newDatasets;
        }
        
        chartInstance.update('active');
    };

    // ═══════════════════════════════════════════════════════════════════════
    // AUTO-INITIALIZE CHARTS FROM DATA ATTRIBUTES
    // ═══════════════════════════════════════════════════════════════════════

    document.addEventListener('DOMContentLoaded', () => {
        // Auto-init sales line charts
        document.querySelectorAll('[data-chart="sales-line"]').forEach(canvas => {
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const current = JSON.parse(canvas.dataset.current || '[]');
            const previous = JSON.parse(canvas.dataset.previous || '[]');
            window.initSalesLineChart(canvas.id, labels, current, previous);
        });

        // Auto-init bar charts
        document.querySelectorAll('[data-chart="bar"]').forEach(canvas => {
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const data = JSON.parse(canvas.dataset.values || '[]');
            window.initTopProductsBar(canvas.id, labels, data);
        });

        // Auto-init donut charts
        document.querySelectorAll('[data-chart="donut"]').forEach(canvas => {
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const data = JSON.parse(canvas.dataset.values || '[]');
            window.initCategoryDonut(canvas.id, labels, data);
        });

        console.log('Chart.js 4 initialized with ShopWise design system');
    });
})();
