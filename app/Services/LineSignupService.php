<?php

namespace App\Services;

use App\Models\LineRegistrationSession;
use App\Models\LineSignupFlow;
use App\Models\MlmProspect;
use App\Models\MlmMember;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LineSignupService
{
    public function __construct(
        private LineService $lineService,
        private MlmProspectService $prospectService,
        private ConversationTimeoutService $timeoutService,
        private ValidationService $validationService,
        private DuplicateDetectionService $duplicateService,
        private AiConversationService $aiService,
        private ConversationContextService $contextService,
        private SmartResponseService $smartResponseService,
        private LineFlexMessageService $flexMessageService,
        private LineProgressService $progressService
    ) {}

    /**
     * Start signup conversation with Flex Messages
     */
    public function startConversation(MlmProspect $prospect): void
    {
        // Check if conversation is expired and can be resumed
        if ($prospect->conversation_expired) {
            $this->timeoutService->resumeConversation($prospect);
            return;
        }

        $firstStep = LineSignupFlow::getFirstStep();

        if (!$firstStep) {
            Log::error('No signup flow configured');
            $this->lineService->sendPushMessage(
                $prospect->line_user_id,
                '❌ ขออภัย ระบบการสมัครสมาชิกยังไม่พร้อมใช้งาน กรุณาติดต่อทีมงาน'
            );
            return;
        }

        // Update prospect status
        $prospect->markAsInProgress();
        $prospect->updateConversationStep($firstStep->step_key);

        // Start conversation tracking
        $this->timeoutService->startTracking($prospect);

        // Initialize progress tracking
        $this->progressService->updateProgress($prospect, 'welcome');

        // Send welcome Flex Message
        $welcomeMessage = $this->flexMessageService->createWelcomeMessage($prospect);
        $this->lineService->sendFlexMessage($prospect->line_user_id, $welcomeMessage);

        // Add to conversation history
        $this->contextService->addMessageToHistory($prospect, 'assistant', 'Welcome message sent');
    }

    /**
     * Handle user message in conversation (AI-Powered)
     */
    public function handleConversationMessage(MlmProspect $prospect, string $message): void
    {
        // Check if conversation is expired
        if ($this->timeoutService->isExpired($prospect)) {
            $this->timeoutService->expireConversation($prospect);
            return;
        }

        // Add user message to history
        $this->contextService->addMessageToHistory($prospect, 'user', $message);

        // Update conversation activity
        $this->timeoutService->updateActivity($prospect);

        // Check if user is asking for help or confused
        if ($this->aiService->isUserConfused($message)) {
            $response = $this->smartResponseService->generateResponse(
                $prospect,
                $message,
                $prospect->conversation_step
            );

            $this->lineService->sendPushMessage($prospect->line_user_id, $response['message']);
            $this->contextService->addMessageToHistory($prospect, 'assistant', $response['message']);
            return;
        }

        // Check for special actions (restart, cancel)
        $action = $this->aiService->detectUserAction($message);
        if ($action['action'] !== 'continue') {
            $response = $this->smartResponseService->generateResponse(
                $prospect,
                $message,
                $prospect->conversation_step
            );

            $this->lineService->sendPushMessage($prospect->line_user_id, $response['message']);
            $this->contextService->addMessageToHistory($prospect, 'assistant', $response['message']);
            return;
        }

        // Get current step
        $currentStep = LineSignupFlow::getByStepKey($prospect->conversation_step);

        if (!$currentStep) {
            Log::warning('Invalid conversation step', [
                'prospect_id' => $prospect->id,
                'step' => $prospect->conversation_step,
            ]);
            $this->startConversation($prospect);
            return;
        }

        // Validate input with enhanced validation
        if ($currentStep->input_type !== 'none') {
            // Use enhanced validation service
            $validation = $this->validateInputWithService($currentStep->input_type, $message);

            if (!$validation['valid']) {
                // Send validation error with suggestions if available
                $errorMessage = implode("\n", $validation['errors']);

                if (!empty($validation['suggestion'])) {
                    $errorMessage .= "\n\n💡 คุณหมายถึง: {$validation['suggestion']} ใช่ไหม?";
                }

                $this->lineService->sendPushMessage(
                    $prospect->line_user_id,
                    "❌ {$errorMessage}\n\nกรุณาลองใหม่อีกครั้ง"
                );
                return;
            }

            // Check for duplicates
            $dataKey = $this->getDataKeyFromInputType($currentStep->input_type);
            $duplicateCheck = $this->checkDuplicates($dataKey, $validation['formatted'] ?? $message);

            if ($duplicateCheck['has_duplicates']) {
                $errorMessage = implode("\n", $duplicateCheck['messages']);
                $this->lineService->sendPushMessage(
                    $prospect->line_user_id,
                    "⚠️ {$errorMessage}\n\nหากคุณเป็นเจ้าของบัญชี กรุณาเข้าสู่ระบบแทนการสมัครใหม่"
                );
                return;
            }

            // Save validated and formatted input data
            $prospect->updateConversationStep($currentStep->step_key, [
                $dataKey => $validation['formatted'] ?? $message,
            ]);
        }

        // Get next step
        $nextStep = $currentStep->getNextStepFor($prospect->conversation_data);

        if (!$nextStep) {
            // Conversation complete - create user account
            $this->completeSignup($prospect);
            return;
        }

        // ✅ Smart skip sponsor_code step ถ้ามีแม่ทีมจาก invitation link อยู่แล้ว
        if ($nextStep->step_key === 'sponsor_code' && $prospect->sponsorUser) {
            // ข้าม sponsor_code เพราะรู้ผู้แนะนำจาก invitation link แล้ว
            Log::info('Skipping sponsor_code step - sponsor already known from invitation link', [
                'prospect_id' => $prospect->id,
                'sponsor_name' => $prospect->sponsorUser->name,
            ]);

            // ไปที่ step ถัดไป (consent)
            $nextStep = $nextStep->getNextStepFor([]);

            if (!$nextStep) {
                // ไม่น่าจะเกิด แต่ป้องกันกรณี edge case
                $this->completeSignup($prospect);
                return;
            }
        }

        // Move to next step
        $prospect->updateConversationStep($nextStep->step_key);
        $this->sendFlowMessage($prospect, $nextStep);
    }

    /**
     * Send flow message to user
     */
    private function sendFlowMessage(MlmProspect $prospect, LineSignupFlow $flow): void
    {
        // Prepare variables for message
        $variables = [
            'sponsor_name' => $prospect->sponsorUser->name ?? 'ทีม',
            'prospect_name' => $prospect->line_display_name ?? 'คุณ',
        ];

        $message = $flow->getFormattedMessage($variables);

        // ✅ Smart sponsor detection - แสดงชื่อแม่ทีมถ้ามี invitation link
        if ($flow->step_key === 'welcome' && $prospect->sponsorUser) {
            $sponsorName = $prospect->sponsorUser->name;
            $sponsorInfo = "\n\n🎉 คุณได้รับเชิญจาก: {$sponsorName}\n✅ ระบบรู้ผู้แนะนำของคุณแล้ว ไม่ต้องกรอกอีกครั้ง";

            // แทรกข้อมูล sponsor ก่อนส่วน "📸 เราได้รับข้อมูลจาก LINE"
            $message = str_replace(
                "\n\n📸 เราได้รับข้อมูลจาก LINE",
                $sponsorInfo . "\n\n📸 เราได้รับข้อมูลจาก LINE",
                $message
            );
        }

        // Check if we should use Flex Message
        if ($flow->message_flex) {
            $flexMessage = [
                'type' => 'flex',
                'altText' => substr($message, 0, 100),
                'contents' => $flow->message_flex,
            ];

            $this->lineService->sendPushMessage(
                $prospect->line_user_id,
                $message,
                [$flexMessage]
            );
        } else {
            // Send text message with Quick Reply if available
            $quickReply = $flow->getQuickReplyStructure();

            if ($quickReply) {
                // LINE API doesn't support quick reply in push messages directly
                // We need to send via reply or use Flex Message instead
                // For now, just send text message
                $this->lineService->sendPushMessage($prospect->line_user_id, $message);
            } else {
                $this->lineService->sendPushMessage($prospect->line_user_id, $message);
            }
        }

        Log::info('Flow message sent', [
            'prospect_id' => $prospect->id,
            'step_key' => $flow->step_key,
        ]);
    }

    /**
     * Complete signup process - Simplified (ใช้ LINE data)
     *
     * ไม่ต้อง validate name/phone เพราะ:
     * - name มาจาก LINE displayName
     * - phone เป็น optional (ค่อยเพิ่มภายหลัง)
     */
    private function completeSignup(MlmProspect $prospect): void
    {
        try {
            DB::beginTransaction();

            $data = $prospect->conversation_data ?? [];

            // ไม่ต้อง validate แล้ว - ใช้ LINE data เป็นหลัก
            // LINE displayName จะเป็น name อัตโนมัติ

            // Create user (ใช้ LINE data)
            $user = $this->createUser($prospect, $data);

            // Create MLM member
            $this->createMlmMember($user, $prospect);

            // Mark prospect as completed
            $this->prospectService->completeRegistration($prospect, $user);

            DB::commit();

            // Clear rate limit attempts after successful signup
            \App\Http\Middleware\LineSignupThrottle::clearAttempts(
                $prospect->line_user_id,
                request()->ip()
            );

            // Send success message
            $this->sendSuccessMessage($prospect, $user);

            // ✅ อัพเดท LineRegistrationSession (ถ้ามี)
            // สำหรับหน้าเว็บที่ polling อยู่จะได้รับทราบว่าสมัครเสร็จแล้ว
            $this->updateRegistrationSession($prospect->line_user_id, $user->id);

            Log::info('Signup completed successfully', [
                'prospect_id' => $prospect->id,
                'user_id' => $user->id,
                'rate_limits_cleared' => true,
                'registration_session_updated' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Signup completion failed', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);

            $this->lineService->sendPushMessage(
                $prospect->line_user_id,
                "❌ เกิดข้อผิดพลาดในการสมัครสมาชิก\n\nกรุณาติดต่อทีมงานหรือลองใหม่อีกครั้ง"
            );
        }
    }

    /**
     * Create user account - ใช้ข้อมูล LINE เป็นหลัก
     *
     * ข้อมูลจาก LINE:
     * - displayName → name
     * - pictureUrl → profile picture
     * - line_user_id → unique identifier
     *
     * ข้อมูลที่ generate:
     * - email: line_{line_user_id}@thaiprompt.local
     * - password: random 16 ตัวอักษร
     *
     * ข้อมูลที่เป็น optional:
     * - phone (ค่อยกรอกภายหลัง)
     * - address (ค่อยกรอกภายหลัง)
     */
    private function createUser(MlmProspect $prospect, array $data): User
    {
        // ใช้ชื่อจาก LINE displayName เป็นหลัก
        $name = $prospect->line_display_name ?? $data['name'] ?? 'User';

        // ใช้ email จาก conversation_data (ถ้ามี) หรือ generate
        $email = $data['email'] ?? 'line_' . $prospect->line_user_id . '@thaiprompt.local';

        // Phone เป็น optional (ค่อยเพิ่มภายหลัง)
        $phone = $data['phone'] ?? null;

        // Generate password สำหรับ login แบบปกติ (ถ้าไม่ใช้ LINE Login)
        $password = Str::random(16);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            // LINE data
            'line_user_id' => $prospect->line_user_id,
            'line_display_name' => $prospect->line_display_name,
            'line_picture_url' => $prospect->line_picture_url, // Avatar จาก LINE
            'line_verified' => true,
            'line_linked_at' => now(),
        ]);

        // บันทึก LINE pictureUrl เป็น profile picture (ถ้ามี)
        if ($prospect->line_picture_url) {
            // อัพเดท avatar_url field (ถ้า User model มี field นี้)
            if (Schema::hasColumn('users', 'avatar_url')) {
                $user->update(['avatar_url' => $prospect->line_picture_url]);
            }
        }

        Log::info('User created via LINE signup (simplified flow)', [
            'user_id' => $user->id,
            'prospect_id' => $prospect->id,
            'name_source' => $prospect->line_display_name ? 'LINE' : 'manual',
            'has_phone' => $phone !== null,
            'has_avatar' => $prospect->line_picture_url !== null,
        ]);

        return $user;
    }

    /**
     * Create MLM member พร้อม auto-placement
     *
     * รองรับการสมัครแบบ:
     * 1. มี sponsor (จาก invitation link) → ใช้ sponsor ที่ระบุ
     * 2. มี sponsor_code (กรอกระหว่างสมัคร) → ใช้ sponsor จาก code
     * 3. ไม่มี sponsor → ใช้ Super Admin + auto-placement
     */
    private function createMlmMember(User $user, MlmProspect $prospect): MlmMember
    {
        // ตรวจสอบ sponsor
        $sponsor = $prospect->sponsorMember;

        // ถ้าไม่มี sponsor จาก invitation link → ตรวจสอบ sponsor_code
        if (!$sponsor) {
            $sponsorCode = $prospect->conversation_data['sponsor_code'] ?? null;

            if ($sponsorCode && $sponsorCode !== 'ข้าม') {
                // ค้นหา sponsor จาก member_code
                $sponsorMember = MlmMember::where('member_code', $sponsorCode)
                    ->where('status', 'active')
                    ->first();

                if ($sponsorMember) {
                    $sponsor = $sponsorMember;
                    Log::info('Found sponsor from code', [
                        'sponsor_code' => $sponsorCode,
                        'sponsor_member_id' => $sponsor->id,
                    ]);
                } else {
                    Log::warning('Invalid sponsor code', [
                        'sponsor_code' => $sponsorCode,
                    ]);
                    // ถ้า code ไม่ถูกต้อง → ใช้ Super Admin fallback
                }
            }
        }

        // ถ้ายังไม่มี sponsor → หา Super Admin (user_id = 1)
        if (!$sponsor) {
            $superAdmin = User::find(1);
            if (!$superAdmin) {
                throw new \Exception('Super Admin not found (user_id = 1)');
            }

            // หา MLM member ของ Super Admin
            $sponsor = MlmMember::where('user_id', $superAdmin->id)->first();

            if (!$sponsor) {
                throw new \Exception('Super Admin MLM member not found');
            }

            Log::info('No sponsor found, using Super Admin', [
                'super_admin_id' => $superAdmin->id,
                'sponsor_member_id' => $sponsor->id,
            ]);
        }

        // Get default MLM plan
        $defaultPlan = \App\Models\MlmPlan::where('is_default', true)->first();

        if (!$defaultPlan) {
            throw new \Exception('No default MLM plan found');
        }

        // หาตำแหน่ง binary แบบ auto-placement
        $binaryService = app(MlmBinaryService::class);
        $placement = $binaryService->findPlacementPosition($sponsor);

        // Create MLM member พร้อม binary placement
        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $defaultPlan->id,
            // Unilevel structure
            'unilevel_sponsor_id' => $sponsor->id,
            'unilevel_level' => $sponsor->unilevel_level + 1,
            'unilevel_path' => $sponsor->unilevel_path . '/' . $sponsor->id,
            // Binary structure (auto-placement)
            'binary_sponsor_id' => $sponsor->id,
            'binary_parent_id' => $placement['parent_id'],
            'binary_position' => $placement['position'],
            // Status
            'status' => 'active',
            'joined_at' => now(),
            'member_code' => MlmMember::generateMemberCode(),
            'is_qualified' => true,
        ]);

        // Update sponsor's direct referral count
        $sponsor->increment('total_direct_referrals');

        // Update binary parent's leg members count
        if ($placement['position'] === 'left') {
            MlmMember::find($placement['parent_id'])->increment('left_leg_members');
        } else {
            MlmMember::find($placement['parent_id'])->increment('right_leg_members');
        }

        Log::info('MLM member created with auto-placement', [
            'member_id' => $member->id,
            'sponsor_id' => $sponsor->id,
            'binary_parent_id' => $placement['parent_id'],
            'binary_position' => $placement['position'],
            'had_prospect_sponsor' => $prospect->sponsorMember !== null,
        ]);

        return $member;
    }

    /**
     * Send success message - พร้อมลิงก์ LINE Login dashboard
     */
    private function sendSuccessMessage(MlmProspect $prospect, User $user): void
    {
        // ดึง MLM member
        $member = $user->mlmMember;
        $sponsor = $prospect->sponsorMember ?? MlmMember::where('user_id', 1)->first();

        // สร้างลิงก์ LINE Login
        $loginUrl = route('line.login');

        $flexMessage = [
            'type' => 'flex',
            'altText' => '🎉 สมัครสมาชิกสำเร็จ!',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎉',
                            'size' => '5xl',
                            'align' => 'center',
                        ],
                    ],
                    'backgroundColor' => '#06C755',
                    'paddingAll' => 'xl',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'สมัครสมาชิกสำเร็จ!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#06C755',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ยินดีต้อนรับ ' . $user->name,
                            'size' => 'sm',
                            'color' => '#999999',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '🆔',
                                            'size' => 'sm',
                                            'flex' => 1,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $member->member_code ?? 'N/A',
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        [
                                            'type' => 'text',
                                            'text' => '👥',
                                            'size' => 'sm',
                                            'flex' => 1,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $sponsor->user->name ?? 'Super Admin',
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => '📝 อย่าลืมเพิ่มข้อมูล:',
                            'size' => 'sm',
                            'color' => '#aaaaaa',
                            'margin' => 'lg',
                        ],
                        [
                            'type' => 'text',
                            'text' => '• เบอร์โทรศัพท์\n• ที่อยู่จัดส่ง\n• ข้อมูลธนาคาร',
                            'size' => 'xs',
                            'color' => '#999999',
                            'margin' => 'sm',
                            'wrap' => true,
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'color' => '#06C755',
                            'action' => [
                                'type' => 'uri',
                                'label' => '🚀 เข้าสู่ระบบด้วย LINE',
                                'uri' => $loginUrl,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => '✅ เข้าได้ทันที ไม่ต้องรหัสผ่าน',
                            'size' => 'xxs',
                            'color' => '#999999',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                    ],
                ],
            ],
        ];

        $this->lineService->sendPushMessage(
            $prospect->line_user_id,
            '🎉 สมัครสมาชิกสำเร็จ!',
            [$flexMessage]
        );

        // ส่งลิงก์ดาวน์โหลดแอพ (ถ้ามี)
        $this->sendAppDownloadMessage($prospect->line_user_id);

        // Send sponsor friend add request
        $this->prospectService->sendSponsorFriendRequest($prospect, $this->lineService);
    }

    /**
     * ส่งข้อความลิงก์ดาวน์โหลดแอพ
     *
     * ดึงลิงก์จาก SiteSetting:
     * - APK: app_apk_url (ถ้า app_apk_enabled = true)
     * - Play Store: app_playstore_url (ถ้า app_playstore_enabled = true)
     * - App Store: app_appstore_url (ถ้า app_appstore_enabled = true)
     *
     * ถ้าไม่มีลิงก์ใดๆ → ส่งข้อความแนะนำดาวน์โหลดเฉยๆ
     *
     * @param string $lineUserId
     * @return void
     */
    private function sendAppDownloadMessage(string $lineUserId): void
    {
        try {
            $settings = SiteSetting::getSetting();

            // ตรวจสอบว่าเปิดใช้งาน section ดาวน์โหลดหรือไม่
            if (!$settings->app_download_enabled) {
                return; // ไม่แสดงอะไร
            }

            $appName = $settings->app_name ?? 'TP Ultra';
            $hasAnyLink = false;
            $downloadButtons = [];

            // APK Direct Download
            if ($settings->app_apk_enabled && !empty($settings->app_apk_url)) {
                $hasAnyLink = true;
                $downloadButtons[] = [
                    'type' => 'button',
                    'style' => 'primary',
                    'color' => '#00C8FF',
                    'action' => [
                        'type' => 'uri',
                        'label' => '📱 ดาวน์โหลด APK',
                        'uri' => $settings->app_apk_url,
                    ],
                ];
            }

            // Google Play Store
            if ($settings->app_playstore_enabled && !empty($settings->app_playstore_url)) {
                $hasAnyLink = true;
                $downloadButtons[] = [
                    'type' => 'button',
                    'style' => 'secondary',
                    'action' => [
                        'type' => 'uri',
                        'label' => '▶️ Play Store',
                        'uri' => $settings->app_playstore_url,
                    ],
                ];
            }

            // Apple App Store
            if ($settings->app_appstore_enabled && !empty($settings->app_appstore_url)) {
                $hasAnyLink = true;
                $downloadButtons[] = [
                    'type' => 'button',
                    'style' => 'secondary',
                    'action' => [
                        'type' => 'uri',
                        'label' => '🍎 App Store',
                        'uri' => $settings->app_appstore_url,
                    ],
                ];
            }

            // ถ้ามีลิงก์อย่างน้อย 1 ลิงก์ → สร้าง Flex Message
            if ($hasAnyLink) {
                $appDescription = $settings->app_description ?? 'แอปพลิเคชั่นสำหรับจัดการธุรกิจ Affiliate ทุกที่ทุกเวลา';

                $flexMessage = [
                    'type' => 'flex',
                    'altText' => "📲 ดาวน์โหลดแอพ {$appName}",
                    'contents' => [
                        'type' => 'bubble',
                        'hero' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '📲',
                                    'size' => '4xl',
                                    'align' => 'center',
                                ],
                            ],
                            'backgroundColor' => '#0099FF',
                            'paddingAll' => 'lg',
                        ],
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => "ดาวน์โหลดแอพ {$appName}",
                                    'weight' => 'bold',
                                    'size' => 'lg',
                                    'color' => '#0099FF',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $appDescription,
                                    'size' => 'sm',
                                    'color' => '#666666',
                                    'wrap' => true,
                                    'margin' => 'md',
                                ],
                                [
                                    'type' => 'separator',
                                    'margin' => 'lg',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✨ ฟีเจอร์เด่น:',
                                    'size' => 'sm',
                                    'color' => '#888888',
                                    'margin' => 'lg',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '• ดูยอดรายได้และคอมมิชชั่น\n• ตรวจสอบสายงานและทีม\n• รับการแจ้งเตือนทันที\n• ถอนเงินได้ง่าย',
                                    'size' => 'xs',
                                    'color' => '#999999',
                                    'wrap' => true,
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        'footer' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => $downloadButtons,
                        ],
                    ],
                ];

                $this->lineService->sendPushMessage(
                    $lineUserId,
                    "📲 แนะนำดาวน์โหลดแอพ {$appName}",
                    [$flexMessage]
                );

                Log::info('App download message sent', [
                    'line_user_id' => $lineUserId,
                    'app_name' => $appName,
                    'has_apk' => $settings->app_apk_enabled && !empty($settings->app_apk_url),
                    'has_playstore' => $settings->app_playstore_enabled && !empty($settings->app_playstore_url),
                    'has_appstore' => $settings->app_appstore_enabled && !empty($settings->app_appstore_url),
                ]);
            } else {
                // ไม่มีลิงก์ → ส่งข้อความแนะนำเฉยๆ
                $this->lineService->sendPushMessage(
                    $lineUserId,
                    "📲 แนะนำดาวน์โหลดแอพ {$appName}\n\n" .
                    "เพื่อความสะดวกในการจัดการธุรกิจ Affiliate ของคุณ\n" .
                    "สามารถดาวน์โหลดแอพได้จาก Play Store หรือ App Store เร็วๆ นี้\n\n" .
                    "ติดตามข่าวสารได้ที่ LINE OA นี้"
                );

                Log::info('App download recommendation sent (no links)', [
                    'line_user_id' => $lineUserId,
                    'app_name' => $appName,
                ]);
            }
        } catch (\Exception $e) {
            // ไม่ให้ error กระทบการสมัครหลัก
            Log::warning('Failed to send app download message', [
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get data key from input type
     */
    private function getDataKeyFromInputType(string $inputType): string
    {
        return match($inputType) {
            'name' => 'name',
            'phone' => 'phone',
            'email' => 'email',
            'confirm' => 'confirmed',
            default => 'response_' . $inputType,
        };
    }

    /**
     * Reset conversation
     */
    public function resetConversation(MlmProspect $prospect): void
    {
        $prospect->update([
            'conversation_step' => null,
            'conversation_data' => null,
            'status' => 'pending',
        ]);

        $this->lineService->sendPushMessage(
            $prospect->line_user_id,
            "🔄 รีเซ็ตการสนทนาเรียบร้อย\n\nพิมพ์ 'เริ่ม' เพื่อเริ่มสมัครสมาชิกใหม่"
        );
    }

    /**
     * Validate input using enhanced validation service
     *
     * @param string $inputType
     * @param string $value
     * @return array
     */
    private function validateInputWithService(string $inputType, string $value): array
    {
        return match($inputType) {
            'phone' => $this->validationService->validateThaiPhone($value),
            'email' => $this->validationService->validateEmail($value, true),
            'name' => $this->validationService->validateThaiName($value),
            'id_card' => $this->validationService->validateThaiIdCard($value),
            'address' => $this->validationService->validateThaiAddress($value),
            'postal_code' => $this->validationService->validateThaiPostalCode($value),
            'text' => $this->validateSponsorCode($value), // สำหรับ sponsor_code
            default => ['valid' => true, 'formatted' => $value, 'errors' => []],
        };
    }

    /**
     * Validate sponsor code
     *
     * ตรวจสอบรหัสผู้แนะนำ:
     * - ถ้าเป็น "ข้าม" → valid (ไม่มี sponsor)
     * - ถ้ากรอกรหัส → ตรวจสอบว่ามี member_code นี้หรือไม่
     *
     * @param string $value
     * @return array
     */
    private function validateSponsorCode(string $value): array
    {
        $trimmed = trim($value);

        // ถ้าเป็น "ข้าม" หรือคำที่คล้ายกัน → valid
        $skipKeywords = ['ข้าม', 'skip', 'ไม่มี', 'no', 'none', 'pass'];
        foreach ($skipKeywords as $keyword) {
            if (mb_stripos($trimmed, $keyword) !== false) {
                return [
                    'valid' => true,
                    'formatted' => 'ข้าม',
                    'errors' => [],
                    'message' => '✅ ข้ามการกรอกรหัสผู้แนะนำ ระบบจะจัดทีมให้อัตโนมัติ',
                ];
            }
        }

        // ตรวจสอบความยาว
        if (mb_strlen($trimmed) < 3 || mb_strlen($trimmed) > 50) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['รหัสผู้แนะนำต้องมีความยาว 3-50 ตัวอักษร'],
            ];
        }

        // ค้นหา sponsor จาก member_code
        $sponsor = MlmMember::where('member_code', $trimmed)
            ->where('status', 'active')
            ->first();

        if (!$sponsor) {
            return [
                'valid' => false,
                'formatted' => null,
                'errors' => ['❌ ไม่พบรหัสผู้แนะนำนี้ในระบบ กรุณาตรวจสอบอีกครั้ง'],
                'suggestion' => 'หากไม่มีรหัสผู้แนะนำ สามารถกด "ข้าม" ได้',
            ];
        }

        // พบ sponsor → valid
        return [
            'valid' => true,
            'formatted' => $trimmed,
            'errors' => [],
            'message' => "✅ พบผู้แนะนำ: {$sponsor->user->name}",
        ];
    }

    /**
     * Check for duplicate data
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    private function checkDuplicates(string $field, string $value): array
    {
        $data = [$field => $value];
        return $this->duplicateService->checkAllDuplicates($data);
    }

    /**
     * อัพเดท LineRegistrationSession เมื่อสมัครเสร็จ
     *
     * ใช้สำหรับ update สถานะ session ของหน้าเว็บ
     * เพื่อให้หน้าเว็บที่ polling อยู่สามารถ redirect ไปหน้าอัพเดทข้อมูลได้อัตโนมัติ
     *
     * @param string $lineUserId LINE User ID ของผู้สมัคร
     * @param int $userId User ID ที่สร้างใหม่
     * @return void
     */
    private function updateRegistrationSession(string $lineUserId, int $userId): void
    {
        try {
            // ตรวจสอบว่า table มีอยู่หรือไม่ (เผื่อยังไม่ได้ migrate)
            if (!Schema::hasTable('line_registration_sessions')) {
                return;
            }

            // ค้นหา session ที่ active อยู่สำหรับ LINE User ID นี้
            $session = LineRegistrationSession::where('line_user_id', $lineUserId)
                ->whereIn('status', [
                    LineRegistrationSession::STATUS_PENDING,
                    LineRegistrationSession::STATUS_FOLLOWED,
                    LineRegistrationSession::STATUS_IN_PROGRESS,
                ])
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($session) {
                // อัพเดทสถานะเป็น completed
                $session->markAsCompleted($userId);

                Log::info('LineRegistrationSession updated to completed', [
                    'session_id' => $session->id,
                    'session_token' => $session->session_token,
                    'line_user_id' => $lineUserId,
                    'user_id' => $userId,
                ]);
            } else {
                Log::info('No active LineRegistrationSession found for LINE user', [
                    'line_user_id' => $lineUserId,
                    'user_id' => $userId,
                ]);
            }
        } catch (\Exception $e) {
            // ไม่ให้ error นี้กระทบการสมัครหลัก
            Log::warning('Failed to update LineRegistrationSession', [
                'line_user_id' => $lineUserId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
