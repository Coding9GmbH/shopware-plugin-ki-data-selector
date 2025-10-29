import './module/search-optimizer';
import './service/search-analytics-api.service';

import localeDE from './module/search-optimizer/snippet/de-DE';
import localeEN from './module/search-optimizer/snippet/en-GB';

Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);