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
        Schema::create('vendor_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Employee Details
            $table->string('employee_code')->unique();
            $table->string('position')->nullable();
            $table->enum('employment_status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');

            // Permissions
            $table->boolean('can_manage_products')->default(false);
            $table->boolean('can_manage_orders')->default(false);
            $table->boolean('can_manage_inventory')->default(false);
            $table->boolean('can_view_sales_reports')->default(false);
            $table->boolean('can_manage_coupons')->default(false);
            $table->boolean('can_manage_reviews')->default(false);
            $table->boolean('can_use_pos')->default(false);
            $table->boolean('can_process_refunds')->default(false);
            $table->boolean('can_manage_shipping')->default(false);
            $table->boolean('can_manage_employees')->default(false);

            // POS Specific
            $table->boolean('can_open_pos_session')->default(false);
            $table->boolean('can_close_pos_session')->default(false);
            $table->boolean('can_void_transactions')->default(false);
            $table->boolean('can_apply_discounts')->default(false);
            $table->decimal('max_discount_percentage', 5, 2)->nullable();
            $table->decimal('max_transaction_amount', 10, 2)->nullable();

            // Restrictions
            $table->json('allowed_ip_addresses')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->json('allowed_days')->nullable(); // ['monday', 'tuesday', etc.]

            // Commission Split (if applicable)
            $table->decimal('commission_percentage', 5, 2)->default(0);

            // Additional Info
            $table->text('notes')->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('last_active_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_code');
            $table->index('employment_status');
            $table->index(['vendor_id', 'employment_status']);

            // Unique constraint: one user can be employee of a vendor only once
            $table->unique(['vendor_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_employees');
    }
};
