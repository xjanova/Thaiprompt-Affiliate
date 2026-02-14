<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลบคอลัมน์ shipping address ออกจากตาราง users
     *
     * ที่อยู่จัดส่งถูกย้ายไปจัดการในตาราง shipping_addresses แทน
     * เพื่อไม่ให้ข้อมูลซ้ำซ้อน และประหยัดพื้นที่ในฐานข้อมูล
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'shipping_address',
                'shipping_city',
                'shipping_state',
                'shipping_postal_code',
                'shipping_country',
                'shipping_phone',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * คืนค่าคอลัมน์ shipping address กลับมา
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->comment('ที่อยู่จัดส่ง');
            }
            if (! Schema::hasColumn('users', 'shipping_city')) {
                $table->string('shipping_city', 100)->nullable()->comment('เมือง/อำเภอ (จัดส่ง)');
            }
            if (! Schema::hasColumn('users', 'shipping_state')) {
                $table->string('shipping_state', 100)->nullable()->comment('จังหวัด (จัดส่ง)');
            }
            if (! Schema::hasColumn('users', 'shipping_postal_code')) {
                $table->string('shipping_postal_code', 20)->nullable()->comment('รหัสไปรษณีย์ (จัดส่ง)');
            }
            if (! Schema::hasColumn('users', 'shipping_country')) {
                $table->string('shipping_country', 10)->nullable()->comment('ประเทศ (จัดส่ง)');
            }
            if (! Schema::hasColumn('users', 'shipping_phone')) {
                $table->string('shipping_phone', 20)->nullable()->comment('เบอร์โทร (จัดส่ง)');
            }
        });
    }
};
