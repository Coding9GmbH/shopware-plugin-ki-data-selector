import template from './revenue-tracking-dashboard.html.twig';
import './revenue-tracking-dashboard.scss';

const { Component, Mixin } = Shopware;

Component.register('revenue-tracking-dashboard', {
    template,

    inject: ['searchOptimizerApiService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            topRevenueTerms: [],
            totalRevenue: 0,
            avgRevenuePerSearch: 0,
            avgConversionRate: 0,
            avgConversionTime: '0m',
            intentAnalysis: [],
            salesChannels: [],
            selectedSalesChannel: null,
            dateRange: {
                from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // 30 days ago
                to: new Date()
            },
            showDetailsModal: false,
            selectedTerm: null,
            selectedTermOrders: [],
            revenueChartData: [],
            revenueChartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
                    }
                }
            },
            revenueColumns: [
                {
                    property: 'search_term',
                    label: this.$tc('search-optimizer.revenue.searchTerm'),
                    routerLink: 'search.optimizer.detail',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'search_count',
                    label: this.$tc('search-optimizer.revenue.searches'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'order_count',
                    label: this.$tc('search-optimizer.revenue.orders'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'total_revenue',
                    label: this.$tc('search-optimizer.revenue.totalRevenue'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'revenue_per_search',
                    label: this.$tc('search-optimizer.revenue.revenuePerSearch'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'conversion_rate',
                    label: this.$tc('search-optimizer.revenue.conversionRate'),
                    allowResize: true,
                    align: 'center'
                }
            ],
            orderColumns: [
                {
                    property: 'order_number',
                    label: this.$tc('search-optimizer.revenue.orderNumber'),
                    allowResize: true
                },
                {
                    property: 'customer_name',
                    label: this.$tc('search-optimizer.revenue.customer'),
                    allowResize: true
                },
                {
                    property: 'order_amount',
                    label: this.$tc('search-optimizer.revenue.amount'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'conversion_time',
                    label: this.$tc('search-optimizer.revenue.timeToConvert'),
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'created_at',
                    label: this.$tc('search-optimizer.revenue.orderDate'),
                    allowResize: true
                }
            ]
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        detailsTitle() {
            return this.selectedTerm 
                ? this.$tc('search-optimizer.revenue.detailsFor', 0, { term: this.selectedTerm.search_term })
                : '';
        }
    },

    created() {
        this.loadSalesChannels();
        this.loadData();
    },

    methods: {
        async loadSalesChannels() {
            try {
                const result = await this.salesChannelRepository.search(
                    new Shopware.Data.Criteria(),
                    Shopware.Context.api
                );
                
                this.salesChannels = result.map(channel => ({
                    value: channel.id,
                    label: channel.translated.name
                }));
            } catch (error) {
                console.error('Failed to load sales channels:', error);
            }
        },

        async loadData() {
            this.isLoading = true;

            try {
                const params = {
                    startDate: this.dateRange.from.toISOString(),
                    endDate: this.dateRange.to.toISOString()
                };

                if (this.selectedSalesChannel) {
                    params.salesChannelId = this.selectedSalesChannel;
                }

                // Load revenue stats
                const response = await this.searchOptimizerApiService.getRevenueStats(params);
                
                this.topRevenueTerms = response.topTerms || [];
                this.totalRevenue = response.totalRevenue || 0;
                this.avgRevenuePerSearch = response.avgRevenuePerSearch || 0;
                this.avgConversionRate = response.avgConversionRate || 0;
                this.avgConversionTime = this.formatMinutes(response.avgConversionTime || 0);
                
                // Load intent analysis
                await this.loadIntentAnalysis();

            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        async loadIntentAnalysis() {
            try {
                const response = await this.searchOptimizerApiService.getIntentRevenue({
                    startDate: this.dateRange.from.toISOString(),
                    endDate: this.dateRange.to.toISOString(),
                    salesChannelId: this.selectedSalesChannel
                });

                this.intentAnalysis = response.intents || [];
            } catch (error) {
                console.error('Failed to load intent analysis:', error);
            }
        },

        async showDetails(item) {
            this.selectedTerm = item;
            this.showDetailsModal = true;
            
            try {
                // Load order details
                const response = await this.searchOptimizerApiService.getSearchTermOrders({
                    searchTerm: item.search_term,
                    startDate: this.dateRange.from.toISOString(),
                    endDate: this.dateRange.to.toISOString(),
                    salesChannelId: this.selectedSalesChannel
                });

                this.selectedTermOrders = response.orders || [];
                
                // Prepare chart data
                this.prepareRevenueChart(response.timeline || []);

            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        prepareRevenueChart(timeline) {
            const labels = timeline.map(item => 
                new Date(item.date).toLocaleDateString()
            );
            
            const data = timeline.map(item => item.revenue);

            this.revenueChartData = [{
                label: this.$tc('search-optimizer.revenue.dailyRevenue'),
                data: data,
                borderColor: '#189eff',
                backgroundColor: 'rgba(24, 158, 255, 0.1)',
                tension: 0.4
            }];

            this.revenueChartOptions.labels = labels;
        },

        async exportData(item) {
            try {
                const response = await this.searchOptimizerApiService.exportRevenueData({
                    searchTerm: item.search_term,
                    startDate: this.dateRange.from.toISOString(),
                    endDate: this.dateRange.to.toISOString()
                });

                // Create download
                const blob = new Blob([response.data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `revenue-${item.search_term}-${Date.now()}.csv`;
                link.click();
                window.URL.revokeObjectURL(url);

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.revenue.exportSuccess')
                });
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        onDateRangeChange(dateRange) {
            this.dateRange = dateRange;
            this.loadData();
        },

        onSalesChannelChange(salesChannelId) {
            this.selectedSalesChannel = salesChannelId;
            this.loadData();
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'EUR'
            }).format(value || 0);
        },

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (days > 0) {
                return `${days}d ${hours % 24}h`;
            } else if (hours > 0) {
                return `${hours}h ${minutes % 60}m`;
            } else {
                return `${minutes}m`;
            }
        },

        formatMinutes(minutes) {
            if (minutes < 60) {
                return `${Math.round(minutes)}m`;
            }
            const hours = Math.floor(minutes / 60);
            return `${hours}h ${Math.round(minutes % 60)}m`;
        },

        getConversionRateVariant(rate) {
            if (rate >= 10) return 'success';
            if (rate >= 5) return 'warning';
            return 'danger';
        },

        getIntentLabel(type) {
            const labels = {
                'informational': this.$tc('search-optimizer.intent.informational'),
                'transactional': this.$tc('search-optimizer.intent.transactional'),
                'navigational': this.$tc('search-optimizer.intent.navigational')
            };
            return labels[type] || type;
        }
    }
});