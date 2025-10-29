import template from './trending-searches-dashboard.html.twig';
import './trending-searches-dashboard.scss';

const { Component, Mixin } = Shopware;

Component.register('trending-searches-dashboard', {
    template,

    inject: ['searchOptimizerApiService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            trendingSearches: [],
            trendingNow: [],
            activeTrends: 0,
            totalSearchesHour: 0,
            avgTrendScore: 0,
            alertsSent: 0,
            chartData: [],
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: this.$tc('search-optimizer.trending.searchCount')
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: this.$tc('search-optimizer.trending.time')
                        }
                    }
                }
            },
            salesChannels: [],
            selectedSalesChannel: null,
            selectedPeriod: '24h',
            timePeriods: [
                { value: '1h', label: this.$tc('search-optimizer.trending.lastHour') },
                { value: '6h', label: this.$tc('search-optimizer.trending.last6Hours') },
                { value: '24h', label: this.$tc('search-optimizer.trending.last24Hours') },
                { value: '7d', label: this.$tc('search-optimizer.trending.last7Days') }
            ],
            refreshInterval: null,
            columns: [
                {
                    property: 'search_term',
                    label: this.$tc('search-optimizer.trending.searchTerm'),
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'trend_score',
                    label: this.$tc('search-optimizer.trending.trendScore'),
                    allowResize: true,
                    align: 'center',
                    width: '120px'
                },
                {
                    property: 'search_count',
                    label: this.$tc('search-optimizer.trending.searches'),
                    allowResize: true,
                    align: 'center',
                    width: '150px'
                },
                {
                    property: 'active_hours',
                    label: this.$tc('search-optimizer.trending.duration'),
                    allowResize: true,
                    align: 'center',
                    width: '100px'
                },
                {
                    property: 'alert_sent',
                    label: this.$tc('search-optimizer.trending.alert'),
                    allowResize: true,
                    align: 'center',
                    width: '80px'
                }
            ]
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        hasRealtimeTrends() {
            return this.trendingNow.length > 0;
        }
    },

    created() {
        this.loadSalesChannels();
        this.loadData();
        this.startAutoRefresh();
    },

    beforeDestroy() {
        this.stopAutoRefresh();
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
                    period: this.selectedPeriod,
                    salesChannelId: this.selectedSalesChannel
                };

                // Load trending data
                const response = await this.searchOptimizerApiService.getTrendingSearches(params);
                
                this.trendingSearches = response.trends || [];
                this.activeTrends = response.activeTrends || 0;
                this.totalSearchesHour = response.totalSearchesHour || 0;
                this.avgTrendScore = response.avgTrendScore || 0;
                this.alertsSent = response.alertsSent || 0;
                
                // Load real-time trends
                await this.loadRealtimeTrends();
                
                // Prepare chart data
                this.prepareChartData(response.timeline || []);

            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        async loadRealtimeTrends() {
            try {
                const response = await this.searchOptimizerApiService.getRealtimeTrends({
                    salesChannelId: this.selectedSalesChannel
                });
                
                this.trendingNow = response.trends || [];
            } catch (error) {
                console.error('Failed to load realtime trends:', error);
            }
        },

        prepareChartData(timeline) {
            if (!timeline || timeline.length === 0) {
                this.chartData = [];
                return;
            }

            // Group by search term
            const termData = {};
            timeline.forEach(item => {
                if (!termData[item.search_term]) {
                    termData[item.search_term] = {
                        label: item.search_term,
                        data: [],
                        borderColor: this.getRandomColor(),
                        backgroundColor: 'transparent',
                        tension: 0.4
                    };
                }
                
                termData[item.search_term].data.push({
                    x: new Date(item.hour_timestamp),
                    y: item.search_count
                });
            });

            // Convert to array and take top 5
            this.chartData = Object.values(termData)
                .sort((a, b) => {
                    const sumA = a.data.reduce((sum, point) => sum + point.y, 0);
                    const sumB = b.data.reduce((sum, point) => sum + point.y, 0);
                    return sumB - sumA;
                })
                .slice(0, 5);
        },

        getRandomColor() {
            const colors = [
                '#189eff', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f7b731',
                '#5f27cd', '#00d2d3', '#ff9ff3', '#54a0ff', '#48dbfb'
            ];
            return colors[Math.floor(Math.random() * colors.length)];
        },

        getTrendIcon(score) {
            if (score > 500) return 'regular-rocket';
            if (score > 200) return 'regular-fire';
            if (score > 100) return 'regular-long-arrow-up';
            return 'regular-arrow-up';
        },

        getTrendColor(score) {
            if (score > 500) return '#ff6b6b';
            if (score > 200) return '#f7b731';
            if (score > 100) return '#4ecdc4';
            return '#45b7d1';
        },

        async viewSearchResults(item) {
            // Navigate to search results
            this.$router.push({
                name: 'sw.product.index',
                query: { search: item.search_term }
            });
        },

        async createRedirect(item) {
            // Show modal to create redirect
            this.$router.push({
                name: 'search.optimizer.redirect.create',
                query: { term: item.search_term }
            });
        },

        async addToPromotions(item) {
            this.createNotificationInfo({
                title: this.$tc('search-optimizer.trending.promote'),
                message: this.$tc('search-optimizer.trending.promoteMessage', 0, { term: item.search_term })
            });
        },

        onSalesChannelChange(salesChannelId) {
            this.selectedSalesChannel = salesChannelId;
            this.loadData();
        },

        startAutoRefresh() {
            // Refresh every 5 minutes
            this.refreshInterval = setInterval(() => {
                this.loadRealtimeTrends();
            }, 5 * 60 * 1000);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        }
    }
});