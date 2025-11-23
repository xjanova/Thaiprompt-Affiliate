<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟิลด์สำหรับระบบล็อคบัตรและข้อมูลบัตร
     *
     * Fields:
     * - is_locked: สถานะการล็อคบัตร (ป้องกันการเขียนทับ)
     * - locked_at: เวลาที่ล็อคบัตร
     * - locked_by: ผู้ที่ล็อคบัตร (admin)
     * - unlocked_at: เวลาที่ปลดล็อคบัตร
     * - unlocked_by: ผู้ที่ปลดล็อคบัตร
     * - card_type_detected: ชนิดของบัตร (NTAG213, NTAG215, NTAG216, Mifare Classic, etc.)
     * - memory_size: ขนาด memory ของบัตร (bytes)
     * - ndef_writeable: บัตรสามารถเขียน NDEF ได้หรือไม่
     * - ndef_records_count: จำนวน NDEF records ที่เขียนไว้
     * - last_read_at: เวลาที่อ่านบัตรครั้งล่าสุด
     * - read_count: จำนวนครั้งที่อ่านบัตร
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('nfc_cards', function (Blueprint $table) {
            // ระบบล็อคบัตร
            $this->safeAddColumn($table, 'nfc_cards', 'is_locked', function ($table) {
                $table->boolean('is_locked')->default(false)->after('status');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'locked_at', function ($table) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'locked_by', function ($table) {
                $table->unsignedBigInteger('locked_by')->nullable()->after('locked_at');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'unlocked_at', function ($table) {
                $table->timestamp('unlocked_at')->nullable()->after('locked_by');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'unlocked_by', function ($table) {
                $table->unsignedBigInteger('unlocked_by')->nullable()->after('unlocked_at');
            });

            // ข้อมูลบัตร NFC
            $this->safeAddColumn($table, 'nfc_cards', 'card_type_detected', function ($table) {
                $table->string('card_type_detected', 50)->nullable()->after('card_type')
                    ->comment('ชนิดบัตรที่ตรวจพบ: NTAG213, NTAG215, NTAG216, Mifare Classic, etc.');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'memory_size', function ($table) {
                $table->integer('memory_size')->nullable()->after('card_type_detected')
                    ->comment('ขนาด memory ของบัตร (bytes)');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'ndef_writeable', function ($table) {
                $table->boolean('ndef_writeable')->default(true)->after('memory_size')
                    ->comment('บัตรสามารถเขียน NDEF ได้หรือไม่');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'ndef_records_count', function ($table) {
                $table->integer('ndef_records_count')->default(0)->after('ndef_writeable')
                    ->comment('จำนวน NDEF records ที่เขียนไว้');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'ndef_records_data', function ($table) {
                $table->json('ndef_records_data')->nullable()->after('ndef_records_count')
                    ->comment('ข้อมูล NDEF records ที่เขียนไว้');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'last_read_at', function ($table) {
                $table->timestamp('last_read_at')->nullable()->after('ndef_records_data')
                    ->comment('เวลาที่อ่านบัตรครั้งล่าสุด');
            });

            $this->safeAddColumn($table, 'nfc_cards', 'read_count', function ($table) {
                $table->integer('read_count')->default(0)->after('last_read_at')
                    ->comment('จำนวนครั้งที่อ่านบัตร');
            });
        });

        // เพิ่ม indexes สำหรับค้นหา
        $this->safeAddIndex('nfc_cards', 'is_locked');
        $this->safeAddIndex('nfc_cards', 'card_type_detected');
        $this->safeAddIndex('nfc_cards', 'locked_at');

        // เพิ่ม foreign keys
        if (!$this->hasForeignKey('nfc_cards', 'nfc_cards_locked_by_foreign')) {
            Schema::table('nfc_cards', function (Blueprint $table) {
                $table->foreign('locked_by', 'nfc_cards_locked_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }

        if (!$this->hasForeignKey('nfc_cards', 'nfc_cards_unlocked_by_foreign')) {
            Schema::table('nfc_cards', function (Blueprint $table) {
                $table->foreign('unlocked_by', 'nfc_cards_unlocked_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ foreign keys
        if ($this->hasForeignKey('nfc_cards', 'nfc_cards_locked_by_foreign')) {
            Schema::table('nfc_cards', function (Blueprint $table) {
                $table->dropForeign('nfc_cards_locked_by_foreign');
            });
        }

        if ($this->hasForeignKey('nfc_cards', 'nfc_cards_unlocked_by_foreign')) {
            Schema::table('nfc_cards', function (Blueprint $table) {
                $table->dropForeign('nfc_cards_unlocked_by_foreign');
            });
        }

        // ลบ indexes
        $this->safeDropIndex('nfc_cards', 'nfc_cards_is_locked_index');
        $this->safeDropIndex('nfc_cards', 'nfc_cards_card_type_detected_index');
        $this->safeDropIndex('nfc_cards', 'nfc_cards_locked_at_index');

        // ลบ columns
        $this->safeDropColumn('nfc_cards', [
            'is_locked',
            'locked_at',
            'locked_by',
            'unlocked_at',
            'unlocked_by',
            'card_type_detected',
            'memory_size',
            'ndef_writeable',
            'ndef_records_count',
            'ndef_records_data',
            'last_read_at',
            'read_count',
        ]);
    }

    /**
     * ตรวจสอบว่ามี foreign key หรือไม่
     *
     * @param string $table
     * @param string $foreignKey
     * @return bool
     */
    protected function hasForeignKey(string $table, string $foreignKey): bool
    {
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        foreach ($foreignKeys as $key) {
            if ($key->getName() === $foreignKey) {
                return true;
            }
        }

        return false;
    }
};
