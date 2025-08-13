/**
 * Admin Utilities
 * Common utility functions for admin dashboard
 */

export class AdminUtils {
    /**
     * Format numbers with proper separators
     */
    static formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    /**
     * Format bytes to human readable format
     */
    static formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Format timestamp to readable format
     */
    static formatTimestamp(timestamp) {
        return new Date(timestamp).toLocaleString('id-ID');
    }

    /**
     * Get status badge class based on value
     */
    static getStatusBadgeClass(status) {
        const statusMap = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info',
            'active': 'bg-success',
            'inactive': 'bg-secondary'
        };

        return statusMap[status] || 'bg-secondary';
    }

    /**
     * Show toast notification
     */
    static showToast(message, type = 'info') {
        // Implementation for toast notifications
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    /**
     * Debounce function for performance
     */
    static debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    /**
     * Throttle function for performance
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}
