<?php

namespace Database\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SafeMigration Trait
 *
 * ใช้สำหรับป้องกันปัญหา Duplicate Column/Index ในการ migrate
 * สามารถนำไปใช้กับ migration ทุกตัวได้
 *
 * Example Usage:
 * ```
 * use Database\Migrations\Concerns\SafeMigration;
 *
 * return new class extends Migration {
 *     use SafeMigration;
 *
 *     public function up(): void {
 *         Schema::table('users', function (Blueprint $table) {
 *             $this->safeAddColumn($table, 'users', 'email_verified', function($table) {
 *                 $table->boolean('email_verified')->default(false);
 *             });
 *         });
 *     }
 * }
 * ```
 */
trait SafeMigration
{
    /**
     * เพิ่ม column อย่างปลอดภัย - จะไม่ error ถ้า column มีอยู่แล้ว
     */
    protected function safeAddColumn(Blueprint $table, string $tableName, string $columnName, callable $definition): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            $definition($table);
        }
    }

    /**
     * ลบ column อย่างปลอดภัย - จะไม่ error ถ้า column ไม่มี
     */
    protected function safeDropColumn(string $tableName, string|array $columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $existingColumns = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                $existingColumns[] = $column;
            }
        }

        if (!empty($existingColumns)) {
            Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }

    /**
     * เพิ่ม index อย่างปลอดภัย - จะไม่ error ถ้า index มีอยู่แล้ว
     */
    protected function safeAddIndex(string $tableName, string|array $columns, string $indexName = null, string $type = 'index'): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $indexName = $indexName ?? $this->generateIndexName($tableName, $columns, $type);

        if (!$this->indexExists($tableName, $indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $type) {
                switch ($type) {
                    case 'unique':
                        $table->unique($columns, $indexName);
                        break;
                    case 'index':
                        $table->index($columns, $indexName);
                        break;
                    case 'fulltext':
                        $table->fullText($columns, $indexName);
                        break;
                }
            });
        }
    }

    /**
     * ลบ index อย่างปลอดภัย - จะไม่ error ถ้า index ไม่มี
     */
    protected function safeDropIndex(string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    /**
     * เพิ่ม foreign key อย่างปลอดภัย
     */
    protected function safeAddForeign(string $tableName, string $column, string $foreignTable, string $foreignColumn = 'id', string $constraintName = null): void
    {
        $constraintName = $constraintName ?? "{$tableName}_{$column}_foreign";

        if (!$this->foreignKeyExists($tableName, $constraintName)) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $foreignTable, $foreignColumn, $constraintName) {
                $table->foreign($column, $constraintName)
                    ->references($foreignColumn)
                    ->on($foreignTable)
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * ลบ foreign key อย่างปลอดภัย
     */
    protected function safeDropForeign(string $tableName, string $constraintName): void
    {
        if ($this->foreignKeyExists($tableName, $constraintName)) {
            Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                $table->dropForeign($constraintName);
            });
        }
    }

    /**
     * เช็คว่า index มีอยู่หรือไม่
     */
    protected function indexExists(string $tableName, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes($tableName);

        return isset($indexes[strtolower($indexName)]);
    }

    /**
     * เช็คว่า foreign key มีอยู่หรือไม่
     */
    protected function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($tableName);

        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getName() === $constraintName) {
                return true;
            }
        }

        return false;
    }

    /**
     * สร้างชื่อ index แบบ standard ของ Laravel
     */
    protected function generateIndexName(string $tableName, array $columns, string $type): string
    {
        $suffix = match($type) {
            'unique' => 'unique',
            'fulltext' => 'fulltext',
            default => 'index',
        };

        return strtolower($tableName . '_' . implode('_', $columns) . '_' . $suffix);
    }

    /**
     * เพิ่มหลาย columns พร้อมกันอย่างปลอดภัย
     */
    protected function safeAddColumns(Blueprint $table, string $tableName, array $columns): void
    {
        foreach ($columns as $columnName => $definition) {
            $this->safeAddColumn($table, $tableName, $columnName, $definition);
        }
    }

    /**
     * Rename column อย่างปลอดภัย
     */
    protected function safeRenameColumn(string $tableName, string $from, string $to): void
    {
        if (Schema::hasColumn($tableName, $from) && !Schema::hasColumn($tableName, $to)) {
            Schema::table($tableName, function (Blueprint $table) use ($from, $to) {
                $table->renameColumn($from, $to);
            });
        }
    }

    /**
     * เช็คและสร้างตารางถ้ายังไม่มี
     */
    protected function safeCreateTable(string $tableName, callable $callback): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, $callback);
        }
    }

    /**
     * เช็คและลบตารางถ้ามี
     */
    protected function safeDropTable(string $tableName): void
    {
        Schema::dropIfExists($tableName);
    }
}
