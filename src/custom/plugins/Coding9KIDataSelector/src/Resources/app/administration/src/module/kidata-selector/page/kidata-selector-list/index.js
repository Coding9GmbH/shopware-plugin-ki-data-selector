import template from './kidata-selector-list.html.twig';
import './kidata-selector-list.scss';
import VersionHelper from '../../../../core/version-helper';

const { Component, Mixin } = Shopware;

Component.register('kidata-selector-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            queries: [],
            isLoading: false,
            total: 0
        };
    },

    computed: {
        hasQueries() {
            return this.queries.length > 0;
        }
    },

    created() {
        this.loadQueries();
    },

    methods: {
        async loadQueries() {
            this.isLoading = true;

            try {
                const response = await VersionHelper.apiFetch('/api/_action/kidata/saved', {
                    method: 'GET'
                });

                const data = await response.json();

                if (data.success) {
                    this.queries = data.queries || [];
                    this.total = this.queries.length;
                } else {
                    this.createNotificationError({
                        title: 'Fehler',
                        message: data.error || 'Fehler beim Laden der gespeicherten Queries'
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

        async deleteQuery(id) {
            const confirmed = confirm('Möchten Sie diese Query wirklich löschen?');

            if (!confirmed) {
                return;
            }

            try {
                const response = await VersionHelper.apiFetch(`/api/_action/kidata/saved/${id}`, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    this.createNotificationSuccess({
                        title: 'Erfolg',
                        message: 'Query wurde gelöscht'
                    });
                    this.loadQueries();
                } else {
                    this.createNotificationError({
                        title: 'Fehler',
                        message: data.error || 'Fehler beim Löschen'
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: error.message || 'An error occurred'
                });
            }
        },

        onCreateQuery() {
            this.$router.push({ name: 'kidata.selector.index' });
        },

        onViewQuery(id) {
            this.$router.push({ name: 'kidata.selector.detail', params: { id } });
        }
    }
});
