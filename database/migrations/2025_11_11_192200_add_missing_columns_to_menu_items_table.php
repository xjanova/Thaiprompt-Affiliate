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
        Schema::table('menu_items', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('menu_items', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }

            if (!Schema::hasColumn('menu_items', 'title_th')) {
                $table->string('title_th')->nullable()->after('title_en');
            }

            if (!Schema::hasColumn('menu_items', 'position')) {
                $table->integer('position')->default(0)->after('order');
            }

            if (!Schema::hasColumn('menu_items', 'permission')) {
                $table->string('permission')->nullable()->after('conditions');
            }

            if (!Schema::hasColumn('menu_items', 'open_in_new_tab')) {
                $table->boolean('open_in_new_tab')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('menu_items', 'menu_location')) {
                $table->string('menu_location')->default('header')->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $columns = ['title_en', 'title_th', 'position', 'permission', 'open_in_new_tab'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('menu_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
