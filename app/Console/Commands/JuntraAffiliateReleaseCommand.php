<?php

namespace App\Console\Commands;

use App\Models\JuntraAccount;
use App\Services\Juntra\JuntraAffiliateException;
use App\Services\Juntra\JuntraAffiliateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔐 (2026-11-03 รีวิว) เครื่องมือแอดมินสำหรับ 409 thaiprompt_taken
 *
 * เคส: ลูกค้าเปิดบัญชีจันทราใหม่แล้วผูก Thaiprompt คนเดิม แต่บัญชีจันทราเก่ายังถือ Thaiprompt คนนั้นอยู่
 *   (จันทราไม่ได้เรียก release เพราะบัญชีเก่าไม่ได้ถูกลบผ่านระบบ) → บิลของบัญชีใหม่ได้ 409 วนไม่จบ
 *   เดิมแอดมินไม่มีทางแก้นอกจากเข้า DB เอง
 *
 * คำสั่งนี้ = ปลดบัญชีจันทราเก่า (เหมือนจันทราเรียก release) หลังแอดมินตรวจแล้วว่าเป็นคนเดียวกัน
 *   บิลถัดไปของบัญชีใหม่จะชี้แถวเดิมมาที่บัญชีใหม่เอง (สายงาน/ตำแหน่งเดิมทั้งหมด ไม่สร้างเงา)
 *   ไม่ลบข้อมูลใด ๆ · บันทึกผู้สั่งและเหตุผลลง log ตรวจสอบย้อนหลัง · ต้องยืนยันก่อนทำ
 *
 * @example php artisan juntra:affiliate-release 1234 --reason="ลูกค้ายืนยันทางโทรศัพท์ บัญชีเก่าเลิกใช้"
 */
class JuntraAffiliateReleaseCommand extends Command
{
    protected $signature = 'juntra:affiliate-release
        {juntra_user_id : user_ref ฝั่งจันทราของบัญชีเก่าที่ถือ Thaiprompt ไว้}
        {--reason= : เหตุผล (บังคับ — เก็บลง log ตรวจสอบ)}
        {--by= : ชื่อแอดมินผู้สั่ง (ไม่ใส่ = ผู้ใช้ระบบปฏิบัติการ)}';

    protected $description = '🔐 ปลดบัญชีจันทราเก่าที่ถือบัญชี Thaiprompt ไว้ (แก้ 409 thaiprompt_taken หลังตรวจแล้วว่าเป็นคนเดียวกัน)';

    public function handle(JuntraAffiliateService $service): int
    {
        $ref = (int) $this->argument('juntra_user_id');
        $reason = trim((string) $this->option('reason'));
        $by = trim((string) ($this->option('by') ?: (get_current_user() ?: 'cli')));

        if ($ref <= 0) {
            $this->error('user_ref ต้องเป็นตัวเลขมากกว่า 0');

            return self::INVALID;
        }
        if ($reason === '') {
            $this->error('ต้องใส่ --reason="..." (เก็บไว้ตรวจสอบย้อนหลัง)');

            return self::INVALID;
        }

        $account = JuntraAccount::where('juntra_user_id', $ref)->first();
        if (! $account) {
            $this->warn("ไม่พบบัญชีจันทรา #{$ref} ในระบบ — ไม่มีอะไรต้องปลด");

            return self::SUCCESS;
        }
        if ($account->released_at !== null) {
            $this->info("บัญชีจันทรา #{$ref} ถูกปลดไว้แล้วตั้งแต่ {$account->released_at}");

            return self::SUCCESS;
        }

        $this->table(['juntra_user_id', 'thaiprompt user_id', 'linked_via', 'enrolled_member_id'], [[
            $account->juntra_user_id, $account->user_id, $account->linked_via, $account->enrolled_member_id ?? '-',
        ]]);

        if (! $this->confirm('ยืนยันปลดบัญชีจันทรานี้? (บัญชีจันทราใหม่ที่ผูก Thaiprompt คนนี้จะรับแถวเดิมไปทั้งสายงาน)', false)) {
            $this->line('ยกเลิก — ไม่มีการเปลี่ยนแปลง');

            return self::FAILURE;
        }

        try {
            $result = $service->releaseAccount($ref, 'admin_cli: '.$reason);
        } catch (JuntraAffiliateException $e) {
            $this->error('ปลดไม่สำเร็จ: '.$e->getMessage());

            return self::FAILURE;
        }

        // บันทึกตรวจสอบ: ใคร ปลดอะไร เพราะอะไร
        Log::warning('Juntra affiliate: แอดมินปลดบัญชีจันทราด้วยคำสั่ง (แก้ thaiprompt_taken)', [
            'juntra_user_id' => $ref,
            'user_id' => $account->user_id,
            'by' => mb_substr($by, 0, 80),
            'reason' => mb_substr($reason, 0, 300),
            'result' => $result,
        ]);

        $this->info("ปลดบัญชีจันทรา #{$ref} แล้ว — บิลของบัญชีใหม่จะผ่านเองในรอบส่งถัดไป");

        return self::SUCCESS;
    }
}
