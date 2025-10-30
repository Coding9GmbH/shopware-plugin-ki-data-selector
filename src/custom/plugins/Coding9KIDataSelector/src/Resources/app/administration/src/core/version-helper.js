/**
 * Version Helper for Shopware Administration
 *
 * Provides compatibility helpers for different Shopware versions
 */

export default class VersionHelper {
    /**
     * Get the auth token for API requests
     * Works across Shopware 6.4 - 6.7
     */
    static getAuthToken() {
        try {
            // Try 6.5+ way first
            if (Shopware.Context?.api?.authToken?.access) {
                return Shopware.Context.api.authToken.access;
            }

            // Fallback for 6.4
            if (Shopware.Context?.api?.authToken) {
                return Shopware.Context.api.authToken;
            }

            // Another fallback
            if (Shopware.State?.getters?.['context/token']) {
                return Shopware.State.getters['context/token'];
            }

            throw new Error('Could not retrieve auth token');
        } catch (error) {
            console.error('Failed to get auth token:', error);
            throw error;
        }
    }

    /**
     * Get API headers for fetch requests
     * @returns {Object} Headers object
     */
    static getApiHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`
        };
    }

    /**
     * Create an API service instance
     * @param {string} name - Service name
     * @returns {Object} Service instance
     */
    static getApiService(name) {
        // 6.5+
        if (Shopware.Service && typeof Shopware.Service === 'function') {
            return Shopware.Service(name);
        }

        // 6.4
        if (Shopware.Application?.getContainer) {
            const container = Shopware.Application.getContainer('service');
            return container.get(name);
        }

        throw new Error(`Could not get API service: ${name}`);
    }

    /**
     * Get repository for an entity
     * @param {string} entityName - Entity name
     * @returns {Object} Repository
     */
    static getRepository(entityName) {
        const repositoryFactory = Shopware.Service('repositoryFactory');
        return repositoryFactory.create(entityName);
    }

    /**
     * Get Shopware version
     * @returns {string|null} Version string or null
     */
    static getShopwareVersion() {
        try {
            // Try different ways to get version
            if (Shopware.Context?.app?.config?.version) {
                return Shopware.Context.app.config.version;
            }

            if (Shopware.Application?.version) {
                return Shopware.Application.version;
            }

            return null;
        } catch (error) {
            console.warn('Could not determine Shopware version:', error);
            return null;
        }
    }

    /**
     * Check if current version is >= target version
     * @param {string} targetVersion - Version to compare (e.g., '6.5.0.0')
     * @returns {boolean}
     */
    static isVersionGte(targetVersion) {
        const currentVersion = this.getShopwareVersion();
        if (!currentVersion) {
            return false;
        }

        return this.compareVersions(currentVersion, targetVersion) >= 0;
    }

    /**
     * Compare two version strings
     * @param {string} v1 - First version
     * @param {string} v2 - Second version
     * @returns {number} -1, 0, or 1
     */
    static compareVersions(v1, v2) {
        const parts1 = v1.split('.').map(Number);
        const parts2 = v2.split('.').map(Number);

        for (let i = 0; i < Math.max(parts1.length, parts2.length); i++) {
            const part1 = parts1[i] || 0;
            const part2 = parts2[i] || 0;

            if (part1 > part2) return 1;
            if (part1 < part2) return -1;
        }

        return 0;
    }

    /**
     * Safe clipboard copy (handles different browser APIs)
     * @param {string} text - Text to copy
     * @returns {Promise<void>}
     */
    static async copyToClipboard(text) {
        // Modern clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        // Fallback for older browsers
        return new Promise((resolve, reject) => {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                document.body.removeChild(textarea);
                resolve();
            } catch (error) {
                document.body.removeChild(textarea);
                reject(error);
            }
        });
    }

    /**
     * Create download from blob (version-safe)
     * @param {Blob} blob - Blob to download
     * @param {string} filename - Filename
     */
    static downloadBlob(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();

        // Cleanup
        setTimeout(() => {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }, 100);
    }

    /**
     * Make API request with proper headers
     * @param {string} url - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Response>}
     */
    static async apiFetch(url, options = {}) {
        const defaultOptions = {
            headers: this.getApiHeaders(),
            ...options
        };

        // Merge headers if provided
        if (options.headers) {
            defaultOptions.headers = {
                ...defaultOptions.headers,
                ...options.headers
            };
        }

        return fetch(url, defaultOptions);
    }

    /**
     * Make API POST request with JSON body
     * @param {string} url - API endpoint
     * @param {Object} data - Data to send
     * @returns {Promise<any>} Parsed JSON response
     */
    static async apiPost(url, data) {
        const response = await this.apiFetch(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`API request failed: ${response.statusText}`);
        }

        return response.json();
    }
}
