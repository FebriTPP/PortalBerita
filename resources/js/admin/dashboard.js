/**
 * Admin Dashboard Main Module
 * Orchestrates all admin dashboard functionality
 */

import { AdminCharts } from './charts.js';
import { AdminQuickActions } from './quick-actions.js';

export class AdminDashboard {
    constructor(analyticsData) {
        this.analyticsData = analyticsData;
        this.charts = new AdminCharts();
        this.quickActions = new AdminQuickActions();

        this.initialize();
    }

    /**
     * Initialize dashboard components
     */
    initialize() {
        // Initialize charts with analytics data
        this.charts.initializeCharts(this.analyticsData);

        // Setup auto-refresh
        this.setupAutoRefresh();
    }

    /**
     * Setup auto-refresh functionality
     */
    setupAutoRefresh() {
        // Auto refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    }

    /**
     * Refresh dashboard data
     */
    async refreshData() {
        try {
            // Implement manual refresh if needed
            location.reload();
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
        }
    }
}

// Global initialization function
window.initAdminDashboard = function(analyticsData) {
    document.addEventListener('DOMContentLoaded', function() {
        new AdminDashboard(analyticsData);
    });
};
