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
        Schema::create('admin_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Employee Details
            $table->string('employee_code')->unique();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->enum('employment_status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');

            // Access Control
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_manage_vendors')->default(false);
            $table->boolean('can_manage_products')->default(false);
            $table->boolean('can_manage_orders')->default(false);
            $table->boolean('can_manage_commissions')->default(false);
            $table->boolean('can_manage_withdrawals')->default(false);
            $table->boolean('can_manage_settings')->default(false);
            $table->boolean('can_manage_security')->default(false);
            $table->boolean('can_manage_employees')->default(false);
            $table->boolean('can_view_reports')->default(false);
            $table->boolean('can_view_analytics')->default(false);
            $table->boolean('can_manage_content')->default(false);
            $table->boolean('can_manage_coupons')->default(false);
            $table->boolean('can_manage_categories')->default(false);

            // Restrictions
            $table->json('allowed_ip_addresses')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->json('allowed_days')->nullable(); // ['monday', 'tuesday', etc.]

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_employees');
    }
};
