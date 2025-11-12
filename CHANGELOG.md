# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-11-12

### Fixed
- Fixed Doctrine DBAL 3.x compatibility issues with Type::getName() method
- Fixed Doctrine DBAL 3.x compatibility with hasPrimaryKey() method
- Added fallback for type name extraction using class name parsing

### Changed
- Removed redundant documentation files from plugin directory
- Centralized documentation in docs/ folder

## [1.0.0] - 2025-10-29

### Added
- Initial release of Coding9 KI Data Selector
- Natural language to SQL query generation using OpenAI GPT models
- Read-only SQL validation and security checks
- Database schema provider for context-aware query generation
- CSV export functionality for query results
- Admin panel interface for query management
- Query logging and history tracking
- Support for Shopware 6.5, 6.6, and 6.7+
- Multi-version compatibility layer
- Comprehensive error handling and validation
- Paginated result display
- Real-time SQL preview before execution

### Security
- Strict read-only query enforcement
- SQL injection prevention
- Automatic validation of all generated queries
- Safe execution environment for database queries

[1.0.0]: https://github.com/coding9/shopware-ki-data-selector/releases/tag/v1.0.0
