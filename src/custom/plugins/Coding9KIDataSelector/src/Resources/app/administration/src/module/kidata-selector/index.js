import './acl';
import './page/kidata-selector-index';
import './page/kidata-selector-list';
import './page/kidata-selector-detail';

const { Module } = Shopware;

Module.register('kidata-selector', {
    type: 'plugin',
    name: 'KI Data Selector',
    title: 'kidata-selector.general.mainMenuItemGeneral',
    description: 'kidata-selector.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'regular-database',

    snippets: {
        'de-DE': {
            'kidata-selector': {
                general: {
                    mainMenuItemGeneral: 'KI Data Selector',
                    descriptionTextModule: 'AI-gestützter SQL-Query-Generator'
                },
                page: {
                    title: 'KI Data Selector',
                    promptLabel: 'Ihre Frage',
                    promptPlaceholder: 'z.B. "Gib mir alle Bestellungen der letzten Woche"',
                    modelLabel: 'OpenAI Modell',
                    generateButton: 'SQL generieren',
                    executeButton: 'Ausführen',
                    exportButton: 'Als CSV exportieren',
                    sqlLabel: 'Generierter SQL-Query',
                    resultsLabel: 'Ergebnisse',
                    totalLabel: 'Gesamt',
                    pageLabel: 'Seite',
                    limitLabel: 'Pro Seite',
                    errorTitle: 'Fehler',
                    successTitle: 'Erfolg',
                    noResults: 'Keine Ergebnisse gefunden',
                    loading: 'Lädt...'
                }
            }
        },
        'en-GB': {
            'kidata-selector': {
                general: {
                    mainMenuItemGeneral: 'KI Data Selector',
                    descriptionTextModule: 'AI-powered SQL query generator'
                },
                page: {
                    title: 'KI Data Selector',
                    promptLabel: 'Your question',
                    promptPlaceholder: 'e.g. "Give me all orders from last week"',
                    modelLabel: 'OpenAI Model',
                    generateButton: 'Generate SQL',
                    executeButton: 'Execute',
                    exportButton: 'Export as CSV',
                    sqlLabel: 'Generated SQL Query',
                    resultsLabel: 'Results',
                    totalLabel: 'Total',
                    pageLabel: 'Page',
                    limitLabel: 'Per page',
                    errorTitle: 'Error',
                    successTitle: 'Success',
                    noResults: 'No results found',
                    loading: 'Loading...'
                }
            }
        }
    },

    routes: {
        index: {
            component: 'kidata-selector-index',
            path: 'index',
            meta: {
                privilege: 'kidata_selector.viewer'
            }
        },
        list: {
            component: 'kidata-selector-list',
            path: 'list',
            meta: {
                privilege: 'kidata_selector.viewer'
            }
        },
        detail: {
            component: 'kidata-selector-detail',
            path: 'detail/:id',
            meta: {
                privilege: 'kidata_selector.viewer'
            }
        }
    },

    navigation: [{
        id: 'kidata-selector',
        label: 'kidata-selector.general.mainMenuItemGeneral',
        color: '#ff3d58',
        icon: 'regular-database',
        path: 'kidata.selector.index',
        position: 100,
        parent: 'sw-catalogue',
        privilege: 'kidata_selector.viewer'
    }]
});
