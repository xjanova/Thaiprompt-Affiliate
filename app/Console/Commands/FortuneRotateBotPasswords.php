<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 🔒 สุ่มรหัสผ่านใหม่ให้บัญชีที่บอทสมัครให้อัตโนมัติ
 *
 * ปัญหา (2026-07-16):
 *   บัญชีที่ FortuneAffiliateService / FortuneConversationService สมัครให้อัตโนมัติ
 *   ถูกตั้งรหัสผ่าน '12345678' เหมือนกันทุกใบ + อีเมลเดาได้จากสูตร
 *   (line_{LINE_UID}@thaiprompt.local / fb_{PSID}@thaiprompt.local)
 *   + LoginController ใช้ Auth::attempt() ตรงๆ ไม่บล็อกอีเมล .local
 *   → ใครรู้ UID ของลูกค้า = ล็อกอินเป็นคนนั้นได้ พร้อมเข้าถึงวอลเลต
 *   ซ้ำร้าย บอทเคยส่ง Flex บอกรหัสผ่านให้ลูกค้าเองด้วย
 *
 * ทำไมสุ่มทิ้งได้:
 *   ลูกค้ากลุ่มนี้เข้าเว็บผ่าน OAuth เสมอ (/auth/line?redirect=... , /auth/facebook?redirect=...)
 *   ไม่เคยใช้รหัสผ่านล็อกอิน และรีเซ็ตรหัสทางอีเมลก็ไม่ได้อยู่แล้ว
 *   (@thaiprompt.local ไม่ใช่อีเมลจริง ส่งเมลไม่ถึง)
 *
 * 🛡️ ความปลอดภัย:
 *   - แตะเฉพาะบัญชีที่ Hash::check('12345678') ผ่านจริงเท่านั้น
 *     → ถ้าลูกค้าเปลี่ยนรหัสเองแล้ว จะไม่โดนแตะ
 *   - จำกัดเฉพาะอีเมล @thaiprompt.local (บัญชีที่บอทสร้าง)
 *   - ข้าม user ที่ล็อกอินได้ทางอื่นไม่ได้ (ไม่มี line_user_id / facebook_user_id
 *     / facebook_psid — ตัวหลังนับเป็นทางเข้าเพราะ FB OAuth map PSID ได้แล้ว)
 *     → กันล็อกคนออกจากบัญชีตัวเองถาวร
 *
 * ใช้:
 *   php artisan fortune:rotate-bot-passwords --dry-run
 *   php artisan fortune:rotate-bot-passwords
 */
class FortuneRotateBotPasswords extends Command
{
    protected $signature = 'fortune:rotate-bot-passwords
                            {--dry-run : ดูผลก่อน ไม่เขียนจริง}
                            {--include-unreachable : สุ่มรหัสบัญชีที่ไม่มี OAuth ด้วย (ดู doc ก่อนใช้)}';

    protected $description = '🔒 สุ่มรหัสผ่านใหม่ให้บัญชีที่บอทสมัครให้ (แก้รหัส 12345678 ซ้ำกันทุกใบ)';

    private const WEAK_PASSWORD = '12345678';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info('🔒 ตรวจบัญชีที่บอทสมัครให้อัตโนมัติ...');
        if ($dry) {
            $this->warn('   [DRY RUN] ไม่เขียนอะไรทั้งสิ้น');
        }

        $candidates = User::where('email', 'like', '%@thaiprompt.local')->get();
        $this->line('   พบบัญชี .local ทั้งหมด: '.$candidates->count().' ใบ');

        $weak = 0;
        $rotated = 0;
        $skippedStrong = 0;
        $skippedNoOauth = 0;

        foreach ($candidates as $user) {
            // แตะเฉพาะใบที่ยังเป็นรหัสอ่อนจริงๆ
            if (! Hash::check(self::WEAK_PASSWORD, $user->password)) {
                $skippedStrong++;

                continue;
            }
            $weak++;

            // 🛡️ ไม่มีช่องทาง OAuth = สุ่มรหัสแล้วเข้าไม่ได้ตลอดกาล (รีเซ็ตทางอีเมลก็ไม่ได้
            //    เพราะ @thaiprompt.local ส่งเมลไม่ถึง) → default ข้ามไว้ก่อน
            //
            // ⚠️ (2026-07-16) แต่ข้อมูลจริงบน prod: บัญชี fb_* 648 ใบไม่มี facebook_user_id
            //    เลยสักใบ เพราะ createUserFromPlatform เก็บ line_user_id เฉพาะ LINE
            //    และ FacebookLoginController:181 บอกเองว่า FB OAuth ส่ง app-scoped ID
            //    ที่ match กับ Messenger PSID ไม่ได้ → กด FB login = ได้บัญชีใหม่คนละใบ
            //    ทั้ง Flex ที่เคยบอกรหัสผ่านก็ส่งเฉพาะ LINE → ลูกค้า FB ไม่เคยรู้รหัสด้วยซ้ำ
            //    ⇒ สำหรับบัญชีกลุ่มนี้ รหัสผ่านไม่ใช่ "ทางเข้าที่เขาใช้" แต่เป็นหนี้ความเสี่ยงล้วนๆ
            //    ใช้ --include-unreachable เพื่อสุ่มทิ้ง (เป็นการตัดสินใจที่ต้องเห็นชัด ไม่ซ่อนใน default)
            //
            // ✅ (2026-07-16 ภายหลัง) facebook_psid นับเป็นทางเข้า OAuth ด้วย —
            //    FacebookLoginController map ASID→PSID ผ่าน ids_for_pages แล้ว
            //    บัญชีที่มี PSID เข้าผ่านปุ่ม FB login ได้ ไม่ต้องพึ่งรหัสผ่าน
            $hasOauth = ! empty($user->line_user_id)
                || ! empty($user->facebook_user_id)
                || ! empty($user->facebook_psid);

            if (! $hasOauth && ! $this->option('include-unreachable')) {
                $skippedNoOauth++;
                if ($skippedNoOauth <= 3) {
                    $this->warn('   ⚠️  ข้าม user#'.$user->id.' ('.$user->email.') — ไม่มี OAuth');
                }

                continue;
            }

            if (! $dry) {
                // ห้ามผ่าน mass-assignment — password อยู่ใน $guarded/$hidden ได้
                $user->forceFill(['password' => Hash::make(Str::random(48))])->saveQuietly();
            }
            $rotated++;
        }

        $this->newLine();
        $this->table(['รายการ', 'จำนวน'], [
            ['บัญชี .local ทั้งหมด', $candidates->count()],
            ['ยังใช้รหัส '.self::WEAK_PASSWORD, $weak],
            [$dry ? 'จะสุ่มใหม่' : '✅ สุ่มรหัสใหม่แล้ว', $rotated],
            ['ข้าม (เปลี่ยนรหัสเองแล้ว)', $skippedStrong],
            ['⚠️ ข้าม (ไม่มี OAuth)', $skippedNoOauth],
        ]);

        if ($skippedNoOauth > 0) {
            $this->warn('⚠️  มี '.$skippedNoOauth.' บัญชีที่ไม่มีช่องทาง OAuth — ยังใช้รหัสอ่อนอยู่');
            $this->warn('   บัญชีกลุ่มนี้ (ส่วนใหญ่ fb_*) เจ้าของเข้าไม่ได้อยู่แล้ว — รหัสเป็นหนี้ความเสี่ยงล้วนๆ');
            $this->warn('   สุ่มทิ้งด้วย: php artisan fortune:rotate-bot-passwords --include-unreachable');
        }

        if ($dry) {
            $this->newLine();
            $this->info('👉 รันจริง: php artisan fortune:rotate-bot-passwords');
        }

        return self::SUCCESS;
    }
}
