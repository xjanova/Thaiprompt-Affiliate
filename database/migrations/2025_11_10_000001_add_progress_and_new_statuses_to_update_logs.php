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
        Schema::table('update_logs', function (Blueprint $table) {
            $table->integer('progress')->default(0)->after('status'); // 0-100
        });

        // Update status enum to include new statuses
        DB::statement("ALTER TABLE `update_logs` MODIFY COLUMN `status` ENUM(
            'pending',
            'downloading',
            'downloaded',
            'preparing',
            'backing_up',
            'migrating',
            'seeding',
            'updating',
            'finalizing',
            'completed',
            'failed',
            'rolled_back'
        ) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('update_logs', function (Blueprint $table) {
            $table->dropColumn('progress');
        });

        // Revert status enum
        DB::statement("ALTER TABLE `update_logs` MODIFY COLUMN `status` ENUM(
            'pending',
            'downloading',
            'downloaded',
            'preparing',
            'backing_up',
            'migrating',
            'updating',
            'completed',
            'failed',
            'rolled_back'
        ) DEFAULT 'pending'");
    }
};
