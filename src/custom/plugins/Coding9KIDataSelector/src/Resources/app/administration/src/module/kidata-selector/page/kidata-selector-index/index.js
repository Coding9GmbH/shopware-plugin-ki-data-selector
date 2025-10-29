import template from './kidata-selector-index.html.twig';
import './kidata-selector-index.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('kidata-selector-index', {
    template,

    inject: ['systemConfigApiService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            prompt: '',
            originalPrompt: '', // Store original prompt for context
            sql: '',
            columns: [],
            rows: [],
            total: 0,
            page: 1,
            limit: 25,
            totalPages: 0,
            model: 'gpt-4o-mini',
            executed: false,
            lastError: null,
            failedSql: null
        };
    },

    computed: {
        hasResults() {
            return this.rows.length > 0;
        },

        hasSQL() {
            return this.sql !== '';
        },

        canExecute() {
            return !this.isLoading && this.hasSQL;
        },

        canExport() {
            return !this.isLoading && this.hasResults;
        },

        hasError() {
            return this.lastError !== null;
        }
    },

    methods: {
        async generateSQL() {
            if (!this.prompt.trim()) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: 'Please enter a prompt'
                });
                return;
            }

            this.isLoading = true;
            this.sql = '';
            this.rows = [];
            this.columns = [];
            this.total = 0;
            this.executed = false;

            // Save original prompt for error correction context
            this.originalPrompt = this.prompt;

            try {
                const response = await this.callApi({
                    prompt: this.prompt,
                    execute: false
                });

                if (response.success) {
                    this.sql = response.sql;
                    this.createNotificationSuccess({
                        title: this.$tc('kidata-selector.page.successTitle'),
                        message: 'SQL generated successfully'
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('kidata-selector.page.errorTitle'),
                        message: response.error || 'Failed to generate SQL'
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        confirmAndExecute() {
            // Show confirmation dialog
            const confirmed = confirm(
                'Sind Sie sicher, dass Sie diese SQL-Abfrage ausführen möchten?\n\n' +
                'Sie sind selbst verantwortlich für mögliche Performance-Probleme oder fehlerhafte Ergebnisse.'
            );

            if (confirmed) {
                this.executeQuery();
            }
        },

        async executeQuery() {
            if (!this.sql.trim()) {
                await this.generateSQL();
                if (!this.sql.trim()) {
                    return;
                }
            }

            this.isLoading = true;
            this.lastError = null;
            this.failedSql = null;

            try {
                const response = await this.callApi({
                    sql: this.sql, // Send generated SQL instead of regenerating
                    execute: true,
                    page: this.page,
                    limit: this.limit
                });

                if (response.success) {
                    this.sql = response.sql;
                    this.columns = response.columns || [];
                    this.rows = response.rows || [];
                    this.total = response.total || 0;
                    this.page = response.page || 1;
                    this.totalPages = response.totalPages || 0;
                    this.executed = true;

                    this.createNotificationSuccess({
                        title: this.$tc('kidata-selector.page.successTitle'),
                        message: `Found ${this.total} results`
                    });
                } else {
                    // Store error for retry
                    this.lastError = response.error || 'Query execution failed';
                    this.failedSql = this.sql;

                    this.createNotificationError({
                        title: this.$tc('kidata-selector.page.errorTitle'),
                        message: response.error || 'Query execution failed'
                    });
                }
            } catch (error) {
                // Store error for retry
                this.lastError = error.message || 'An error occurred';
                this.failedSql = this.sql;

                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        async changePage(params) {
            // sw-pagination passes an object with { page, limit }
            this.page = params.page || params;
            if (params.limit) {
                this.limit = params.limit;
            }
            await this.executeQuery();
        },

        async fixQueryWithError() {
            if (!this.failedSql || !this.lastError) {
                return;
            }

            this.isLoading = true;

            try {
                // Create error context prompt with original context
                let errorPrompt = 'Der folgende SQL Query hat einen Fehler verursacht:\n\n';

                // Include original prompt if available
                if (this.originalPrompt) {
                    errorPrompt += `Ursprüngliche Anfrage: ${this.originalPrompt}\n\n`;
                }

                errorPrompt += `Generierter SQL: ${this.failedSql}\n\n`;
                errorPrompt += `Fehler: ${this.lastError}\n\n`;
                errorPrompt += 'Bitte analysiere den Fehler und generiere einen korrigierten SQL Query.';

                // Update prompt field with error context
                this.prompt = errorPrompt;

                const response = await this.callApi({
                    prompt: errorPrompt,
                    errorContext: {
                        originalPrompt: this.originalPrompt,
                        failedSql: this.failedSql,
                        error: this.lastError
                    },
                    execute: false
                });

                if (response.success) {
                    this.sql = response.sql;
                    this.lastError = null;
                    this.failedSql = null;

                    this.createNotificationSuccess({
                        title: this.$tc('kidata-selector.page.successTitle'),
                        message: 'Query wurde korrigiert. Bitte prüfen und erneut ausführen.'
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('kidata-selector.page.errorTitle'),
                        message: response.error || 'Fehlerkorrektur fehlgeschlagen'
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        async exportCSV() {
            if (!this.sql.trim()) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: 'Please generate SQL first'
                });
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/api/_action/kidata/export', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
                    },
                    body: JSON.stringify({
                        sql: this.sql, // Send generated SQL instead of prompt
                        delimiter: ';',
                        enclosure: '"'
                    })
                });

                if (!response.ok) {
                    throw new Error('Export failed');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `kidata-export-${Date.now()}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                this.createNotificationSuccess({
                    title: this.$tc('kidata-selector.page.successTitle'),
                    message: 'CSV exported successfully'
                });
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: error.message || 'Export failed'
                });
            } finally {
                this.isLoading = false;
            }
        },

        async callApi(payload) {
            const response = await fetch('/api/_action/kidata/query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
                },
                body: JSON.stringify(payload)
            });

            return await response.json();
        },

        copySQLToClipboard() {
            if (!this.sql) return;

            navigator.clipboard.writeText(this.sql).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('kidata-selector.page.successTitle'),
                    message: 'SQL copied to clipboard'
                });
            });
        },

        async saveQuery() {
            if (!this.sql) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: 'Kein SQL zum Speichern vorhanden'
                });
                return;
            }

            const name = prompt('Name für die Query:');
            if (!name) {
                return;
            }

            const description = prompt('Beschreibung (optional):');

            this.isLoading = true;

            try {
                const response = await fetch('/api/_action/kidata/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
                    },
                    body: JSON.stringify({
                        name: name,
                        description: description || '',
                        sql: this.sql,
                        prompt: this.prompt
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.createNotificationSuccess({
                        title: this.$tc('kidata-selector.page.successTitle'),
                        message: 'Query wurde gespeichert'
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('kidata-selector.page.errorTitle'),
                        message: data.error || 'Fehler beim Speichern'
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('kidata-selector.page.errorTitle'),
                    message: error.message || 'An error occurred'
                });
            } finally {
                this.isLoading = false;
            }
        },

        goToSavedQueries() {
            this.$router.push({ name: 'kidata.selector.list' });
        }
    }
});
