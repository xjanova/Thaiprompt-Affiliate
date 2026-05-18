<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🌥️ (2026-05-18) Cloud Storage Support สำหรับ Voice Summary
 *
 * เพิ่มฟิลด์เก็บการตั้งค่า cloud storage:
 *   - voice_storage_driver  : local / r2 / s3 / gcs / firebase
 *   - voice_storage_config  : JSON เก็บ credentials + bucket + public URL ของแต่ละ driver
 *
 * เพิ่มในตาราง fortune_readings:
 *   - voice_audio_disk : ชื่อ driver ที่ใช้เซฟ audio นี้ (สำหรับ migration tool)
 *
 * Reason: ลูกค้าจ่าย Celtic 99฿ เพิ่มขึ้น → ไฟล์ mp3 บน local disk เต็มเซิร์ฟเวอร์เร็ว
 *         ย้ายไป cloud (R2 / Firebase Storage / GCS / S3) เพื่อแก้ปัญหานี้
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        // 1. fortune_telling_settings — เพิ่ม voice_storage_driver + voice_storage_config
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_telling_settings', 'voice_storage_driver')) {
                    $table->string('voice_storage_driver', 32)
                        ->default('local')
                        ->comment('Driver ที่ใช้เก็บไฟล์เสียง: local / r2 / s3 / gcs / firebase');
                }

                if (! Schema::hasColumn('fortune_telling_settings', 'voice_storage_config')) {
                    $table->json('voice_storage_config')
                        ->nullable()
                        ->comment('JSON: per-driver config (credentials + bucket + public URL)');
                }
            });
        }

        // 2. fortune_readings — เพิ่ม voice_audio_disk + voice_audio_url
        if (Schema::hasTable('fortune_readings')) {
            Schema::table('fortune_readings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_readings', 'voice_audio_disk')) {
                    $table->string('voice_audio_disk', 32)
                        ->nullable()
                        ->after('voice_audio_path')
                        ->comment('Driver ที่ใช้เซฟ audio นี้ (สำหรับ migration tool)');
                }

                // 🌥️ voice_audio_url = full URL ที่อ่านได้
                //    เก็บไว้แทนการ reconstruct จาก path เพราะ Firebase ต้องมี token ใน URL
                //    R2/S3: URL = public_url + path (เปลี่ยน CDN ได้)
                //    Firebase: URL = firebasestorage.googleapis.com/...?token=xxx
                //    GCS: URL = storage.googleapis.com/bucket/path
                if (! Schema::hasColumn('fortune_readings', 'voice_audio_url')) {
                    $table->text('voice_audio_url')
                        ->nullable()
                        ->after('voice_audio_disk')
                        ->comment('Full URL ของ audio (โดยเฉพาะ Firebase ที่ต้องมี token ใน URL)');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            $this->safeDropColumn('fortune_telling_settings', [
                'voice_storage_driver',
                'voice_storage_config',
            ]);
        }

        if (Schema::hasTable('fortune_readings')) {
            $this->safeDropColumn('fortune_readings', ['voice_audio_disk', 'voice_audio_url']);
        }
    }
};
