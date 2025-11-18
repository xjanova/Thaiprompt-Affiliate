<?php

namespace App\Http\Controllers;

use App\Models\LineSignupInvitation;
use App\Models\LineSignupSession;
use App\Services\LineMembershipSignupService;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineMembershipSignupController extends Controller
{
    protected $signupService;
    protected $lineService;
    protected $aiService;
    protected $richMenuService;

    public function __construct(
        LineMembershipSignupService $signupService,
        LineService $lineService,
        \App\Services\LineSignupAiService $aiService,
        \App\Services\LineSignupRichMenuService $richMenuService
    ) {
        $this->signupService = $signupService;
        $this->lineService = $lineService;
        $this->aiService = $aiService;
        $this->richMenuService = $richMenuService;
    }

    /**
     * Handle LINE webhook for signup
     */
    public function webhook(Request $request)
    {
        try {
            $events = $request->input('events', []);

            foreach ($events as $event) {
                $this->handleEvent($event);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('LINE Signup Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle individual LINE event
     */
    protected function handleEvent(array $event): void
    {
        $type = $event['type'] ?? null;
        $source = $event['source'] ?? [];
        $lineUserId = $source['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        switch ($type) {
            case 'message':
                $this->handleMessageEvent($event);
                break;

            case 'follow':
                $this->handleFollowEvent($event);
                break;

            case 'postback':
                $this->handlePostbackEvent($event);
                break;
        }
    }

    /**
     * Handle message event
     */
    protected function handleMessageEvent(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $message = $event['message'] ?? [];
        $messageType = $message['type'] ?? null;

        if ($messageType !== 'text') {
            return;
        }

        // Check if user has active signup session
        $session = LineSignupSession::where('line_user_id', $lineUserId)
            ->where('status', LineSignupSession::STATUS_ACTIVE)
            ->first();

        if (!$session) {
            // No active session, check if message is signup trigger
            $text = strtolower(trim($message['text'] ?? ''));

            if (in_array($text, ['สมัครสมาชิก', 'signup', 'register', 'เริ่มต้น'])) {
                $session = $this->signupService->startSignupSession($lineUserId);
                $this->sendWelcomeMessage($lineUserId);
            } else {
                // Not in signup flow, ignore
                return;
            }
        }

        // Process message in signup flow
        $response = $this->signupService->processSignupMessage($lineUserId, $message);

        // Send response
        $this->sendResponse($lineUserId, $response);
    }

    /**
     * Handle follow event (new friend)
     */
    protected function handleFollowEvent(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (!$lineUserId) {
            return;
        }

        // Send welcome and offer signup
        $this->sendWelcomeOfferMessage($lineUserId);
    }

    /**
     * Handle postback event
     */
    protected function handlePostbackEvent(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $data = $event['postback']['data'] ?? '';

        parse_str($data, $params);
        $action = $params['action'] ?? null;

        switch ($action) {
            case 'start_signup':
                $referralCode = $params['ref'] ?? null;
                $session = $this->signupService->startSignupSession($lineUserId, $referralCode);
                $this->sendWelcomeMessage($lineUserId);
                break;

            case 'wealth_path':
                $this->sendWealthPathMessage($lineUserId);
                break;

            case 'how_it_works':
                $this->sendHowItWorksMessage($lineUserId);
                break;

            case 'success_stories':
                $this->sendSuccessStoriesMessage($lineUserId);
                break;

            case 'learn_more':
                $this->sendLearnMoreMessage($lineUserId);
                break;

            case 'earning_potential':
                $this->sendEarningPotentialMessage($lineUserId);
                break;

            case 'resend_otp':
                $this->handleResendOtp($lineUserId);
                break;

            case 'edit_data':
                $this->handleEditData($lineUserId);
                break;

            case 'wealth_detail':
                $step = $params['step'] ?? 1;
                $this->sendWealthDetailMessage($lineUserId, $step);
                break;

            case 'skip_step':
                // Handle skip
                break;
        }
    }

    /**
     * Send wealth path message
     */
    protected function sendWealthPathMessage(string $lineUserId): void
    {
        $message = $this->richMenuService->buildWealthPathMessage();
        $this->lineService->pushMessage($lineUserId, $message);

        // Also send a text introduction
        $introText = "💰 เส้นทางสู่เศรษฐี - 5 ขั้นตอน\n\nนี่คือแผนที่ชัดเจนสำหรับการสร้างรายได้ออนไลน์!\n\nดูแต่ละขั้นตอนเพื่อเข้าใจเส้นทางของคุณ 🚀";

        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => $introText,
        ]);
    }

    /**
     * Send how it works message
     */
    protected function sendHowItWorksMessage(string $lineUserId): void
    {
        $message = $this->richMenuService->buildHowItWorksMessage();
        $this->lineService->pushMessage($lineUserId, $message);
    }

    /**
     * Send success stories message
     */
    protected function sendSuccessStoriesMessage(string $lineUserId): void
    {
        // Get success stories from AI service
        $stories = $this->aiService->getWealthPathContent()['success_stories'];

        foreach ($stories as $story) {
            $this->lineService->pushMessage($lineUserId, [
                'type' => 'text',
                'text' => "🌟 {$story['name']}\n\n⬇️ ก่อน: {$story['before']}\n⬆️ หลัง: {$story['after']}\n\n💬 \"{$story['quote']}\"",
            ]);
        }

        // Send CTA
        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => "คุณก็ทำได้เหมือนกัน! 💪\n\nพร้อมเริ่มต้นแล้วหรือยัง? 🚀",
            'quickReply' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🚀 เริ่มสมัครเลย!',
                            'data' => 'action=start_signup',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send learn more message
     */
    protected function sendLearnMoreMessage(string $lineUserId): void
    {
        $messages = [
            [
                'type' => 'text',
                'text' => "📖 ข้อมูลเพิ่มเติมเกี่ยวกับระบบของเรา\n\n✅ สมัครฟรี 100%\n✅ ไม่มีค่าใช้จ่ายแอบแฝง\n✅ รับโบนัสต้อนรับ 100 คะแนน\n✅ เข้าถึงคอร์สเรียนฟรี 3 คอร์ส\n✅ eBook เส้นทางสู่เศรษฐีฟรี!",
            ],
            [
                'type' => 'text',
                'text' => "💰 วิธีการสร้างรายได้:\n\n1. แชร์ลิงก์ของคุณ\n2. เพื่อนสมัครผ่านลิงก์\n3. คุณได้รับค่าคอมมิชชั่น!\n\nยิ่งแชร์มาก ยิ่งได้มาก! 🚀",
            ],
        ];

        foreach ($messages as $message) {
            $this->lineService->pushMessage($lineUserId, $message);
        }
    }

    /**
     * Send earning potential message
     */
    protected function sendEarningPotentialMessage(string $lineUserId): void
    {
        $examples = [
            ['referrals' => 5, 'monthly' => '1,000', 'yearly' => '12,000'],
            ['referrals' => 10, 'monthly' => '3,000', 'yearly' => '36,000'],
            ['referrals' => 20, 'monthly' => '8,000', 'yearly' => '96,000'],
            ['referrals' => 50, 'monthly' => '25,000', 'yearly' => '300,000'],
        ];

        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => "💰 ตารางรายได้ที่เป็นไปได้\n\n(ตัวอย่างเฉลี่ยจากสมาชิกจริง)",
        ]);

        foreach ($examples as $example) {
            $this->lineService->pushMessage($lineUserId, [
                'type' => 'text',
                'text' => "👥 {$example['referrals']} คน\n💰 {$example['monthly']} บาท/เดือน\n📈 {$example['yearly']} บาท/ปี",
            ]);
        }

        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => "💡 ยิ่งมีทีมงานมาก ยิ่งได้มาก!\n\nพร้อมเริ่มแล้วหรือยัง? 🚀",
            'quickReply' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🚀 เริ่มสมัครเลย!',
                            'data' => 'action=start_signup',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Handle resend OTP
     */
    protected function handleResendOtp(string $lineUserId): void
    {
        $session = LineSignupSession::where('line_user_id', $lineUserId)
            ->where('status', LineSignupSession::STATUS_ACTIVE)
            ->first();

        if (!$session || $session->current_step !== 'otp') {
            return;
        }

        // Generate new OTP
        $otp = $this->signupService->generateOtp();
        $session->otp_code = $otp;
        $session->otp_expires_at = now()->addMinutes(5);
        $session->save();

        // Send new OTP
        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => "🔐 รหัส OTP ใหม่ของคุณคือ: {$otp}\n\nรหัสนี้จะหมดอายุใน 5 นาที",
        ]);
    }

    /**
     * Handle edit data
     */
    protected function handleEditData(string $lineUserId): void
    {
        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => "ขออภัยค่ะ ฟีเจอร์แก้ไขข้อมูลกำลังพัฒนาอยู่\n\nหากต้องการเปลี่ยนแปลงข้อมูล กรุณาติดต่อทีมงานค่ะ 🙏",
        ]);
    }

    /**
     * Send wealth detail message
     */
    protected function sendWealthDetailMessage(string $lineUserId, int $step): void
    {
        $wealthPath = $this->aiService->getWealthPathContent();
        $stepData = $wealthPath['steps'][$step - 1] ?? null;

        if (!$stepData) {
            return;
        }

        $message = "🎯 {$stepData['title']}\n\n";
        $message .= "{$stepData['icon']} {$stepData['description']}\n\n";
        $message .= "💰 รายได้: {$stepData['earning']}\n";
        $message .= "⏱️ ระยะเวลา: {$stepData['time']}\n\n";

        if ($step === 1) {
            $message .= "🚀 เริ่มต้นได้ง่ายๆ ภายใน 2 นาที!\n\nพร้อมเริ่มแล้วไหม?";
        }

        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => $message,
            'quickReply' => [
                'items' => [
                    [
                        'type' => 'action',
                        'action' => [
                            'type' => 'postback',
                            'label' => '🚀 เริ่มสมัครเลย!',
                            'data' => 'action=start_signup',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Send welcome message
     */
    protected function sendWelcomeMessage(string $lineUserId): void
    {
        $message = [
            'type' => 'text',
            'text' => "✨ ยินดีต้อนรับสู่ระบบสมัครสมาชิก!\n\nเราจะช่วยคุณสมัครสมาชิกภายใน 2 นาที\n\nเริ่มกันเลยไหม? 😊",
        ];

        $this->lineService->pushMessage($lineUserId, $message);
    }

    /**
     * Send welcome offer message
     */
    protected function sendWelcomeOfferMessage(string $lineUserId): void
    {
        $message = [
            'type' => 'flex',
            'altText' => 'ยินดีต้อนรับ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '👋 ยินดีต้อนรับ!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ขอบคุณที่เพิ่มเราเป็นเพื่อน!',
                            'size' => 'md',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'สมัครสมาชิกวันนี้รับสิทธิ์พิเศษ:',
                            'size' => 'sm',
                            'margin' => 'lg',
                            'weight' => 'bold',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'md',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '✓ รับคะแนนสะสม 100 คะแนน',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✓ แชร์ลิงก์รับค่าคอมมิชชั่น',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✓ โปรโมชั่นพิเศษสำหรับสมาชิก',
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'postback',
                                'label' => '🚀 สมัครสมาชิกเลย!',
                                'data' => 'action=start_signup',
                            ],
                            'style' => 'primary',
                            'color' => '#1DB446',
                        ],
                    ],
                ],
            ],
        ];

        $this->lineService->pushMessage($lineUserId, $message);
    }

    /**
     * Send response to user
     */
    protected function sendResponse(string $lineUserId, array $response): void
    {
        $type = $response['type'] ?? 'text';
        $message = $response['message'] ?? [];

        if ($type === 'text') {
            $this->lineService->pushMessage($lineUserId, [
                'type' => 'text',
                'text' => is_string($message) ? $message : ($message['text'] ?? 'Error'),
            ]);
        } elseif ($type === 'flex') {
            $this->lineService->pushMessage($lineUserId, $message);
        }
    }

    /**
     * Show invitation page
     */
    public function showInvitation(Request $request, string $token)
    {
        $invitation = LineSignupInvitation::where('invitation_token', $token)->first();

        if (!$invitation || !$invitation->canBeUsed()) {
            return view('line.signup.invitation-invalid');
        }

        return view('line.signup.invitation', [
            'invitation' => $invitation,
            'inviter' => $invitation->inviter,
        ]);
    }

    /**
     * Process invitation signup
     */
    public function processInvitation(Request $request, string $token)
    {
        $invitation = LineSignupInvitation::where('invitation_token', $token)->first();

        if (!$invitation || !$invitation->canBeUsed()) {
            return redirect()->route('line.signup.invitation', $token)
                ->with('error', 'ลิงก์เชิญหมดอายุหรือถูกใช้งานแล้ว');
        }

        // Redirect to LINE Login with referral code
        $referralCode = $invitation->referral_code;
        $state = base64_encode(json_encode([
            'action' => 'signup',
            'referral_code' => $referralCode,
            'invitation_token' => $token,
        ]));

        return redirect()->route('auth.line.redirect', ['state' => $state]);
    }

    /**
     * Show signup progress (for debugging)
     */
    public function showProgress(Request $request, string $sessionToken)
    {
        $session = LineSignupSession::where('session_token', $sessionToken)->firstOrFail();

        return view('line.signup.progress', [
            'session' => $session,
            'steps' => $session->stepLogs()->orderBy('created_at')->get(),
            'conversations' => $session->conversations()->orderBy('created_at')->get(),
        ]);
    }

    /**
     * Get signup analytics data (for admin)
     */
    public function getAnalytics(Request $request)
    {
        $this->authorize('viewAny', LineSignupSession::class);

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $analytics = [
            'total_sessions' => LineSignupSession::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_sessions' => LineSignupSession::where('status', LineSignupSession::STATUS_COMPLETED)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'abandoned_sessions' => LineSignupSession::where('status', LineSignupSession::STATUS_ABANDONED)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'active_sessions' => LineSignupSession::where('status', LineSignupSession::STATUS_ACTIVE)->count(),
        ];

        $analytics['completion_rate'] = $analytics['total_sessions'] > 0
            ? round(($analytics['completed_sessions'] / $analytics['total_sessions']) * 100, 2)
            : 0;

        return response()->json($analytics);
    }

    /**
     * Create invitation link
     */
    public function createInvitation(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|exists:affiliates,referral_code',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = $request->user();
        $mlmMember = $user->mlmMembers()->first();

        if (!$mlmMember) {
            return response()->json(['error' => 'No MLM member account found'], 400);
        }

        $invitation = LineSignupInvitation::create([
            'inviter_user_id' => $user->id,
            'referral_code' => $request->input('referral_code', $mlmMember->member_code),
            'max_uses' => $request->input('max_uses', 1),
            'expires_at' => $request->input('expires_at'),
            'status' => LineSignupInvitation::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'invitation' => $invitation,
            'url' => $invitation->getUrl(),
        ]);
    }
}
