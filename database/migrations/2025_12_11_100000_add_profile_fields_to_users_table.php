<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fields สำหรับ profile ใน Mobile App
 * - bio: แนะนำตัว
 * - bank_name: ชื่อธนาคาร
 * - bank_account: เลขบัญชี
 * - bank_account_name: ชื่อบัญชี
 */
return new class extends Migration
{
    /**
     * เพิ่ม columns ใหม่ในตาราง users
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เพิ่ม bio หลัง address
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('address')->comment('แนะนำตัว/ประวัติย่อ');
            }

            // เพิ่มข้อมูลธนาคาร
            if (! Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('bio')->comment('ชื่อธนาคาร');
            }

            if (! Schema::hasColumn('users', 'bank_account')) {
                $table->string('bank_account', 50)->nullable()->after('bank_name')->comment('เลขบัญชีธนาคาร');
            }

            if (! Schema::hasColumn('users', 'bank_account_name')) {
                $table->string('bank_account_name', 255)->nullable()->after('bank_account')->comment('ชื่อบัญชีธนาคาร');
            }
        });
    }

    /**
     * ลบ columns ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['bio', 'bank_name', 'bank_account', 'bank_account_name'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
