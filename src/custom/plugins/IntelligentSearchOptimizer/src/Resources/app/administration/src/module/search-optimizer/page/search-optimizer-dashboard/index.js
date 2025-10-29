import template from './search-optimizer-dashboard.html.twig';
import './search-optimizer-dashboard.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('search-optimizer-dashboard', {
    template,

    inject: ['searchAnalyticsApiService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: true,
            dashboardData: null,
            dateFrom: null,
            dateTo: null,
            selectedSalesChannelId: null,
            selectedLanguageId: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        topSearchColumns() {
            return [
                {
                    property: 'search_term',
                    label: this.$tc('search-optimizer.dashboard.topSearches.term'),
                    rawData: true
                },
                {
                    property: 'search_count',
                    label: this.$tc('search-optimizer.dashboard.topSearches.count'),
                    align: 'right'
                },
                {
                    property: 'avg_results',
                    label: this.$tc('search-optimizer.dashboard.topSearches.results'),
                    align: 'right'
                },
                {
                    property: 'conversion_rate',
                    label: this.$tc('search-optimizer.dashboard.topSearches.conversion'),
                    rawData: true,
                    align: 'right'
                }
            ];
        },

        zeroResultColumns() {
            return [
                {
                    property: 'search_term',
                    label: this.$tc('search-optimizer.zeroResults.term'),
                    rawData: true
                },
                {
                    property: 'search_count',
                    label: this.$tc('search-optimizer.zeroResults.count'),
                    align: 'right'
                },
                {
                    property: 'last_searched',
                    label: this.$tc('search-optimizer.zeroResults.lastSearched'),
                    align: 'right'
                }
            ];
        }
    },

    created() {
        this.loadDashboardData();
    },

    methods: {
        async loadDashboardData() {
            this.isLoading = true;

            try {
                const params = {
                    salesChannelId: this.selectedSalesChannelId,
                    languageId: this.selectedLanguageId
                };

                if (this.dateFrom) {
                    params.from = this.dateFrom;
                }

                if (this.dateTo) {
                    params.to = this.dateTo;
                }

                const response = await this.searchAnalyticsApiService.getDashboardData(params);
                this.dashboardData = response;
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('search-optimizer.dashboard.error.title'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        onRefresh() {
            this.loadDashboardData();
        },

        onExport(type) {
            const params = {
                type: type,
                salesChannelId: this.selectedSalesChannelId,
                languageId: this.selectedLanguageId
            };

            if (this.dateFrom) {
                params.from = this.dateFrom;
            }

            if (this.dateTo) {
                params.to = this.dateTo;
            }

            this.searchAnalyticsApiService.exportData(params)
                .then(response => {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `search-analytics-${type}-${new Date().toISOString().split('T')[0]}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                })
                .catch(error => {
                    this.createNotificationError({
                        title: this.$tc('search-optimizer.dashboard.error.export'),
                        message: error.message
                    });
                });
        },

        getConversionVariant(rate) {
            if (rate >= 10) return 'success';
            if (rate >= 5) return 'warning';
            return 'danger';
        },

        createSynonym(item) {
            this.$router.push({ name: 'search.optimizer.synonyms', query: { term: item.search_term } });
        },

        createRedirect(item) {
            this.$router.push({ name: 'search.optimizer.redirects', query: { term: item.search_term } });
        }
    }
});