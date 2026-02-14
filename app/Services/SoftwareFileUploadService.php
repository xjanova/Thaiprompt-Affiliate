<?php

namespace App\Services;

use App\Models\SoftwareProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SoftwareFileUploadService
 *
 * จัดการการอัพโหลดไฟล์ซอฟต์แวร์แบบ Multi-Cloud
 * รองรับ 4 วิธี: URL, AWS S3, Google Drive, Local Server
 */
class SoftwareFileUploadService
{
    /**
     * อัพโหลดไฟล์ตามประเภทที่เลือก
     *
     * @param  string  $uploadType  (url, s3, google_drive, local)
     * @param  mixed  $file  ไฟล์หรือข้อมูล URL
     * @param  int  $userId  ผู้อัพโหลด
     * @return array ผลลัพธ์การอัพโหลด
     *
     * @throws \Exception
     */
    public function upload(SoftwareProduct $product, string $uploadType, $file, int $userId): array
    {
        // ตรวจสอบว่า method นี้เปิดใช้งานหรือไม่
        if (! $this->isMethodEnabled($uploadType)) {
            throw new \Exception("Upload method '{$uploadType}' is not enabled");
        }

        return match ($uploadType) {
            'url' => $this->uploadFromUrl($product, $file, $userId),
            's3' => $this->uploadToS3($product, $file, $userId),
            'google_drive' => $this->uploadToGoogleDrive($product, $file, $userId),
            'local' => $this->uploadToLocal($product, $file, $userId),
            default => throw new \Exception("Invalid upload type: {$uploadType}"),
        };
    }

    /**
     * อัพโหลดจาก URL Link
     *
     *
     * @throws \Exception
     */
    protected function uploadFromUrl(SoftwareProduct $product, string $url, int $userId): array
    {
        // ตรวจสอบ URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL format');
        }

        // ตรวจสอบว่า domain อยู่ใน allowed list
        $allowed = config('software-upload.url.allowed_domains', []);
        $domain = parse_url($url, PHP_URL_HOST);

        $isAllowed = false;
        foreach ($allowed as $allowedDomain) {
            if (Str::endsWith($domain, $allowedDomain)) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            throw new \Exception("Domain '{$domain}' is not allowed");
        }

        // ตรวจสอบว่า URL accessible (optional)
        if (config('software-upload.url.validate_accessibility', true)) {
            try {
                $response = Http::timeout(config('software-upload.url.validation_timeout', 10))
                    ->head($url);

                if (! $response->successful()) {
                    throw new \Exception('URL is not accessible');
                }
            } catch (\Exception $e) {
                throw new \Exception('Failed to validate URL: '.$e->getMessage());
            }
        }

        // บันทึกข้อมูล
        $product->update([
            'storage_type' => 'url',
            'file_url' => $url,
            'uploaded_at' => now(),
            'uploaded_by' => $userId,
        ]);

        return [
            'success' => true,
            'storage_type' => 'url',
            'file_url' => $url,
        ];
    }

    /**
     * อัพโหลดไปยัง AWS S3
     *
     *
     * @throws \Exception
     */
    protected function uploadToS3(SoftwareProduct $product, UploadedFile $file, int $userId): array
    {
        // ตรวจสอบไฟล์
        $this->validateFile($file);

        $bucket = config('software-upload.s3.bucket');
        $region = config('software-upload.s3.region');
        $directory = config('software-upload.s3.directory', 'software');

        // สร้างชื่อไฟล์ unique
        $filename = $this->generateUniqueFilename($file);
        $s3Key = "{$directory}/{$filename}";

        try {
            // อัพโหลดไปยัง S3
            $path = Storage::disk('s3')->putFileAs(
                $directory,
                $file,
                $filename,
                config('software-upload.s3.visibility', 'private')
            );

            // บันทึกข้อมูล
            $product->update([
                'storage_type' => 's3',
                's3_bucket' => $bucket,
                's3_key' => $s3Key,
                's3_region' => $region,
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
                'uploaded_by' => $userId,
            ]);

            return [
                'success' => true,
                'storage_type' => 's3',
                's3_bucket' => $bucket,
                's3_key' => $s3Key,
                's3_region' => $region,
                'file_size' => $file->getSize(),
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to upload to S3: '.$e->getMessage());
        }
    }

    /**
     * อัพโหลดไปยัง Google Drive
     *
     *
     * @throws \Exception
     */
    protected function uploadToGoogleDrive(SoftwareProduct $product, UploadedFile $file, int $userId): array
    {
        // ตรวจสอบไฟล์
        $this->validateFile($file);

        // TODO: Implement Google Drive API integration
        // ต้องติดตั้ง package: composer require google/apiclient

        throw new \Exception('Google Drive upload is not implemented yet. Please use URL, S3, or Local upload.');
        // Pseudo code:
        /*
        $client = new \Google_Client();
        $client->setAuthConfig(config('software-upload.google_drive.credentials_path'));
        $client->addScope(\Google_Service_Drive::DRIVE_FILE);

        $driveService = new \Google_Service_Drive($client);

        $fileMetadata = new \Google_Service_Drive_DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [config('software-upload.google_drive.folder_id')]
        ]);

        $content = file_get_contents($file->getRealPath());

        $uploadedFile = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
        ]);

        $product->update([
            'storage_type' => 'google_drive',
            'google_drive_file_id' => $uploadedFile->id,
            'file_size' => $file->getSize(),
            'file_name' => $file->getClientOriginalName(),
            'file_extension' => $file->getClientOriginalExtension(),
            'file_mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
            'uploaded_by' => $userId,
        ]);

        return [
            'success' => true,
            'storage_type' => 'google_drive',
            'google_drive_file_id' => $uploadedFile->id,
            'file_size' => $file->getSize(),
        ];
        */
    }

    /**
     * อัพโหลดไปยัง Local Server
     *
     *
     * @throws \Exception
     */
    protected function uploadToLocal(SoftwareProduct $product, UploadedFile $file, int $userId): array
    {
        // ตรวจสอบไฟล์
        $this->validateFile($file);

        $disk = config('software-upload.local.disk', 'local');
        $directory = config('software-upload.local.directory', 'software');

        // สร้างชื่อไฟล์ unique
        $filename = $this->generateUniqueFilename($file);

        try {
            // อัพโหลดไฟล์
            $path = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                $filename
            );

            // บันทึกข้อมูล
            $product->update([
                'storage_type' => 'local',
                'local_file_path' => $path,
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
                'uploaded_by' => $userId,
            ]);

            return [
                'success' => true,
                'storage_type' => 'local',
                'local_file_path' => $path,
                'file_size' => $file->getSize(),
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to upload to local storage: '.$e->getMessage());
        }
    }

    /**
     * ตรวจสอบไฟล์
     *
     *
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // ตรวจสอบนามสกุลไฟล์
        $allowedExtensions = config('software-upload.allowed_extensions', ['zip']);
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowedExtensions)) {
            throw new \Exception("File extension '.{$extension}' is not allowed. Only ".implode(', ', $allowedExtensions).' files are permitted.');
        }

        // ตรวจสอบขนาดไฟล์
        $maxSize = config('software-upload.max_file_size', 10 * 1024 * 1024);
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            throw new \Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }

        // ตรวจสอบ MIME type
        $allowedMimeTypes = config('software-upload.allowed_mime_types', []);
        if (! empty($allowedMimeTypes) && ! in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception("File type '{$file->getMimeType()}' is not allowed");
        }

        // ตรวจสอบความสมบูรณ์ของ ZIP (optional)
        if (config('software-upload.security.verify_zip_integrity', true)) {
            $this->verifyZipIntegrity($file);
        }
    }

    /**
     * ตรวจสอบความสมบูรณ์ของไฟล์ ZIP
     *
     *
     * @throws \Exception
     */
    protected function verifyZipIntegrity(UploadedFile $file): void
    {
        $zip = new \ZipArchive;
        $result = $zip->open($file->getRealPath(), \ZipArchive::CHECKCONS);

        if ($result !== true) {
            throw new \Exception('ZIP file is corrupted or invalid');
        }

        // ตรวจสอบจำนวนไฟล์ใน ZIP
        $maxFiles = config('software-upload.security.max_files_in_zip', 1000);
        if ($zip->numFiles > $maxFiles) {
            $zip->close();
            throw new \Exception("ZIP contains too many files (max: {$maxFiles})");
        }

        // ตรวจสอบนามสกุลไฟล์ที่ห้ามใน ZIP
        $disallowedExtensions = config('software-upload.security.zip_disallowed_extensions', []);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($extension, $disallowedExtensions)) {
                $zip->close();
                throw new \Exception("ZIP contains disallowed file type: .{$extension}");
            }
        }

        $zip->close();
    }

    /**
     * สร้างชื่อไฟล์ unique
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $pattern = config('software-upload.local.filename_pattern', '{timestamp}_{random}_{original}');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Sanitize original filename
        $originalName = Str::slug($originalName);

        // Replace placeholders
        $filename = str_replace([
            '{timestamp}',
            '{random}',
            '{original}',
        ], [
            time(),
            Str::random(8),
            $originalName,
        ], $pattern);

        return $filename.'.'.$extension;
    }

    /**
     * ตรวจสอบว่า upload method เปิดใช้งานหรือไม่
     */
    protected function isMethodEnabled(string $method): bool
    {
        $enabled = config('software-upload.enabled_methods', []);

        return $enabled[$method] ?? false;
    }

    /**
     * ลบไฟล์ที่อัพโหลด
     */
    public function deleteFile(SoftwareProduct $product): bool
    {
        try {
            switch ($product->storage_type) {
                case 's3':
                    if ($product->s3_key) {
                        Storage::disk('s3')->delete($product->s3_key);
                    }
                    break;

                case 'local':
                    if ($product->local_file_path) {
                        Storage::disk('local')->delete($product->local_file_path);
                    }
                    break;

                case 'google_drive':
                    // TODO: Implement Google Drive file deletion
                    break;

                case 'url':
                    // ไม่ต้องลบ URL
                    break;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete software file: '.$e->getMessage());

            return false;
        }
    }
}
