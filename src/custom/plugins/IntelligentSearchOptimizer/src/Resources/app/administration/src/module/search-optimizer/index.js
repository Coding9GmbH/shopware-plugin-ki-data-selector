import './page/search-optimizer-dashboard';
import './page/search-optimizer-synonyms';
import './page/search-optimizer-redirects';
import './page/search-optimizer-zero-results';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const { Module } = Shopware;

Module.register('search-optimizer', {
    type: 'plugin',
    name: 'search-optimizer',
    title: 'search-optimizer.general.mainMenuItemGeneral',
    description: 'search-optimizer.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#ff6b6b',
    icon: 'default-action-search',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        dashboard: {
            component: 'search-optimizer-dashboard',
            path: 'dashboard'
        },
        synonyms: {
            component: 'search-optimizer-synonyms',
            path: 'synonyms'
        },
        redirects: {
            component: 'search-optimizer-redirects',
            path: 'redirects'
        },
        'zero-results': {
            component: 'search-optimizer-zero-results',
            path: 'zero-results'
        }
    },

    navigation: [{
        id: 'search-optimizer',
        label: 'search-optimizer.general.mainMenuItemGeneral',
        color: '#ff6b6b',
        icon: 'default-action-search',
        position: 100,
        parent: 'sw-marketing'
    }, {
        path: 'search.optimizer.dashboard',
        label: 'search-optimizer.general.mainMenuItemDashboard',
        parent: 'search-optimizer',
        position: 100
    }, {
        path: 'search.optimizer.zero.results',
        label: 'search-optimizer.general.mainMenuItemZeroResults',
        parent: 'search-optimizer',
        position: 200
    }, {
        path: 'search.optimizer.synonyms',
        label: 'search-optimizer.general.mainMenuItemSynonyms',
        parent: 'search-optimizer',
        position: 300
    }, {
        path: 'search.optimizer.redirects',
        label: 'search-optimizer.general.mainMenuItemRedirects',
        parent: 'search-optimizer',
        position: 400
    }]
});