<?php

/**
 * Safe Migration Example Template
 *
 * คัดลอกไฟล์นี้และปรับแต่งตามต้องการเมื่อสร้าง migration ใหม่
 * ไฟล์นี้เป็นเพียงตัวอย่าง - ไม่ได้ถูก run จริง
 */

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Example 1: เพิ่ม columns แบบทีละตัว
        Schema::table('your_table_name', function (Blueprint $table) {
            $this->safeAddColumn($table, 'your_table_name', 'column_name', function($table) {
                $table->string('column_name')->nullable()->after('existing_column');
            });
        });

        // Example 2: เพิ่มหลาย columns พร้อมกัน
        Schema::table('your_table_name', function (Blueprint $table) {
            $this->safeAddColumns($table, 'your_table_name', [
                'first_name' => fn($t) => $t->string('first_name')->nullable(),
                'last_name' => fn($t) => $t->string('last_name')->nullable(),
                'age' => fn($t) => $t->integer('age')->default(0),
            ]);
        });

        // Example 3: เพิ่ม index
        $this->safeAddIndex('your_table_name', 'column_name');
        $this->safeAddIndex('your_table_name', 'email', null, 'unique');
        $this->safeAddIndex('your_table_name', ['col1', 'col2']); // composite

        // Example 4: เพิ่ม foreign key
        $this->safeAddForeign('your_table_name', 'user_id', 'users');

        // Example 5: สร้างตารางใหม่
        $this->safeCreateTable('new_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลำดับการลบ:
        // 1. Foreign keys ก่อน
        $this->safeDropForeign('your_table_name', 'your_table_name_user_id_foreign');

        // 2. Indexes
        $this->safeDropIndex('your_table_name', 'your_table_name_column_name_index');
        $this->safeDropIndex('your_table_name', 'your_table_name_email_unique');

        // 3. Columns
        $this->safeDropColumn('your_table_name', [
            'first_name',
            'last_name',
            'age',
            'column_name',
        ]);

        // 4. Tables (ถ้ามี)
        $this->safeDropTable('new_table');
    }
};
