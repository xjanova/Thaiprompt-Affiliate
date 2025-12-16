<?php

namespace App\Http\Controllers;

use App\Models\LineOaSetting;
use App\Models\LineRegistrationSession;
use App\Models\LineSignupReward;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * LineRegistrationController
 *
 * จัดการกระบวนการสมัครสมาชิกผ่าน LINE OA
 * รองรับการ tracking session จากหน้าเว็บ และ auto-redirect หลังสมัครเสร็จ
 */
class LineRegistrationController extends Controller
{
    public function __construct(
        private LineService $lineService
    ) {}

    /**
     * แสดงหน้าสมัครสมาชิกผ่าน LINE OA
     *
     * Flow:
     * 1. สร้าง session token
     * 2. แสดง QR Code สำหรับเพิ่มเพื่อน LINE OA
     * 3. หน้าเว็บจะ polling เช็คสถานะ
     * 4. เมื่อสมัครเสร็จ → redirect ไปหน้าอัพเดทข้อมูล
     *
     * @param Request $request
     * @return View
     */
    public function showRegister(Request $request): View
    {
        $referralCode = $request->query('ref') ?? $request->query('referral_code');

        // สร้าง session ใหม่
        $session = LineRegistrationSession::createSession(
            $referralCode,
            $request->ip(),
            $request->userAgent()
        );

        // ดึงข้อมูล LINE OA Settings
        $lineSettings = LineOaSetting::getActive();

        // ดึงข้อมูล sponsor (ถ้ามี)
        $sponsor = null;
        if ($session->sponsor_user_id) {
            $sponsor = User::find($session->sponsor_user_id);
        }

        // ดึงข้อมูลรางวัลเมื่อสมัคร (สำหรับ free signup)
        $signupRewards = LineSignupReward::getAvailableRewards(null, $sponsor !== null);

        Log::info('LINE Registration: Session created', [
            'session_token' => $session->session_token,
            'referral_code' => $referralCode,
            'sponsor_id' => $session->sponsor_user_id,
        ]);

        return view('line.registration.register', [
            'session' => $session,
            'lineSettings' => $lineSettings,
            'sponsor' => $sponsor,
            'qrCodeUrl' => $this->getLineOaQrCodeUrl($lineSettings),
            'addFriendUrl' => $this->getLineOaAddFriendUrl($lineSettings),
            'signupRewards' => $signupRewards,
        ]);
    }

    /**
     * API: ตรวจสอบสถานะ session (สำหรับ polling)
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function checkStatus(Request $request, string $token): JsonResponse
    {
        $session = LineRegistrationSession::findByToken($token);

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        // ตรวจสอบว่าหมดอายุหรือไม่
        if ($session->isExpired()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'expired',
                    'message' => 'Session หมดอายุแล้ว กรุณาเริ่มใหม่',
                ],
            ]);
        }

        // ดึงข้อมูลสำหรับ response
        $responseData = $session->toApiResponse();

        // ถ้าสมัครเสร็จแล้ว ให้ generate auth token สำหรับ auto-login
        if ($session->status === LineRegistrationSession::STATUS_COMPLETED && $session->user_id) {
            $user = User::find($session->user_id);
            if ($user) {
                // สร้าง one-time token สำหรับ auto-login
                $authToken = $this->generateOneTimeAuthToken($user, $session);
                $responseData['auth_token'] = $authToken;
                $responseData['redirect_url'] = route('line.registration.complete', ['token' => $authToken]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    /**
     * API: บันทึกว่าผู้ใช้คลิกปุ่มเพิ่มเพื่อน LINE OA
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function recordAddFriendClick(Request $request, string $token): JsonResponse
    {
        $session = LineRegistrationSession::findByToken($token);

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        // บันทึกว่าคลิกปุ่มเพิ่มเพื่อนแล้ว
        $session->updateMetadata([
            'add_friend_clicked_at' => now()->toIso8601String(),
            'add_friend_click_ip' => $request->ip(),
        ]);

        Log::info('LINE Registration: Add friend clicked', [
            'session_token' => $token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recorded',
        ]);
    }

    /**
     * หน้า complete - auto-login และ redirect ไปอัพเดทข้อมูล
     *
     * @param Request $request
     * @param string $token Auth token
     * @return \Illuminate\Http\RedirectResponse|View
     */
    public function completeRegistration(Request $request, string $token)
    {
        // ตรวจสอบ auth token
        $authData = $this->validateOneTimeAuthToken($token);

        if (!$authData) {
            return redirect()->route('login')
                ->with('error', 'ลิงก์หมดอายุหรือไม่ถูกต้อง กรุณาเข้าสู่ระบบ');
        }

        $user = User::find($authData['user_id']);

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'ไม่พบผู้ใช้ในระบบ');
        }

        // Auto-login
        Auth::login($user);

        Log::info('LINE Registration: Auto-login successful', [
            'user_id' => $user->id,
            'session_token' => $authData['session_token'] ?? null,
        ]);

        // Redirect ไปหน้าอัพเดทข้อมูล
        return redirect()->route('user.profile.edit')
            ->with('success', '🎉 สมัครสมาชิกสำเร็จ! กรุณากรอกข้อมูลเพิ่มเติม');
    }

    /**
     * API: ยกเลิก session
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function cancelSession(Request $request, string $token): JsonResponse
    {
        $session = LineRegistrationSession::findByToken($token);

        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
            ], 404);
        }

        $session->markAsCancelled();

        return response()->json([
            'success' => true,
            'message' => 'Session cancelled',
        ]);
    }

    /**
     * สร้าง one-time auth token สำหรับ auto-login
     *
     * @param User $user
     * @param LineRegistrationSession $session
     * @return string
     */
    private function generateOneTimeAuthToken(User $user, LineRegistrationSession $session): string
    {
        $data = [
            'user_id' => $user->id,
            'session_token' => $session->session_token,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(15)->timestamp,
        ];

        // เข้ารหัส data
        $payload = json_encode($data);
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return base64_encode($payload . '.' . $signature);
    }

    /**
     * ตรวจสอบ one-time auth token
     *
     * @param string $token
     * @return array|null
     */
    private function validateOneTimeAuthToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('.', $decoded);

            if (count($parts) !== 2) {
                return null;
            }

            [$payload, $signature] = $parts;
            $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));

            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            $data = json_decode($payload, true);

            // ตรวจสอบว่าหมดอายุหรือไม่
            if ($data['expires_at'] < now()->timestamp) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning('Invalid auth token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * สร้าง QR Code URL สำหรับเพิ่มเพื่อน LINE OA
     *
     * @param LineOaSetting|null $settings
     * @return string|null
     */
    private function getLineOaQrCodeUrl(?LineOaSetting $settings): ?string
    {
        if (!$settings || !$settings->messaging_channel_id) {
            return null;
        }

        // LINE OA QR Code URL
        return 'https://qr-official.line.me/gs/M_' . $settings->messaging_channel_id . '_GW.png';
    }

    /**
     * สร้าง URL สำหรับเพิ่มเพื่อน LINE OA
     *
     * @param LineOaSetting|null $settings
     * @return string|null
     */
    private function getLineOaAddFriendUrl(?LineOaSetting $settings): ?string
    {
        if (!$settings || !$settings->line_id) {
            return null;
        }

        // LINE OA Add Friend URL
        return 'https://line.me/R/ti/p/' . $settings->line_id;
    }
}
