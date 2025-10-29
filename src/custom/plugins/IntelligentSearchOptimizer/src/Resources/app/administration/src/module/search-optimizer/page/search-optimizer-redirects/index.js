import template from './search-optimizer-redirects.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('search-optimizer-redirects', {
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
        redirectRepository() {
            return this.repositoryFactory.create('search_redirect');
        },

        columns() {
            return [
                {
                    property: 'searchTerm',
                    label: this.$tc('search-optimizer.redirects.searchTerm'),
                    routerLink: 'search.optimizer.redirects.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'targetUrl',
                    label: this.$tc('search-optimizer.redirects.targetUrl'),
                    inlineEdit: 'string',
                    allowResize: true
                },
                {
                    property: 'targetType',
                    label: this.$tc('search-optimizer.redirects.targetType'),
                    allowResize: true
                },
                {
                    property: 'priority',
                    label: this.$tc('search-optimizer.redirects.priority'),
                    inlineEdit: 'number',
                    allowResize: true,
                    align: 'right'
                },
                {
                    property: 'active',
                    label: this.$tc('search-optimizer.redirects.active'),
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
            criteria.addSorting(Criteria.sort('priority', 'DESC'));

            return criteria;
        }
    },

    created() {
        this.repository = this.redirectRepository;
        
        const searchTerm = this.$route.query.term;
        if (searchTerm) {
            this.showModal = true;
            this.currentItem = this.repository.create(Shopware.Context.api);
            this.currentItem.searchTerm = searchTerm;
            this.currentItem.targetType = 'url';
            this.currentItem.priority = 0;
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

        onInlineEditSave(promise, item) {
            promise.then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('search-optimizer.redirects.saveSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('search-optimizer.redirects.saveError')
                });
                this.getList();
            });
        },

        onClickNew() {
            this.showModal = true;
            this.currentItem = this.repository.create(Shopware.Context.api);
            this.currentItem.targetType = 'url';
            this.currentItem.priority = 0;
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
            if (!this.currentItem.searchTerm || !this.currentItem.targetUrl) {
                this.createNotificationError({
                    message: this.$tc('search-optimizer.redirects.validationError')
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
                        message: this.$tc(isNew ? 'search-optimizer.redirects.createSuccess' : 'search-optimizer.redirects.saveSuccess')
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc(isNew ? 'search-optimizer.redirects.createError' : 'search-optimizer.redirects.saveError')
                    });
                });
        },

        onSelectCategory(category) {
            if (category) {
                this.currentItem.targetUrl = `/navigation/${category.id}`;
            }
        },

        onSelectProduct(product) {
            if (product) {
                this.currentItem.targetUrl = `/detail/${product.id}`;
            }
        },

        onSelectLandingPage(landingPage) {
            if (landingPage) {
                this.currentItem.targetUrl = `/landingpage/${landingPage.id}`;
            }
        }
    }
});