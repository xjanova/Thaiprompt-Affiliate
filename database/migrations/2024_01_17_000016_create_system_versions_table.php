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
        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('current_version')->comment('Current installed version');
            $table->string('latest_version')->nullable()->comment('Latest available version from GitHub');
            $table->text('release_notes')->nullable();
            $table->string('download_url')->nullable();
            $table->boolean('update_available')->default(false);
            $table->boolean('is_critical')->default(false)->comment('Critical security update');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->string('github_repo')->default('xjanova/Thaiprompt-Affiliate');
            $table->json('changelog')->nullable();
            $table->json('breaking_changes')->nullable();
            $table->timestamps();
        });

        // System update logs
        Schema::create('system_update_logs', function (Blueprint $table) {
            $table->id();
            $table->string('version_from');
            $table->string('version_to');
            $table->string('update_status')->comment('started, in_progress, completed, failed, rolled_back');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('update_steps')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Insert initial version
        DB::table('system_versions')->insert([
            'current_version' => '1.0.0',
            'latest_version' => '1.0.0',
            'update_available' => false,
            'last_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_update_logs');
        Schema::dropIfExists('system_versions');
    }
};
