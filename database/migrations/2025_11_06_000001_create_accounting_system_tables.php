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
        // Settings table for accounting module
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('currency', 3)->default('THB');
            $table->string('date_format')->default('d/m/Y');
            $table->string('timezone')->default('Asia/Bangkok');
            $table->string('fiscal_year_start')->default('01-01'); // MM-DD format
            $table->boolean('auto_sync_flowaccount')->default(false);
            $table->json('tax_settings')->nullable();
            $table->json('invoice_settings')->nullable();
            $table->json('expense_settings')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Companies/Organizations
        Schema::create('accounting_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_eng')->nullable();
            $table->string('tax_id')->nullable()->unique();
            $table->string('branch_code')->default('00000');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Thailand');
            $table->string('logo')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        // Chart of Accounts - ผังบัญชี
        Schema::create('accounting_chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // e.g., 1100, 2100
            $table->string('name');
            $table->string('name_eng')->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('sub_type', [
                'current_asset', 'fixed_asset', 'other_asset',
                'current_liability', 'long_term_liability',
                'owner_equity',
                'revenue', 'other_revenue',
                'cost_of_goods_sold', 'operating_expense', 'other_expense'
            ]);
            $table->foreignId('parent_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->integer('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'code']);
        });

        // Contacts - ลูกค้าและผู้จำหน่าย
        Schema::create('accounting_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable()->unique();
            $table->enum('type', ['customer', 'vendor', 'both'])->default('customer');
            $table->string('name');
            $table->string('name_eng')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('branch_code')->default('00000');
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Thailand');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('credit_days')->default(0);
            $table->enum('payment_terms', ['cash', 'credit', 'cod'])->default('cash');
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('flowaccount_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        // Products/Services
        Schema::create('accounting_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->string('name');
            $table->string('name_eng')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->default('unit');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->foreignId('income_account_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(7.00);
            $table->boolean('is_active')->default(true);
            $table->string('flowaccount_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        // Tax Rates
        Schema::create('accounting_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
        });

        // Bank Accounts
        Schema::create('accounting_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('branch')->nullable();
            $table->enum('account_type', ['savings', 'current', 'fixed'])->default('savings');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->foreignId('chart_account_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
        });

        // Invoices - ใบแจ้งหนี้/ใบกำกับภาษี
        Schema::create('accounting_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('accounting_companies')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('accounting_contacts')->cascadeOnDelete();
            $table->string('document_type')->default('invoice'); // invoice, tax_invoice, receipt, quotation
            $table->string('document_number')->unique();
            $table->string('reference_number')->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->text('terms')->nullable();
            $table->string('flowaccount_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'document_date']);
        });

        // Invoice Items
        Schema::create('accounting_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('accounting_products')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });

        // Expenses - รายจ่าย
        Schema::create('accounting_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('accounting_companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('accounting_contacts')->nullOnDelete();
            $table->string('document_type')->default('expense'); // expense, purchase, bill
            $table->string('document_number')->unique();
            $table->string('reference_number')->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'cancelled'])->default('draft');
            $table->foreignId('category_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->string('receipt_image')->nullable();
            $table->string('flowaccount_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'document_date']);
        });

        // Expense Items
        Schema::create('accounting_expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('accounting_expenses')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('accounting_products')->nullOnDelete();
            $table->foreignId('chart_account_id')->nullable()->constrained('accounting_chart_of_accounts')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->default('unit');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('expense_id');
        });

        // Payments - รับ/จ่ายเงิน
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('payable'); // invoice_id or expense_id
            $table->enum('payment_type', ['income', 'expense'])->default('income');
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'cheque', 'other'])->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained('accounting_bank_accounts')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->string('receipt_image')->nullable();
            $table->string('flowaccount_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'payment_type']);
            $table->index('payment_date');
        });

        // Journal Entries - บันทึกรายการบัญชี
        Schema::create('accounting_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('journal_number')->unique();
            $table->date('journal_date');
            $table->morphs('journalable'); // invoice, expense, payment, etc.
            $table->foreignId('chart_account_id')->constrained('accounting_chart_of_accounts')->cascadeOnDelete();
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'journal_date']);
            $table->index(['chart_account_id', 'type']);
        });

        // FlowAccount Connections
        Schema::create('accounting_flowaccount_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('flowaccount_company_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_sync')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_settings')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Export Templates
        Schema::create('accounting_export_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('document_type', ['invoice', 'expense', 'receipt', 'quotation', 'statement']);
            $table->enum('format', ['pdf', 'word', 'excel']);
            $table->text('description')->nullable();
            $table->json('template_data'); // Layout, styling, fields
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'document_type']);
        });

        // Activity Logs
        Schema::create('accounting_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('loggable');
            $table->string('action'); // created, updated, deleted, synced
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_activity_logs');
        Schema::dropIfExists('accounting_export_templates');
        Schema::dropIfExists('accounting_flowaccount_connections');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('accounting_payments');
        Schema::dropIfExists('accounting_expense_items');
        Schema::dropIfExists('accounting_expenses');
        Schema::dropIfExists('accounting_invoice_items');
        Schema::dropIfExists('accounting_invoices');
        Schema::dropIfExists('accounting_bank_accounts');
        Schema::dropIfExists('accounting_tax_rates');
        Schema::dropIfExists('accounting_products');
        Schema::dropIfExists('accounting_contacts');
        Schema::dropIfExists('accounting_chart_of_accounts');
        Schema::dropIfExists('accounting_companies');
        Schema::dropIfExists('accounting_settings');
    }
};
