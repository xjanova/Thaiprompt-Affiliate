<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;

class SchemaVerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:verify
                            {--fix : Generate ALTER TABLE statements to fix schema}
                            {--snapshot : Update schema snapshot file}
                            {--table= : Verify specific table only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify database schema integrity and detect schema drift';

    /**
     * Schema snapshot file path
     */
    protected $snapshotPath;

    /**
     * Issues found during verification
     */
    protected $issues = [];

    /**
     * Tables to verify
     */
    protected $tablesToVerify = [
        'line_bot_ai_settings',
        'line_bot_knowledge_bases',
        'line_bot_conversations',
        'line_bot_messages',
        'line_flex_message_templates',
        'line_rich_menus',
        'line_chat_widget_settings',
        'line_avatars',
        'line_broadcast_messages',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->snapshotPath = database_path('schema_snapshot.json');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Schema Verification System');
        $this->newLine();

        // Filter by specific table if provided
        if ($table = $this->option('table')) {
            $this->tablesToVerify = [$table];
        }

        // Update snapshot if requested
        if ($this->option('snapshot')) {
            return $this->updateSnapshot();
        }

        // Verify schema
        $this->info('→ Checking database schema integrity...');
        $this->newLine();

        $hasIssues = false;

        foreach ($this->tablesToVerify as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("✗ Table '{$table}' does not exist!");
                $this->issues[] = [
                    'table' => $table,
                    'type' => 'missing_table',
                    'message' => "Table does not exist",
                ];
                $hasIssues = true;
                continue;
            }

            $this->info("Checking table: {$table}");

            // Verify columns
            $issues = $this->verifyTableSchema($table);

            if (!empty($issues)) {
                $hasIssues = true;
                foreach ($issues as $issue) {
                    $this->warn("  ⚠ {$issue}");
                }
            } else {
                $this->line("  ✓ Schema is correct");
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        if ($hasIssues) {
            $this->error('═══════════════════════════════════════');
            $this->error('  ⚠️  Schema Issues Detected!');
            $this->error('═══════════════════════════════════════');
            $this->newLine();

            $this->warn('💡 Recommendations:');
            $this->line('  1. Review the issues above');
            $this->line('  2. Run: php artisan schema:verify --fix');
            $this->line('  3. Or manually fix the schema');
            $this->line('  4. Then run: php artisan migrate --force');
            $this->newLine();

            if ($this->option('fix')) {
                $this->generateFixStatements();
            }

            return Command::FAILURE;
        } else {
            $this->info('═══════════════════════════════════════');
            $this->info('  ✅ All Schema Checks Passed!');
            $this->info('═══════════════════════════════════════');
            return Command::SUCCESS;
        }
    }

    /**
     * Verify table schema against expected structure
     */
    protected function verifyTableSchema(string $table): array
    {
        $issues = [];

        try {
            // Get expected schema
            $expectedSchema = $this->getExpectedSchema($table);
            if (empty($expectedSchema)) {
                return ["No expected schema defined for verification"];
            }

            // Get actual columns
            $actualColumns = $this->getTableColumns($table);

            // Check for missing columns
            foreach ($expectedSchema as $columnName => $expectedType) {
                if (!isset($actualColumns[$columnName])) {
                    $issues[] = "Missing column: {$columnName} ({$expectedType})";
                    $this->issues[] = [
                        'table' => $table,
                        'type' => 'missing_column',
                        'column' => $columnName,
                        'expected_type' => $expectedType,
                    ];
                }
            }

            // Check for extra columns (may not be an issue, just informational)
            $expectedKeys = array_keys($expectedSchema);
            $actualKeys = array_keys($actualColumns);
            $extraColumns = array_diff($actualKeys, $expectedKeys);

            if (!empty($extraColumns)) {
                foreach ($extraColumns as $extra) {
                    $this->issues[] = [
                        'table' => $table,
                        'type' => 'extra_column',
                        'column' => $extra,
                        'actual_type' => $actualColumns[$extra],
                    ];
                }
            }

        } catch (\Exception $e) {
            $issues[] = "Error verifying schema: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Get table columns using Schema facade
     */
    protected function getTableColumns(string $table): array
    {
        $columns = [];

        try {
            $columnNames = Schema::getColumnListing($table);

            foreach ($columnNames as $columnName) {
                $columnType = Schema::getColumnType($table, $columnName);
                $columns[$columnName] = $columnType;
            }
        } catch (\Exception $e) {
            $this->error("Error getting columns for {$table}: " . $e->getMessage());
        }

        return $columns;
    }

    /**
     * Get expected schema for a table
     */
    protected function getExpectedSchema(string $table): array
    {
        // Define expected schemas for LINE Bot tables
        $schemas = [
            'line_bot_ai_settings' => [
                'id' => 'bigint',
                'name' => 'string',
                'provider' => 'string',
                'api_key' => 'text',
                'api_endpoint' => 'string',
                'model' => 'string',
                'temperature' => 'decimal',
                'max_tokens' => 'integer',
                'system_prompt' => 'text',
                'is_active' => 'boolean',
                'enable_conversation_history' => 'boolean',
                'conversation_memory_limit' => 'integer',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_bot_knowledge_bases' => [
                'id' => 'bigint',
                'ai_setting_id' => 'bigint',
                'name' => 'string',
                'source_type' => 'string',
                'source_url' => 'string',
                'source_file_path' => 'string',
                'content' => 'text',
                'priority' => 'integer',
                'is_active' => 'boolean',
                'last_synced_at' => 'datetime',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_bot_conversations' => [
                'id' => 'bigint',
                'user_id' => 'bigint',
                'line_user_id' => 'string',
                'session_id' => 'string',
                'ai_setting_id' => 'bigint',
                'message_count' => 'integer',
                'last_message_at' => 'datetime',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_bot_messages' => [
                'id' => 'bigint',
                'conversation_id' => 'bigint',
                'role' => 'string',
                'message' => 'text',
                'metadata' => 'json',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
            ],
            'line_flex_message_templates' => [
                'id' => 'bigint',
                'name' => 'string',
                'category' => 'string',
                'description' => 'text',
                'flex_content' => 'json',
                'thumbnail' => 'string',
                'is_active' => 'boolean',
                'created_by' => 'bigint',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_rich_menus' => [
                'id' => 'bigint',
                'name' => 'string',
                'rich_menu_id' => 'string',
                'size_width' => 'integer',
                'size_height' => 'integer',
                'selected' => 'boolean',
                'chat_bar_text' => 'string',
                'image_url' => 'string',
                'areas' => 'json',
                'is_active' => 'boolean',
                'created_by' => 'bigint',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_chat_widget_settings' => [
                'id' => 'bigint',
                'name' => 'string',
                'position' => 'string',
                'primary_color' => 'string',
                'bot_avatar' => 'string',
                'welcome_message' => 'text',
                'offline_message' => 'text',
                'enable_user_chat' => 'boolean',
                'enable_seller_chat' => 'boolean',
                'enable_ai_bot' => 'boolean',
                'ai_setting_id' => 'bigint',
                'is_active' => 'boolean',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_avatars' => [
                'id' => 'bigint',
                'name' => 'string',
                'type' => 'string',
                'file_path' => 'string',
                'file_url' => 'string',
                'file_size' => 'integer',
                'source_type' => 'string',
                'description' => 'text',
                'is_active' => 'boolean',
                'is_default' => 'boolean',
                'created_by' => 'bigint',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
            'line_broadcast_messages' => [
                'id' => 'bigint',
                'name' => 'string',
                'message_type' => 'string',
                'content' => 'text',
                'flex_template_id' => 'bigint',
                'message_data' => 'json',
                'target_type' => 'string',
                'target_users' => 'json',
                'status' => 'string',
                'scheduled_at' => 'datetime',
                'sent_at' => 'datetime',
                'total_recipients' => 'integer',
                'sent_count' => 'integer',
                'failed_count' => 'integer',
                'created_by' => 'bigint',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
                'deleted_at' => 'datetime',
            ],
        ];

        return $schemas[$table] ?? [];
    }

    /**
     * Generate ALTER TABLE statements to fix schema issues
     */
    protected function generateFixStatements()
    {
        $this->newLine();
        $this->info('🔧 Generated SQL Fix Statements:');
        $this->newLine();

        $hasStatements = false;

        foreach ($this->issues as $issue) {
            if ($issue['type'] === 'missing_table') {
                $this->line("-- Table '{$issue['table']}' is missing");
                $this->line("-- Please run: php artisan migrate --force");
                $this->newLine();
                $hasStatements = true;
            } elseif ($issue['type'] === 'missing_column') {
                $hasStatements = true;
                $sqlType = $this->mapToSqlType($issue['expected_type']);
                $this->line("ALTER TABLE `{$issue['table']}` ADD COLUMN `{$issue['column']}` {$sqlType};");
            }
        }

        if (!$hasStatements) {
            $this->info('No fix statements needed.');
        }

        $this->newLine();
    }

    /**
     * Map Laravel column type to SQL type
     */
    protected function mapToSqlType(string $type): string
    {
        $mapping = [
            'bigint' => 'BIGINT UNSIGNED',
            'integer' => 'INT',
            'string' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'boolean' => 'TINYINT(1)',
            'datetime' => 'TIMESTAMP NULL',
            'decimal' => 'DECIMAL(8,2)',
            'json' => 'JSON',
        ];

        return $mapping[$type] ?? 'VARCHAR(255)';
    }

    /**
     * Update schema snapshot file
     */
    protected function updateSnapshot()
    {
        $this->info('📸 Creating schema snapshot...');
        $this->newLine();

        $snapshot = [
            'created_at' => now()->toDateTimeString(),
            'database' => config('database.default'),
            'tables' => [],
        ];

        foreach ($this->tablesToVerify as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Skipping {$table} (does not exist)");
                continue;
            }

            $this->info("Snapshotting: {$table}");

            $snapshot['tables'][$table] = [
                'columns' => $this->getTableColumns($table),
                'indexes' => $this->getTableIndexes($table),
            ];
        }

        // Save snapshot
        file_put_contents(
            $this->snapshotPath,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->newLine();
        $this->info("✓ Snapshot saved to: {$this->snapshotPath}");
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Get table indexes
     */
    protected function getTableIndexes(string $table): array
    {
        $indexes = [];

        try {
            $rawIndexes = DB::select("SHOW INDEX FROM `{$table}`");

            foreach ($rawIndexes as $index) {
                $keyName = $index->Key_name;
                if (!isset($indexes[$keyName])) {
                    $indexes[$keyName] = [
                        'columns' => [],
                        'unique' => $index->Non_unique == 0,
                    ];
                }
                $indexes[$keyName]['columns'][] = $index->Column_name;
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return $indexes;
    }
}
