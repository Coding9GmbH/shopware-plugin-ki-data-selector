🧠 Claude Code Prompt – „KIDataSelector" (SQL-Layer, Full Schema, Pagination & CSV)

Du bist ein erfahrener Shopware-6-Core-Entwickler und Backend-Architekt.
Erstelle ein vollständiges, produktionsreifes Shopware-6-Plugin mit folgender Spezifikation.

# =====================================================================
# PLUGIN-METADATEN
# =====================================================================
Plugin-Name: Coding9KIDataSelector
Namespace:   Coding9\KIDataSelector
Pfad:        /custom/plugins/Coding9KIDataSelector
Kompatibel:  Shopware 6.5+, PHP 8.1+
Autoload:    PSR-4
Ziel:        AI Query Layer – natürliche Sprache → SQL (READ-ONLY), Validierung, Ausführung, Anzeige im Admin (paginierte Tabelle), CSV-Export.

# =====================================================================
# FUNKTIONALES VERHALTEN (HIGH LEVEL)
# =====================================================================
- Eingabe: Natürliche Sprache (Prompt) wie „Gib mir alle Bestellungen der letzten Woche".
- Interne Verarbeitung:
    1) Laufzeit-Schema-Ermittlung: Lies Tabellen, Spalten, Datentypen, Primary Keys und Foreign Keys aus INFORMATION_SCHEMA/Referential Constraints (Doctrine DBAL).
    2) Erzeuge Systemprompt für ChatGPT, der das Shop-Schema als JSON objektiv übergibt.
    3) ChatGPT generiert ausschließlich einen SQL-String (nur SELECT).
    4) SQL-Validator erzwingt Read-only (keine INSERT/UPDATE/DELETE/ALTER/TRUNCATE/DROP/CREATE; keine ; Ketten; kein COPY/LOAD/INTO/OUTFILE).
    5) Optional: Paginierte Ausführung (server-seitig LIMIT/OFFSET), Sortierung, Feldauswahl.
    6) Ergebnis wird im Admin als Tabelle angezeigt; Export als CSV möglich.
- Ausgabe: JSON mit { success, sql, rows, columns, total, page, limit } bzw. CSV-Stream.

# =====================================================================
# SYSTEM-KONFIG
# =====================================================================
Gruppe: KI Data Selector Settings
- kidata.apiKey           (string, required)
- kidata.model            (enum: gpt-4o-mini, gpt-4o, gpt-4-turbo; default: gpt-4o-mini)
- kidata.enableLogging    (bool, default true)
- kidata.defaultPageSize  (int, default 25)
- kidata.maxPageSize      (int, default 200)
- kidata.sqlTimeoutMs     (int, default 20000)  // DB-Query Timeout (Doctrine)
- kidata.locale           (string, default de_DE) // für natürliche Sprachzeiten wie „letzte Woche"

# =====================================================================
# VERZEICHNISSTRUKTUR
# =====================================================================
/custom/plugins/Coding9KIDataSelector
├─ composer.json
├─ src/
│   ├─ Coding9KIDataSelector.php
│   ├─ Core/
│   │   ├─ Api/
│   │   │   ├─ Controller/
│   │   │   │   ├─ KiDataController.php          // /api/_action/kidata/query (JSON, paginiert) & /api/_action/kidata/export (CSV)
│   │   ├─ Service/
│   │   │   ├─ SchemaProvider.php                // INFORMATION_SCHEMA → JSON Schema Snapshot
│   │   │   ├─ KiChatGptService.php              // OpenAI Chat Completions
│   │   │   ├─ SqlValidatorService.php           // Read-only, Tabellen-/Token-Check
│   │   │   ├─ SqlExecutorService.php            // Paginierte Ausführung, Count, CSV
│   │   │   ├─ PromptBuilder.php                 // Systemprompt inkl. Schema + Beispiele
│   │   ├─ Subscriber/
│   │   │   └─ AdminAclSubscriber.php            // Nur Admins & Berechtigte
│   ├─ Storefront/ (optional, hier nicht benötigt)
│   ├─ Admin/
│   │   ├─ Module/ki-data-selector/
│   │   │   ├─ index.js                          // Vue Admin App: Prompt-Form, Table, Pager, CSV
│   ├─ Migration/
│   │   └─ MigrationXXXXXXCreateKiDataTables.php // kidata_query_log
│   └─ Resources/
│       ├─ config/
│       │   ├─ services.xml
│       │   ├─ plugin.xml
│       │   └─ config.xml                        // System-Config Felder
│       └─ app/administration/
│           └─ src/module/kidata-selector/       // Shopware Admin Module (Vue)
│               ├─ page/kidata-selector-index/
│               │   ├─ kidata-selector-index.html.twig
│               │   └─ kidata-selector-index.js
│               ├─ component/kidata-table.vue
│               └─ main.js
└─ README.md

# =====================================================================
# DATENBANK / LOGGING
# =====================================================================
Tabelle: kidata_query_log
- id (BINARY(16) PK) // uuid
- prompt (LONGTEXT)
- sql_query (LONGTEXT)
- executed (TINYINT(1))
- row_count (INT NULL)
- created_at (DATETIME(3))

# =====================================================================
# SCHEMA-PROVIDER (LAUFZEIT)
# =====================================================================
- Nutze Doctrine DBAL, um:
    - alle Tabellen der aktiven Shopware-DB zu listen
    - Spaltenname, Typ, nullable, default, PK, Indexe
    - Foreign Keys (Quelle.Tabelle.Spalte → Ziel.Tabelle.Spalte)
- Gib ein **kompaktes JSON** zurück (z. B. { "tables": { "order": { "columns": [...], "fks": [...] }, ... } }).
- Dieses JSON fließt in den Systemprompt (nicht im Log, wenn zu groß → ggf. kürzen mit Top-100 Tabellen nach FK-Grad; dennoch **alle Tabellen erlauben** für den Validator).

# =====================================================================
# PROMPT-BUILDER (STRIKTE REGELN)
# =====================================================================
Systemrolle für ChatGPT:
- „Du bist ein SQL-Generator für MySQL/MariaDB (Shopware 6). Antworte **ausschließlich** mit einem einzigen SQL-Statement (ohne Erklärung, ohne Backticks, ohne Kommentare).
- Nur **SELECT** mit optionalen JOIN/GROUP BY/HAVING/ORDER BY/LIMIT/OFFSET.
- Nutze ausschließlich existierende Tabellen/Spalten gemäß Schema (JSON folgt).
- Zeitbezüge wie 'letzte Woche' sind relativ zu NOW() (MySQL)."
  Füge an:
- Schema-JSON (gekürzt, falls sehr groß) + Beispiele:
    - „Gib mir alle Bestellungen der letzten Woche" → `SELECT o.id, o.order_number, o.order_date_time FROM order o WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY);`
    - „Gib mir alle verkauften Artikel der letzten Woche" → JOIN `order_line_item` (type='product') + `order` + optional `product`.
    - „Top 10 Kunden nach Umsatz im letzten Monat" → SUM(line_item.total_price) GROUP BY customer.id ORDER BY SUM(...) DESC LIMIT 10.
      Antwortformat: **nur** SQL-String, keine Code-Fences.

# =====================================================================
# VALIDATOR (READ-ONLY & SICHERHEIT)
# =====================================================================
- Rejecte jede Query mit verbotenen Tokens (case-insensitive):
  ALTER|DROP|TRUNCATE|CREATE|REPLACE|INSERT|UPDATE|DELETE|MERGE|GRANT|REVOKE|ATTACH|DETACH|ANALYZE|EXPLAIN|DESCRIBE|SHOW|SET|USE|PRAGMA|CALL|HANDLER|LOAD|OUTFILE|INFILE|INTO|LOCK|UNLOCK|KILL|FLUSH|SHUTDOWN|/*!|-- |#|;
- Genau ein Statement (kein Semikolon im String).
- Muss mit `SELECT` beginnen.
- Optional: Whitelist optional leer lassen → **alle Tabellen erlaubt** (nur Read-only Regel gilt).
- Optional: max. Spaltenanzahl & max. Joins (konfigurierbar) → um extreme Queries zu bremsen.
- Liefere eindeutige Fehlermeldungen (validierungsfehler, verbotene Tokens, unbekannte Tabelle/Spalte → wenn Schema-Check aktiviert).

# =====================================================================
# EXECUTOR (PAGINATION & CSV)
# =====================================================================
- Endpunkt akzeptiert `page` (>=1), `limit` (<= kidata.maxPageSize, default kidata.defaultPageSize), `sort` (optional „col ASC|DESC"), `csv` (bool), `execute` (bool).
- Bei JSON:
    - Führe zwei Queries aus:
        1) **Count-Wrapping**: `SELECT COUNT(*) FROM ( <SQL OHNE LIMIT/OFFSET> ) x`
        2) **Seitenabfrage**: `<SQL> + " LIMIT :limit OFFSET :offset"`
    - Rückgabe: { success, sql, columns: [name], rows: [ {...} ], total, page, limit }
- Bei CSV:
    - Streame `text/csv` (UTF-8, Semikolon oder Komma konfigurierbar) mit Headerzeile.
- Timeouts & Fehler sauber handhaben (Doctrine Query Timeout gemäß config).

# =====================================================================
# ADMIN UI (SHOPWARE ADMIN MODUL)
# =====================================================================
- Modul „KI Data Selector"
    - Eingabe: Prompt (textarea), Model (select), Page, Limit, optional Sort, Toggle „als CSV exportieren"
    - Buttons: „Nur SQL generieren", „Ausführen", „CSV exportieren"
    - Anzeige:
        - generierter SQL (readonly textarea, copy-button)
        - paginierte Tabelle (Server-Paging), Spalten aus `columns` dynamisch
        - Paginator (Seiten, total)
- Berechtigungen: Nur Admin-Rolle oder spezifische ACL.

# =====================================================================
# REST-API
# =====================================================================
1) POST /api/_action/kidata/query
   Body:
   {
   "prompt": "Gib mir alle Bestellungen der letzten Woche",
   "page": 1,
   "limit": 25,
   "sort": null,              // optional "spalte ASC|DESC"
   "execute": true,           // false = nur SQL zurückgeben
   "csv": false
   }
   Ablauf:
    - SchemaProvider → JSON
    - PromptBuilder → System+User Messages (inkl. Schema)
    - KiChatGptService → SQL
    - SqlValidatorService → Validierung
    - Wenn execute=true:
        * SqlExecutorService: total & page rows
    - kidata_query_log → speichern
      Antwort (JSON):
      {
      "success": true,
      "sql": "SELECT ...",
      "columns": ["colA","colB",...],
      "rows": [ { "colA": "...", "colB": ... }, ... ],
      "total": 1234,
      "page": 1,
      "limit": 25
      }

2) POST /api/_action/kidata/export
   Body wie oben, `csv: true`
    - Gleiches Vorgehen, aber Antwort ist CSV-Stream (Disposition: attachment; filename="kidata-export-YYYYMMDD-HHMM.csv")

# =====================================================================
# OPENAI-SERVICE
# =====================================================================
- Endpoint: https://api.openai.com/v1/chat/completions
- Request:
  {
  "model": "<kidata.model>",
  "messages": [
  { "role": "system", "content": "<PROMPT_BUILDER_SYSTEM + <SCHEMA_JSON_KOMPAKT>>" },
  { "role": "user",   "content": "<prompt aus request>" }
  ],
  "temperature": 0.0
  }
- Rückgabe: Nur `content` der ersten Wahl → SQL-String.
- Logging: Nur Metadaten (Länge), niemals API-Key.

# =====================================================================
# BEISPIELE (MUST-HAVE IM PROMPT)
# =====================================================================
User: "Gib mir alle Bestellungen der letzten Woche"
SQL (Beispiel):
SELECT o.id, o.order_number, o.order_date_time
FROM `order` o
WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY)
ORDER BY o.order_date_time DESC;

User: "Gib mir alle verkauften Artikel der letzten Woche"
SQL (Beispiel):
SELECT oli.product_id, SUM(oli.quantity) AS qty
FROM `order` o
JOIN order_line_item oli ON oli.order_id = o.id
WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY)
AND oli.type = 'product'
GROUP BY oli.product_id
ORDER BY qty DESC;

# =====================================================================
# CODE-ANFORDERUNGEN
# =====================================================================
- Liefere vollständigen, kompilierbaren Code für ALLE oben genannten Dateien inkl. services.xml, plugin.xml, config.xml, Migration, Controller, Services, Admin-Module (Vue), README.
- Saubere Typisierung, PHPDoc, Fehlerbehandlung.
- Unit-Test-Stubs für Validator und PromptBuilder.
- Keine Platzhalter, funktionsfähige Beispiele (z. B. CSV-Streaming, Pagination-Query).
- Beachte MySQL-Zitatregeln (Backticks für reservierte Tabellennamen wie `order`).
- Entferne LIMIT/OFFSET aus ChatGPT-SQL, falls enthalten, um serverseitig korrekt zu paginieren (Executor darf LIMIT/OFFSET selbst setzen).

# =====================================================================
# INSTALLATION
# =====================================================================
bin/console plugin:refresh
bin/console plugin:install --activate Coding9KIDataSelector
bin/console cache:clear


⸻

Hinweise aus der Praxis
•	Schema live injizieren ist der Game-Changer: Du bekommst robuste, korrekte SQLs trotz wechselnder Shopware-Versionen/Plugins.
•	Validator strikt halten (nur SELECT) – damit sind „alle Tabellen erlaubt" trotzdem sicher.
•	Executor kapselt Pagination: Auch bei komplexen Joins bleibt die UI schnell.
•	CSV-Export über denselben Query-Pfad verhindert Drift zwischen UI & Export.

Wenn du willst, schreibe ich dir jetzt noch Beispiel-Dateiinhalte (z. B. SqlValidatorService.php, SchemaProvider.php und das Admin-Vue-Modul), damit du direkt loslegen kannst.
