<?php

namespace App\Http\Controllers;

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
}
