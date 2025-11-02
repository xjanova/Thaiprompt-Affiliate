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
        Schema::table('blocked_ips', function (Blueprint $table) {
            // Add IP type field to distinguish between single IP, range, and CIDR
            $table->enum('ip_type', ['single', 'range', 'cidr'])->default('single')->after('ip_address');

            // For IP ranges: store start and end IP
            $table->string('ip_range_start', 45)->nullable()->after('ip_type');
            $table->string('ip_range_end', 45)->nullable()->after('ip_range_start');

            // For CIDR: store CIDR notation
            $table->string('ip_cidr', 50)->nullable()->after('ip_range_end');

            // Add index for better performance
            $table->index('ip_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->dropIndex(['ip_type']);
            $table->dropColumn(['ip_type', 'ip_range_start', 'ip_range_end', 'ip_cidr']);
        });
    }
};
