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
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique(); // IPv4 or IPv6
            $table->enum('type', ['blacklist', 'whitelist'])->default('blacklist');
            $table->string('reason')->nullable();
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // NULL = permanent
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('ip_address');
            $table->index('type');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
