<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม Foreign Key Constraints สำหรับตาราง SMS Payment
 *
 * ⚠️ ใช้ Schema::table() + hasColumn() เพราะเป็น ALTER TABLE
 * ห้ามใช้ hasTable() + return
 */
return new class extends Migration
{
    /**
     * เพิ่ม Foreign Key Constraints
     *
     * @return void
     */
    public function up(): void
    {
        // FK: sms_checker_devices.user_id → users.id
        if (Schema::hasTable('sms_checker_devices') && Schema::hasTable('users')) {
            Schema::table('sms_checker_devices', function (Blueprint $table) {
                // เช็คว่ามี FK อยู่แล้วหรือยัง โดยลอง index name
                $existingIndexes = collect(Schema::getIndexes('sms_checker_devices'))->pluck('name')->toArray();

                if (!in_array('sms_devices_user_fk', $existingIndexes)) {
                    $table->foreign('user_id', 'sms_devices_user_fk')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
            });
        }

        // FK: sms_payment_notifications.matched_transaction_id → payment_transactions.id
        if (Schema::hasTable('sms_payment_notifications') && Schema::hasTable('payment_transactions')) {
            Schema::table('sms_payment_notifications', function (Blueprint $table) {
                $existingIndexes = collect(Schema::getIndexes('sms_payment_notifications'))->pluck('name')->toArray();

                if (!in_array('sms_notif_txn_fk', $existingIndexes)) {
                    $table->foreign('matched_transaction_id', 'sms_notif_txn_fk')
                        ->references('id')
                        ->on('payment_transactions')
                        ->onDelete('set null');
                }
            });
        }

        // FK: unique_payment_amounts.transaction_id → payment_transactions.id
        if (Schema::hasTable('unique_payment_amounts') && Schema::hasTable('payment_transactions')) {
            Schema::table('unique_payment_amounts', function (Blueprint $table) {
                $existingIndexes = collect(Schema::getIndexes('unique_payment_amounts'))->pluck('name')->toArray();

                if (!in_array('unique_amt_txn_fk', $existingIndexes)) {
                    $table->foreign('transaction_id', 'unique_amt_txn_fk')
                        ->references('id')
                        ->on('payment_transactions')
                        ->onDelete('set null');
                }
            });
        }

        // FK: sms_payment_nonces.device_id → sms_checker_devices.device_id
        if (Schema::hasTable('sms_payment_nonces') && Schema::hasTable('sms_checker_devices')) {
            Schema::table('sms_payment_nonces', function (Blueprint $table) {
                $existingIndexes = collect(Schema::getIndexes('sms_payment_nonces'))->pluck('name')->toArray();

                if (!in_array('sms_nonce_device_fk', $existingIndexes)) {
                    $table->foreign('device_id', 'sms_nonce_device_fk')
                        ->references('device_id')
                        ->on('sms_checker_devices')
                        ->onDelete('cascade');
                }
            });
        }
    }

    /**
     * ลบ Foreign Key Constraints
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('sms_payment_nonces')) {
            Schema::table('sms_payment_nonces', function (Blueprint $table) {
                $table->dropForeign('sms_nonce_device_fk');
            });
        }

        if (Schema::hasTable('unique_payment_amounts')) {
            Schema::table('unique_payment_amounts', function (Blueprint $table) {
                $table->dropForeign('unique_amt_txn_fk');
            });
        }

        if (Schema::hasTable('sms_payment_notifications')) {
            Schema::table('sms_payment_notifications', function (Blueprint $table) {
                $table->dropForeign('sms_notif_txn_fk');
            });
        }

        if (Schema::hasTable('sms_checker_devices')) {
            Schema::table('sms_checker_devices', function (Blueprint $table) {
                $table->dropForeign('sms_devices_user_fk');
            });
        }
    }
};
