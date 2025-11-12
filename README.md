# Coding9 KI Data Selector

[![Shopware 6](https://img.shields.io/badge/Shopware-6.5%2B-blue)](https://www.shopware.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Latest Release](https://img.shields.io/badge/release-v1.0.0-blue)](versions/Coding9KiDataSelector-1.0.0.zip)

Transform natural language into SQL queries with AI - a Shopware 6 plugin for intelligent data analysis.

## 🚀 Quick Start

```bash
# Download the latest version
wget https://github.com/coding9/shopware-ki-data-selector/raw/main/versions/Coding9KiDataSelector-1.0.0.zip

# Install in Shopware
cd /path/to/shopware
unzip Coding9KiDataSelector-1.0.0.zip -d custom/plugins/
php bin/console plugin:refresh
php bin/console plugin:install --activate Coding9KiDataSelector
php bin/console cache:clear

# Configure your OpenAI API Key
php bin/console system:config:set Coding9KiDataSelector.config.apiKey "your-api-key-here"
```

## ✨ Features

- **🧠 Natural Language to SQL** - Ask questions in plain language, get optimized SQL
- **🔒 Read-Only Security** - Automatic validation ensures only SELECT queries
- **📊 CSV Export** - Export results directly to CSV format
- **🔄 Multi-Version Support** - Compatible with Shopware 6.5, 6.6, and 6.7+
- **🎯 Schema-Aware** - AI knows your complete database structure
- **⚡ Fast & Efficient** - Optimized queries with pagination support

## ⚠️ Important Notice

**This plugin generates and executes SQL queries based on AI-generated code. Please read carefully:**

- **Test First:** ALWAYS test this plugin in a development or staging environment before using it in production
- **No Warranty:** This software is provided "AS IS" without warranty of any kind
- **User Responsibility:** You are solely responsible for any queries executed and their results
- **Data Safety:** While the plugin enforces read-only queries, always backup your database before installation
- **Review Queries:** Always review generated SQL queries before execution
- **OpenAI Costs:** Using this plugin will incur costs from OpenAI API usage

By using this plugin, you acknowledge that Coding9 and the plugin authors are not liable for any damages, data loss, or business interruption that may occur.

## 📦 Installation

### Requirements

- Shopware 6.5.0 or higher
- PHP 8.0 or higher
- OpenAI API Key ([Get one here](https://platform.openai.com/api-keys))
- **Development/Staging Environment for initial testing (required)**

### Method 1: Download Release (Recommended)

1. Download the latest release: [Coding9KiDataSelector-1.0.0.zip](versions/Coding9KiDataSelector-1.0.0.zip)
2. Extract to `custom/plugins/Coding9KiDataSelector`
3. Run installation commands:

```bash
php bin/console plugin:refresh
php bin/console plugin:install --activate Coding9KiDataSelector
php bin/console cache:clear
```

### Method 2: Via Composer

```bash
composer require coding9/ki-data-selector
php bin/console plugin:refresh
php bin/console plugin:install --activate Coding9KiDataSelector
php bin/console cache:clear
```

### Configure API Key

**Command Line:**
```bash
php bin/console system:config:set Coding9KiDataSelector.config.apiKey "sk-..."
```

**Admin Panel:**
1. Go to Settings → System → Plugins
2. Find "KI Data Selector" → Configure
3. Enter your OpenAI API key → Save

## 🎯 Usage

### In Shopware Admin

1. Navigate to **Extensions → KI Data Selector**
2. Enter your question in natural language:
   ```
   Show me all orders from the last 30 days
   ```
3. Click **Generate Query** to see the SQL
4. Click **Execute** to run it and view results
5. Export to CSV if needed

### Example Queries

```
Show me all customers who ordered in the last 30 days
List products with stock below 10 units
Find orders with total amount over 1000 EUR
Show me the top 10 bestselling products this year
```

### API Endpoints

```bash
# Generate SQL from natural language
POST /api/_action/kidata/query
{
  "prompt": "Show me all orders from last week",
  "execute": true,
  "page": 1,
  "limit": 25
}

# Export results as CSV
POST /api/_action/kidata/export
{
  "prompt": "Show me all orders from last week"
}
```

## 🛠️ Development

### Build Plugin

```bash
# Clone repository
git clone https://github.com/coding9/shopware-ki-data-selector.git
cd shopware-ki-data-selector

# Build plugin ZIP
make build

# The ZIP will be created in versions/
```

### Available Commands

```bash
make help          # Show all available commands
make build         # Build plugin ZIP for distribution
make clean         # Clean build artifacts
make install       # Install plugin in Docker environment
make setup-dev     # Setup complete development environment
```

## 🏗️ Architecture

### Core Components

- **SchemaProvider** - Reads database schema at runtime
- **KiChatGptService** - Communicates with OpenAI API
- **SqlValidatorService** - Validates queries for read-only access
- **SqlExecutorService** - Executes queries safely with pagination
- **PromptBuilder** - Constructs optimized AI prompts

### Security Features

- ✅ Only SELECT statements allowed
- ✅ SQL injection prevention
- ✅ Automatic query validation
- ✅ No destructive operations (INSERT, UPDATE, DELETE, DROP, etc.)
- ✅ Schema-based suggestions

## 📚 Documentation

- [Technical Specification](docs/SPECIFICATION.md) - Detailed technical documentation
- [Store Marketing](docs/STORE_FIELDS.md) - Marketing texts for Shopware Store
- [Testing Guide](docs/TEST_ROUTE.md) - How to test API endpoints
- [Changelog](CHANGELOG.md) - Version history

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

**Use at your own risk. Always test in a non-production environment first.**

## 🐛 Support

- **Issues:** [GitHub Issues](https://github.com/coding9/shopware-ki-data-selector/issues)
- **Email:** support@coding9.de
- **Website:** [https://coding9.de](https://coding9.de)

## 🙏 Credits

Developed with ❤️ by [Coding9](https://coding9.de)

---

**Current Version:** [1.0.0](versions/Coding9KiDataSelector-1.0.0.zip) | **Released:** 2025-10-29
