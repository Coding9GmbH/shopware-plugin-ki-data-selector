# Store Felder - KI Data Selector

## 📋 DEUTSCH (DE)

### Name (max. 80 Zeichen)
```
KI Data Selector - AI-gestützte SQL-Abfragen für Shopware 6
```
_(63 Zeichen)_

---

### Kurzbeschreibung (max. 150 Zeichen)
```
Generieren Sie SQL-Abfragen mit KI! Stellen Sie Fragen in natürlicher Sprache und erhalten Sie sofort ausführbare Queries - ohne SQL-Kenntnisse.
```
_(148 Zeichen)_

---

### Meta-Beschreibung (max. 160 Zeichen)
```
KI-gestützter SQL-Generator für Shopware 6. Analysieren Sie Shop-Daten ohne SQL-Kenntnisse, mit Fehlerkorrektur, CSV-Export und Multi-Layer-Sicherheit.
```
_(160 Zeichen)_

---

### Beschreibung (HTML)

```html
<h2>Revolutionieren Sie Ihre Datenanalyse</h2>

<p><strong>Stellen Sie Fragen in natürlicher Sprache und erhalten Sie sofort ausführbare SQL-Queries für Ihre Shopware 6 Datenbank - ohne SQL-Kenntnisse!</strong></p>

<p><strong>Beispiel:</strong> "Zeige mir alle Bestellungen der letzten Woche" → Die KI generiert automatisch den perfekten SQL-Query, validiert ihn und führt ihn sicher aus.</p>

<hr>

<h2>Features</h2>

<ul>
<li><strong>AI-Powered SQL-Generierung:</strong> Natural Language Processing versteht Fragen in deutscher und englischer Sprache. Die KI kennt das komplette Shopware 6 Datenbankschema und optimiert Queries automatisch.</li>
<li><strong>Intelligente Fehlerkorrektur:</strong> Bei Fehlern analysiert die KI automatisch das Problem und schlägt korrigierte Queries vor. Der Original-Context bleibt erhalten.</li>
<li><strong>Query-Management-System:</strong> Speichern Sie häufig verwendete Queries mit Namen und Notizen. Wiederverwendung per Klick.</li>
<li><strong>Export &amp; Anzeige:</strong> Paginierte Tabelle mit CSV-Export, konfigurierbarem Delimiter und automatischer UTF-8-Bereinigung.</li>
<li><strong>Multi-Layer-Sicherheit:</strong> Nur SELECT-Statements erlaubt. Blockiert destruktive Operationen. Schema-Validierung und Timeout-Protection schützen Ihre Datenbank.</li>
</ul>

<hr>

<h2>Für wen ist dieses Plugin?</h2>

<ul>
<li><strong>Shop-Betreiber:</strong> Analysieren Sie Verkaufsdaten ohne technisches Know-how</li>
<li><strong>Marketing-Teams:</strong> Erstellen Sie Reports für Kampagnen-Analysen</li>
<li><strong>Business Intelligence:</strong> Generieren Sie Ad-hoc-Analysen und Daten-Exports</li>
<li><strong>Entwickler:</strong> Beschleunigen Sie Testing und Datenvalidierung</li>
<li><strong>Support-Teams:</strong> Beantworten Sie Kundenanfragen schneller</li>
</ul>

<hr>

<h2>So funktioniert's in 5 Schritten</h2>

<ol>
<li><strong>Frage stellen:</strong> "Welche 20 Produkte wurden am häufigsten verkauft?"</li>
<li><strong>AI generiert SQL:</strong> GPT-4 analysiert Ihre Frage und erstellt optimalen Query</li>
<li><strong>Validierung:</strong> Automatische Sicherheits-Validierung vor Ausführung</li>
<li><strong>Ergebnisse anzeigen:</strong> Paginierte Tabellendarstellung</li>
<li><strong>Export:</strong> CSV-Download für Excel oder BI-Tools</li>
</ol>

<hr>

<h2>Sicherheit steht an erster Stelle</h2>

<h3>Multi-Layer-Validierung schützt Ihre Datenbank</h3>

<ul>
<li>Read-Only-Modus - Nur SELECT-Statements erlaubt</li>
<li>Whitelist/Blacklist - Destruktive Operationen werden blockiert</li>
<li>Schema-Validierung - Prüfung auf existierende Tabellen und Spalten</li>
<li>Timeout-Protection - Verhindert zu lange laufende Queries</li>
<li>User-Confirmation - Warnung vor Ausführung</li>
<li>ACL-Integration - Nutzt Shopware 6 Berechtigungssystem</li>
</ul>

<h3>Was NICHT möglich ist</h3>

<ul>
<li>Daten löschen oder ändern</li>
<li>Datenbank-Struktur modifizieren</li>
<li>System-Befehle ausführen</li>
<li>Externe Verbindungen öffnen</li>
</ul>

<hr>

<h2>Beispiel-Anwendungen</h2>

<p><strong>Bestellanalyse:</strong> "Gib mir alle Bestellungen der letzten 7 Tage"</p>
<p><strong>Top-Produkte:</strong> "Welche 20 Produkte wurden am häufigsten verkauft?"</p>
<p><strong>Kunden-Ranking:</strong> "Top 10 Kunden nach Gesamtumsatz im letzten Monat"</p>
<p><strong>Lagerbestand:</strong> "Zeige alle Produkte mit weniger als 10 Stück auf Lager"</p>
<p><strong>Status-Reports:</strong> "Alle Bestellungen mit Status 'offen' von heute"</p>

<hr>

<h2>Einfache Konfiguration</h2>

<p><strong>Schnellstart in 3 Schritten:</strong></p>

<ol>
<li>Plugin installieren: <em>bin/console plugin:install --activate Coding9KIDataSelector</em></li>
<li>OpenAI API-Key eintragen: Einstellungen → System → Plugins → Configuration</li>
<li>Loslegen: Kataloge → KI Data Selector</li>
</ol>

<h3>Konfigurierbare Parameter</h3>

<ul>
<li>OpenAI Model (gpt-4o-mini empfohlen, schnell &amp; günstig)</li>
<li>SQL Timeout (Standard: 20 Sekunden)</li>
<li>Page Size (Standard: 25, Max: 200)</li>
<li>Schema Validation (erhöht Sicherheit)</li>
<li>Optional Logging für Debugging</li>
</ul>

<hr>

<h2>Technische Details</h2>

<ul>
<li>Shopware Version: 6.4.0 oder höher</li>
<li>PHP Version: 8.0 oder höher</li>
<li>AI Engine: OpenAI GPT-4 / GPT-4o / GPT-3.5</li>
<li>Datenbank: MySQL 5.7+ / MariaDB 10.3+</li>
<li>Sprachen: Deutsch, Englisch</li>
</ul>

<hr>

<h2>Support</h2>

<p>E-Mail: support@coding9.de<br>
Website: <a href="https://coding9.de">https://coding9.de</a><br>
Dokumentation: Ausführliche README (DE/EN) im Plugin enthalten</p>

<p><strong>Wichtig:</strong> Benötigt OpenAI API-Key. Kosten sind nutzungsabhängig (~$1-3 pro 1.000 Queries mit gpt-4o-mini).</p>
```

---

### Highlights (max. 5, je max. 140 Zeichen)

**1. AI-Powered SQL-Generierung**
```
Fragen in natürlicher Sprache stellen - KI generiert automatisch optimierte SQL-Queries. Keine SQL-Kenntnisse erforderlich.
```
_(135 Zeichen)_

**2. Intelligente Fehlerkorrektur**
```
KI analysiert Fehler automatisch und schlägt korrigierte Queries vor. Ein-Klick-Korrektur mit vollständigem Context.
```
_(119 Zeichen)_

**3. Query-Management-System**
```
Speichern Sie häufig verwendete Queries mit Namen. Wiederverwendung per Klick. Original-Prompt wird versioniert.
```
_(116 Zeichen)_

**4. CSV-Export & Paginierung**
```
Export aller Ergebnisse als CSV mit konfigurierbarem Delimiter. Paginierte Tabelle mit 1-200 Einträgen pro Seite.
```
_(120 Zeichen)_

**5. Multi-Layer-Sicherheit**
```
100% Read-Only. Blockiert destruktive Operationen. Schema-Validierung, Timeout-Protection und ACL-Integration.
```
_(114 Zeichen)_

---

### Features (Aufzählung)

```
- Natural Language Processing in Deutsch und Englisch
- Vollständiges Shopware 6 Datenbankschema-Wissen
- Automatische Query-Optimierung für Performance
- HEX-Konvertierung für BINARY(16) IDs
- Fehleranalyse mit Original-Context-Erhaltung
- Ein-Klick-Fehlerkorrektur durch KI
- Query-Bibliothek mit Speicher-Funktion
- Versionierung von Original-Prompts
- Paginierte Ergebnistabelle (1-200 Einträge)
- Anpassbare Sortierung
- CSV-Export mit konfigurierbarem Delimiter
- Automatische UTF-8-Bereinigung
- Whitelist-basierte Validierung (nur SELECT)
- Blacklist für destruktive Operationen
- Schema-Validierung von Tabellen und Spalten
- Konfigurierbare Query-Timeouts
- User-Confirmation vor Ausführung
- ACL-Integration für Berechtigungen
- Optional aktivierbares Logging
- Mehrsprachiges UI (DE/EN)
- GPT-4o-mini / GPT-4o / GPT-3.5 Support
- Compact Schema Mode für große Datenbanken
```

---

### Installationsanleitung

```html
<h2>Installation</h2>

<p><strong>Voraussetzungen:</strong></p>
<ul>
<li>Shopware 6.4.0 oder höher</li>
<li>PHP 8.0 oder höher</li>
<li>MySQL 5.7+ oder MariaDB 10.3+</li>
<li>OpenAI API-Key (<a href="https://platform.openai.com" target="_blank">platform.openai.com</a>)</li>
</ul>

<h3>Schritt 1: Plugin installieren</h3>

<p>Über die Administration:</p>
<ol>
<li>Einstellungen → System → Plugins → Plugin hochladen</li>
<li>ZIP-Datei auswählen und hochladen</li>
<li>Plugin installieren und aktivieren</li>
</ol>

<p>Über die CLI:</p>
<p><em>bin/console plugin:refresh<br>
bin/console plugin:install --activate Coding9KIDataSelector<br>
bin/console cache:clear</em></p>

<h3>Schritt 2: OpenAI API-Key konfigurieren</h3>

<ol>
<li>Erstellen Sie einen API-Key auf <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></li>
<li>Navigieren Sie zu: Einstellungen → System → Plugins → KI Data Selector → Konfiguration</li>
<li>Tragen Sie Ihren API-Key ein (Format: sk-...)</li>
<li>Wählen Sie ein Modell (empfohlen: gpt-4o-mini)</li>
<li>Speichern Sie die Konfiguration</li>
</ol>

<h3>Schritt 3: Berechtigungen setzen</h3>

<ol>
<li>Einstellungen → System → Benutzer &amp; Berechtigungen → Rollen</li>
<li>Wählen Sie die gewünschte Rolle</li>
<li>Aktivieren Sie: System → Einstellungen</li>
<li>Speichern</li>
</ol>

<h3>Schritt 4: Plugin nutzen</h3>

<ol>
<li>Navigieren Sie zu: Kataloge → KI Data Selector</li>
<li>Geben Sie Ihre Frage ein</li>
<li>Klicken Sie auf "SQL generieren"</li>
<li>Prüfen Sie die Query und klicken Sie auf "Ausführen"</li>
</ol>

<h3>Konfiguration optimieren</h3>

<p><strong>Empfohlene Einstellungen:</strong></p>
<ul>
<li>OpenAI Model: gpt-4o-mini (schnell &amp; kostengünstig)</li>
<li>SQL Timeout: 20000ms (20 Sekunden)</li>
<li>Max Page Size: 200</li>
<li>Default Page Size: 25</li>
<li>Schema Validation: Aktiviert</li>
<li>Compact Schema Mode: Aktiviert</li>
<li>Logging: Nach Bedarf</li>
</ul>
```

---

### FAQ

**F: Benötige ich SQL-Kenntnisse?**
```
Nein! Das ist der große Vorteil. Sie stellen einfach Fragen in natürlicher Sprache wie "Zeige mir alle Bestellungen von heute" und die KI generiert automatisch die passende SQL-Abfrage.
```

**F: Kann das Plugin meine Daten löschen oder ändern?**
```
Nein, absolut nicht. Das Plugin ist 100% Read-Only. Durch Multi-Layer-Validierung sind nur lesende SELECT-Statements möglich. Destruktive Operationen wie INSERT, UPDATE, DELETE, DROP werden automatisch blockiert.
```

**F: Was kostet die Nutzung?**
```
Das Plugin selbst ist eine einmalige Lizenz. Die OpenAI API-Kosten sind nutzungsabhängig und sehr günstig. Mit dem empfohlenen Modell gpt-4o-mini kostet eine typische Query etwa $0.001-0.003. Bei 1.000 Queries pro Monat entstehen Kosten von ca. $1-3.
```

**F: Welche OpenAI-Modelle werden unterstützt?**
```
GPT-4o-mini (empfohlen - schnell & günstig), GPT-4o (höchste Qualität), GPT-4-turbo (Balance) und GPT-3.5-turbo (kostengünstig). Sie können das Modell jederzeit in der Konfiguration wechseln.
```

**F: Ist das Plugin sicher für den Produktiv-Einsatz?**
```
Ja! Das Plugin verwendet mehrschichtige Sicherheitsmechanismen und ist ausschließlich read-only. Es kann keine Daten ändern oder löschen. Zusätzlich gibt es Schema-Validierung, Timeout-Protection und Integration ins Shopware ACL-System.
```

**F: Kann ich Queries für mein Team speichern?**
```
Ja! Das Query-Management-System ermöglicht das Speichern, Benennen und Teilen von Queries. Alle Benutzer mit den entsprechenden Berechtigungen können gespeicherte Queries ausführen.
```

**F: Was passiert bei einer fehlerhaften Query?**
```
Die KI analysiert den Fehler automatisch und schlägt eine korrigierte Version vor. Sie sehen den Original-Context, den fehlerhaften SQL und die Fehlermeldung. Mit einem Klick können Sie die Korrektur anwenden.
```

**F: Unterstützt das Plugin mehrere Sprachen?**
```
Ja, die KI versteht Fragen in Deutsch und Englisch. Die Benutzeroberfläche ist ebenfalls in beiden Sprachen verfügbar. Sie können problemlos zwischen den Sprachen wechseln.
```

**F: Wie funktioniert die Fehlerkorrektur?**
```
Bei einem Fehler erhält die KI den vollständigen Context: ursprüngliche Frage, generierte Query und Fehlermeldung. Die KI analysiert das Problem und generiert automatisch eine korrigierte Version. Der Original-Context bleibt dabei erhalten.
```

**F: Kann ich eigene Delimiter für CSV-Export festlegen?**
```
Ja, beim CSV-Export können Sie zwischen Semikolon, Komma oder anderen Trennzeichen wählen. Die Daten werden automatisch UTF-8-kodiert und korrekt escaped.
```

---

### Changelog

```
Version 1.0.0 (2024-01-15)
- Initiales Release
- AI-gestützte SQL-Generierung mit GPT-4
- Natural Language Processing (DE/EN)
- Automatische Fehlerkorrektur mit Context-Erhaltung
- Query-Management-System mit Speicher-Funktion
- CSV-Export mit konfigurierbarem Delimiter
- Multi-Layer-Sicherheitsvalidierung
- Schema-Validierung für Tabellen und Spalten
- Timeout-Protection
- ACL-Integration
- HEX-Konvertierung für BINARY(16) IDs
- UTF-8-Bereinigung
- Paginierte Ergebnistabelle (1-200 Einträge)
- Responsive Admin-Interface
- Mehrsprachiges UI (DE/EN)
- Ausführliche Dokumentation (README DE/EN)
```

---

---

## 📋 ENGLISH (EN)

### Name (max. 80 characters)
```
KI Data Selector - AI-Powered SQL Queries for Shopware 6
```
_(60 characters)_

---

### Short Description (max. 150 characters)
```
Generate SQL queries with AI! Ask questions in natural language and get executable queries instantly - no SQL knowledge required.
```
_(138 characters)_

---

### Meta Description (max. 160 characters)
```
AI-powered SQL generator for Shopware 6. Analyze shop data without SQL knowledge, with error correction, CSV export and multi-layer security.
```
_(154 characters)_

---

### Description (HTML)

```html
<h2>Revolutionize Your Data Analysis</h2>

<p><strong>Ask questions in natural language and get immediately executable SQL queries for your Shopware 6 database - no SQL knowledge required!</strong></p>

<p><strong>Example:</strong> "Show me all orders from the last week" → The AI automatically generates the perfect SQL query, validates it, and executes it securely.</p>

<hr>

<h2>Features</h2>

<ul>
<li><strong>AI-Powered SQL Generation:</strong> Natural Language Processing understands questions in German and English. The AI knows the complete Shopware 6 database schema and optimizes queries automatically.</li>
<li><strong>Intelligent Error Correction:</strong> In case of errors, the AI automatically analyzes the problem and suggests corrected queries. Original context is preserved.</li>
<li><strong>Query Management System:</strong> Save frequently used queries with names and notes. Reuse with one click.</li>
<li><strong>Export &amp; Display:</strong> Paginated table with CSV export, configurable delimiter and automatic UTF-8 cleanup.</li>
<li><strong>Multi-Layer Security:</strong> Only SELECT statements allowed. Blocks destructive operations. Schema validation and timeout protection protect your database.</li>
</ul>

<hr>

<h2>Who is this plugin for?</h2>

<ul>
<li><strong>Shop Owners:</strong> Analyze sales data without technical know-how</li>
<li><strong>Marketing Teams:</strong> Create reports for campaign analysis</li>
<li><strong>Business Intelligence:</strong> Generate ad-hoc analyses and data exports</li>
<li><strong>Developers:</strong> Speed up testing and data validation</li>
<li><strong>Support Teams:</strong> Answer customer inquiries faster</li>
</ul>

<hr>

<h2>How it works in 5 steps</h2>

<ol>
<li><strong>Ask a question:</strong> "Which 20 products were sold most frequently?"</li>
<li><strong>AI generates SQL:</strong> GPT-4 analyzes your question and creates optimal query</li>
<li><strong>Validation:</strong> Automatic security validation before execution</li>
<li><strong>Display results:</strong> Paginated table view</li>
<li><strong>Export:</strong> CSV download for Excel or BI tools</li>
</ol>

<hr>

<h2>Security comes first</h2>

<h3>Multi-Layer Validation protects your database</h3>

<ul>
<li>Read-Only Mode - Only SELECT statements allowed</li>
<li>Whitelist/Blacklist - Destructive operations are blocked</li>
<li>Schema Validation - Checks for existing tables and columns</li>
<li>Timeout Protection - Prevents too long running queries</li>
<li>User Confirmation - Warning before execution</li>
<li>ACL Integration - Uses Shopware 6 permission system</li>
</ul>

<h3>What is NOT possible</h3>

<ul>
<li>Delete or modify data</li>
<li>Modify database structure</li>
<li>Execute system commands</li>
<li>Open external connections</li>
</ul>

<hr>

<h2>Example Use Cases</h2>

<p><strong>Order Analysis:</strong> "Give me all orders from the last 7 days"</p>
<p><strong>Top Products:</strong> "Which 20 products were sold most frequently?"</p>
<p><strong>Customer Ranking:</strong> "Top 10 customers by total revenue in the last month"</p>
<p><strong>Stock Levels:</strong> "Show all products with less than 10 pieces in stock"</p>
<p><strong>Status Reports:</strong> "All orders with status 'open' from today"</p>

<hr>

<h2>Easy Configuration</h2>

<p><strong>Quick Start in 3 Steps:</strong></p>

<ol>
<li>Install plugin: <em>bin/console plugin:install --activate Coding9KIDataSelector</em></li>
<li>Enter OpenAI API Key: Settings → System → Plugins → Configuration</li>
<li>Get started: Catalogues → KI Data Selector</li>
</ol>

<h3>Configurable Parameters</h3>

<ul>
<li>OpenAI Model (gpt-4o-mini recommended, fast &amp; cheap)</li>
<li>SQL Timeout (Default: 20 seconds)</li>
<li>Page Size (Default: 25, Max: 200)</li>
<li>Schema Validation (increases security)</li>
<li>Optional Logging for debugging</li>
</ul>

<hr>

<h2>Technical Details</h2>

<ul>
<li>Shopware Version: 6.4.0 or higher</li>
<li>PHP Version: 8.0 or higher</li>
<li>AI Engine: OpenAI GPT-4 / GPT-4o / GPT-3.5</li>
<li>Database: MySQL 5.7+ / MariaDB 10.3+</li>
<li>Languages: German, English</li>
</ul>

<hr>

<h2>Support</h2>

<p>Email: support@coding9.de<br>
Website: <a href="https://coding9.de">https://coding9.de</a><br>
Documentation: Comprehensive README (DE/EN) included in plugin</p>

<p><strong>Important:</strong> Requires OpenAI API key. Costs are usage-based (~$1-3 per 1,000 queries with gpt-4o-mini).</p>
```

---

### Highlights (max. 5, each max. 140 characters)

**1. AI-Powered SQL Generation**
```
Ask questions in natural language - AI generates optimized SQL queries automatically. No SQL knowledge required.
```
_(117 characters)_

**2. Intelligent Error Correction**
```
AI analyzes errors automatically and suggests corrected queries. One-click correction with full context preservation.
```
_(127 characters)_

**3. Query Management System**
```
Save frequently used queries with names. Reuse with one click. Original prompt is versioned for traceability.
```
_(118 characters)_

**4. CSV Export & Pagination**
```
Export all results as CSV with configurable delimiter. Paginated table with 1-200 entries per page.
```
_(107 characters)_

**5. Multi-Layer Security**
```
100% Read-Only. Blocks destructive operations. Schema validation, timeout protection and ACL integration.
```
_(112 characters)_

---

### Features (List)

```
- Natural Language Processing in German and English
- Complete Shopware 6 database schema knowledge
- Automatic query optimization for performance
- HEX conversion for BINARY(16) IDs
- Error analysis with original context preservation
- One-click error correction by AI
- Query library with save function
- Versioning of original prompts
- Paginated results table (1-200 entries)
- Customizable sorting
- CSV export with configurable delimiter
- Automatic UTF-8 cleanup
- Whitelist-based validation (SELECT only)
- Blacklist for destructive operations
- Schema validation of tables and columns
- Configurable query timeouts
- User confirmation before execution
- ACL integration for permissions
- Optional activatable logging
- Multilingual UI (DE/EN)
- GPT-4o-mini / GPT-4o / GPT-3.5 support
- Compact schema mode for large databases
```

---

### Installation Manual

```html
<h2>Installation</h2>

<p><strong>Requirements:</strong></p>
<ul>
<li>Shopware 6.4.0 or higher</li>
<li>PHP 8.0 or higher</li>
<li>MySQL 5.7+ or MariaDB 10.3+</li>
<li>OpenAI API Key (<a href="https://platform.openai.com" target="_blank">platform.openai.com</a>)</li>
</ul>

<h3>Step 1: Install Plugin</h3>

<p>Via Administration:</p>
<ol>
<li>Settings → System → Plugins → Upload plugin</li>
<li>Select and upload ZIP file</li>
<li>Install and activate plugin</li>
</ol>

<p>Via CLI:</p>
<p><em>bin/console plugin:refresh<br>
bin/console plugin:install --activate Coding9KIDataSelector<br>
bin/console cache:clear</em></p>

<h3>Step 2: Configure OpenAI API Key</h3>

<ol>
<li>Create an API key on <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></li>
<li>Navigate to: Settings → System → Plugins → KI Data Selector → Configuration</li>
<li>Enter your API key (Format: sk-...)</li>
<li>Select a model (recommended: gpt-4o-mini)</li>
<li>Save configuration</li>
</ol>

<h3>Step 3: Set Permissions</h3>

<ol>
<li>Settings → System → Users &amp; Permissions → Roles</li>
<li>Select desired role</li>
<li>Activate: System → Settings</li>
<li>Save</li>
</ol>

<h3>Step 4: Use Plugin</h3>

<ol>
<li>Navigate to: Catalogues → KI Data Selector</li>
<li>Enter your question</li>
<li>Click "Generate SQL"</li>
<li>Review query and click "Execute"</li>
</ol>

<h3>Optimize Configuration</h3>

<p><strong>Recommended Settings:</strong></p>
<ul>
<li>OpenAI Model: gpt-4o-mini (fast &amp; cost-effective)</li>
<li>SQL Timeout: 20000ms (20 seconds)</li>
<li>Max Page Size: 200</li>
<li>Default Page Size: 25</li>
<li>Schema Validation: Enabled</li>
<li>Compact Schema Mode: Enabled</li>
<li>Logging: As needed</li>
</ul>
```

---

### FAQ

**Q: Do I need SQL knowledge?**
```
No! That's the big advantage. You simply ask questions in natural language like "Show me all orders from today" and the AI automatically generates the appropriate SQL query.
```

**Q: Can the plugin delete or modify my data?**
```
No, absolutely not. The plugin is 100% read-only. Through multi-layer validation, only read-only SELECT statements are possible. Destructive operations like INSERT, UPDATE, DELETE, DROP are automatically blocked.
```

**Q: What does usage cost?**
```
The plugin itself is a one-time license. OpenAI API costs are usage-based and very affordable. With the recommended model gpt-4o-mini, a typical query costs about $0.001-0.003. With 1,000 queries per month, costs amount to approx. $1-3.
```

**Q: Which OpenAI models are supported?**
```
GPT-4o-mini (recommended - fast & cheap), GPT-4o (highest quality), GPT-4-turbo (balance) and GPT-3.5-turbo (cost-effective). You can change the model anytime in the configuration.
```

**Q: Is the plugin safe for production use?**
```
Yes! The plugin uses multi-layered security mechanisms and is exclusively read-only. It cannot modify or delete data. Additionally, there's schema validation, timeout protection and integration into Shopware's ACL system.
```

**Q: Can I save queries for my team?**
```
Yes! The query management system allows saving, naming and sharing queries. All users with appropriate permissions can execute saved queries.
```

**Q: What happens with a faulty query?**
```
The AI analyzes the error automatically and suggests a corrected version. You see the original context, the faulty SQL and the error message. With one click you can apply the correction.
```

**Q: Does the plugin support multiple languages?**
```
Yes, the AI understands questions in German and English. The user interface is also available in both languages. You can easily switch between languages.
```

**Q: How does error correction work?**
```
In case of an error, the AI receives the complete context: original question, generated query and error message. The AI analyzes the problem and automatically generates a corrected version. The original context is preserved.
```

**Q: Can I set custom delimiters for CSV export?**
```
Yes, when exporting CSV you can choose between semicolon, comma or other separators. Data is automatically UTF-8 encoded and properly escaped.
```

---

### Changelog

```
Version 1.0.0 (2024-01-15)
- Initial release
- AI-powered SQL generation with GPT-4
- Natural Language Processing (DE/EN)
- Automatic error correction with context preservation
- Query management system with save function
- CSV export with configurable delimiter
- Multi-layer security validation
- Schema validation for tables and columns
- Timeout protection
- ACL integration
- HEX conversion for BINARY(16) IDs
- UTF-8 cleanup
- Paginated results table (1-200 entries)
- Responsive admin interface
- Multilingual UI (DE/EN)
- Comprehensive documentation (README DE/EN)
```
