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
        Schema::table('security_logs', function (Blueprint $table) {
            // IP Intelligence
            $table->string('country_code', 2)->nullable()->after('ip_address'); // US, TH, CN
            $table->string('country_name', 100)->nullable()->after('country_code'); // United States, Thailand
            $table->string('city', 100)->nullable()->after('country_name');
            $table->decimal('latitude', 10, 8)->nullable()->after('city');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Device Intelligence
            $table->string('os', 50)->nullable()->after('user_agent'); // Windows, macOS, Linux, Android, iOS
            $table->string('os_version', 50)->nullable()->after('os');
            $table->string('browser', 50)->nullable()->after('os_version'); // Chrome, Firefox, Safari, Edge
            $table->string('browser_version', 50)->nullable()->after('browser');
            $table->string('device_type', 20)->nullable()->after('browser_version'); // desktop, mobile, tablet, bot

            // Additional metadata
            $table->boolean('is_vpn')->default(false)->after('device_type');
            $table->boolean('is_proxy')->default(false)->after('is_vpn');
            $table->boolean('is_tor')->default(false)->after('is_proxy');

            // Indexes for analytics
            $table->index('country_code');
            $table->index('os');
            $table->index('browser');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropIndex(['os']);
            $table->dropIndex(['browser']);
            $table->dropIndex(['device_type']);

            $table->dropColumn([
                'country_code',
                'country_name',
                'city',
                'latitude',
                'longitude',
                'os',
                'os_version',
                'browser',
                'browser_version',
                'device_type',
                'is_vpn',
                'is_proxy',
                'is_tor',
            ]);
        });
    }
};
