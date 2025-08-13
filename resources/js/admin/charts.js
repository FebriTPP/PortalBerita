/**
 * Admin Dashboard Charts
 * Handles all Chart.js visualizations for the admin dashboard
 */

export class AdminCharts {
    constructor() {
        this.baseChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            resizeDelay: 100
        };

        this.setupResizeHandler();
    }

    /**
     * Initialize all charts
     */
    initializeCharts(analyticsData) {
        this.createCacheChart(analyticsData.cache_stats);
        this.createRequestsChart(analyticsData.hourly_requests);
        this.createCategoriesChart(analyticsData.top_categories);
    }

    /**
     * Cache Performance Doughnut Chart
     */
    createCacheChart(cacheStats) {
        const ctx = document.getElementById('cacheChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cache Hits', 'Cache Misses'],
                datasets: [{
                    data: [cacheStats.hits, cacheStats.misses],
                    backgroundColor: ['#198754', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: Object.assign({}, this.baseChartOptions, {
                plugins: { legend: { position: 'bottom' } }
            })
        });
    }

    /**
     * External Requests Line Chart
     */
    createRequestsChart(hourlyRequests) {
        const ctx = document.getElementById('requestsChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: hourlyRequests.labels,
                datasets: [{
                    label: 'API Requests',
                    data: hourlyRequests.data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4
                }]
            },
            options: Object.assign({}, this.baseChartOptions, {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: { legend: { display: false } }
            })
        });
    }

    /**
     * Top Categories Bar Chart
     */
    createCategoriesChart(topCategories) {
        const ctx = document.getElementById('categoriesChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topCategories.labels,
                datasets: [{
                    label: 'Hits',
                    data: topCategories.data,
                    backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14'],
                    borderRadius: 6,
                    maxBarThickness: 48
                }]
            },
            options: Object.assign({}, this.baseChartOptions, {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            })
        });
    }

    /**
     * Handle window resize events for charts
     */
    setupResizeHandler() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Charts are responsive; nothing explicit needed
            }, 120);
        });
    }
}
