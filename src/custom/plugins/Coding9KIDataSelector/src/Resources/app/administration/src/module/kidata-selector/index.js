import './acl';
import './page/kidata-selector-index';
import './page/kidata-selector-list';
import './page/kidata-selector-detail';
import VersionHelper from '../../core/version-helper';

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

    navigation: (function() {
        // Check if we're on 6.7+ or if sw-catalogue exists
        const is67Plus = VersionHelper.isVersionGte('6.7.0.0');
        const hasCatalogueMenu = Shopware.Module &&
                                Shopware.Module.getModuleRegistry &&
                                Shopware.Module.getModuleRegistry().get('sw-catalogue');

        // Create navigation entries for different scenarios
        const navigationEntries = [];

        // Primary entry: Try sw-catalogue parent (for 6.4-6.6)
        navigationEntries.push({
            id: 'kidata-selector',
            label: 'kidata-selector.general.mainMenuItemGeneral',
            color: '#ff3d58',
            icon: 'regular-database',
            path: 'kidata.selector.index',
            position: 100,
            parent: 'sw-catalogue',
            privilege: 'kidata_selector.viewer'
        });

        // Fallback entry: Top-level (for 6.7+ if catalogue doesn't exist)
        if (is67Plus || !hasCatalogueMenu) {
            navigationEntries.push({
                id: 'kidata-selector-main',
                label: 'kidata-selector.general.mainMenuItemGeneral',
                color: '#ff3d58',
                icon: 'regular-database',
                path: 'kidata.selector.index',
                position: 100,
                parent: null, // Top-level
                privilege: 'kidata_selector.viewer'
            });
        }

        return navigationEntries;
    })(),

    // Add settingsItem for visibility in Settings menu (works in all versions)
    settingsItem: [{
        group: 'plugins',
        to: 'kidata.selector.index',
        icon: 'regular-database',
        label: 'kidata-selector.general.mainMenuItemGeneral',
        privilege: 'kidata_selector.viewer'
    }]
});

// Additional registration: Try to add menu entry via MainMenuService (6.7+)
// This is a fallback to ensure menu appears even if Module.register doesn't work properly
try {
    if (Shopware.Service && typeof Shopware.Service === 'function') {
        const mainMenuService = Shopware.Service('mainMenuService');
        if (mainMenuService && typeof mainMenuService.addMenuItem === 'function') {
            // 6.7+ approach: Register via MainMenuService
            mainMenuService.addMenuItem({
                id: 'kidata-selector-service',
                label: 'kidata-selector.general.mainMenuItemGeneral',
                color: '#ff3d58',
                icon: 'regular-database',
                path: 'kidata.selector.index',
                position: 100,
                privilege: 'kidata_selector.viewer'
            });
        }
    }
} catch (error) {
    // Service might not exist in older versions, silently ignore
    console.debug('MainMenuService not available, using classic navigation registration');
}
