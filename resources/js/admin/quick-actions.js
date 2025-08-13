/**
 * Admin Quick Actions
 * Handles all quick action functionality for admin dashboard
 */

export class AdminQuickActions {
    constructor() {
        this.initializeElements();
        this.setupEventListeners();
    }

    /**
     * Initialize DOM elements
     */
    initializeElements() {
        this.testApiBtn = document.getElementById('testApiBtn');
        this.refreshApiKeyBtn = document.getElementById('refreshApiKeyBtn');
        this.checkCacheBtn = document.getElementById('checkCacheBtn');
        this.actionResults = document.getElementById('actionResults');
        this.actionAlert = document.getElementById('actionAlert');
        this.actionSpinner = document.getElementById('actionSpinner');
        this.actionMessage = document.getElementById('actionMessage');
    }

    /**
     * Setup event listeners for all buttons
     */
    setupEventListeners() {
        if (this.testApiBtn) {
            this.testApiBtn.addEventListener('click', () => this.testApiConnection());
        }

        if (this.refreshApiKeyBtn) {
            this.refreshApiKeyBtn.addEventListener('click', () => this.refreshApiKey());
        }

        if (this.checkCacheBtn) {
            this.checkCacheBtn.addEventListener('click', () => this.checkCacheStatus());
        }
    }

    /**
     * Test API Connection
     */
    async testApiConnection() {
        this.showActionProgress('Testing API connection...');

        try {
            const response = await this.makeApiRequest('/admin/api/test-connection');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                throw new Error('Server returned non-JSON response: ' + textResponse.substring(0, 100) + '...');
            }

            const data = await response.json();

            if (data.success) {
                this.showActionResult('success', `✅ API Test Success! Latency: ${data.latency}ms, Status: ${data.status}`);
            } else {
                this.showActionResult('danger', `❌ API Test Failed: ${data.error}`);
            }
        } catch (error) {
            this.showActionResult('danger', `❌ Connection Error: ${error.message}`);
        }
    }

    /**
     * Refresh API Key
     */
    async refreshApiKey() {
        if (!confirm('Are you sure you want to refresh the API key? This will clear the current cached key.')) {
            return;
        }

        this.showActionProgress('Refreshing API key...');

        try {
            const response = await this.makeApiRequest('/admin/api/refresh-key');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                throw new Error('Server returned non-JSON response: ' + textResponse.substring(0, 100) + '...');
            }

            const data = await response.json();

            if (data.success) {
                this.showActionResult('success', `🔑 API Key refreshed successfully! New key expires at: ${data.expires_at}`);
                setTimeout(() => location.reload(), 2000); // Reload to show new data
            } else {
                this.showActionResult('danger', `❌ Failed to refresh API key: ${data.error}`);
            }
        } catch (error) {
            this.showActionResult('danger', `❌ Refresh Error: ${error.message}`);
        }
    }

    /**
     * Check Cache Status
     */
    async checkCacheStatus() {
        this.showActionProgress('Analyzing cache status...');

        try {
            const response = await this.makeApiRequest('/admin/api/cache-status');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                throw new Error('Server returned non-JSON response: ' + textResponse.substring(0, 100) + '...');
            }

            const data = await response.json();

            if (data.success) {
                const hitRate = data.hit_rate;
                const emoji = hitRate >= 80 ? '🟢' : hitRate >= 60 ? '🟡' : '🔴';
                this.showActionResult('info',
                    `${emoji} Cache Analysis: Hit Rate ${hitRate}% | Total: ${data.total} requests | Efficiency: ${data.efficiency}%`
                );
            } else {
                this.showActionResult('danger', `❌ Cache analysis failed: ${data.error}`);
            }
        } catch (error) {
            this.showActionResult('danger', `❌ Analysis Error: ${error.message}`);
        }
    }

    /**
     * Make API request with CSRF token
     */
    async makeApiRequest(url) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        });
    }

    /**
     * Show action progress state
     */
    showActionProgress(message) {
        if (!this.actionMessage || !this.actionAlert || !this.actionSpinner || !this.actionResults) return;

        this.actionMessage.textContent = message;
        this.actionAlert.className = 'alert alert-info';
        this.actionSpinner.style.display = 'inline-block';
        this.actionResults.style.display = 'block';
    }

    /**
     * Show action result
     */
    showActionResult(type, message) {
        if (!this.actionMessage || !this.actionAlert || !this.actionSpinner) return;

        this.actionMessage.textContent = message;
        this.actionAlert.className = `alert alert-${type}`;
        this.actionSpinner.style.display = 'none';

        // Auto hide after 5 seconds for success messages
        if (type === 'success' && this.actionResults) {
            setTimeout(() => {
                this.actionResults.style.display = 'none';
            }, 5000);
        }
    }
}
