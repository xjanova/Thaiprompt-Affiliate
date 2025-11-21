<?php

namespace App\Http\Controllers;

use App\Models\LeadLock;
use App\Models\LineOaSetting;
use App\Models\MlmMember;
use App\Models\RecruitCustomization;
use App\Models\RecruitPageVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/**
 * Public Controller สำหรับหน้า Recruit Page
 *
 * หน้านี้เป็นหน้าสาธารณะที่ผู้มุ่งหวังจะเห็น
 * มีระบบ Lead Lock 48 ชั่วโมง และการติดตามสถิติ
 */
class RecruitController extends Controller
{
    /**
     * แสดงหน้า Recruit ของแม่ทีม
     *
     * @param string $memberCode รหัสสมาชิกของแม่ทีม
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function show(string $memberCode, Request $request)
    {
        // ค้นหาแม่ทีมจาก member_code
        $mlmMember = MlmMember::where('member_code', $memberCode)
            ->with('user')
            ->firstOrFail();

        $teamLeader = $mlmMember->user;

        // ดึงข้อมูล customization
        $customization = RecruitCustomization::where('user_id', $teamLeader->id)
            ->with('template')
            ->first();

        // ถ้าไม่มี customization หรือไม่ active
        if (!$customization || !$customization->is_active) {
            abort(404, 'หน้านี้ไม่พร้อมใช้งาน');
        }

        // ดึงเทมเพลต
        $template = $customization->template;
        if (!$template || !$template->is_active) {
            $template = \App\Models\RecruitTemplate::getDefault();
        }

        // สร้าง Visitor Identifier (IP + User Agent hash)
        $visitorIdentifier = $this->generateVisitorIdentifier($request);

        // บันทึกการเข้าชมและจัดการ Lead Lock
        $this->trackVisit($teamLeader->id, $visitorIdentifier, $request);
        $leadLock = $this->handleLeadLock($teamLeader->id, $visitorIdentifier, $request);

        // ดึงการตั้งค่า LINE OA
        $lineSettings = LineOaSetting::getActive();

        // URL สำหรับสมัคร
        $registerUrl = route('register', ['ref' => $memberCode]);

        return view('recruit.show', [
            'teamLeader' => $teamLeader,
            'mlmMember' => $mlmMember,
            'customization' => $customization,
            'template' => $template,
            'leadLock' => $leadLock,
            'lineSettings' => $lineSettings,
            'registerUrl' => $registerUrl,
        ]);
    }

    /**
     * สร้าง Visitor Identifier จาก IP และ User Agent
     *
     * @param Request $request
     * @return string
     */
    protected function generateVisitorIdentifier(Request $request): string
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        // สร้าง fingerprint
        return hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * บันทึกการเข้าชม
     *
     * @param int $teamLeaderId
     * @param string $visitorIdentifier
     * @param Request $request
     * @return void
     */
    protected function trackVisit(int $teamLeaderId, string $visitorIdentifier, Request $request): void
    {
        try {
            // Parse User Agent
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            // ดึง UTM parameters
            $utmParams = [
                'utm_source' => $request->get('utm_source'),
                'utm_medium' => $request->get('utm_medium'),
                'utm_campaign' => $request->get('utm_campaign'),
                'utm_term' => $request->get('utm_term'),
                'utm_content' => $request->get('utm_content'),
            ];

            // กรองเฉพาะที่มีค่า
            $utmParams = array_filter($utmParams);

            // บันทึกการเข้าชม
            RecruitPageVisit::recordVisit($teamLeaderId, $visitorIdentifier, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isTablet() ? 'tablet' : 'mobile'),
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'source_url' => $request->fullUrl(),
                'referrer_url' => $request->headers->get('referer'),
                'utm_params' => !empty($utmParams) ? $utmParams : null,
                'session_id' => session()->getId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track recruit page visit', [
                'team_leader_id' => $teamLeaderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * จัดการ Lead Lock (ระบบล็อคผู้มุ่งหวัง 48 ชั่วโมง)
     *
     * @param int $teamLeaderId
     * @param string $visitorIdentifier
     * @param Request $request
     * @return LeadLock|null
     */
    protected function handleLeadLock(int $teamLeaderId, string $visitorIdentifier, Request $request): ?LeadLock
    {
        try {
            // ตรวจสอบว่ามี Lead Lock ที่ active อยู่หรือไม่
            $existingLock = LeadLock::getActiveLock($visitorIdentifier);

            if ($existingLock) {
                // มี lock อยู่แล้ว ไม่สร้างใหม่
                // ให้สิทธิ์คนแรก (First-come, first-served)
                return $existingLock;
            }

            // ไม่มี lock → สร้าง lock ใหม่
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            // ดึง UTM parameters
            $utmParams = [
                'utm_source' => $request->get('utm_source'),
                'utm_medium' => $request->get('utm_medium'),
                'utm_campaign' => $request->get('utm_campaign'),
            ];
            $utmParams = array_filter($utmParams);

            $leadLock = LeadLock::createLock($visitorIdentifier, $teamLeaderId, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'source_url' => $request->fullUrl(),
                'referrer_url' => $request->headers->get('referer'),
                'utm_params' => !empty($utmParams) ? $utmParams : null,
            ]);

            // อัพเดทจำนวน leads ของ customization
            $customization = RecruitCustomization::where('user_id', $teamLeaderId)->first();
            if ($customization) {
                $customization->incrementLeads();
            }

            return $leadLock;

        } catch (\Exception $e) {
            Log::error('Failed to handle lead lock', [
                'team_leader_id' => $teamLeaderId,
                'visitor_identifier' => $visitorIdentifier,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * API Endpoint: บันทึกพฤติกรรมผู้เข้าชม (สำหรับ JavaScript tracking)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackBehavior(Request $request)
    {
        try {
            $validated = $request->validate([
                'session_id' => 'required|string',
                'scrolled_to_bottom' => 'boolean',
                'clicked_register_button' => 'boolean',
                'time_spent' => 'nullable|integer',
            ]);

            // ค้นหา visit จาก session_id
            $visit = RecruitPageVisit::where('session_id', $validated['session_id'])
                ->latest()
                ->first();

            if ($visit) {
                $visit->update([
                    'scrolled_to_bottom' => $validated['scrolled_to_bottom'] ?? $visit->scrolled_to_bottom,
                    'clicked_register_button' => $validated['clicked_register_button'] ?? $visit->clicked_register_button,
                    'time_spent' => $validated['time_spent'] ?? $visit->time_spent,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'บันทึกพฤติกรรมสำเร็จ',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการเข้าชม',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to track behavior', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }
}
