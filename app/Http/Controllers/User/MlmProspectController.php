<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\MlmProspectService;
use Illuminate\Http\Request;

class MlmProspectController extends Controller
{
    public function __construct(
        private MlmProspectService $prospectService
    ) {}

    /**
     * Display prospects list (ผู้มุ่งหวัง)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        // ถ้าไม่ได้เป็น MLM member ให้แสดงหน้าแนะนำแทนที่จะ redirect
        // ใช้ empty paginator เพื่อให้ view ทำงานได้ถูกต้อง
        if (! $mlmMember) {
            return view('user.prospects.index', [
                'prospects' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'expired' => 0,
                ],
                'notMlmMember' => true,
            ]);
        }

        // รับเฉพาะค่าที่รู้จัก — ค่าแปลกจาก URL ถือว่า "ทั้งหมด"
        $status = in_array($request->input('status'), ['pending', 'in_progress', 'completed', 'expired', 'cancelled'], true)
            ? $request->input('status') : null;
        $type = $request->input('type') === 'fortune' ? 'fortune' : null;

        // 🐛 (2026-11-02) ฟอร์มกรองมีช่องค้นหา/ช่วงวันที่มานาน แต่ controller ไม่เคยอ่าน → กดกรองแล้วไม่มีอะไรเปลี่ยน
        $isDate = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
        $filters = [
            'search' => is_string($request->input('search')) ? mb_substr(trim($request->input('search')), 0, 100) : null,
            'date_from' => $isDate($request->input('date_from')) ? $request->input('date_from') : null,
            'date_to' => $isDate($request->input('date_to')) ? $request->input('date_to') : null,
        ];

        $prospects = $this->prospectService->getProspectsForSponsor(
            $mlmMember->id,
            $status,
            $type,
            15,
            $filters
        )->withQueryString();

        $stats = $this->prospectService->getProspectStats($mlmMember->id);

        return view('user.prospects.index', compact('prospects', 'stats'));
    }

    /**
     * Show create invitation link form
     */
    public function create()
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        // ถ้าไม่ได้เป็น MLM member ให้แสดงหน้าแนะนำ
        if (! $mlmMember) {
            return view('user.prospects.create', [
                'mlmMember' => null,
                'notMlmMember' => true,
            ]);
        }

        return view('user.prospects.create', compact('mlmMember'));
    }

    /**
     * Generate invitation link
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (! $mlmMember) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = $this->prospectService->createInvitationLink($mlmMember);

        return redirect()->route('user.prospects.show', $prospect->id)
            ->with('success', 'สร้างลิงก์เชิญสำเร็จ!');
    }

    /**
     * Show prospect details
     * แสดงรายละเอียดผู้มุ่งหวัง
     */
    public function show($id)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (! $mlmMember) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = \App\Models\MlmProspect::where('id', $id)
            ->where('sponsor_mlm_member_id', $mlmMember->id)
            ->with(['registeredUser', 'sponsorUser'])
            ->firstOrFail();

        // สร้าง URL เชิญแบบสดจากข้อมูลปัจจุบัน (รองรับทั้ง prospect เก่า/ใหม่ — เลี่ยง invitation_url เดิมที่อาจชี้ route ที่ถูกลบ)
        $invitationUrl = route('line.registration.register', [
            'ref' => $mlmMember->member_code,
            'prospect' => $prospect->referral_token,
        ]);

        return view('user.prospects.show', compact('prospect', 'invitationUrl'));
    }

    /**
     * Delete prospect invitation
     * ลบลิงก์เชิญที่ยังไม่ได้ใช้งาน
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (! $mlmMember) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = \App\Models\MlmProspect::where('id', $id)
            ->where('sponsor_mlm_member_id', $mlmMember->id)
            ->firstOrFail();

        // ตรวจสอบว่าสามารถลบได้หรือไม่ (เฉพาะ pending หรือ expired)
        if (! in_array($prospect->status, ['pending', 'expired'])) {
            return redirect()->route('user.prospects.index')
                ->with('error', 'ไม่สามารถลบลิงก์เชิญที่มีการใช้งานแล้ว');
        }

        $prospect->delete();

        return redirect()->route('user.prospects.index')
            ->with('success', 'ลบลิงก์เชิญเรียบร้อยแล้ว');
    }

    /**
     * Renew expired prospect invitation
     * ต่ออายุลิงก์เชิญที่หมดอายุ
     */
    public function renew($id)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (! $mlmMember) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = \App\Models\MlmProspect::where('id', $id)
            ->where('sponsor_mlm_member_id', $mlmMember->id)
            ->firstOrFail();

        // ตรวจสอบว่าสามารถต่ออายุได้หรือไม่ (เฉพาะ pending หรือ expired)
        if (! in_array($prospect->status, ['pending', 'expired'])) {
            return redirect()->route('user.prospects.show', $id)
                ->with('error', 'ไม่สามารถต่ออายุลิงก์เชิญที่มีการใช้งานแล้ว');
        }

        // 🐛 (2026-11-02) เดิมเขียน expires_at ซึ่งไม่มีคอลัมน์นี้ (ไม่อยู่ใน fillable → ถูกทิ้งเงียบ)
        //    ลิงก์ที่ล็อกหมดเวลาแล้วจึงกลับเป็น "หมดอายุ" ทันทีที่เปิดดู (isLocked() เห็น locked_until เก่า)
        //    ตอนนี้: เคยมีคนกดลิงก์แล้ว → ต่อเวลาล็อกตามการตั้งค่ากลาง (เหมือนตอนคลิกครั้งแรกใน MlmProspectService)
        //    ยังไม่เคยมีใครกด → ปลดล็อกรอคนกดใหม่
        //    ⚙️ แอดมินปิดการล็อกผู้มุ่งหวัง (enable_prospect_lock = false) → ไม่ล็อก แต่ยังตั้งเวลาหมดอายุใหม่ให้ลิงก์
        $globalSettings = \App\Models\MlmGlobalSetting::first();
        $lockEnabled = (bool) ($globalSettings->enable_prospect_lock ?? true);
        $lockHours = (int) ($globalSettings->prospect_lock_duration_hours ?? 24);
        if ($lockHours <= 0) {
            $lockHours = 24;
        }

        $prospect->update([
            'status' => 'pending',
            'is_locked' => $prospect->clicked_at ? $lockEnabled : false,
            'locked_until' => $prospect->clicked_at ? now()->addHours($lockHours) : null,
        ]);

        return redirect()->route('user.prospects.show', $id)
            ->with('success', 'ต่ออายุลิงก์เชิญเรียบร้อยแล้ว');
    }

    /**
     * Resend invitation link notification
     * ส่งลิงก์เชิญอีกครั้ง (สำหรับติดตาม)
     */
    public function resend($id)
    {
        $user = auth()->user();
        $mlmMember = $user->mlmMember;

        if (! $mlmMember) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        $prospect = \App\Models\MlmProspect::where('id', $id)
            ->where('sponsor_mlm_member_id', $mlmMember->id)
            ->firstOrFail();

        // ตรวจสอบว่ายังไม่ completed
        if ($prospect->status === 'completed') {
            return redirect()->route('user.prospects.show', $id)
                ->with('error', 'ผู้มุ่งหวังนี้สมัครสมาชิกแล้ว ไม่จำเป็นต้องส่งลิงก์อีก');
        }

        // 🐛 (2026-11-02) เดิมอัพเดท last_reminded_at ซึ่งไม่มีคอลัมน์ (ถูกทิ้งเงียบ ไม่มีอะไรถูกบันทึก)
        //    ใช้เวลาอัพเดทล่าสุดของเรคคอร์ดเป็นเวลาติดตามล่าสุดแทน (หน้ารายละเอียดแสดง "อัพเดทล่าสุด")
        $prospect->touch();

        return redirect()->route('user.prospects.show', $id)
            ->with('success', 'บันทึกการติดตามเรียบร้อยแล้ว');
    }
}
