<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มฟิลด์ File Storage สำหรับ Multi-Cloud Upload
     *
     * รองรับ 4 วิธีการอัพโหลด:
     * 1. URL Link (เช่น Google Drive Share Link, Dropbox Link)
     * 2. AWS S3 (Object Storage)
     * 3. Google Drive API (Direct Upload)
     * 4. Local Server (≤10MB, .zip only)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('software_products', function (Blueprint $table) {
            // Storage Type (url, s3, google_drive, local)
            $this->safeAddColumn($table, 'software_products', 'storage_type', function($table) {
                $table->enum('storage_type', ['url', 's3', 'google_drive', 'local'])
                    ->default('local')
                    ->after('is_customizable')
                    ->comment('ประเภทการจัดเก็บไฟล์: url, s3, google_drive, local');
            });

            // URL Link (for URL type)
            $this->safeAddColumn($table, 'software_products', 'file_url', function($table) {
                $table->text('file_url')
                    ->nullable()
                    ->after('storage_type')
                    ->comment('URL สำหรับดาวน์โหลด (Google Drive, Dropbox, etc.)');
            });

            // S3 Path (for S3 type)
            $this->safeAddColumn($table, 'software_products', 's3_bucket', function($table) {
                $table->string('s3_bucket')
                    ->nullable()
                    ->after('file_url')
                    ->comment('AWS S3 Bucket Name');
            });

            $this->safeAddColumn($table, 'software_products', 's3_key', function($table) {
                $table->string('s3_key')
                    ->nullable()
                    ->after('s3_bucket')
                    ->comment('AWS S3 Object Key (Path)');
            });

            $this->safeAddColumn($table, 'software_products', 's3_region', function($table) {
                $table->string('s3_region')
                    ->nullable()
                    ->after('s3_key')
                    ->comment('AWS S3 Region');
            });

            // Google Drive (for google_drive type)
            $this->safeAddColumn($table, 'software_products', 'google_drive_file_id', function($table) {
                $table->string('google_drive_file_id')
                    ->nullable()
                    ->after('s3_region')
                    ->comment('Google Drive File ID');
            });

            // Local Server (for local type)
            $this->safeAddColumn($table, 'software_products', 'local_file_path', function($table) {
                $table->string('local_file_path')
                    ->nullable()
                    ->after('google_drive_file_id')
                    ->comment('เส้นทางไฟล์บนเซิร์ฟเวอร์ (storage/app/software/)');
            });

            // File Metadata
            $this->safeAddColumn($table, 'software_products', 'file_size', function($table) {
                $table->bigInteger('file_size')
                    ->nullable()
                    ->after('local_file_path')
                    ->comment('ขนาดไฟล์ (bytes)');
            });

            $this->safeAddColumn($table, 'software_products', 'file_name', function($table) {
                $table->string('file_name')
                    ->nullable()
                    ->after('file_size')
                    ->comment('ชื่อไฟล์ต้นฉบับ');
            });

            $this->safeAddColumn($table, 'software_products', 'file_extension', function($table) {
                $table->string('file_extension', 10)
                    ->nullable()
                    ->after('file_name')
                    ->comment('นามสกุลไฟล์ (เช่น zip)');
            });

            $this->safeAddColumn($table, 'software_products', 'file_mime_type', function($table) {
                $table->string('file_mime_type')
                    ->nullable()
                    ->after('file_extension')
                    ->comment('MIME Type');
            });

            // Upload Metadata
            $this->safeAddColumn($table, 'software_products', 'uploaded_at', function($table) {
                $table->timestamp('uploaded_at')
                    ->nullable()
                    ->after('file_mime_type')
                    ->comment('วันที่อัพโหลดไฟล์');
            });

            $this->safeAddColumn($table, 'software_products', 'uploaded_by', function($table) {
                $table->foreignId('uploaded_by')
                    ->nullable()
                    ->after('uploaded_at')
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('ผู้อัพโหลด');
            });

            // Download Stats
            $this->safeAddColumn($table, 'software_products', 'download_count', function($table) {
                $table->integer('download_count')
                    ->default(0)
                    ->after('uploaded_by')
                    ->comment('จำนวนครั้งที่ดาวน์โหลด');
            });

            $this->safeAddColumn($table, 'software_products', 'last_downloaded_at', function($table) {
                $table->timestamp('last_downloaded_at')
                    ->nullable()
                    ->after('download_count')
                    ->comment('ดาวน์โหลดล่าสุดเมื่อ');
            });
        });

        // เพิ่ม indexes
        $this->safeAddIndex('software_products', 'storage_type');
        $this->safeAddIndex('software_products', 'uploaded_by');
    }

    /**
     * ลบฟิลด์ File Storage
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ indexes ก่อน
        $this->safeDropIndex('software_products', 'software_products_storage_type_index');
        $this->safeDropIndex('software_products', 'software_products_uploaded_by_index');

        // ลบ columns
        $this->safeDropColumn('software_products', [
            'storage_type',
            'file_url',
            's3_bucket',
            's3_key',
            's3_region',
            'google_drive_file_id',
            'local_file_path',
            'file_size',
            'file_name',
            'file_extension',
            'file_mime_type',
            'uploaded_at',
            'uploaded_by',
            'download_count',
            'last_downloaded_at',
        ]);
    }
};
