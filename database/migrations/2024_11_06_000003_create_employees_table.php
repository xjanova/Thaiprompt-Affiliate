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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('employee_id')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('position_id')->constrained()->onDelete('restrict');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onDelete('set null');

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('first_name_th')->nullable();
            $table->string('last_name_th')->nullable();
            $table->string('nickname')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('passport_number')->nullable();

            // Contact Information
            $table->string('personal_email')->nullable();
            $table->string('work_email')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // Address
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();

            // Employment Details
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'freelance'])->default('full_time');
            $table->enum('employment_status', ['active', 'probation', 'notice_period', 'resigned', 'terminated', 'retired'])->default('active');

            // Compensation
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('salary_currency', 3)->default('THB');
            $table->enum('payment_frequency', ['monthly', 'biweekly', 'weekly'])->default('monthly');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Work Schedule
            $table->string('work_schedule')->nullable(); // monday-friday, shifts, etc
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();

            // Tax and Social Security
            $table->string('tax_id')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('provident_fund_number')->nullable();

            // Leave Balances
            $table->decimal('annual_leave_balance', 5, 2)->default(0);
            $table->decimal('sick_leave_balance', 5, 2)->default(0);
            $table->decimal('personal_leave_balance', 5, 2)->default(0);

            // Profile
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();

            // System
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'employment_status']);
            $table->index('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
