<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\Setting;
use App\Models\User;
use App\Support\FortuneBackOfficeAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * รหัสแนะนำ + ลิงก์สมัครที่แอปเอาไปแชร์/ทำ QR (2026-11-11)
 *
 * รหัสแนะนำจริงของระบบคือ mlm_members.member_code — หน้าสมัครเว็บ/LINE อ่าน ?ref= เป็น member_code
 * เดิม API แอปส่ง users.referral_code (ว่างเกือบทุกบัญชี) → หน้า "ชวนเพื่อน" โหลดไม่ขึ้น / QR ใช้ไม่ได้
 * ทุกจุดในแอปต้องใช้คลาสนี้ชุดเดียว
 */
class ReferralShareService
{
    public function __construct(private readonly FortuneAffiliateService $affiliate) {}

    /**
     * รหัสที่แชร์ได้ของ user (ไม่สร้างสมาชิกใหม่) — สำหรับ payload โปรไฟล์/ล็อกอิน
     */
    public function codeFor(User $user): ?string
    {
        $code = MlmMember::where('user_id', $user->id)->value('member_code');

        return $code ?: ($user->referral_code ?: null);
    }

    /**
     * ลิงก์สมัครที่ผูกผู้แนะนำ — ชุดเดียวกับเว็บ (MlmDashboardController::referralUrlFor)
     */
    public function linkFor(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        foreach (['line.registration.register', 'register'] as $name) {
            if (Route::has($name)) {
                return route($name, ['ref' => $code]);
            }
        }

        return url('/register?ref='.urlencode($code));
    }

    /**
     * รหัสสำหรับหน้า "ชวนเพื่อน" — ถ้ายังไม่เป็นสมาชิกสายงาน (เช่น สมัครด้วยอีเมลในแอป) ต่อสายให้ตอนนี้
     * ใต้ผู้แนะนำเดิม (users.sponsor_id) หรือผู้แนะนำเริ่มต้น · บัญชีทีมงานไม่ต่อสายให้อัตโนมัติ
     */
    public function ensureCodeFor(User $user): ?string
    {
        $code = MlmMember::where('user_id', $user->id)->value('member_code');
        if ($code) {
            return $code;
        }

        if (! FortuneBackOfficeAccount::is($user)) {
            $member = $this->enroll($user);
            if ($member?->member_code) {
                return $member->member_code;
            }
        }

        return $user->referral_code ?: null;
    }

    /**
     * หาสมาชิกผู้แนะนำจากรหัสที่ผู้ใช้กรอก — รับทั้ง member_code (ของจริง) และ users.referral_code (รหัสเก่า)
     */
    public function sponsorFromCode(?string $code): ?MlmMember
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $member = MlmMember::where('member_code', $code)->first();
        if ($member) {
            return $member;
        }

        $userId = User::where('referral_code', $code)->value('id');

        return $userId ? MlmMember::where('user_id', $userId)->first() : null;
    }

    /**
     * ต่อสาย user ใต้ผู้แนะนำ (idempotent — มีอยู่แล้วคืนตัวเดิม ไม่ re-parent)
     * ล็อกต่อ user กันกดซ้ำ/เปิดสองเครื่องพร้อมกันแล้วได้สมาชิกสองแถว
     */
    public function enroll(User $user, ?MlmMember $sponsor = null): ?MlmMember
    {
        try {
            return Cache::lock('mlm-enroll:'.$user->id, 15)->block(10, function () use ($user, $sponsor) {
                $existing = MlmMember::where('user_id', $user->id)->first();
                if ($existing) {
                    return $existing;
                }

                $sponsor ??= $this->defaultSponsorFor($user);
                if (! $sponsor || (int) $sponsor->user_id === (int) $user->id) {
                    return null;
                }

                return $this->affiliate->enrollUserUnderSponsor($user, $sponsor);
            });
        } catch (\Throwable $e) {
            Log::warning('ReferralShare: ต่อสายสมาชิกไม่สำเร็จ', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ผู้แนะนำตั้งต้น: คนที่ชวนมา (users.sponsor_id) → ค่าตั้งค่า default_sponsor_member_code → สมาชิกของ Super Admin
     */
    private function defaultSponsorFor(User $user): ?MlmMember
    {
        if ($user->sponsor_id) {
            $member = MlmMember::where('user_id', $user->sponsor_id)->first();
            if ($member) {
                return $member;
            }
        }

        $code = Setting::get('default_sponsor_member_code');
        if ($code) {
            $member = MlmMember::where('member_code', $code)->first();
            if ($member) {
                return $member;
            }
        }

        return $this->affiliate->defaultSponsor();
    }
}
