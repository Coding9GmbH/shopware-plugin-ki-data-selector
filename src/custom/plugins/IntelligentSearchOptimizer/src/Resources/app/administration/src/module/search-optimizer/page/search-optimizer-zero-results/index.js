import template from './search-optimizer-zero-results.html.twig';

const { Component, Mixin } = Shopware;

Component.register('search-optimizer-zero-results', {
    template,

    inject: ['searchAnalyticsApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: true,
            items: [],
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
        columns() {
            return [
                {
                    property: 'search_term',
                    label: this.$tc('search-optimizer.zeroResults.term'),
                    rawData: true,
                    primary: true
                },
                {
                    property: 'search_count',
                    label: this.$tc('search-optimizer.zeroResults.count'),
                    align: 'right'
                },
                {
                    property: 'unique_sessions',
                    label: this.$tc('search-optimizer.zeroResults.uniqueSessions'),
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
        this.loadData();
    },

    methods: {
        async loadData() {
            this.isLoading = true;

            try {
                const params = {
                    limit: 100,
                    salesChannelId: this.selectedSalesChannelId,
                    languageId: this.selectedLanguageId
                };

                if (this.dateFrom) {
                    params.from = this.dateFrom;
                }

                if (this.dateTo) {
                    params.to = this.dateTo;
                }

                const response = await this.searchAnalyticsApiService.getZeroResults(params);
                this.items = response.data || [];
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('search-optimizer.zeroResults.error.title'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        onRefresh() {
            this.loadData();
        },

        createSynonym(item) {
            this.$router.push({ 
                name: 'search.optimizer.synonyms', 
                query: { term: item.search_term } 
            });
        },

        createRedirect(item) {
            this.$router.push({ 
                name: 'search.optimizer.redirects', 
                query: { term: item.search_term } 
            });
        },

        onExport() {
            const params = {
                type: 'zero',
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
                    link.setAttribute('download', `zero-results-${new Date().toISOString().split('T')[0]}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                })
                .catch(error => {
                    this.createNotificationError({
                        title: this.$tc('search-optimizer.zeroResults.error.export'),
                        message: error.message
                    });
                });
        }
    }
});