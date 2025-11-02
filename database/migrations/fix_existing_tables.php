<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Check and create only missing tables
        $tables = [
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

        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                echo "Table {$table} does not exist, please run the specific migration for it.\n";
            } else {
                echo "Table {$table} already exists, skipping.\n";
            }
        }
    }

    private function tableExists($table)
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
};
