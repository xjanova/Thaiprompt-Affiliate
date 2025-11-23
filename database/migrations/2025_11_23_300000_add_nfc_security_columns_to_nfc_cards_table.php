<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ NFC Security ในตาราง nfc_cards
     *
     * คอลัมน์ที่เพิ่ม:
     * - nfc_uid: Serial Number (UID) ของบัตร NFC
     * - anti_counterfeit_code: รหัสป้องกันปลอม (SHA-256)
     * - nfc_signature: Digital signature (HMAC-SHA256)
     * - nfc_written_at: วันเวลาที่เขียนข้อมูลลงบัตร
     * - nfc_written_by: ผู้เขียนข้อมูลลงบัตร
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('nfc_cards', function (Blueprint $table) {
            // NFC UID (Serial Number) - Unique identifier ของบัตร
            $this->safeAddColumn($table, 'nfc_cards', 'nfc_uid', function ($table) {
                $table->string('nfc_uid', 100)->nullable()->after('card_number');
            });

            // Anti-Counterfeit Code - รหัสป้องกันปลอม (SHA-256 Hash)
            $this->safeAddColumn($table, 'nfc_cards', 'anti_counterfeit_code', function ($table) {
                $table->string('anti_counterfeit_code', 64)->nullable()->after('nfc_uid');
            });

            // NFC Signature - Digital signature (HMAC-SHA256)
            $this->safeAddColumn($table, 'nfc_cards', 'nfc_signature', function ($table) {
                $table->string('nfc_signature', 64)->nullable()->after('anti_counterfeit_code');
            });

            // NFC Written At - วันเวลาที่เขียนข้อมูลลงบัตร
            $this->safeAddColumn($table, 'nfc_cards', 'nfc_written_at', function ($table) {
                $table->timestamp('nfc_written_at')->nullable()->after('nfc_signature');
            });

            // NFC Written By - ผู้เขียนข้อมูลลงบัตร
            $this->safeAddColumn($table, 'nfc_cards', 'nfc_written_by', function ($table) {
                $table->unsignedBigInteger('nfc_written_by')->nullable()->after('nfc_written_at');
            });
        });

        // เพิ่ม index สำหรับการค้นหา
        $this->safeAddIndex('nfc_cards', 'nfc_uid');
        $this->safeAddIndex('nfc_cards', 'anti_counterfeit_code');

        // เพิ่ม foreign key สำหรับ nfc_written_by
        if (Schema::hasColumn('nfc_cards', 'nfc_written_by')) {
            Schema::table('nfc_cards', function (Blueprint $table) {
                if (!$this->foreignKeyExists('nfc_cards', 'nfc_cards_nfc_written_by_foreign')) {
                    $table->foreign('nfc_written_by', 'nfc_cards_nfc_written_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('nfc_cards', function (Blueprint $table) {
            // ลบ foreign key ก่อน
            if ($this->foreignKeyExists('nfc_cards', 'nfc_cards_nfc_written_by_foreign')) {
                $table->dropForeign('nfc_cards_nfc_written_by_foreign');
            }
        });

        // ลบ indexes
        $this->safeDropIndex('nfc_cards', 'nfc_cards_nfc_uid_index');
        $this->safeDropIndex('nfc_cards', 'nfc_cards_anti_counterfeit_code_index');

        // ลบคอลัมน์
        $this->safeDropColumn('nfc_cards', [
            'nfc_uid',
            'anti_counterfeit_code',
            'nfc_signature',
            'nfc_written_at',
            'nfc_written_by',
        ]);
    }

    /**
     * ตรวจสอบว่า foreign key มีอยู่หรือไม่
     *
     * @param string $table
     * @param string $foreignKey
     * @return bool
     */
    protected function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $result = $conn->select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $foreignKey]
        );

        return count($result) > 0;
    }
};
