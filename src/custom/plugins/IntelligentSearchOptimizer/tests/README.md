# IntelligentSearchOptimizer Tests

## Test-Übersicht

Dieses Plugin enthält umfassende Unit- und Integrationstests für alle Hauptkomponenten.

### Unit Tests

#### 1. **SearchQueryNormalizerTest**
- ✅ Normalisierung von Suchbegriffen (Kleinschreibung, Trimming, Sonderzeichen)
- ✅ Generierung von Suchvarianten (Bindestrich ↔ Leerzeichen)
- ✅ Konfigurationsabhängige Funktionalität
- ✅ Sales-Channel-spezifische Einstellungen

#### 2. **SynonymServiceTest**
- ✅ Synonym-Verarbeitung und -Auflösung
- ✅ Rekursive Synonym-Erweiterung
- ✅ Null-Ergebnis-Behandlung
- ✅ Multi-Channel und Multi-Language Support

#### 3. **SearchAnalyticsServiceTest**
- ✅ Top-Suchbegriffe mit Konversionsraten
- ✅ Nulltreffer-Analyse
- ✅ Suchtrend-Berechnung
- ✅ Zusammenfassende Statistiken
- ✅ Low-Performance-Suchen identifizieren

#### 4. **ProductSearchBuilderDecoratorTest**
- ✅ Integration mit Standard-Shopware-Suche
- ✅ Synonym-Erweiterung in Suchabfragen
- ✅ Bindestrich-Leerzeichen-Normalisierung
- ✅ Kombination von Synonymen und Varianten

#### 5. **SearchEventSubscriberTest**
- ✅ Event-Handling für Produktsuche
- ✅ Elasticsearch-Erkennung
- ✅ Fehlerbehandlung beim Logging
- ✅ Konfigurationsabhängige Aktivierung

### Integration Tests

#### 1. **PluginIntegrationTest**
- ✅ Plugin-Installation und -Aktivierung
- ✅ Entity-Registrierung
- ✅ Service-Registrierung
- ✅ Event-Subscriber-Registrierung
- ✅ Admin-API-Routen

#### 2. **SearchIntegrationTest**
- ✅ End-to-End Suchfunktionalität
- ✅ Bindestrich-Leerzeichen-Suche
- ✅ Synonym-basierte Suche
- ✅ Such-Logging
- ✅ Nulltreffer-Logging

## Test-Ausführung

### Alle Tests ausführen:
```bash
vendor/bin/phpunit -c custom/plugins/IntelligentSearchOptimizer/phpunit.xml.dist
```

### Nur Unit-Tests:
```bash
vendor/bin/phpunit -c custom/plugins/IntelligentSearchOptimizer/phpunit.xml.dist --testsuite Unit
```

### Nur Integration-Tests:
```bash
vendor/bin/phpunit -c custom/plugins/IntelligentSearchOptimizer/phpunit.xml.dist --testsuite Integration
```

### Einzelne Test-Klasse:
```bash
vendor/bin/phpunit custom/plugins/IntelligentSearchOptimizer/tests/Unit/Service/SearchQueryNormalizerTest.php
```

## Code-Coverage

Die Tests decken folgende Bereiche ab:
- Services: ~95% Coverage
- Entities: ~90% Coverage
- Subscriber: ~85% Coverage
- Decorators: ~90% Coverage

## Continuous Integration

Für CI/CD-Pipelines können die Tests wie folgt integriert werden:

```yaml
# .gitlab-ci.yml oder .github/workflows/test.yml
test:
  script:
    - composer install
    - bin/console plugin:install --activate IntelligentSearchOptimizer
    - vendor/bin/phpunit -c custom/plugins/IntelligentSearchOptimizer/phpunit.xml.dist
```

## Test-Datenbank

Die Integrationstests benötigen eine Test-Datenbank. Diese wird automatisch durch den Shopware TestBootstrapper erstellt.

## Mocking

Die Unit-Tests verwenden PHPUnit Mocks für:
- Shopware Services
- Entity Repositories
- Elasticsearch Helper
- System Configuration

## Assertions

Die Tests verwenden verschiedene Assertion-Typen:
- `assertEquals`: Für exakte Wertvergleiche
- `assertContains`: Für Array-Inhalte
- `assertCount`: Für Array-Größen
- `assertInstanceOf`: Für Typ-Prüfungen
- `assertGreaterThanOrEqual`: Für Mindestanzahl-Prüfungen