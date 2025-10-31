<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StorageLinkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * ตรวจสอบและสร้าง storage symlink อัตโนมัติเมื่อ application boot
     * แก้ปัญหาโลโก้/favicon หายหลัง deploy
     */
    public function boot(): void
    {
        // ทำงานเฉพาะใน production และถ้ายังไม่มี symlink
        if ($this->shouldCreateSymlink()) {
            $this->ensureStorageLink();
        }
    }

    /**
     * ตรวจสอบว่าควรสร้าง symlink หรือไม่
     */
    protected function shouldCreateSymlink(): bool
    {
        $link = public_path('storage');

        // ถ้ายังไม่มี symlink หรือเป็น directory ธรรมดา (ไม่ใช่ symlink)
        return !file_exists($link) || (!is_link($link) && is_dir($link));
    }

    /**
     * สร้าง storage symlink
     */
    protected function ensureStorageLink(): void
    {
        try {
            $target = storage_path('app/public');
            $link = public_path('storage');

            // ถ้ามี directory ธรรมดาอยู่ ให้ลบออกก่อน
            if (is_dir($link) && !is_link($link)) {
                // ลบ directory เฉพาะถ้าว่างเปล่า
                if ($this->isDirectoryEmpty($link)) {
                    rmdir($link);
                } else {
                    Log::warning('Cannot create storage symlink: public/storage exists and is not empty');
                    return;
                }
            }

            // สร้าง symlink ถ้ายังไม่มี
            if (!file_exists($link)) {
                // สร้าง target directory ถ้ายังไม่มี
                if (!is_dir($target)) {
                    File::makeDirectory($target, 0755, true);
                }

                // สร้าง symlink
                if (symlink($target, $link)) {
                    Log::info('Storage symlink created successfully');
                } else {
                    Log::error('Failed to create storage symlink');
                }
            }
        } catch (\Exception $e) {
            // บันทึก error แต่ไม่ให้ crash application
            Log::error('Error creating storage symlink: ' . $e->getMessage());
        }
    }

    /**
     * ตรวจสอบว่า directory ว่างเปล่าหรือไม่
     */
    protected function isDirectoryEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = scandir($path);
        // ลบ . และ .. ออก
        $files = array_diff($files, ['.', '..']);

        return count($files) === 0;
    }
}
