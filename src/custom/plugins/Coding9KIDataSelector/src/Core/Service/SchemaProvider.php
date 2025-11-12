<?php declare(strict_types=1);

namespace Coding9\KIDataSelector\Core\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Provides database schema information at runtime
 *
 * Reads tables, columns, data types, primary keys and foreign keys
 * from INFORMATION_SCHEMA using Doctrine DBAL.
 *
 * @package Coding9\KIDataSelector\Core\Service
 */
class SchemaProvider
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Get complete database schema as JSON structure
     *
     * @return array Schema structure with tables, columns, and foreign keys
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSchema(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTables();

        $schema = [
            'database' => $this->connection->getDatabase(),
            'tables' => []
        ];

        foreach ($tables as $table) {
            $tableName = $table->getName();

            $tableInfo = [
                'name' => $tableName,
                'columns' => [],
                'primaryKey' => [],
                'foreignKeys' => [],
                'indexes' => []
            ];

            // Columns
            foreach ($table->getColumns() as $column) {
                $type = $column->getType();

                // Get type name - compatible with Doctrine DBAL 2.x and 3.x
                if (method_exists($type, 'getName')) {
                    $typeName = $type->getName();
                } else {
                    // Fallback for DBAL 3.x: extract type from class name
                    $className = get_class($type);
                    $typeName = strtolower(str_replace(['Doctrine\\DBAL\\Types\\', 'Type'], '', $className));
                }

                $tableInfo['columns'][] = [
                    'name' => $column->getName(),
                    'type' => $typeName,
                    'length' => $column->getLength(),
                    'nullable' => !$column->getNotnull(),
                    'default' => $column->getDefault(),
                    'autoIncrement' => $column->getAutoincrement(),
                    'comment' => $column->getComment()
                ];
            }

            // Primary Key
            if ($table->hasPrimaryKey()) {
                $tableInfo['primaryKey'] = $table->getPrimaryKey()->getColumns();
            }

            // Foreign Keys
            foreach ($table->getForeignKeys() as $foreignKey) {
                $tableInfo['foreignKeys'][] = [
                    'name' => $foreignKey->getName(),
                    'localColumns' => $foreignKey->getLocalColumns(),
                    'foreignTable' => $foreignKey->getForeignTableName(),
                    'foreignColumns' => $foreignKey->getForeignColumns(),
                    'onUpdate' => $foreignKey->onUpdate(),
                    'onDelete' => $foreignKey->onDelete()
                ];
            }

            // Indexes
            foreach ($table->getIndexes() as $index) {
                if ($index->isPrimary()) {
                    continue; // Skip primary key, already captured
                }

                $tableInfo['indexes'][] = [
                    'name' => $index->getName(),
                    'columns' => $index->getColumns(),
                    'unique' => $index->isUnique()
                ];
            }

            $schema['tables'][$tableName] = $tableInfo;
        }

        return $schema;
    }

    /**
     * Get compact schema representation for prompt (reduced size)
     *
     * Only includes essential information: table names, column names,
     * types, and foreign key relationships.
     *
     * @param int $maxTables Maximum number of tables to include (0 = all)
     * @return array Compact schema structure
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCompactSchema(int $maxTables = 0): array
    {
        $fullSchema = $this->getSchema();
        $compact = [
            'database' => $fullSchema['database'],
            'tables' => []
        ];

        // Sort tables by foreign key count (most connected first)
        $tablesWithFkCount = [];
        foreach ($fullSchema['tables'] as $tableName => $tableInfo) {
            $fkCount = count($tableInfo['foreignKeys']);
            $tablesWithFkCount[$tableName] = $fkCount;
        }
        arsort($tablesWithFkCount);

        $count = 0;
        foreach ($tablesWithFkCount as $tableName => $fkCount) {
            if ($maxTables > 0 && $count >= $maxTables) {
                break;
            }

            $tableInfo = $fullSchema['tables'][$tableName];

            $compactTable = [
                'columns' => [],
                'pk' => $tableInfo['primaryKey'],
                'fks' => []
            ];

            // Only include column name and type
            foreach ($tableInfo['columns'] as $column) {
                $compactTable['columns'][$column['name']] = [
                    'type' => $column['type'],
                    'nullable' => $column['nullable']
                ];
            }

            // Foreign keys with simplified structure
            foreach ($tableInfo['foreignKeys'] as $fk) {
                $compactTable['fks'][] = [
                    'from' => $fk['localColumns'],
                    'to' => $fk['foreignTable'],
                    'toCols' => $fk['foreignColumns']
                ];
            }

            $compact['tables'][$tableName] = $compactTable;
            $count++;
        }

        return $compact;
    }

    /**
     * Get list of all table names
     *
     * @return array List of table names
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllTableNames(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        return array_map(
            fn($table) => $table->getName(),
            $schemaManager->listTables()
        );
    }

    /**
     * Check if a table exists
     *
     * @param string $tableName Table name to check
     * @return bool True if table exists
     * @throws \Doctrine\DBAL\Exception
     */
    public function tableExists(string $tableName): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        return $schemaManager->tablesExist([$tableName]);
    }

    /**
     * Get columns for a specific table
     *
     * @param string $tableName Table name
     * @return array Column information
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTableColumns(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns($tableName);

        $result = [];
        foreach ($columns as $column) {
            $type = $column->getType();

            // Get type name - compatible with Doctrine DBAL 2.x and 3.x
            if (method_exists($type, 'getName')) {
                $typeName = $type->getName();
            } else {
                // Fallback for DBAL 3.x: extract type from class name
                $className = get_class($type);
                $typeName = strtolower(str_replace(['Doctrine\\DBAL\\Types\\', 'Type'], '', $className));
            }

            $result[] = [
                'name' => $column->getName(),
                'type' => $typeName,
                'nullable' => !$column->getNotnull()
            ];
        }

        return $result;
    }
}
