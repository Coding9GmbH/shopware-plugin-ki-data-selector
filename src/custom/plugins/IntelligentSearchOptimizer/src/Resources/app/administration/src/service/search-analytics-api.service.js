const { ApiService } = Shopware.Classes;

class SearchAnalyticsApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'search-optimizer') {
        super(httpClient, loginService, apiEndpoint);
    }

    getDashboardData(params = {}) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get('search-optimizer/analytics/dashboard', {
                params,
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getTopSearches(params = {}) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get('search-optimizer/analytics/top-searches', {
                params,
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getZeroResults(params = {}) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get('search-optimizer/analytics/zero-results', {
                params,
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    exportData(params = {}) {
        const headers = this.getBasicHeaders();
        headers['Content-Type'] = 'text/csv';

        return this.httpClient
            .get('search-optimizer/analytics/export', {
                params,
                headers,
                responseType: 'blob'
            });
    }
}

Shopware.Service('searchAnalyticsApiService', SearchAnalyticsApiService);