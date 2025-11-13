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
        Schema::create('qr_barcode_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_barcode_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('scanned_at');
            $table->json('metadata')->nullable(); // Additional tracking data

            // Indexes
            $table->index('qr_barcode_id');
            $table->index('scanned_at');
            $table->index('country');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_barcode_scans');
    }
};
