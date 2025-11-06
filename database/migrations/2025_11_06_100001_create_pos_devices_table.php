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
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('vendor_stores')->nullOnDelete();
            $table->string('device_name');
            $table->string('device_code')->unique(); // รหัสเครื่อง POS
            $table->string('device_type')->default('standard'); // standard, premium
            $table->string('license_key')->unique();
            $table->string('hardware_id')->nullable(); // ผูกกับเครื่อง

            // Subscription/Rental
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'suspended'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(true);

            // Device Configuration
            $table->json('features')->nullable(); // enabled features
            $table->json('settings')->nullable(); // device settings
            $table->boolean('dual_screen_enabled')->default(true);
            $table->boolean('offline_mode_enabled')->default(true);
            $table->boolean('receipt_printer_enabled')->default(true);

            // Connection & Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_online_at')->nullable();
            $table->string('last_ip_address')->nullable();
            $table->text('device_info')->nullable(); // browser, OS info

            // Statistics
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('store_id');
            $table->index('device_code');
            $table->index('subscription_status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_devices');
    }
};
