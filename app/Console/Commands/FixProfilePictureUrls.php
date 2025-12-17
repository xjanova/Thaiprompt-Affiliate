<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * แก้ไข profile_picture ที่เป็น URL ให้เป็น PATH
 *
 * ปัญหา: Mobile API เวอร์ชันเก่าบันทึก URL เต็มลง database (เช่น '/storage/avatars/1_123456.jpg')
 * แก้ไข: แปลงเป็น relative path (เช่น 'avatars/1_123456.jpg')
 */
class FixProfilePictureUrls extends Command
{
    /**
     * คำสั่ง Artisan
     *
     * @var string
     */
    protected $signature = 'fix:profile-pictures
                            {--dry-run : ดูผลลัพธ์โดยไม่บันทึกลง database}
                            {--force : บังคับแก้ไขแม้ว่าไฟล์ไม่พบ}';

    /**
     * คำอธิบายคำสั่ง
     *
     * @var string
     */
    protected $description = 'แก้ไข profile_picture ที่เป็น URL ให้เป็น PATH สำหรับความเข้ากันได้กับระบบใหม่';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🔧 เริ่มแก้ไข profile_picture URLs...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  Dry Run Mode: จะไม่บันทึกการเปลี่ยนแปลงลง database');
            $this->newLine();
        }

        // หา users ที่มี profile_picture เป็น URL
        $users = User::whereNotNull('profile_picture')
            ->where(function ($query) {
                $query->where('profile_picture', 'like', '/storage/%')
                      ->orWhere('profile_picture', 'like', 'http%');
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('✅ ไม่พบ profile_picture ที่ต้องแก้ไข');
            return Command::SUCCESS;
        }

        $this->info("พบ {$users->count()} users ที่ต้องแก้ไข");
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $oldValue = $user->profile_picture;

            // แปลง URL → PATH
            $path = $this->convertUrlToPath($oldValue);

            // ตรวจสอบว่าไฟล์มีอยู่จริง
            if (Storage::disk('public')->exists($path)) {
                if (!$dryRun) {
                    $user->profile_picture = $path;
                    $user->save();
                }

                $fixedCount++;
                $progressBar->advance();
            } elseif ($force) {
                // บังคับแก้ไขแม้ว่าไฟล์ไม่พบ
                if (!$dryRun) {
                    $user->profile_picture = $path;
                    $user->save();
                }

                $fixedCount++;
                $progressBar->advance();
            } else {
                // Skip - ไฟล์ไม่พบ
                $skippedCount++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // สรุปผลลัพธ์
        $this->info('📊 สรุปผลลัพธ์:');
        $this->table(
            ['สถานะ', 'จำนวน'],
            [
                ['✅ แก้ไขสำเร็จ', $fixedCount],
                ['⏭️  ข้าม (ไฟล์ไม่พบ)', $skippedCount],
                ['❌ ข้อผิดพลาด', $errorCount],
                ['📝 รวมทั้งหมด', $users->count()],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Tip: รันคำสั่งโดยไม่ใช้ --dry-run เพื่อบันทึกการเปลี่ยนแปลง');
        }

        if ($skippedCount > 0 && !$force) {
            $this->newLine();
            $this->warn("⚠️  มี {$skippedCount} users ที่ข้ามไปเพราะไฟล์ไม่พบ");
            $this->info('💡 Tip: ใช้ --force เพื่อบังคับแก้ไขแม้ว่าไฟล์ไม่พบ');
        }

        $this->newLine();
        $this->info('✅ เสร็จสิ้น!');

        return Command::SUCCESS;
    }

    /**
     * แปลง URL → PATH
     *
     * @param string $url
     * @return string
     */
    private function convertUrlToPath(string $url): string
    {
        // ลบ prefix ทั้งหมดที่เป็นไปได้
        $prefixes = [
            '/storage/',
            url('/storage/'),
            Storage::disk('public')->url(''),
            config('app.url') . '/storage/',
            'http://' . request()->getHost() . '/storage/',
            'https://' . request()->getHost() . '/storage/',
        ];

        $path = $url;
        foreach ($prefixes as $prefix) {
            if ($prefix && str_starts_with($path, $prefix)) {
                $path = str_replace($prefix, '', $path);
                break;
            }
        }

        return $path;
    }
}
