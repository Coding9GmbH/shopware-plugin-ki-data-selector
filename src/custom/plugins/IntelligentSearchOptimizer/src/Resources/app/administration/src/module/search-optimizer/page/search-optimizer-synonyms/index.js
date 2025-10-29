import template from './search-optimizer-synonyms.html.twig';
import './search-optimizer-synonyms.scss';
import '../../component/search-normalization-tester';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('search-optimizer-synonyms', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing')
    ],

    data() {
        return {
            repository: null,
            items: null,
            showModal: false,
            currentItem: null,
            isLoading: true,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        synonymRepository() {
            return this.repositoryFactory.create('search_synonym');
        },

        columns() {
            return [
                {
                    property: 'keyword',
                    label: this.$tc('search-optimizer.synonyms.keyword'),
                    routerLink: 'search.optimizer.synonyms.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'synonym',
                    label: this.$tc('search-optimizer.synonyms.synonym'),
                    inlineEdit: 'string',
                    allowResize: true
                },
                {
                    property: 'language.name',
                    label: this.$tc('search-optimizer.synonyms.language'),
                    allowResize: true
                },
                {
                    property: 'salesChannel.name',
                    label: this.$tc('search-optimizer.synonyms.salesChannel'),
                    allowResize: true
                },
                {
                    property: 'active',
                    label: this.$tc('search-optimizer.synonyms.active'),
                    inlineEdit: 'boolean',
                    allowResize: true,
                    align: 'center'
                }
            ];
        },

        criteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.setTerm(this.term);
            criteria.addAssociation('language');
            criteria.addAssociation('salesChannel');

            return criteria;
        }
    },

    created() {
        this.repository = this.synonymRepository;
        
        // Check if we should create a new synonym for a search term
        const searchTerm = this.$route.query.term;
        if (searchTerm) {
            this.showModal = true;
            this.currentItem = this.repository.create(Shopware.Context.api);
            this.currentItem.keyword = searchTerm;
            this.currentItem.active = true;
        }
    },

    methods: {
        getList() {
            this.isLoading = true;

            return this.repository
                .search(this.criteria, Shopware.Context.api)
                .then((result) => {
                    this.items = result;
                    this.total = result.total;
                    this.isLoading = false;

                    return result;
                });
        },

        onChangeLanguage(languageId) {
            Shopware.State.commit('context/setApiLanguageId', languageId);
            this.getList();
        },

        onInlineEditSave(promise, item) {
            promise.then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('search-optimizer.synonyms.saveSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('search-optimizer.synonyms.saveError')
                });
                this.getList();
            });
        },

        onClickNew() {
            this.showModal = true;
            this.currentItem = this.repository.create(Shopware.Context.api);
            this.currentItem.active = true;
        },

        onClickEdit(item) {
            this.showModal = true;
            this.currentItem = item;
        },

        onCloseModal() {
            this.showModal = false;
            this.currentItem = null;
        },

        onSaveModal() {
            if (!this.currentItem.keyword || !this.currentItem.synonym) {
                this.createNotificationError({
                    message: this.$tc('search-optimizer.synonyms.validationError')
                });
                return;
            }

            const isNew = !this.currentItem.id;

            this.repository
                .save(this.currentItem, Shopware.Context.api)
                .then(() => {
                    this.getList();
                    this.showModal = false;
                    this.createNotificationSuccess({
                        message: this.$tc(isNew ? 'search-optimizer.synonyms.createSuccess' : 'search-optimizer.synonyms.saveSuccess')
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc(isNew ? 'search-optimizer.synonyms.createError' : 'search-optimizer.synonyms.saveError')
                    });
                });
        }
    }
});