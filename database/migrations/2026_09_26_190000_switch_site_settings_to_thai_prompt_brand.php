<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ไฟล์แบรนด์เก่าที่แอดมินเคยอัปโหลดไว้บน production (ค่าจริงใน site_settings ณ 2026-09-26)
     *
     * เจ้าของสั่ง "เปลี่ยนเป็นของใหม่ทั้งหมด" (2026-09-26) — ล้างเฉพาะค่าที่ตรงกับไฟล์เดิมเป๊ะ
     * accessor ใน SiteSetting คืนไฟล์แบรนด์ Thai Prompt ใหม่เมื่อค่าว่าง
     * ถ้าแอดมินอัปโหลดไฟล์ใหม่ภายหลัง ชื่อไฟล์จะไม่ตรง → migration นี้ไม่แตะ · ไฟล์เดิมไม่ได้ถูกลบ
     *
     * @var array<string, string>
     */
    private const OLD_UPLOADS = [
        'logo' => 'site-settings/8dwwosOHRBGit1UUXLKRNEIt6WKPkViOvOk4JWCm.webp',
        'logo_dark' => 'site-settings/AEldZQHLHWyAl3YKB3ZYOqbDXb7evtg1HEyRk2XB.webp',
        'favicon' => 'site-settings/81EJoF8Mhs0lelZDfqddFwRniRYn1ouei2APeuGI.webp',
        'app_icon' => 'app-icons/emr9ntLwV2qFpl2HgNGOhj22NfreuvI14HMhITY0.webp',
    ];

    /**
     * ชื่อเดิม → ชื่อใหม่ (เปลี่ยนเฉพาะแถวที่ยังเป็นชื่อเดิม)
     *
     * @var array<string, array{0: list<string>, 1: string}>
     */
    private const OLD_NAMES = [
        'site_name' => [['TP-Affiliate'], 'Thai Prompt'],
        'app_name' => [['TP Ultra App', 'TP Ultra', 'TP UltraAPP', 'TP UltraApp'], 'Thai Prompt APP'],
    ];

    /**
     * เปลี่ยนค่าตั้งค่าเว็บไปใช้แบรนด์ Thai Prompt
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        foreach (self::OLD_UPLOADS as $column => $oldFile) {
            if (Schema::hasColumn('site_settings', $column)) {
                DB::table('site_settings')->where($column, $oldFile)->update([$column => null]);
            }
        }

        foreach (self::OLD_NAMES as $column => [$oldNames, $newName]) {
            if (Schema::hasColumn('site_settings', $column)) {
                DB::table('site_settings')->whereIn($column, $oldNames)->update([$column => $newName]);
            }
        }

        // SiteSetting แคชค่าไว้ 1 ชม. — ล้างให้หน้าเว็บเห็นทันที (deploy ล้างแคชซ้ำอีกรอบอยู่แล้ว)
        Cache::forget('site_settings');
    }

    /**
     * คืนค่าไฟล์/ชื่อเดิม (ไฟล์เดิมยังอยู่ใน storage)
     */
    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        foreach (self::OLD_UPLOADS as $column => $oldFile) {
            if (Schema::hasColumn('site_settings', $column)) {
                DB::table('site_settings')->whereNull($column)->update([$column => $oldFile]);
            }
        }

        if (Schema::hasColumn('site_settings', 'site_name')) {
            DB::table('site_settings')->where('site_name', 'Thai Prompt')->update(['site_name' => 'TP-Affiliate']);
        }
        if (Schema::hasColumn('site_settings', 'app_name')) {
            DB::table('site_settings')->where('app_name', 'Thai Prompt APP')->update(['app_name' => 'TP Ultra App']);
        }

        Cache::forget('site_settings');
    }
};
