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
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method'); // HTTP method
            $table->string('path'); // Request path
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            $table->string('user_agent')->nullable();
            $table->integer('response_code'); // HTTP response code (200, 404, 500, etc.)
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->text('request_payload')->nullable(); // Request data (จำกัดขนาด)
            $table->text('response_payload')->nullable(); // Response data (จำกัดขนาด)
            $table->text('error_message')->nullable(); // Error message ถ้ามี
            $table->timestamps();

            // Indexes
            $table->index('api_key_id');
            $table->index('api_endpoint_id');
            $table->index('response_code');
            $table->index('created_at');
            $table->index(['api_key_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
