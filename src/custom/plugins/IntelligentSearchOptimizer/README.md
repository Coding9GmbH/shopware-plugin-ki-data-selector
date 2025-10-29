# Intelligent Search Optimizer für Shopware 6

Ein umfassendes Plugin zur Analyse und Optimierung der Shop-Suche mit KI-gestützten Features.

## Features

### 🔍 Basis-Funktionen

#### 1. **Such-Protokollierung & Analyse**
- Automatische Erfassung aller Suchanfragen
- Tracking von Nulltreffern und erfolgreichen Suchen
- Detaillierte Statistiken und Berichte
- Export-Funktionen für weitere Analysen

#### 2. **Synonym-Management**
- Verwaltung von Suchbegriff-Synonymen
- Automatische Synonym-Erweiterung bei Suchen
- Import/Export von Synonym-Listen
- Elasticsearch-Integration wenn verfügbar

#### 3. **Such-Weiterleitungen**
- Direkte Weiterleitungen für bestimmte Suchbegriffe
- Prioritäts-basierte Regelverarbeitung
- Verwaltung über Admin-Interface

#### 4. **Zeichen-Normalisierung**
- Flexible Konfiguration von Zeichen-Ersetzungen
- Standard: Bindestrich/Leerzeichen-Normalisierung
- Unterstützung für Umlaute und Sonderzeichen
- Bidirektionale Zuordnungen möglich

### 🚀 Erweiterte Features

#### 5. **Tippfehler-Korrektur (Did you mean?)**
- Intelligente Rechtschreibvorschläge basierend auf Levenshtein-Distanz
- Automatisches Lernen von erfolgreichen Suchen
- Eigenes Wörterbuch für Markennamen und technische Begriffe
- Konfidenz-basierte Vorschläge

#### 6. **Such-Intent Erkennung**
- Kategorisierung von Suchanfragen:
  - **Informational**: Anleitungen, How-tos, Guides
  - **Transactional**: Kaufabsicht, Preise, Angebote
  - **Navigational**: Kontakt, Support, Versand
- Content-Priorisierung basierend auf Intent
- Anpassbare Pattern-Erkennung

#### 7. **Revenue-Tracking pro Suchbegriff**
- Verfolgung welche Suchbegriffe zu Käufen führen
- ROI-Berechnung für jeden Suchbegriff
- Conversion-Rate Analyse
- Zeit von Suche bis zum Kauf
- Detaillierte Revenue-Reports

#### 8. **Echtzeit Trending Searches**
- Erkennung von plötzlichen Suchanstiegen (>200% Steigerung)
- Stündliche Trend-Analyse
- E-Mail-Benachrichtigungen bei neuen Trends
- Real-time Dashboard mit Visualisierungen
- Automatische Trend-Alerts

## Installation

### Voraussetzungen
- Shopware 6.5.x oder 6.6.x
- PHP 8.1 oder höher
- Optional: Elasticsearch für erweiterte Features

### Installation via Composer
```bash
composer require swag/intelligent-search-optimizer
bin/console plugin:refresh
bin/console plugin:install --activate IntelligentSearchOptimizer
```

### Manuelle Installation
1. Plugin in `custom/plugins/IntelligentSearchOptimizer` kopieren
2. Im Shop-Root ausführen:
```bash
bin/console plugin:refresh
bin/console plugin:install --activate IntelligentSearchOptimizer
bin/console cache:clear
```

## Konfiguration

### Plugin-Einstellungen

Die Konfiguration erfolgt unter **Einstellungen → Plugins → Intelligent Search Optimizer**

#### Allgemeine Einstellungen
- **Suchprotokollierung aktivieren**: Ein/Aus
- **Aufbewahrung der Suchprotokolle**: 1-365 Tage (Standard: 90)
- **Minimale Suchbegriffslänge**: 1-10 Zeichen (Standard: 3)
- **Anonyme Suchen protokollieren**: Ein/Aus

#### Zeichen-Normalisierung
- **Zeichen-Normalisierung aktivieren**: Ein/Aus
- **Zeichen-Zuordnungen**: Konfigurierbare Mappings
  ```
  - [Leerzeichen]
  _ [Leerzeichen]
  / [Leerzeichen]
  & und
  ä ae
  ö oe
  ü ue
  ß ss
  ```
- **Bidirektionale Zuordnung**: Ersetzungen in beide Richtungen

#### Nulltreffer-Einstellungen
- **Vorschläge bei Nulltreffern anzeigen**: Ein/Aus
- **Nulltreffer-Warnschwelle**: 1-100 (Standard: 10)
- **Warn-E-Mail-Adresse**: E-Mail für Benachrichtigungen

#### Rechtschreibprüfung
- **Rechtschreibvorschläge aktivieren**: Ein/Aus
- **Automatisch von erfolgreichen Suchen lernen**: Ein/Aus

#### Trending Searches
- **Trending-Erkennung aktivieren**: Ein/Aus
- **Trending-Schwellenwert**: 50-1000% (Standard: 200%)
- **Trending-Benachrichtigungs-E-Mail**: E-Mail für Alerts

## Admin-Interface

### Dashboard
Zugriff über **Marketing → Search Optimizer**

#### Hauptübersicht
- Such-Statistiken der letzten 30 Tage
- Top-Suchbegriffe mit Ergebnissen
- Nulltreffer-Übersicht
- Quick-Actions für häufige Aufgaben

#### Spell Check Dashboard
- Verwaltung des Wörterbuchs
- Überprüfung von Rechtschreibvorschlägen
- Import/Export von Wörterbüchern
- Kategorisierung (Marken, Technisch, Allgemein)

#### Revenue Tracking
- Umsatz pro Suchbegriff
- Conversion-Rates
- ROI-Berechnungen
- Intent-basierte Umsatzanalyse
- Export-Funktionen

#### Trending Searches
- Echtzeit-Trending-Anzeige
- Historische Trend-Daten
- Alert-Management
- Quick-Actions (Redirect erstellen, Promotion)

#### Synonym-Verwaltung
- Synonym-Gruppen erstellen/bearbeiten
- Bulk-Import via CSV
- Aktivierung/Deaktivierung
- Sprach-spezifische Synonyme

#### Such-Weiterleitungen
- Weiterleitungsregeln verwalten
- Prioritäten festlegen
- Zeitliche Begrenzung möglich
- A/B-Testing Support

## API-Endpoints

### REST API
```
GET /api/search-optimizer/analytics
GET /api/search-optimizer/zero-results
GET /api/search-optimizer/trending
GET /api/search-optimizer/revenue-stats
POST /api/search-optimizer/synonyms
POST /api/search-optimizer/redirects
POST /api/search-optimizer/dictionary
```

### Admin API Service
```javascript
// In Admin-Komponenten verfügbar
this.searchOptimizerApiService.getAnalytics(params)
this.searchOptimizerApiService.getRevenueStats(params)
this.searchOptimizerApiService.getTrendingSearches(params)
this.searchOptimizerApiService.addToDictionary(word)
```

## Scheduled Tasks

### Automatische Aufgaben
1. **Cleanup alte Logs**: Täglich um 2:00 Uhr
2. **Trending Analysis**: Stündlich
3. **Revenue Aggregation**: Täglich um 3:00 Uhr

### Manuelle Ausführung
```bash
bin/console search-optimizer:cleanup-logs
bin/console search-optimizer:analyze-trends
```

## Events

### Verfügbare Events
- `search.optimizer.query.logged`: Nach Protokollierung einer Suche
- `search.optimizer.zero.result`: Bei Nulltreffer
- `search.optimizer.redirect.triggered`: Bei Weiterleitung
- `search.optimizer.trend.detected`: Bei neuem Trend

### Event Subscriber Beispiel
```php
class CustomSearchSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'search.optimizer.zero.result' => 'onZeroResult',
        ];
    }
    
    public function onZeroResult(ZeroResultEvent $event): void
    {
        // Custom logic
    }
}
```

## Datenbank-Tabellen

### Haupt-Tabellen
- `search_query_log`: Alle Suchanfragen
- `search_synonym`: Synonym-Definitionen
- `search_redirect`: Weiterleitungsregeln
- `search_optimizer_dictionary`: Rechtschreib-Wörterbuch
- `search_optimizer_spell_corrections`: Korrektur-Mappings
- `search_optimizer_intent_patterns`: Intent-Erkennungsmuster
- `search_optimizer_revenue_tracking`: Umsatz-Tracking
- `search_optimizer_trending`: Trend-Daten

## Performance-Optimierung

### Best Practices
1. **Log-Retention**: Nicht länger als 90 Tage aufbewahren
2. **Async Logging**: In Konfiguration aktivieren
3. **Cleanup Batch Size**: Bei großen Shops erhöhen
4. **Elasticsearch**: Für Shops > 10k Produkte empfohlen

### Cache-Nutzung
- Synonym-Cache: 1 Stunde
- Dictionary-Cache: 24 Stunden
- Trending-Cache: 5 Minuten

## Troubleshooting

### Häufige Probleme

#### Plugin lässt sich nicht installieren
```bash
bin/console cache:clear
bin/console plugin:refresh --skip-asset-build
bin/console plugin:install IntelligentSearchOptimizer --activate
```

#### Admin-Assets werden nicht geladen
```bash
bin/console assets:install
bin/build-administration.sh
```

#### Elasticsearch-Fehler
- Prüfen ob Elasticsearch konfiguriert ist
- Plugin funktioniert auch ohne Elasticsearch

### Debug-Modus
In `.env`:
```
SEARCH_OPTIMIZER_DEBUG=1
```

## Entwicklung

### Plugin erweitern
```php
// Service überschreiben
<service id="YourNamespace\CustomSpellCheckService"
         decorates="Swag\IntelligentSearchOptimizer\Service\SpellCheckService">
    <argument type="service" id="YourNamespace\CustomSpellCheckService.inner"/>
</service>
```

### Neue Intent-Patterns hinzufügen
```php
$intentService->addPattern('%warranty%', 'informational', 80);
$intentService->addPattern('%discount code%', 'transactional', 90);
```

## Changelog

### Version 1.0.0
- Initial Release
- Basis-Features: Logging, Synonyme, Redirects
- Zeichen-Normalisierung
- Admin Dashboard

### Version 1.1.0
- Tippfehler-Korrektur
- Such-Intent Erkennung
- Revenue Tracking
- Trending Searches
- Erweiterte Admin-UI

## Support

**Entwickelt von:** Coding 9 GmbH  
**Website:** https://coding9.de  
**Support:** https://coding9.de/kontakt  
**E-Mail:** kontakt@coding9.de

## Lizenz

Proprietär - © 2024 Coding 9 GmbH. Alle Rechte vorbehalten.