<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('show_immediately');
            $table->boolean('is_scheduled')->default(false)->after('scheduled_at');
            $table->boolean('is_sent')->default(true)->after('is_scheduled'); // false if scheduled
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'is_scheduled', 'is_sent']);
        });
    }
};
