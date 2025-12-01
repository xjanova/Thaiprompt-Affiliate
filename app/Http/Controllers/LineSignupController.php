<?php

namespace App\Http\Controllers;

use App\Models\MlmMember;
use App\Models\MlmProspect;
use App\Services\MlmProspectService;
use App\Services\LineSignupService;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineSignupController extends Controller
{
    public function __construct(
        private MlmProspectService $prospectService,
        private LineSignupService $signupService,
        private LineService $lineService
    ) {}

    /**
     * Handle invitation link click
     */
    public function handleInvitation(Request $request, string $token)
    {
        try {
            // Get prospect
            $prospect = $this->prospectService->getProspectByToken($token);

            if (!$prospect) {
                return view('line.signup.error', [
                    'message' => 'ลิงก์เชิญไม่ถูกต้อง',
                ]);
            }

            // Check if already completed
            if ($prospect->status === 'completed') {
                return view('line.signup.already-registered', [
                    'prospect' => $prospect,
                ]);
            }

            // Check if expired
            if ($prospect->isExpired()) {
                return view('line.signup.expired', [
                    'prospect' => $prospect,
                    'sponsor' => $prospect->sponsorUser,
                ]);
            }

            // Redirect to LINE Login with referral token
            $state = json_encode([
                'referral_token' => $token,
                'timestamp' => time(),
            ]);

            $authUrl = $this->lineService->getAuthorizationUrl($state);

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('Invitation handling error', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('line.signup.error', [
                'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
            ]);
        }
    }

    /**
     * Handle LINE callback after authorization
     */
    public function handleCallback(Request $request)
    {
        try {
            $code = $request->input('code');
            $state = json_decode($request->input('state'), true);
            $referralToken = $state['referral_token'] ?? null;

            if (!$code || !$referralToken) {
                return view('line.signup.error', [
                    'message' => 'ข้อมูลไม่ครบถ้วน',
                ]);
            }

            // Exchange code for access token
            $tokenData = $this->lineService->getAccessToken($code);
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('Failed to get access token');
            }

            // Get LINE profile
            $profile = $this->lineService->getUserProfile($accessToken);

            // Update prospect with click info
            $prospect = $this->prospectService->handleInvitationClick(
                $referralToken,
                $profile['userId'] ?? null,
                $profile,
                $request->ip(),
                $request->userAgent()
            );

            if (!$prospect) {
                return view('line.signup.error', [
                    'message' => 'ไม่พบข้อมูลการเชิญ',
                ]);
            }

            // Start signup conversation in LINE
            $this->signupService->startConversation($prospect);

            return view('line.signup.started', [
                'prospect' => $prospect,
                'sponsor' => $prospect->sponsorUser,
            ]);

        } catch (\Exception $e) {
            Log::error('LINE callback error', [
                'error' => $e->getMessage(),
            ]);

            return view('line.signup.error', [
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ LINE',
            ]);
        }
    }

    /**
     * สร้าง invitation จาก member_code และ redirect ไป LINE signup
     *
     * เมื่อผู้มุ่งหวังสแกน QR Code จะมาที่ route นี้
     * ระบบจะสร้าง invitation อัตโนมัติและพาไปเพิ่มเพื่อน LINE OA
     *
     * @param string $memberCode รหัสสมาชิก MLM
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function inviteByMemberCode(string $memberCode)
    {
        try {
            // หา MLM Member จาก member_code
            $member = MlmMember::where('member_code', $memberCode)->first();

            if (!$member) {
                Log::warning('LINE invite: member code not found', [
                    'member_code' => $memberCode,
                ]);

                return view('line.signup.error', [
                    'message' => 'ไม่พบรหัสสมาชิก กรุณาตรวจสอบลิงก์อีกครั้ง',
                ]);
            }

            // ตรวจสอบว่ามี invitation ที่ยังใช้ได้อยู่หรือไม่
            $existingProspect = MlmProspect::where('sponsor_mlm_member_id', $member->id)
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->where('is_locked', false)
                ->first();

            if ($existingProspect) {
                // ใช้ invitation ที่มีอยู่แล้ว
                $token = $existingProspect->referral_token;

                Log::info('LINE invite: reusing existing prospect', [
                    'member_code' => $memberCode,
                    'prospect_id' => $existingProspect->id,
                ]);
            } else {
                // สร้าง invitation ใหม่
                $prospect = $this->prospectService->createInvitationLink($member);
                $token = $prospect->referral_token;

                Log::info('LINE invite: created new prospect', [
                    'member_code' => $memberCode,
                    'prospect_id' => $prospect->id,
                ]);
            }

            // Redirect ไปหน้า invitation
            return redirect()->route('line.signup.invitation', ['token' => $token]);

        } catch (\Exception $e) {
            Log::error('LINE invite error', [
                'member_code' => $memberCode,
                'error' => $e->getMessage(),
            ]);

            return view('line.signup.error', [
                'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
            ]);
        }
    }
}
