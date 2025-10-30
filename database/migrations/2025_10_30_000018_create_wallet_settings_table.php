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
        Schema::create('wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting key (e.g., 'withdrawal_fee_percentage')
            $table->string('group')->default('general'); // Group settings: 'fees', 'tax', 'limits', 'general'
            $table->text('value')->nullable(); // Setting value (can be JSON for complex values)
            $table->string('type')->default('string'); // 'string', 'number', 'boolean', 'json', 'percentage'
            $table->string('label'); // Display label
            $table->text('description')->nullable(); // Setting description
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // Can users see this setting?
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('key');
            $table->index('group');
            $table->index(['group', 'is_active']);
        });

        // Insert default wallet settings
        DB::table('wallet_settings')->insert([
            // Withdrawal settings
            [
                'key' => 'withdrawal_min_amount',
                'group' => 'limits',
                'value' => '100',
                'type' => 'number',
                'label' => 'ยอดถอนขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถถอนได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_max_amount',
                'group' => 'limits',
                'value' => '100000',
                'type' => 'number',
                'label' => 'ยอดถอนสูงสุด',
                'description' => 'จำนวนเงินสูงสุดที่สามารถถอนได้ต่อครั้ง (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_fee_type',
                'group' => 'fees',
                'value' => 'percentage',
                'type' => 'string',
                'label' => 'ประเภทค่าธรรมเนียมการถอน',
                'description' => 'fixed = คงที่, percentage = เปอร์เซ็นต์',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_fee_amount',
                'group' => 'fees',
                'value' => '2.5',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมการถอน',
                'description' => 'หากเป็น percentage ให้ระบุเป็น % (เช่น 2.5 = 2.5%), หากเป็น fixed ให้ระบุเป็นบาท',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_fee_min',
                'group' => 'fees',
                'value' => '10',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมขั้นต่ำ',
                'description' => 'ค่าธรรมเนียมขั้นต่ำต่อการถอน (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_fee_max',
                'group' => 'fees',
                'value' => '500',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมสูงสุด',
                'description' => 'ค่าธรรมเนียมสูงสุดต่อการถอน (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Tax settings
            [
                'key' => 'tax_enabled',
                'group' => 'tax',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานการหักภาษี',
                'description' => 'หักภาษี ณ ที่จ่ายจากการถอนเงิน',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'tax_percentage',
                'group' => 'tax',
                'value' => '3',
                'type' => 'percentage',
                'label' => 'เปอร์เซ็นต์ภาษี',
                'description' => 'เปอร์เซ็นต์ภาษีหัก ณ ที่จ่าย (%)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'tax_threshold',
                'group' => 'tax',
                'value' => '1000',
                'type' => 'number',
                'label' => 'ยอดเงินขั้นต่ำที่ต้องเสียภาษี',
                'description' => 'ถอนเงินมากกว่ายอดนี้จึงจะถูกหักภาษี (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Transfer settings
            [
                'key' => 'transfer_fee_amount',
                'group' => 'fees',
                'value' => '5',
                'type' => 'number',
                'label' => 'ค่าธรรมเนียมการโอน',
                'description' => 'ค่าธรรมเนียมการโอนเงินระหว่างกระเป๋า (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'transfer_min_amount',
                'group' => 'limits',
                'value' => '10',
                'type' => 'number',
                'label' => 'ยอดโอนขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถโอนได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Deposit settings
            [
                'key' => 'deposit_min_amount',
                'group' => 'limits',
                'value' => '1',
                'type' => 'number',
                'label' => 'ยอดฝากขั้นต่ำ',
                'description' => 'จำนวนเงินขั้นต่ำที่สามารถฝากได้ (บาท)',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Payment gateway settings
            [
                'key' => 'promptpay_enabled',
                'group' => 'payment_methods',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน PromptPay',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ PromptPay',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'stripe_enabled',
                'group' => 'payment_methods',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน Stripe',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ Stripe',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'paypal_enabled',
                'group' => 'payment_methods',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'เปิดใช้งาน PayPal',
                'description' => 'อนุญาตให้ผู้ใช้ใช้ PayPal',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Withdrawal approval
            [
                'key' => 'withdrawal_requires_approval',
                'group' => 'general',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'การถอนเงินต้องได้รับการอนุมัติ',
                'description' => 'ต้องรอแอดมินอนุมัติก่อนจึงจะถอนเงินได้',
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 16,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'auto_approve_threshold',
                'group' => 'general',
                'value' => '0',
                'type' => 'number',
                'label' => 'ยอดเงินที่อนุมัติอัตโนมัติ',
                'description' => 'ยอดถอนที่น้อยกว่านี้จะได้รับการอนุมัติอัตโนมัติ (0 = ปิดใช้งาน)',
                'is_active' => true,
                'is_public' => false,
                'sort_order' => 17,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_settings');
    }
};
