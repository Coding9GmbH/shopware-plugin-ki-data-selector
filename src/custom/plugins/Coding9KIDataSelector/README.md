# KI Data Selector - Shopware 6 Plugin

AI-powered SQL query generator for Shopware 6 that transforms natural language into validated, read-only SQL queries with pagination and CSV export capabilities.

## Features

- **Natural Language to SQL**: Convert plain language questions into SQL queries using OpenAI ChatGPT
- **Runtime Schema Detection**: Automatically reads your Shopware database schema including tables, columns, foreign keys
- **Read-Only Security**: Strict validation ensures only SELECT queries are executed
- **Paginated Results**: Server-side pagination for efficient data browsing
- **CSV Export**: Export query results directly to CSV
- **Admin UI**: Intuitive Shopware Admin interface for query generation and execution
- **Query Logging**: Track all generated and executed queries

## Requirements

- Shopware 6.5+
- PHP 8.1+
- OpenAI API Key (for ChatGPT access)

## Installation

### 1. Install Plugin

```bash
# Copy plugin to custom/plugins directory
cd /path/to/shopware
cp -r Coding9KIDataSelector custom/plugins/

# Or if using composer (recommended)
composer require coding9/ki-data-selector

# Refresh plugin list
bin/console plugin:refresh

# Install and activate
bin/console plugin:install --activate Coding9KIDataSelector

# Clear cache
bin/console cache:clear
```

### 2. Configure Plugin

1. Go to **Settings** > **System** > **Plugins**
2. Find **KI Data Selector** and click **Configure**
3. Enter your OpenAI API Key
4. Select the ChatGPT model (gpt-4o-mini recommended for cost efficiency)
5. Adjust other settings as needed:
   - **Enable Logging**: Enable detailed logging
   - **Default Page Size**: Default results per page (25)
   - **Max Page Size**: Maximum results per page (200)
   - **SQL Timeout**: Database query timeout in milliseconds (20000)
   - **Locale**: Locale for time references (de_DE, en_US)

### 3. Access the Module

Navigate to **Catalogue** > **KI Data Selector** in the Shopware Admin panel.

## Usage

### Basic Query

1. Enter a natural language question:
   ```
   Give me all orders from last week
   ```

2. Click **Generate SQL** to generate the query without executing it

3. Click **Execute** to run the query and see results

### Example Queries

```
Give me all orders from last week

Show me all sold products from last month

Top 10 customers by revenue this year

Show me products with low stock (less than 10)

Orders with status 'open' today

List all customers who ordered more than 5 times

Show me the most expensive products in category X
```

### CSV Export

Click **Export as CSV** to download the query results as a CSV file with semicolon delimiter.

### Pagination

Use the pagination controls at the bottom of the results table to navigate through large result sets.

## API Endpoints

### POST /api/_action/kidata/query

Generate and optionally execute SQL queries.

**Request:**
```json
{
  "prompt": "Give me all orders from last week",
  "page": 1,
  "limit": 25,
  "sort": null,
  "execute": true,
  "checkSchema": false
}
```

**Response:**
```json
{
  "success": true,
  "sql": "SELECT o.id, o.order_number, o.order_date_time FROM `order` o WHERE o.order_date_time >= (NOW() - INTERVAL 7 DAY) ORDER BY o.order_date_time DESC",
  "executed": true,
  "columns": ["id", "order_number", "order_date_time"],
  "rows": [...],
  "total": 150,
  "page": 1,
  "limit": 25,
  "totalPages": 6
}
```

### POST /api/_action/kidata/export

Export query results as CSV.

**Request:**
```json
{
  "prompt": "Give me all orders from last week",
  "delimiter": ";",
  "enclosure": "\""
}
```

**Response:** CSV file stream

## Security

### Read-Only Validation

The plugin enforces strict read-only access:

- **Allowed**: `SELECT` queries with `JOIN`, `GROUP BY`, `HAVING`, `ORDER BY`, `LIMIT`, `OFFSET`
- **Forbidden**: `INSERT`, `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, `ALTER`, `CREATE`, etc.
- **Blocked**: Multiple statements, SQL comments, MySQL conditional comments
- **Protected**: SQL injection patterns, system procedures

### Access Control

- Only administrators and users with `system.config` privilege can access the module
- All queries are logged with timestamp, prompt, SQL, and execution status
- API endpoints require admin authentication

## Architecture

### Core Services

1. **SchemaProvider** (`src/Core/Service/SchemaProvider.php`)
   - Reads database schema at runtime
   - Provides table/column information to ChatGPT

2. **PromptBuilder** (`src/Core/Service/PromptBuilder.php`)
   - Builds system prompts with schema and examples
   - Instructs ChatGPT on SQL generation rules

3. **KiChatGptService** (`src/Core/Service/KiChatGptService.php`)
   - Communicates with OpenAI API
   - Generates SQL from natural language

4. **SqlValidatorService** (`src/Core/Service/SqlValidatorService.php`)
   - Validates SQL for read-only access
   - Checks for forbidden keywords and patterns

5. **SqlExecutorService** (`src/Core/Service/SqlExecutorService.php`)
   - Executes validated SQL with pagination
   - Generates CSV exports

### Database

**Table: kidata_query_log**
```sql
CREATE TABLE `kidata_query_log` (
    `id` BINARY(16) NOT NULL,
    `prompt` LONGTEXT NOT NULL,
    `sql_query` LONGTEXT NOT NULL,
    `executed` TINYINT(1) NOT NULL DEFAULT 0,
    `row_count` INT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
);
```

## Configuration Options

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `kidata.apiKey` | string | required | OpenAI API key |
| `kidata.model` | enum | gpt-4o-mini | ChatGPT model (gpt-4o-mini, gpt-4o, gpt-4-turbo) |
| `kidata.enableLogging` | bool | true | Enable detailed logging |
| `kidata.defaultPageSize` | int | 25 | Default results per page |
| `kidata.maxPageSize` | int | 200 | Maximum results per page |
| `kidata.sqlTimeoutMs` | int | 20000 | SQL query timeout (ms) |
| `kidata.locale` | string | de_DE | Locale for time references |

## Development

### Project Structure

```
Coding9KIDataSelector/
├── composer.json
├── README.md
├── src/
│   ├── Coding9KIDataSelector.php
│   ├── Core/
│   │   ├── Api/Controller/
│   │   │   └── KiDataController.php
│   │   ├── Service/
│   │   │   ├── SchemaProvider.php
│   │   │   ├── PromptBuilder.php
│   │   │   ├── KiChatGptService.php
│   │   │   ├── SqlValidatorService.php
│   │   │   └── SqlExecutorService.php
│   │   └── Subscriber/
│   │       └── AdminAclSubscriber.php
│   ├── Migration/
│   │   └── Migration1700000000CreateKiDataTables.php
│   └── Resources/
│       ├── config/
│       │   ├── services.xml
│       │   ├── plugin.xml
│       │   └── config.xml
│       └── app/administration/src/
│           ├── main.js
│           └── module/kidata-selector/
│               ├── index.js
│               └── page/kidata-selector-index/
│                   ├── index.js
│                   ├── kidata-selector-index.html.twig
│                   └── kidata-selector-index.scss
```

### Running Tests

```bash
# Unit tests (when available)
bin/phpunit --configuration custom/plugins/Coding9KIDataSelector/phpunit.xml
```

## Troubleshooting

### "OpenAI API key not configured"

Make sure you've entered your OpenAI API key in the plugin configuration.

### "Query execution failed"

- Check if the generated SQL is valid
- Ensure tables and columns exist in your database
- Check SQL timeout settings if query is complex

### "Access denied"

Make sure your user has administrator privileges or the `system.config` permission.

### No results in Admin UI

- Check browser console for JavaScript errors
- Clear Shopware cache: `bin/console cache:clear`
- Rebuild administration: `bin/console bundle:dump && bin/build-administration.sh`

## Best Practices

1. **Start Simple**: Begin with simple queries to understand how the system interprets your prompts
2. **Be Specific**: More specific prompts generate better SQL
3. **Review Generated SQL**: Always review the generated SQL before executing on production
4. **Use Pagination**: For large result sets, use pagination instead of exporting everything
5. **Monitor Costs**: OpenAI API usage has costs - monitor your usage in OpenAI dashboard

## Support

For issues, feature requests, or contributions, please visit:
- Email: support@coding9.com

## Credits

Developed by Coding9
Built for Shopware 6

---

**Version**: 1.0.0
**Last Updated**: 2025