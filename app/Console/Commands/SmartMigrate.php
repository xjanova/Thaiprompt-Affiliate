<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SmartMigrate extends Command
{
    protected $signature = 'migrate:smart {--force : Force the operation to run in production}';
    protected $description = 'Smart migration system that handles existing tables';

    private $stats = [
        'tables_created' => 0,
        'tables_updated' => 0,
        'tables_skipped' => 0,
        'columns_added' => 0,
        'columns_modified' => 0,
        'errors' => [],
    ];

    public function handle()
    {
        $this->info('🎯 Smart Migration System');
        $this->newLine();

        // Check database connection
        if (!$this->checkDatabaseConnection()) {
            $this->error('✗ Database connection failed');
            return 1;
        }
        $this->info('✓ Database connection OK');
        $this->newLine();

        // Get pending migrations
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            $this->info('✓ No pending migrations');
            return 0;
        }

        $this->info("Found " . count($pending) . " pending migration(s)");
        $this->newLine();

        foreach ($pending as $migration) {
            $this->processMigration($migration);
        }

        // Show summary
        $this->showSummary();

        // Mark migrations as ran
        $this->markMigrationsAsRan($pending);

        return 0;
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
    }

    private function getPendingMigrations(): array
    {
        $migrations = [];
        $migrationFiles = glob(database_path('migrations/*.php'));

        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');

            // Check if migration already ran
            $exists = DB::table('migrations')
                ->where('migration', $migrationName)
                ->exists();

            if (!$exists) {
                $migrations[] = [
                    'name' => $migrationName,
                    'file' => $file,
                ];
            }
        }

        return $migrations;
    }

    private function processMigration(array $migration): void
    {
        $this->line("→ Processing: {$migration['name']}");

        // Detect table name from migration filename
        $tableName = $this->extractTableName($migration['name']);

        if (!$tableName) {
            $this->warn("  ⚠ Could not detect table name, running normal migration");
            $this->runNormalMigration($migration);
            return;
        }

        // Check if table exists
        if (Schema::hasTable($tableName)) {
            $this->handleExistingTable($migration, $tableName);
        } else {
            $this->handleNewTable($migration, $tableName);
        }
    }

    private function extractTableName(string $migrationName): ?string
    {
        // Pattern: create_TABLE_NAME_table
        if (preg_match('/create_(.+?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        // Pattern: add_COLUMN_to_TABLE_table
        if (preg_match('/add_.+?_to_(.+?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function handleExistingTable(array $migration, string $tableName): void
    {
        $this->line("  → Table '{$tableName}' exists, checking schema...");

        // Get expected columns from migration
        $expectedColumns = $this->parseExpectedColumns($migration['file']);

        if (empty($expectedColumns)) {
            $this->warn("  ⚠ Could not parse migration, skipping");
            $this->stats['tables_skipped']++;
            return;
        }

        // Get existing columns
        $existingColumns = $this->getTableColumns($tableName);

        // Find missing columns
        $missingColumns = array_diff_key($expectedColumns, $existingColumns);

        if (empty($missingColumns)) {
            $this->info("  ✓ Schema up to date (skipped)");
            $this->stats['tables_skipped']++;
            return;
        }

        // Add missing columns
        $this->line("  → Adding " . count($missingColumns) . " missing column(s)...");

        foreach ($missingColumns as $columnName => $columnDef) {
            if ($this->addColumn($tableName, $columnName, $columnDef)) {
                $this->info("    ✓ Added: {$columnName}");
                $this->stats['columns_added']++;
            } else {
                $this->error("    ✗ Failed to add: {$columnName}");
                $this->stats['errors'][] = "Failed to add {$columnName} to {$tableName}";
            }
        }

        $this->stats['tables_updated']++;
    }

    private function handleNewTable(array $migration, string $tableName): void
    {
        $this->line("  → Creating new table '{$tableName}'...");

        if ($this->runNormalMigration($migration)) {
            $this->info("  ✓ Table created successfully");
            $this->stats['tables_created']++;
        } else {
            $this->error("  ✗ Failed to create table");
            $this->stats['errors'][] = "Failed to create table {$tableName}";
        }
    }

    private function parseExpectedColumns(string $migrationFile): array
    {
        $columns = [];
        $content = file_get_contents($migrationFile);

        // Common column patterns
        $patterns = [
            '/\$table->(\w+)\(\'(\w+)\'[^;]*\)/',  // $table->string('name')
            '/\$table->(\w+)\(\)/',                  // $table->timestamps()
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $type = $match[1];
                    $name = $match[2] ?? $this->inferColumnName($type);

                    if ($name) {
                        $columns[$name] = [
                            'type' => $type,
                            'definition' => $match[0],
                        ];
                    }
                }
            }
        }

        // Handle timestamps()
        if (stripos($content, '$table->timestamps()') !== false) {
            $columns['created_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'created_at\')->nullable()'];
            $columns['updated_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'updated_at\')->nullable()'];
        }

        // Handle softDeletes()
        if (stripos($content, '$table->softDeletes()') !== false) {
            $columns['deleted_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'deleted_at\')->nullable()'];
        }

        // Handle id()
        if (stripos($content, '$table->id()') !== false) {
            $columns['id'] = ['type' => 'bigInteger', 'definition' => '$table->id()'];
        }

        return $columns;
    }

    private function inferColumnName(string $type): ?string
    {
        $mapping = [
            'timestamps' => null,  // Returns multiple columns
            'softDeletes' => 'deleted_at',
            'id' => 'id',
            'rememberToken' => 'remember_token',
        ];

        return $mapping[$type] ?? null;
    }

    private function getTableColumns(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $result = [];

        foreach ($columns as $column) {
            $result[$column] = true;
        }

        return $result;
    }

    private function addColumn(string $tableName, string $columnName, array $columnDef): bool
    {
        try {
            Schema::table($tableName, function ($table) use ($columnName, $columnDef) {
                // Determine column type and add accordingly
                $type = $columnDef['type'];

                switch ($type) {
                    case 'string':
                        $table->string($columnName)->nullable();
                        break;
                    case 'text':
                        $table->text($columnName)->nullable();
                        break;
                    case 'integer':
                    case 'int':
                        $table->integer($columnName)->nullable()->default(0);
                        break;
                    case 'bigInteger':
                        $table->bigInteger($columnName)->nullable();
                        break;
                    case 'decimal':
                        $table->decimal($columnName, 10, 2)->nullable()->default(0);
                        break;
                    case 'boolean':
                        $table->boolean($columnName)->default(false);
                        break;
                    case 'json':
                        $table->json($columnName)->nullable();
                        break;
                    case 'timestamp':
                        $table->timestamp($columnName)->nullable();
                        break;
                    case 'date':
                        $table->date($columnName)->nullable();
                        break;
                    case 'foreignId':
                        $table->foreignId($columnName)->nullable();
                        break;
                    default:
                        // Generic nullable column
                        $table->string($columnName)->nullable();
                }
            });

            return true;
        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
            return false;
        }
    }

    private function runNormalMigration(array $migration): bool
    {
        try {
            require_once $migration['file'];

            // Get the migration class
            $migrationClass = include $migration['file'];

            if (is_object($migrationClass) && method_exists($migrationClass, 'up')) {
                $migrationClass->up();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
            return false;
        }
    }

    private function markMigrationsAsRan(array $migrations): void
    {
        if (empty($migrations)) {
            return;
        }

        $maxBatch = DB::table('migrations')->max('batch') ?? 0;
        $nextBatch = $maxBatch + 1;

        foreach ($migrations as $migration) {
            DB::table('migrations')->insert([
                'migration' => $migration['name'],
                'batch' => $nextBatch,
            ]);
        }

        $this->newLine();
        $this->info("✓ Marked " . count($migrations) . " migration(s) as ran (batch: {$nextBatch})");
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('📊 Smart Migration Summary:');
        $this->line("  • Tables created: {$this->stats['tables_created']}");
        $this->line("  • Tables updated: {$this->stats['tables_updated']}");
        $this->line("  • Tables skipped: {$this->stats['tables_skipped']}");
        $this->line("  • Columns added: {$this->stats['columns_added']}");

        if (!empty($this->stats['errors'])) {
            $this->newLine();
            $this->warn('⚠ Errors encountered:');
            foreach ($this->stats['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }

        $this->newLine();
    }
}
