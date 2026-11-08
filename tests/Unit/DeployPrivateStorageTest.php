<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * โฟลเดอร์ไฟล์ส่วนตัวใต้ storage/app ต้องรอด deploy.sh
 *
 * deploy.sh STEP 4.4 รัน `git clean -fdx` พร้อมรายการ -e ตายตัว — โฟลเดอร์ที่ไม่อยู่ในรายการโดนลบทุก deploy
 * (เคยเกิดจริงกับสลิปใน storage/app/fortune, รูป eKYC และเอกสารไรเดอร์)
 * โฟลเดอร์ private แต่ละตัวต้องอยู่ครบ 3 ที่:
 *   1. -e ของบรรทัด git clean     — กันรอบแรกที่ยังเป็นโฟลเดอร์จริง (ยังไม่ได้ย้ายออก)
 *   2. persist_private_storage_dir — ย้ายของจริงออกนอก git tree แล้ว symlink กลับมา
 *   3. .gitignore (ไม่มี / ท้าย)    — ไม่งั้น STEP 4.1 `git stash push -u` กวาดไฟล์ลูกค้าเข้า .git
 *      และ pattern ที่มี / ท้ายไม่จับ symlink
 *
 * อ่านไฟล์อย่างเดียว — ไม่บูตแอป ไม่แตะ DB
 */
class DeployPrivateStorageTest extends TestCase
{
    /** โฟลเดอร์ที่แอปเขียนลง disk local (= storage/app) ขณะทำงานจริง → ตัวที่เขียน */
    private const RUNTIME_PRIVATE_DIRS = [
        'storage/app/ekyc' => 'config/ekyc.php disk local',
        'storage/app/profile-photos' => 'App\Services\Media\ProfilePhotoService::DISK',
        'storage/app/kyc-private' => 'App\Services\Kyc\KycDocumentStorage',
        'storage/app/riders' => 'App\Services\RiderAccountService::DOCUMENT_DISK',
        'storage/app/rider_jobs' => 'App\Services\Rider\HandoverService::PHOTO_DISK',
    ];

    public function test_every_runtime_private_dir_is_persisted_by_deploy(): void
    {
        $persisted = array_keys($this->persistedDirs());

        foreach (self::RUNTIME_PRIVATE_DIRS as $dir => $writer) {
            $this->assertContains($dir, $persisted,
                "{$dir} ({$writer}) ต้องมี persist_private_storage_dir ใน deploy.sh ไม่งั้นไฟล์ลูกค้าหายทุก deploy");
        }
    }

    public function test_every_persisted_dir_is_excluded_from_git_clean_and_ignored(): void
    {
        $excluded = $this->gitCleanExcludes();
        $ignored = $this->gitignoreLines();

        foreach ($this->persistedDirs() as $dir => $external) {
            $this->assertContains($dir, $excluded, "{$dir} ต้องอยู่ในรายการ -e ของ git clean ใน deploy.sh");
            $this->assertContains($dir, $ignored, "{$dir} ต้องอยู่ใน .gitignore แบบไม่มี / ท้าย");
        }
    }

    public function test_persisted_dirs_do_not_share_an_external_folder(): void
    {
        $externals = array_values($this->persistedDirs());

        $this->assertSame(array_unique($externals), $externals,
            'โฟลเดอร์ปลายทางนอก git tree ต้องไม่ซ้ำกัน ไม่งั้นไฟล์ 2 ระบบปนกัน');
    }

    /**
     * @return array<string, string> path ใน repo → โฟลเดอร์ปลายทาง (ชื่อท้าย)
     */
    private function persistedDirs(): array
    {
        // อาร์กิวเมนต์ที่ 2 = "$(dirname "$SCRIPT_DIR")/xxx_data" (มี " ซ้อน) → เอาแค่ชื่อท้ายบรรทัด
        preg_match_all('/^persist_private_storage_dir "([^"]+)" .*\/([\w.-]+)"\s*$/m', $this->deployScript(), $m);
        $this->assertNotEmpty($m[1], 'หา persist_private_storage_dir ใน deploy.sh ไม่เจอ');

        return array_combine($m[1], $m[2]);
    }

    /**
     * @return list<string>
     */
    private function gitCleanExcludes(): array
    {
        preg_match_all('/^git clean -fdx .*$/m', $this->deployScript(), $lines);
        $this->assertCount(1, $lines[0], 'deploy.sh ต้องมีบรรทัด git clean -fdx บรรทัดเดียว');
        preg_match_all("/-e '([^']+)'/", $lines[0][0], $m);

        return $m[1];
    }

    /**
     * @return list<string>
     */
    private function gitignoreLines(): array
    {
        $lines = preg_split('/\R/', (string) file_get_contents($this->repoPath('.gitignore')));

        return array_map('trim', $lines);
    }

    private function deployScript(): string
    {
        return (string) file_get_contents($this->repoPath('deploy.sh'));
    }

    private function repoPath(string $file): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$file;
    }
}
