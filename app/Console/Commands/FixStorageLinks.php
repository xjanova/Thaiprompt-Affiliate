<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixStorageLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:fix
                            {--force : Force the operation to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'แก้ไขปัญหา storage symlink (สำหรับปัญหาโลโก้/favicon หาย)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 กำลังตรวจสอบ storage symlink...');
        $this->newLine();

        $target = storage_path('app/public');
        $link = public_path('storage');

        // 1. ตรวจสอบ target directory
        if (!is_dir($target)) {
            $this->info('📁 สร้าง storage/app/public directory...');
            File::makeDirectory($target, 0755, true);
            $this->info('✓ สร้าง storage/app/public สำเร็จ');
        } else {
            $this->info('✓ storage/app/public มีอยู่แล้ว');
        }

        // 2. สร้าง branding directory ถ้ายังไม่มี
        $brandingDir = $target . '/branding';
        if (!is_dir($brandingDir)) {
            $this->info('📁 สร้าง branding directory...');
            File::makeDirectory($brandingDir, 0755, true);
            $this->info('✓ สร้าง branding directory สำเร็จ');
        } else {
            $this->info('✓ branding directory มีอยู่แล้ว');
        }

        // 3. ตรวจสอบและจัดการ symlink
        if (file_exists($link)) {
            if (is_link($link)) {
                // มี symlink อยู่แล้ว
                $linkTarget = readlink($link);
                if ($linkTarget === $target) {
                    $this->info('✓ Storage symlink ทำงานถูกต้องอยู่แล้ว');
                    $this->showStorageInfo($target, $link);
                    return Command::SUCCESS;
                } else {
                    // Symlink ชี้ไปผิดที่
                    $this->warn('⚠ Storage symlink ชี้ไปยัง: ' . $linkTarget);
                    $this->warn('   แต่ควรชี้ไปยัง: ' . $target);

                    if ($this->option('force') || $this->confirm('ต้องการลบและสร้าง symlink ใหม่?')) {
                        unlink($link);
                        $this->info('🗑 ลบ symlink เก่าแล้ว');
                    } else {
                        $this->error('ยกเลิกการดำเนินการ');
                        return Command::FAILURE;
                    }
                }
            } else if (is_dir($link)) {
                // เป็น directory ธรรมดา
                $this->warn('⚠ public/storage เป็น directory ธรรมดา (ไม่ใช่ symlink)');

                $files = File::files($link);
                if (count($files) > 0) {
                    $this->error('❌ public/storage ไม่ว่างเปล่า มี ' . count($files) . ' ไฟล์');
                    $this->error('   กรุณาสำรองไฟล์ออกก่อน หรือใช้ --force เพื่อลบทิ้ง');

                    if ($this->option('force')) {
                        File::deleteDirectory($link);
                        $this->info('🗑 ลบ directory เก่าแล้ว');
                    } else {
                        return Command::FAILURE;
                    }
                } else {
                    rmdir($link);
                    $this->info('🗑 ลบ directory ว่างเปล่าแล้ว');
                }
            }
        }

        // 4. สร้าง symlink ใหม่
        if (!file_exists($link)) {
            $this->info('🔗 กำลังสร้าง storage symlink...');

            try {
                symlink($target, $link);
                $this->info('✅ สร้าง storage symlink สำเร็จ!');
                $this->newLine();
                $this->showStorageInfo($target, $link);

                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error('❌ ไม่สามารถสร้าง symlink ได้: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * แสดงข้อมูล storage
     */
    protected function showStorageInfo(string $target, string $link): void
    {
        $this->newLine();
        $this->info('📊 ข้อมูล Storage:');
        $this->line('   Target: ' . $target);
        $this->line('   Link:   ' . $link);

        // นับจำนวนไฟล์
        $brandingFiles = File::files($target . '/branding');
        $this->line('   ไฟล์ใน branding/: ' . count($brandingFiles));

        if (count($brandingFiles) > 0) {
            $this->newLine();
            $this->info('📁 ไฟล์ในโฟลเดอร์ branding:');
            foreach ($brandingFiles as $file) {
                $this->line('   - ' . basename($file) . ' (' . $this->formatBytes(filesize($file)) . ')');
            }
        }

        $this->newLine();
        $this->info('💡 วิธีเข้าถึงไฟล์:');
        $this->line('   Path: /storage/branding/filename.ext');
        $this->line('   URL:  ' . url('/storage/branding/filename.ext'));
    }

    /**
     * Format bytes เป็น human-readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
