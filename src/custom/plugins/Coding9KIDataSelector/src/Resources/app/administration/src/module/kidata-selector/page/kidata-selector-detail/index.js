import template from './kidata-selector-detail.html.twig';
import './kidata-selector-detail.scss';
import VersionHelper from '../../../../core/version-helper';

const { Component, Mixin } = Shopware;

Component.register('kidata-selector-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            queryId: null,
            queryName: '',
            queryDescription: '',
            sql: '',
            originalPrompt: '',
            columns: [],
            rows: [],
            total: 0,
            page: 1,
            limit: 25,
            totalPages: 0,
            executed: false
        };
    },

    computed: {
        hasResults() {
            return this.rows.length > 0;
        },

        canExecute() {
            return !this.isLoading && this.sql !== '';
        },

        canExport() {
            return !this.isLoading && this.hasResults;
        }
    },

    created() {
        this.queryId = this.$route.params.id;
        this.loadQuery();
    },

    methods: {
        async loadQuery() {
            this.isLoading = true;

            try {
                const response = await VersionHelper.apiFetch(`/api/_action/kidata/saved/${this.queryId}`, {
                    method: 'GET'
                });

                const data = await response.json();

                if (data.success) {
                    const query = data.query;
                    this.queryName = query.name;
                    this.queryDescription = query.description || '';
                    this.sql = query.sql_query;
                    this.originalPrompt = query.original_prompt || '';
                } else {
                    this.createNotificationError({
                        title: 'Fehler',
                        message: data.error || 'Query nicht gefunden'
                    });
                    this.$router.push({ name: 'kidata.selector.list' });
                }
            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        confirmAndExecute() {
            const confirmed = confirm(
                'Sind Sie sicher, dass Sie diese SQL-Abfrage ausführen möchten?\n\n' +
                'Sie sind selbst verantwortlich für mögliche Performance-Probleme oder fehlerhafte Ergebnisse.'
            );

            if (confirmed) {
                this.executeQuery();
            }
        },

        async executeQuery() {
            this.isLoading = true;

            try {
                const response = await this.callApi({
                    sql: this.sql,
                    execute: true,
                    page: this.page,
                    limit: this.limit
                });

                if (response.success) {
                    this.columns = response.columns || [];
                    this.rows = response.rows || [];
                    this.total = response.total || 0;
                    this.page = response.page || 1;
                    this.totalPages = response.totalPages || 0;
                    this.executed = true;

                    this.createNotificationSuccess({
                        title: 'Erfolg',
                        message: `${this.total} Ergebnisse gefunden`
                    });
                } else {
                    this.createNotificationError({
                        title: 'Fehler',
                        message: response.error || 'Query execution failed'
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        async changePage(params) {
            this.page = params.page || params;
            if (params.limit) {
                this.limit = params.limit;
            }
            await this.executeQuery();
        },

        async exportCSV() {
            if (!this.sql.trim()) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Please generate SQL first'
                });
                return;
            }

            this.isLoading = true;

            try {
                const response = await VersionHelper.apiFetch('/api/_action/kidata/export', {
                    method: 'POST',
                    body: JSON.stringify({
                        sql: this.sql,
                        delimiter: ';',
                        enclosure: '"'
                    })
                });

                if (!response.ok) {
                    throw new Error('Export failed');
                }

                const blob = await response.blob();
                const filename = `${this.queryName.replace(/[^a-zA-Z0-9]/g, '_')}_${Date.now()}.csv`;
                VersionHelper.downloadBlob(blob, filename);

                this.createNotificationSuccess({
                    title: 'Erfolg',
                    message: 'CSV exported successfully'
                });
            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: error.message || 'Export failed'
                });
            } finally {
                this.isLoading = false;
            }
        },

        async callApi(payload) {
            return await VersionHelper.apiPost('/api/_action/kidata/query', payload);
        },

        async copySQLToClipboard() {
            if (!this.sql) return;

            try {
                await VersionHelper.copyToClipboard(this.sql);
                this.createNotificationSuccess({
                    title: 'Erfolg',
                    message: 'SQL copied to clipboard'
                });
            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Failed to copy to clipboard'
                });
            }
        },

        goBackToList() {
            this.$router.push({ name: 'kidata.selector.list' });
        }
    }
});
