<?php

namespace App\Services;

use App\Models\LineSignupFlow;
use App\Models\MlmProspect;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Affiliate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineSignupService
{
    public function __construct(
        private LineService $lineService,
        private MlmProspectService $prospectService,
        private ConversationTimeoutService $timeoutService
    ) {}

    /**
     * Start signup conversation
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

        // Send first message
        $this->sendFlowMessage($prospect, $firstStep);
    }

    /**
     * Handle user message in conversation
     */
    public function handleConversationMessage(MlmProspect $prospect, string $message): void
    {
        // Check if conversation is expired
        if ($this->timeoutService->isExpired($prospect)) {
            $this->timeoutService->expireConversation($prospect);
            return;
        }

        // Update conversation activity
        $this->timeoutService->updateActivity($prospect);

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

        // Validate input
        if ($currentStep->input_type !== 'none') {
            $validation = $currentStep->validateInput($message);

            if (!$validation['valid']) {
                // Send validation error
                $errorMessage = implode("\n", $validation['errors']);
                $this->lineService->sendPushMessage(
                    $prospect->line_user_id,
                    "❌ {$errorMessage}\n\nกรุณาลองใหม่อีกครั้ง"
                );
                return;
            }

            // Save input data
            $dataKey = $this->getDataKeyFromInputType($currentStep->input_type);
            $prospect->updateConversationStep($currentStep->step_key, [
                $dataKey => $message,
            ]);
        }

        // Get next step
        $nextStep = $currentStep->getNextStepFor($prospect->conversation_data);

        if (!$nextStep) {
            // Conversation complete - create user account
            $this->completeSignup($prospect);
            return;
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
     * Complete signup process
     */
    private function completeSignup(MlmProspect $prospect): void
    {
        try {
            DB::beginTransaction();

            $data = $prospect->conversation_data;

            // Validate required data
            if (!isset($data['name']) || !isset($data['phone'])) {
                throw new \Exception('Missing required data: name or phone');
            }

            // Create user
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

            Log::info('Signup completed successfully', [
                'prospect_id' => $prospect->id,
                'user_id' => $user->id,
                'rate_limits_cleared' => true,
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
     * Create user account
     */
    private function createUser(MlmProspect $prospect, array $data): User
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'];
        $name = $data['name'];

        // Generate email if not provided
        if (!$email) {
            $email = 'line_' . $prospect->line_user_id . '@thaiprompt.local';
        }

        // Generate password
        $password = Str::random(16);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'line_user_id' => $prospect->line_user_id,
            'line_display_name' => $prospect->line_display_name,
            'line_picture_url' => $prospect->line_picture_url,
            'line_verified' => true,
            'line_linked_at' => now(),
        ]);

        Log::info('User created via LINE signup', [
            'user_id' => $user->id,
            'prospect_id' => $prospect->id,
        ]);

        return $user;
    }

    /**
     * Create MLM member
     */
    private function createMlmMember(User $user, MlmProspect $prospect): MlmMember
    {
        $sponsor = $prospect->sponsorMember;

        // Get default MLM plan
        $defaultPlan = \App\Models\MlmPlan::where('is_default', true)->first();

        if (!$defaultPlan) {
            throw new \Exception('No default MLM plan found');
        }

        // Create MLM member
        $member = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $defaultPlan->id,
            'unilevel_sponsor_id' => $sponsor->id,
            'unilevel_level' => $sponsor->unilevel_level + 1,
            'unilevel_path' => $sponsor->unilevel_path . '/' . $sponsor->id,
            'status' => 'active',
            'joined_at' => now(),
            'member_code' => MlmMember::generateMemberCode(),
            'is_qualified' => true,
        ]);

        // Update sponsor's direct referral count
        $sponsor->increment('total_direct_referrals');

        Log::info('MLM member created', [
            'member_id' => $member->id,
            'sponsor_id' => $sponsor->id,
        ]);

        return $member;
    }

    /**
     * Send success message
     */
    private function sendSuccessMessage(MlmProspect $prospect, User $user): void
    {
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
                    'backgroundColor' => '#1DB446',
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
                            'color' => '#1DB446',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ยินดีต้อนรับสู่ระบบ MLM ของเรา',
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
                                            'text' => 'ชื่อ',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $user->name,
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
                                            'text' => 'อีเมล',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $user->email,
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
                                            'text' => 'แม่ทีม',
                                            'color' => '#aaaaaa',
                                            'size' => 'sm',
                                            'flex' => 2,
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => $prospect->sponsorUser->name ?? 'N/A',
                                            'wrap' => true,
                                            'color' => '#666666',
                                            'size' => 'sm',
                                            'flex' => 5,
                                        ],
                                    ],
                                ],
                            ],
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
                            'color' => '#1DB446',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'เข้าสู่ระบบ',
                                'uri' => route('login'),
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'กรุณาเช็คอีเมลเพื่อรับข้อมูลการเข้าสู่ระบบ',
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

        // Send sponsor friend add request
        $this->prospectService->sendSponsorFriendRequest($prospect, $this->lineService);
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
}
