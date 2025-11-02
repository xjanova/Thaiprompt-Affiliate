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
        Schema::create('threat_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index(); // IPv4 or IPv6
            $table->enum('threat_type', ['proxy', 'vpn', 'tor', 'spam', 'abuse', 'malware', 'scanner', 'other'])->index();
            $table->string('source', 50); // firehol, abuseipdb, ipqualityscore
            $table->integer('confidence_score')->default(0); // 0-100
            $table->text('details')->nullable(); // JSON details
            $table->timestamp('last_seen')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Composite index for fast lookups
            $table->index(['ip_address', 'threat_type']);
            $table->index(['source', 'last_seen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threat_ips');
    }
};
