<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add fulltext index for better search
        DB::statement('ALTER TABLE tickets ADD FULLTEXT search_index (ticket_number, subject, description)');

        // Add fulltext to replies
        DB::statement('ALTER TABLE ticket_replies ADD FULLTEXT reply_search_index (message)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('search_index');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropIndex('reply_search_index');
        });
    }
};
