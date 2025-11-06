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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('payment_date')->nullable();

            // Salary Components
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);

            // Deductions
            $table->decimal('tax_deduction', 12, 2)->default(0);
            $table->decimal('social_security', 12, 2)->default(0);
            $table->decimal('provident_fund', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Net Pay
            $table->decimal('net_salary', 12, 2)->default(0);

            // Working Days & Hours
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);

            // Payment Details
            $table->string('payment_method')->nullable(); // bank_transfer, cash, cheque
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('payment_reference')->nullable();

            // Status
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();

            $table->text('remarks')->nullable();
            $table->json('payslip_data')->nullable(); // store detailed breakdown

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['month', 'year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
