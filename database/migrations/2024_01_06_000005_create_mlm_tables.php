<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MLM Network Relationships
        Schema::create('mlm_networks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sponsor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('level')->default(0);
            $table->enum('position', ['left', 'right', 'center'])->nullable(); // For binary tree
            $table->string('path')->nullable(); // Path from root to this node
            $table->timestamps();

            $table->index(['sponsor_id', 'level']);
            $table->index('path');
        });

        // MLM Ranks
        Schema::create('mlm_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum, Diamond
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('badge_image')->nullable();
            $table->integer('level')->unique(); // 1, 2, 3, 4, 5
            $table->decimal('required_sales', 15, 2); // Personal sales required
            $table->decimal('required_team_sales', 15, 2); // Team sales required
            $table->integer('required_direct_referrals')->default(0);
            $table->decimal('bonus_percentage', 5, 2)->default(0);
            $table->json('benefits')->nullable(); // Additional benefits
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User Ranks History
        Schema::create('user_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rank_id')->constrained('mlm_ranks')->onDelete('cascade');
            $table->timestamp('achieved_at');
            $table->boolean('is_current')->default(false);
            $table->decimal('sales_at_achievement', 15, 2);
            $table->decimal('team_sales_at_achievement', 15, 2);
            $table->timestamps();

            $table->index(['user_id', 'is_current']);
        });

        // MLM Genealogy (Complete downline tracking)
        Schema::create('mlm_genealogy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ancestor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('descendant_id')->constrained('users')->onDelete('cascade');
            $table->integer('depth'); // 1 = direct, 2 = level 2, etc.
            $table->timestamps();

            $table->unique(['ancestor_id', 'descendant_id']);
            $table->index(['ancestor_id', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mlm_genealogy');
        Schema::dropIfExists('user_ranks');
        Schema::dropIfExists('mlm_ranks');
        Schema::dropIfExists('mlm_networks');
    }
};
