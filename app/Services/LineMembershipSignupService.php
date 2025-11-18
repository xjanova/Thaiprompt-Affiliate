<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\LineSignupConversation;
use App\Models\LineSignupInvitation;
use App\Models\LineSignupReward;
use App\Models\LineSignupSession;
use App\Models\LineSignupStepLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineMembershipSignupService
{
    protected $lineService;
    protected $lineFlexMessageService;
    protected $lineBotAiService;

    public function __construct(
        LineService $lineService,
        LineFlexMessageService $lineFlexMessageService,
        LineBotAiService $lineBotAiService
    ) {
        $this->lineService = $lineService;
        $this->lineFlexMessageService = $lineFlexMessageService;
        $this->lineBotAiService = $lineBotAiService;
    }

    /**
     * Start a new signup session
     */
    public function startSignupSession(string $lineUserId, ?string $referralCode = null): LineSignupSession
    {
        // Check for existing active session
        $existingSession = LineSignupSession::where('line_user_id', $lineUserId)
            ->where('status', LineSignupSession::STATUS_ACTIVE)
            ->first();

        if ($existingSession) {
            // Resume existing session
            $existingSession->last_activity_at = now();
            $existingSession->save();
            return $existingSession;
        }

        // Get LINE profile
        $lineProfile = $this->lineService->getProfile($lineUserId);

        // Create new session
        $session = LineSignupSession::create([
            'line_user_id' => $lineUserId,
            'session_token' => Str::random(32),
            'current_step' => 'welcome',
            'status' => LineSignupSession::STATUS_ACTIVE,
            'language' => 'th',
            'started_at' => now(),
            'last_activity_at' => now(),
            'collected_data' => [
                'line_profile' => $lineProfile,
            ],
        ]);

        // If referral code provided, store it
        if ($referralCode) {
            $this->processReferralCode($session, $referralCode);
        }

        // Log step
        $this->logStep($session, 'welcome', LineSignupStepLog::STATUS_STARTED);

        return $session;
    }

    /**
     * Process incoming LINE message for signup
     */
    public function processSignupMessage(string $lineUserId, array $message): array
    {
        // Get or create session
        $session = $this->getOrCreateSession($lineUserId);

        // Log conversation
        $this->logConversation($session, LineSignupConversation::ROLE_USER, $message['text'] ?? '', [
            'line_message_payload' => $message,
        ]);

        // Process based on current step
        $response = $this->processStep($session, $message);

        // Log AI response
        $this->logConversation($session, LineSignupConversation::ROLE_ASSISTANT, $response['message'] ?? '', [
            'step' => $session->current_step,
        ]);

        return $response;
    }

    /**
     * Process current step
     */
    protected function processStep(LineSignupSession $session, array $message): array
    {
        $currentStep = $session->current_step;

        switch ($currentStep) {
            case 'welcome':
                return $this->handleWelcomeStep($session, $message);

            case 'name':
                return $this->handleNameStep($session, $message);

            case 'email':
                return $this->handleEmailStep($session, $message);

            case 'phone':
                return $this->handlePhoneStep($session, $message);

            case 'otp':
                return $this->handleOtpStep($session, $message);

            case 'password':
                return $this->handlePasswordStep($session, $message);

            case 'referral':
                return $this->handleReferralStep($session, $message);

            case 'confirmation':
                return $this->handleConfirmationStep($session, $message);

            default:
                return $this->getAiResponse($session, $message);
        }
    }

    /**
     * Handle welcome step
     */
    protected function handleWelcomeStep(LineSignupSession $session, array $message): array
    {
        $lineProfile = $session->getCollectedData('line_profile');
        $displayName = $lineProfile['displayName'] ?? 'คุณ';

        // Move to name step
        $session->moveToNextStep();
        $this->logStep($session, 'welcome', LineSignupStepLog::STATUS_COMPLETED);
        $this->logStep($session, 'name', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildNameInputMessage($displayName),
        ];
    }

    /**
     * Handle name step
     */
    protected function handleNameStep(LineSignupSession $session, array $message): array
    {
        $name = trim($message['text'] ?? '');

        // Validate name
        if (empty($name) || strlen($name) < 2) {
            return [
                'type' => 'text',
                'message' => '❌ กรุณากรอกชื่อให้ถูกต้อง (อย่างน้อย 2 ตัวอักษร)',
            ];
        }

        // Use AI to validate and enhance
        $aiValidation = $this->validateNameWithAi($name);

        if (!$aiValidation['valid']) {
            return [
                'type' => 'text',
                'message' => $aiValidation['message'],
            ];
        }

        // Save name
        $session->updateCollectedData('name', $name);
        $this->logStep($session, 'name', LineSignupStepLog::STATUS_COMPLETED, ['name' => $name]);

        // Move to email step
        $session->moveToNextStep();
        $this->logStep($session, 'email', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildEmailInputMessage($name),
        ];
    }

    /**
     * Handle email step
     */
    protected function handleEmailStep(LineSignupSession $session, array $message): array
    {
        $email = trim(strtolower($message['text'] ?? ''));

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'type' => 'text',
                'message' => '❌ รูปแบบอีเมลไม่ถูกต้อง กรุณากรอกใหม่อีกครั้ง',
            ];
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return [
                'type' => 'text',
                'message' => '❌ อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น หรือ Login ด้วย LINE แทน',
            ];
        }

        // Save email
        $session->updateCollectedData('email', $email);
        $this->logStep($session, 'email', LineSignupStepLog::STATUS_COMPLETED, ['email' => $email]);

        // Move to phone step
        $session->moveToNextStep();
        $this->logStep($session, 'phone', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildPhoneInputMessage(),
        ];
    }

    /**
     * Handle phone step
     */
    protected function handlePhoneStep(LineSignupSession $session, array $message): array
    {
        $phone = $this->normalizePhoneNumber($message['text'] ?? '');

        // Validate phone
        if (!$this->isValidThaiPhone($phone)) {
            return [
                'type' => 'text',
                'message' => '❌ หมายเลขโทรศัพท์ไม่ถูกต้อง กรุณากรอกเบอร์ 10 หลัก เช่น 0812345678',
            ];
        }

        // Check if phone already exists
        if (User::where('phone', $phone)->exists()) {
            return [
                'type' => 'text',
                'message' => '❌ หมายเลขนี้ถูกใช้งานแล้ว กรุณาใช้หมายเลขอื่น',
            ];
        }

        // Save phone
        $session->updateCollectedData('phone', $phone);
        $this->logStep($session, 'phone', LineSignupStepLog::STATUS_COMPLETED, ['phone' => $phone]);

        // Generate and send OTP
        $otp = $this->generateOtp();
        $session->otp_code = $otp;
        $session->otp_expires_at = now()->addMinutes(5);
        $session->otp_attempts = 0;
        $session->save();

        // Send OTP via LINE (in real app, send via SMS)
        $this->sendOtpMessage($session->line_user_id, $otp);

        // Move to OTP step
        $session->moveToNextStep();
        $this->logStep($session, 'otp', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildOtpVerificationMessage($phone),
        ];
    }

    /**
     * Handle OTP step
     */
    protected function handleOtpStep(LineSignupSession $session, array $message): array
    {
        $otp = trim($message['text'] ?? '');

        // Check remaining attempts
        if ($session->getRemainingOtpAttempts() <= 0) {
            return [
                'type' => 'text',
                'message' => '❌ คุณพยายามยืนยัน OTP เกินจำนวนครั้งที่กำหนด กรุณาขอ OTP ใหม่',
            ];
        }

        // Validate OTP
        if (!$session->isOtpValid($otp)) {
            $session->incrementOtpAttempts();
            $remaining = $session->getRemainingOtpAttempts();

            return [
                'type' => 'text',
                'message' => "❌ รหัส OTP ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง (เหลือ {$remaining} ครั้ง)",
            ];
        }

        // OTP valid
        $session->resetOtpAttempts();
        $session->updateCollectedData('phone_verified', true);
        $this->logStep($session, 'otp', LineSignupStepLog::STATUS_COMPLETED);

        // Move to password step
        $session->moveToNextStep();
        $this->logStep($session, 'password', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildPasswordInputMessage(),
        ];
    }

    /**
     * Handle password step
     */
    protected function handlePasswordStep(LineSignupSession $session, array $message): array
    {
        $password = $message['text'] ?? '';

        // Validate password strength
        if (strlen($password) < 8) {
            return [
                'type' => 'text',
                'message' => '❌ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            ];
        }

        // Save password (hashed)
        $session->updateCollectedData('password', Hash::make($password));
        $this->logStep($session, 'password', LineSignupStepLog::STATUS_COMPLETED);

        // Move to referral step
        $session->moveToNextStep();
        $this->logStep($session, 'referral', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildReferralInputMessage(),
        ];
    }

    /**
     * Handle referral step
     */
    protected function handleReferralStep(LineSignupSession $session, array $message): array
    {
        $referralCode = trim(strtoupper($message['text'] ?? ''));

        // Allow skip
        if ($referralCode === 'SKIP' || $referralCode === 'ข้าม' || empty($referralCode)) {
            $session->updateCollectedData('skip_referral', true);
            $this->logStep($session, 'referral', LineSignupStepLog::STATUS_SKIPPED);
        } else {
            // Validate referral code
            $result = $this->processReferralCode($session, $referralCode);

            if (!$result['valid']) {
                return [
                    'type' => 'text',
                    'message' => $result['message'],
                ];
            }

            $this->logStep($session, 'referral', LineSignupStepLog::STATUS_COMPLETED, [
                'referral_code' => $referralCode,
            ]);
        }

        // Move to confirmation step
        $session->moveToNextStep();
        $this->logStep($session, 'confirmation', LineSignupStepLog::STATUS_STARTED);

        return [
            'type' => 'flex',
            'message' => $this->lineFlexMessageService->buildConfirmationMessage($session),
        ];
    }

    /**
     * Handle confirmation step
     */
    protected function handleConfirmationStep(LineSignupSession $session, array $message): array
    {
        $response = strtolower(trim($message['text'] ?? ''));

        // Check confirmation
        if (!in_array($response, ['confirm', 'ยืนยัน', 'yes', 'ใช่'])) {
            return [
                'type' => 'text',
                'message' => 'กรุณากดยืนยัน หรือพิมพ์ "ยืนยัน" เพื่อดำเนินการต่อ',
            ];
        }

        // Create user account
        try {
            $user = $this->createUserAccount($session);

            // Grant rewards
            $this->grantSignupRewards($session, $user);

            // Mark session as completed
            $session->markAsCompleted();
            $this->logStep($session, 'confirmation', LineSignupStepLog::STATUS_COMPLETED);

            // Send welcome message
            $this->sendWelcomeMessage($user);

            return [
                'type' => 'flex',
                'message' => $this->lineFlexMessageService->buildSuccessMessage($user),
            ];
        } catch (\Exception $e) {
            Log::error('Signup completion failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'text',
                'message' => '❌ เกิดข้อผิดพลาดในการสร้างบัญชี กรุณาติดต่อผู้ดูแลระบบ',
            ];
        }
    }

    /**
     * Create user account from session data
     */
    protected function createUserAccount(LineSignupSession $session): User
    {
        return DB::transaction(function () use ($session) {
            $data = $session->collected_data;
            $lineProfile = $data['line_profile'] ?? [];

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'user',
                'status' => 'active',
                'line_user_id' => $session->line_user_id,
                'line_display_name' => $lineProfile['displayName'] ?? null,
                'line_picture_url' => $lineProfile['pictureUrl'] ?? null,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]);

            // Create MLM member account
            $parentMember = null;
            if (!empty($data['referral_code'])) {
                $parentMember = MlmMember::where('member_code', $data['referral_code'])->first();
            }

            if (!$parentMember) {
                // Use default sponsor (admin)
                $parentMember = MlmMember::where('user_id', 1)->first();
            }

            // Get default MLM plan
            $defaultPlan = \App\Models\MlmPlan::where('is_default', true)->first();
            if (!$defaultPlan) {
                $defaultPlan = \App\Models\MlmPlan::first();
            }

            $mlmMember = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $defaultPlan?->id,
                'unilevel_sponsor_id' => $parentMember?->id,
                'unilevel_level' => $parentMember ? $parentMember->unilevel_level + 1 : 1,
                'unilevel_path' => $parentMember ? $parentMember->unilevel_path . '/' . $parentMember->id : '/' . $user->id,
                'member_code' => $this->generateUniqueReferralCode(),
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now(),
            ]);

            // Update session
            $session->user_id = $user->id;
            $session->save();

            return $user;
        });
    }

    /**
     * Grant signup rewards
     */
    protected function grantSignupRewards(LineSignupSession $session, User $user): void
    {
        // Welcome bonus
        LineSignupReward::create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'reward_type' => LineSignupReward::TYPE_POINTS,
            'reward_name' => 'Welcome Bonus',
            'reward_amount' => 100,
            'reward_description' => 'ยินดีต้อนรับสู่ระบบ! รับคะแนนสะสม 100 คะแนน',
            'status' => LineSignupReward::STATUS_GRANTED,
            'granted_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Send welcome message
     */
    protected function sendWelcomeMessage(User $user): void
    {
        $message = $this->lineFlexMessageService->buildWelcomeSequenceMessage($user);
        $this->lineService->pushMessage($user->line_user_id, $message);
    }

    /**
     * Validate name with AI
     */
    protected function validateNameWithAi(string $name): array
    {
        // Simple validation for now
        if (preg_match('/[0-9]/', $name)) {
            return [
                'valid' => false,
                'message' => '❌ ชื่อไม่ควรมีตัวเลข กรุณากรอกชื่อจริงของคุณ',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process referral code
     */
    protected function processReferralCode(LineSignupSession $session, string $referralCode): array
    {
        $mlmMember = MlmMember::where('member_code', $referralCode)
            ->where('is_qualified', true)
            ->first();

        if (!$mlmMember) {
            return [
                'valid' => false,
                'message' => '❌ ไม่พบรหัสผู้แนะนำนี้ กรุณาตรวจสอบอีกครั้ง หรือพิมพ์ "ข้าม" เพื่อข้ามขั้นตอนนี้',
            ];
        }

        $session->updateCollectedData('referral_code', $referralCode);
        $session->updateCollectedData('parent_member_id', $mlmMember->id);

        return [
            'valid' => true,
            'mlmMember' => $mlmMember,
        ];
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with +66, replace with 0
        if (str_starts_with($phone, '66')) {
            $phone = '0' . substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Validate Thai phone number
     */
    protected function isValidThaiPhone(string $phone): bool
    {
        return preg_match('/^0[0-9]{9}$/', $phone);
    }

    /**
     * Generate OTP
     */
    protected function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP message
     */
    protected function sendOtpMessage(string $lineUserId, string $otp): void
    {
        $message = "🔐 รหัส OTP ของคุณคือ: {$otp}\n\nรหัสนี้จะหมดอายุใน 5 นาที\n\n⚠️ ห้ามแชร์รหัสนี้กับผู้อื่น";

        $this->lineService->pushMessage($lineUserId, [
            'type' => 'text',
            'text' => $message,
        ]);
    }

    /**
     * Generate unique referral code
     */
    protected function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (MlmMember::where('member_code', $code)->exists());

        return $code;
    }

    /**
     * Get or create session
     */
    protected function getOrCreateSession(string $lineUserId): LineSignupSession
    {
        $session = LineSignupSession::where('line_user_id', $lineUserId)
            ->where('status', LineSignupSession::STATUS_ACTIVE)
            ->first();

        if (!$session) {
            $session = $this->startSignupSession($lineUserId);
        }

        return $session;
    }

    /**
     * Log step
     */
    protected function logStep(LineSignupSession $session, string $stepName, string $status, array $data = []): void
    {
        LineSignupStepLog::create([
            'session_id' => $session->id,
            'step_name' => $stepName,
            'status' => $status,
            'step_data' => $data,
            'started_at' => now(),
            'completed_at' => $status === LineSignupStepLog::STATUS_COMPLETED ? now() : null,
        ]);
    }

    /**
     * Log conversation
     */
    protected function logConversation(LineSignupSession $session, string $role, string $message, array $metadata = []): void
    {
        LineSignupConversation::create([
            'session_id' => $session->id,
            'role' => $role,
            'message' => $message,
            'metadata' => $metadata,
            'message_type' => LineSignupConversation::TYPE_TEXT,
        ]);
    }

    /**
     * Get AI response for general queries
     */
    protected function getAiResponse(LineSignupSession $session, array $message): array
    {
        // Use existing AI bot service
        $prompt = "You are a helpful signup assistant. The user is in the middle of signing up. Current step: {$session->current_step}. Help them with their question: " . ($message['text'] ?? '');

        $aiResponse = $this->lineBotAiService->generateResponse($prompt);

        return [
            'type' => 'text',
            'message' => $aiResponse,
        ];
    }
}
