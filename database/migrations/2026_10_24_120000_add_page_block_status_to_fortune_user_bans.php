<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_user_bans')) {
            return;
        }

        Schema::table('fortune_user_bans', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_user_bans', 'page_block_status')) {
                $table->string('page_block_status', 30)->nullable()->index();
            }
            if (! Schema::hasColumn('fortune_user_bans', 'page_block_error')) {
                $table->text('page_block_error')->nullable();
            }
            if (! Schema::hasColumn('fortune_user_bans', 'page_block_attempted_at')) {
                $table->timestamp('page_block_attempted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_user_bans')) {
            return;
        }

        if (Schema::hasIndex('fortune_user_bans', 'fortune_user_bans_page_block_status_index')) {
            Schema::table('fortune_user_bans', fn (Blueprint $table) => $table->dropIndex('fortune_user_bans_page_block_status_index'));
        }

        foreach (['page_block_status', 'page_block_error', 'page_block_attempted_at'] as $column) {
            if (Schema::hasColumn('fortune_user_bans', $column)) {
                Schema::table('fortune_user_bans', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
