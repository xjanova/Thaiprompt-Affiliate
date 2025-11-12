<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop all tables related to the auto-update system that has been removed.
     */
    public function up(): void
    {
        Schema::dropIfExists('update_notifications');
        Schema::dropIfExists('update_logs');
        Schema::dropIfExists('system_updates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - tables and data will be permanently deleted
        // If needed, restore from backup
    }
};
