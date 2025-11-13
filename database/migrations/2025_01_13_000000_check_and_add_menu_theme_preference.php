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
        // Check if column exists before adding
        if (!Schema::hasColumn('users', 'menu_theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('menu_theme_preference')->default('millennium')->after('preferred_language');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'menu_theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('menu_theme_preference');
            });
        }
    }
};
