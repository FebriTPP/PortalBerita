import './bootstrap';

import { ThemeManager } from './modules/themeManager.js';
import { ScrollManager } from './modules/scrollManager.js';
import { AvatarManager } from './modules/avatarManager.js';
import { PasswordValidator } from './modules/passwordValidator.js';
import { GlobalFunctions } from './globalFunctions.js';
import { AdminDashboard } from './admin/dashboard.js';
import { AdminUtils } from './admin/utils.js';
import { AdminCharts } from './admin/charts.js';
import { AdminQuickActions } from './admin/quick-actions.js';


/**
 * Main Application Entry Point
 * Initializes all modules and manages application lifecycle
 */
class WinnewsApp {
    constructor() {
        this.modules = {
            theme: ThemeManager,
            scroll: ScrollManager,
            avatar: AvatarManager,
            password: PasswordValidator
        };

        // Admin Dashboard instance
        this.adminDashboard = null;
    }

    /**
     * Initialize the application
     */
    init() {
        // Initialize all modules
        Object.values(this.modules).forEach(module => {
            if (module.init && typeof module.init === 'function') {
                module.init();
            }
        });

        // Initialize admin dashboard if on admin page
        this.initAdminDashboard();

        // Initialize global functions (already done in globalFunctions.js)
        // This ensures they're available for onclick handlers
    }

    /**
     * Initialize Admin Dashboard if we're on admin page
     */
    initAdminDashboard() {
        // Check if we're on admin dashboard page
        if (window.location.pathname.includes('/admin/dashboard')) {
            // Check if analytics data is available
            if (window.adminAnalyticsData) {
                this.adminDashboard = new AdminDashboard(window.adminAnalyticsData);
            }
        }
    }

    /**
     * Get a specific module instance
     * @param {string} moduleName - Name of the module
     * @returns {Object} Module instance
     */
    getModule(moduleName) {
        if (moduleName === 'admin') {
            return this.adminDashboard;
        }
        return this.modules[moduleName];
    }

    /**
     * Reinitialize a specific module
     * @param {string} moduleName - Name of the module to reinitialize
     */
    reinitModule(moduleName) {
        if (moduleName === 'admin') {
            this.initAdminDashboard();
            return;
        }

        const module = this.modules[moduleName];
        if (module && module.init) {
            module.init();
        }
    }
}

// Create application instance
const App = new WinnewsApp();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => App.init());

// Export for potential external use and debugging
export default App;

// Make App available globally for debugging and blade templates
window.App = App;
window.WinnewsApp = WinnewsApp;
