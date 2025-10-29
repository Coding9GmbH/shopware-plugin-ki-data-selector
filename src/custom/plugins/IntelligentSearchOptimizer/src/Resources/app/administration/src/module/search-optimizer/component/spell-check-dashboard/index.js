import template from './spell-check-dashboard.html.twig';
import './spell-check-dashboard.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('spell-check-dashboard', {
    template,

    inject: ['repositoryFactory', 'searchOptimizerApiService'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            suggestions: [],
            dictionaryWords: [],
            dictionarySearchTerm: '',
            showAddWordModal: false,
            newWord: {
                word: '',
                type: 'general',
                language: 'en-GB'
            },
            suggestionColumns: [
                {
                    property: 'misspelling',
                    label: this.$tc('search-optimizer.spellCheck.misspelling'),
                    routerLink: 'search.optimizer.detail',
                    allowResize: true
                },
                {
                    property: 'correction',
                    label: this.$tc('search-optimizer.spellCheck.correction'),
                    allowResize: true
                },
                {
                    property: 'confidence',
                    label: this.$tc('search-optimizer.spellCheck.confidence'),
                    allowResize: true
                },
                {
                    property: 'usage_count',
                    label: this.$tc('search-optimizer.spellCheck.usageCount'),
                    allowResize: true
                }
            ],
            dictionaryColumns: [
                {
                    property: 'word',
                    label: this.$tc('search-optimizer.spellCheck.word'),
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'type',
                    label: this.$tc('search-optimizer.spellCheck.type'),
                    allowResize: true
                },
                {
                    property: 'frequency',
                    label: this.$tc('search-optimizer.spellCheck.frequency'),
                    allowResize: true
                },
                {
                    property: 'language',
                    label: this.$tc('search-optimizer.spellCheck.language'),
                    allowResize: true
                }
            ]
        };
    },

    computed: {
        dictionaryRepository() {
            return this.repositoryFactory.create('search_optimizer_dictionary');
        },

        spellCorrectionRepository() {
            return this.repositoryFactory.create('search_optimizer_spell_corrections');
        }
    },

    created() {
        this.loadData();
    },

    methods: {
        async loadData() {
            this.isLoading = true;

            await Promise.all([
                this.loadSuggestions(),
                this.loadDictionary()
            ]);

            this.isLoading = false;
        },

        async loadSuggestions() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('auto_generated', true));
            criteria.addSorting(Criteria.sort('confidence', 'DESC'));
            criteria.setLimit(20);

            try {
                const result = await this.spellCorrectionRepository.search(criteria, Shopware.Context.api);
                this.suggestions = result;
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        async loadDictionary() {
            const criteria = new Criteria();
            
            if (this.dictionarySearchTerm) {
                criteria.addFilter(Criteria.contains('word', this.dictionarySearchTerm));
            }
            
            criteria.addSorting(Criteria.sort('frequency', 'DESC'));
            criteria.setPage(this.page);
            criteria.setLimit(this.limit);

            try {
                const result = await this.dictionaryRepository.search(criteria, Shopware.Context.api);
                this.dictionaryWords = result;
                this.total = result.total;
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        async approveSuggestion(suggestion) {
            try {
                await this.spellCorrectionRepository.save({
                    id: suggestion.id,
                    auto_generated: false
                }, Shopware.Context.api);

                // Add to dictionary
                await this.searchOptimizerApiService.addToDictionary({
                    word: suggestion.correction,
                    type: 'general',
                    language: suggestion.language
                });

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.spellCheck.suggestionApproved')
                });

                this.loadData();
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        async rejectSuggestion(suggestion) {
            try {
                await this.spellCorrectionRepository.delete(suggestion.id, Shopware.Context.api);

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.spellCheck.suggestionRejected')
                });

                this.loadSuggestions();
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        async saveWord() {
            if (!this.newWord.word) {
                return;
            }

            try {
                await this.searchOptimizerApiService.addToDictionary(this.newWord);

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.spellCheck.wordAdded')
                });

                this.showAddWordModal = false;
                this.newWord = {
                    word: '',
                    type: 'general',
                    language: 'en-GB'
                };

                this.loadDictionary();
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        async deleteWord(word) {
            try {
                await this.dictionaryRepository.delete(word.id, Shopware.Context.api);

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.spellCheck.wordDeleted')
                });

                this.loadDictionary();
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        editWord(word) {
            // Implement edit functionality
            console.log('Edit word:', word);
        },

        async importDictionary() {
            // Implement import functionality
            this.createNotificationInfo({
                title: this.$tc('global.default.info'),
                message: 'Import functionality coming soon'
            });
        },

        async exportDictionary() {
            try {
                const response = await this.searchOptimizerApiService.exportDictionary();
                
                // Create download link
                const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'spell-check-dictionary.json';
                link.click();
                window.URL.revokeObjectURL(url);

                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: this.$tc('search-optimizer.spellCheck.dictionaryExported')
                });
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message
                });
            }
        },

        getTypeVariant(type) {
            const variants = {
                'brand': 'info',
                'technical': 'warning',
                'general': 'default'
            };
            return variants[type] || 'default';
        }
    }
});