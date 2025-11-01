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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_rank_id')->nullable()->after('affiliate_id')
                ->constrained('ranks')->onDelete('set null');
            $table->integer('rank_points')->default(0)->after('current_rank_id');
            $table->timestamp('rank_updated_at')->nullable()->after('rank_points');
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('rank_id')->nullable()->after('status')
                ->constrained('ranks')->onDelete('set null');
            $table->integer('rank_points')->default(0)->after('rank_id');
            $table->decimal('monthly_sales', 12, 2)->default(0)->after('rank_points');
            $table->decimal('team_sales', 12, 2)->default(0)->after('monthly_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_rank_id']);
            $table->dropColumn(['current_rank_id', 'rank_points', 'rank_updated_at']);
        });

        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['rank_id']);
            $table->dropColumn(['rank_id', 'rank_points', 'monthly_sales', 'team_sales']);
        });
    }
};
