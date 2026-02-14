<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\LineSignupSession;
use App\Services\AI\AiServiceFactory;
use App\Services\AI\PostXAgentHealthCheck;
use App\Services\AI\PostXAgentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LineSignupAiService
{
    /**
     * PostXAgent Health Check Service
     */
    protected ?PostXAgentHealthCheck $healthCheck = null;

    /**
     * ใช้ PostXAgent หรือไม่
     */
    protected bool $usePostXAgent = false;

    /**
     * สร้าง instance
     */
    public function __construct()
    {
        // ตรวจสอบว่าเปิดใช้งาน PostXAgent สำหรับ LINE signup หรือไม่
        if (config('postxagent.line_signup.enabled', false)) {
            $this->healthCheck = new PostXAgentHealthCheck;
            $this->usePostXAgent = $this->healthCheck->isAvailable();
        }
    }

    /**
     * Generate AI response with context awareness
     */
    public function generateSmartResponse(LineSignupSession $session, string $userMessage): string
    {
        $step = $session->current_step;
        $context = $this->buildContext($session);

        // Get AI prompt based on step
        $systemPrompt = $this->getSystemPrompt($step, $context);
        $userPrompt = $this->getUserPrompt($userMessage, $step);

        // Call AI service (integrate with your existing AI service)
        $aiResponse = $this->callAiService($systemPrompt, $userPrompt);

        return $aiResponse;
    }

    /**
     * Build context for AI
     */
    protected function buildContext(LineSignupSession $session): array
    {
        $lineProfile = $session->getCollectedData('line_profile', []);

        return [
            'user_name' => $lineProfile['displayName'] ?? 'คุณ',
            'current_step' => $session->current_step,
            'progress' => $session->getProgressPercentage(),
            'collected_data' => $session->collected_data,
        ];
    }

    /**
     * Get system prompt based on step
     */
    protected function getSystemPrompt(string $step, array $context): string
    {
        $userName = $context['user_name'];
        $progress = $context['progress'];

        $basePrompt = <<<EOT
คุณคือ AI Assistant ที่เป็นมิตร ชื่อ "พี่ช่วย" สำหรับระบบสมัครสมาชิก Thaiprompt Affiliate

บทบาทของคุณ:
- ช่วยเหลือผู้สมัครตลอดกระบวนการสมัครสมาชิก
- ตอบคำถามเกี่ยวกับระบบ Affiliate Marketing
- แนะนำเส้นทางสู่ความมั่งคั่งผ่านระบบของเรา
- ให้กำลังใจและสร้างแรงบันดาลใจ

บุคลิกภาพ:
- เป็นมิตร อบอุ่น
- ให้คำแนะนำที่ชัดเจน
- ใช้ emojis อย่างเหมาะสม
- สื่อสารภาษาไทยที่เป็นกันเอง

ข้อมูลผู้ใช้ปัจจุบัน:
- ชื่อ: {$userName}
- ขั้นตอนปัจจุบัน: {$step}
- ความคืบหน้า: {$progress}%

EOT;

        // Add step-specific instructions
        $stepInstructions = $this->getStepInstructions($step);

        return $basePrompt."\n\n".$stepInstructions;
    }

    /**
     * Get step-specific instructions
     */
    protected function getStepInstructions(string $step): string
    {
        $instructions = [
            'welcome' => <<<'EOT'
ขั้นตอน: ต้อนรับ

ภารกิจของคุณ:
- ต้อนรับผู้ใช้อย่างอบอุ่น
- อธิบายว่าระบบสมัครสมาชิกใช้เวลาแค่ 2-3 นาที
- สร้างความตื่นเต้นเกี่ยวกับโอกาสในการสร้างรายได้
- เล่าเรื่องสั้นๆ เกี่ยวกับคนที่ประสบความสำเร็จผ่านระบบ

ตัวอย่างการตอบ:
"สวัสดีค่ะ! ยินดีต้อนรับสู่ครอบครัว Thaiprompt 🎉

คุณรู้ไหมคะว่า สมาชิกของเรามากกว่า 50% สามารถสร้างรายได้เสริมได้ภายใน 30 วันแรก! 💰

การสมัครง่ายมากๆ แค่ 7 ขั้นตอน ใช้เวลาแค่ 2-3 นาที ✨

พร้อมเริ่มต้นเส้นทางสู่อิสรภาพทางการเงินกันไหมคะ? 🚀"
EOT,
            'name' => <<<'EOT'
ขั้นตอน: กรอกชื่อ

ภารกิจของคุณ:
- ขอให้ผู้ใช้กรอกชื่อจริง-นามสกุล
- อธิบายว่าชื่อจะใช้สำหรับอะไร (เอกสาร, ใบรับรอง)
- ตรวจสอบว่าชื่อถูกต้องและไม่มีตัวเลข
- ให้กำลังใจว่าเป็นขั้นตอนแรกที่ง่ายมาก

หากชื่อไม่ถูกต้อง:
- บอกอย่างนุ่มนวลว่าตรวจพบปัญหา
- ขอให้กรอกใหม่พร้อมตัวอย่าง
- ไม่ทำให้รู้สึกผิดหรืออับอาย
EOT,
            'email' => <<<'EOT'
ขั้นตอน: กรอกอีเมล

ภารกิจของคุณ:
- อธิบายว่าอีเมลสำคัญอย่างไร (รับข้อมูล, reset password)
- แนะนำให้ใช้อีเมลที่ใช้งานจริง
- ตรวจสอบรูปแบบอีเมล
- แจ้งหากอีเมลซ้ำในระบบ

ข้อมูลที่ควรบอก:
- อีเมลจะไม่ถูกขายหรือแชร์ไปที่อื่น
- จะได้รับ Newsletter เกี่ยวกับเทคนิคการขาย
- สามารถยกเลิกการรับข่าวสารได้ทุกเมื่อ
EOT,
            'phone' => <<<'EOT'
ขั้นตอน: กรอกเบอร์โทรศัพท์

ภารกิจของคุณ:
- อธิบายว่าเบอร์โทรใช้สำหรับยืนยันตัวตน
- แจ้งว่าจะส่ง OTP มาทาง LINE (ไม่ใช่ SMS)
- ตรวจสอบรูปแบบเบอร์ (0812345678)
- สร้างความมั่นใจว่าข้อมูลปลอดภัย

ข้อมูลความปลอดภัย:
- ข้อมูลเข้ารหัส SSL 256-bit
- ไม่แชร์เบอร์กับบุคคลที่สาม
- ใช้เฉพาะการยืนยันตัวตนเท่านั้น
EOT,
            'otp' => <<<'EOT'
ขั้นตอน: ยืนยัน OTP

ภารกิจของคุณ:
- อธิบายว่าหา OTP ได้จากไหน (ดูข้อความด้านบน)
- แจ้งว่า OTP หมดอายุใน 5 นาที
- หาก OTP ผิด: บอกจำนวนครั้งที่เหลือ
- หาก OTP หมดอายุ: แนะนำให้ขอใหม่

เคล็ดลับ:
- OTP คือตัวเลข 6 หลัก
- ห้ามแชร์ OTP กับใคร
- หากไม่ได้รับ OTP ให้รอ 30 วินาที
EOT,
            'password' => <<<EOT
ขั้นตอน: ตั้งรหัสผ่าน

ภารกิจของคุณ:
- แนะนำให้ตั้งรหัสผ่านที่แข็งแกร่ง
- อธิบายเงื่อนไข (อย่างน้อย 8 ตัวอักษร)
- แนะนำเทคนิค: ใช้ตัวพิมพ์เล็ก-ใหญ่ ตัวเลข และสัญลักษณ์
- เตือนไม่ให้ใช้รหัสผ่านที่ง่ายเกินไป (123456, password)

ตัวอย่างรหัสผ่านที่ดี:
- MyPass@2025
- Thaiprompt#99
- Affiliate$Success

เคล็ดลับความปลอดภัย:
- อย่าใช้รหัสผ่านเดียวกันหลายเว็บ
- อย่าแชร์รหัสผ่านกับใคร
- เปลี่ยนรหัสผ่านทุก 3 เดือน
EOT,
            'referral' => <<<'EOT'
ขั้นตอน: กรอกรหัสผู้แนะนำ

ภารกิจของคุณ:
- อธิบายว่ารหัสผู้แนะนำคืออะไร
- บอกว่าสามารถข้ามได้หากไม่มี
- อธิบายประโยชน์ของการมีผู้แนะนำ (ได้คำแนะนำ, โบนัสพิเศษ)
- ตรวจสอบรหัสว่ามีจริงในระบบ

ประโยชน์ของการมีผู้แนะนำ:
- ได้รับคำแนะนำจากผู้มีประสบการณ์
- เข้ากลุ่มไลน์สำหรับสมาชิกใหม่
- รับโบนัสต้อนรับเพิ่ม 50%
- มีพี่เลี้ยงช่วยเหลือตลอด

หากไม่มีผู้แนะนำ:
- ข้ามขั้นตอนนี้ได้เลย
- ระบบจะจับคู่ผู้แนะนำให้อัตโนมัติ
- ยังได้รับโบนัสต้อนรับเหมือนเดิม
EOT,
            'confirmation' => <<<'EOT'
ขั้นตอน: ยืนยันข้อมูล

ภารกิจของคุณ:
- ให้ผู้ใช้ตรวจสอบข้อมูลทั้งหมด
- อธิบายว่าข้อมูลจะใช้สำหรับอะไรบ้าง
- สร้างความตื่นเต้นว่าใกล้เสร็จแล้ว!
- แจ้งว่าหลังยืนยันจะได้รับอะไรบ้าง

สิ่งที่จะได้รับหลังสมัคร:
- รหัสสมาชิก (Member ID)
- รหัสแนะนำส่วนตัว (ใช้ชวนเพื่อน)
- คะแนนสะสมต้อนรับ 100 คะแนน
- เข้าถึงคอร์สเรียนฟรี 3 คอร์ส
- eBook "เส้นทางสู่เศรษฐี" ฟรี!

คำพูดสร้างแรงบันดาลใจ:
"คุณกำลังจะก้าวเข้าสู่โลกใหม่ของโอกาสทางการเงิน! 🚀"
EOT,
        ];

        return $instructions[$step] ?? 'ช่วยเหลือผู้ใช้ในขั้นตอนปัจจุบัน';
    }

    /**
     * Get user prompt
     */
    protected function getUserPrompt(string $userMessage, string $step): string
    {
        return <<<EOT
ขั้นตอนปัจจุบัน: {$step}
ข้อความจากผู้ใช้: {$userMessage}

กรุณาตอบกลับอย่างเป็นมิตรและให้ข้อมูลที่เป็นประโยชน์
ใช้ภาษาไทยที่เข้าใจง่าย และใส่ emoji ให้น่ารัก
ตอบสั้นๆ กระชับ ไม่เกิน 3-4 บรรทัด
EOT;
    }

    /**
     * Call AI service
     *
     * รองรับทั้ง PostXAgent และ fallback เป็น local AI
     */
    protected function callAiService(string $systemPrompt, string $userPrompt): string
    {
        try {
            // ลองใช้ PostXAgent ก่อน (ถ้าเปิดใช้งานและพร้อม)
            if ($this->usePostXAgent) {
                $response = $this->callPostXAgent($systemPrompt, $userPrompt);

                if ($response !== null) {
                    return $response;
                }

                // PostXAgent ไม่สำเร็จ ลอง fallback
                Log::info('LineSignupAi: PostXAgent failed, falling back to local');
            }

            // ลองใช้ AI provider จากระบบ
            $response = $this->callSystemAiProvider($systemPrompt, $userPrompt);

            if ($response !== null) {
                return $response;
            }

            // Fallback สุดท้าย: ใช้ smart default response
            $cacheKey = 'ai_response_'.md5($systemPrompt.$userPrompt);

            return Cache::remember($cacheKey, 3600, function () use ($userPrompt) {
                return $this->getSmartDefaultResponse($userPrompt);
            });
        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'error' => $e->getMessage(),
                'use_postxagent' => $this->usePostXAgent,
            ]);

            return 'ขออภัยค่ะ ตอนนี้ระบบ AI กำลังยุ่งอยู่ 🙏 แต่ไม่ต้องกังวลนะคะ คุณสามารถดำเนินการสมัครสมาชิกต่อได้เลย หรือหากมีคำถาม ติดต่อทีมงานได้ที่ Line: @thaiprompt 💚';
        }
    }

    /**
     * เรียกใช้ PostXAgent AI
     */
    protected function callPostXAgent(string $systemPrompt, string $userPrompt): ?string
    {
        try {
            // ดึง provider/model ที่ดีที่สุดจาก PostXAgent
            $best = $this->healthCheck?->selectBestAvailableProvider();

            if (! $best) {
                Log::warning('LineSignupAi: No available provider from PostXAgent');

                return null;
            }

            // สร้าง dummy AiProvider และ AiModel สำหรับ PostXAgent
            $provider = new AiProvider([
                'name' => 'postxagent',
                'display_name' => 'PostXAgent - '.($best['provider']['name'] ?? 'Unknown'),
                'api_endpoint' => config('postxagent.api_url'),
                'is_active' => true,
                'config' => [
                    'postxagent_provider' => $best['provider']['id'] ?? $best['provider']['name'],
                ],
            ]);

            $model = new AiModel([
                'model_identifier' => $best['model']['id'] ?? $best['model']['name'] ?? 'default',
                'display_name' => $best['model']['name'] ?? 'Default',
                'max_output_tokens' => 2048,
            ]);

            $service = new PostXAgentService($provider, $model);

            // เตรียม messages
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];

            $result = $service->chat($messages, [
                'temperature' => 0.7,
                'max_tokens' => 1024,
            ]);

            $content = $result['content'] ?? '';

            if (! empty($content)) {
                Log::info('LineSignupAi: PostXAgent response success', [
                    'provider' => $best['provider']['name'] ?? 'unknown',
                    'model' => $best['model']['name'] ?? 'unknown',
                    'tokens' => $result['tokens'] ?? 0,
                ]);

                return $content;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('LineSignupAi: PostXAgent call failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * เรียกใช้ AI provider จากระบบ (fallback)
     */
    protected function callSystemAiProvider(string $systemPrompt, string $userPrompt): ?string
    {
        try {
            // หา provider ที่ active และพร้อมใช้งาน
            $provider = AiProvider::where('is_active', true)
                ->where('is_available', true)
                ->whereIn('name', ['meta-local', 'deepseek-local', 'deepseek', 'openai'])
                ->first();

            if (! $provider) {
                return null;
            }

            $model = $provider->models()->where('is_active', true)->first();

            if (! $model) {
                return null;
            }

            $service = AiServiceFactory::create($provider, $model);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];

            $result = $service->chat($messages, [
                'temperature' => 0.7,
                'max_tokens' => 1024,
            ]);

            $content = $result['content'] ?? '';

            if (! empty($content)) {
                Log::info('LineSignupAi: System AI response success', [
                    'provider' => $provider->name,
                    'model' => $model->model_identifier,
                ]);

                return $content;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('LineSignupAi: System AI call failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ตรวจสอบว่า PostXAgent พร้อมใช้งานหรือไม่
     */
    public function isPostXAgentAvailable(): bool
    {
        return $this->usePostXAgent && $this->healthCheck?->isAvailable();
    }

    /**
     * ดึงสถานะ AI providers ทั้งหมด
     */
    public function getAiProvidersStatus(): array
    {
        $status = [
            'postxagent' => [
                'enabled' => config('postxagent.line_signup.enabled', false),
                'available' => $this->isPostXAgentAvailable(),
                'providers' => [],
            ],
            'system' => [
                'available' => false,
                'providers' => [],
            ],
        ];

        // PostXAgent providers
        if ($this->healthCheck) {
            $status['postxagent']['providers'] = $this->healthCheck->getReadyProviders();
        }

        // System providers
        $systemProviders = AiProvider::where('is_active', true)
            ->where('is_available', true)
            ->with(['models' => fn ($q) => $q->where('is_active', true)])
            ->get();

        if ($systemProviders->isNotEmpty()) {
            $status['system']['available'] = true;
            $status['system']['providers'] = $systemProviders->map(function ($p) {
                return [
                    'name' => $p->display_name,
                    'models' => $p->models->pluck('display_name')->toArray(),
                ];
            })->toArray();
        }

        return $status;
    }

    /**
     * Get smart default response
     */
    protected function getSmartDefaultResponse(string $userPrompt): string
    {
        $lowerPrompt = mb_strtolower($userPrompt);

        // คำถามเกี่ยวกับรายได้
        if (preg_match('/รายได้|เงิน|ได้เท่าไร|รวย/', $lowerPrompt)) {
            return "💰 สมาชิกของเราสร้างรายได้เฉลี่ย 5,000-50,000 บาท/เดือน ขึ้นอยู่กับความพยายาม!\n\nยิ่งแชร์มาก ยิ่งได้มาก! 🚀\n\nพร้อมเริ่มต้นแล้วไหมคะ? 😊";
        }

        // คำถามเกี่ยวกับระบบ
        if (preg_match('/ทำงาน|ระบบ|affiliate|แชร์/', $lowerPrompt)) {
            return "🎯 ระบบของเราง่ายมากๆ!\n\n1. แชร์ลิงก์ของคุณ\n2. เพื่อนสมัครผ่านลิงก์\n3. คุณได้รับค่าคอมมิชชั่น!\n\nเริ่มต้นได้ทันทีหลังสมัคร 🚀";
        }

        // คำถามเกี่ยวกับค่าใช้จ่าย
        if (preg_match('/ฟรี|เสียเงิน|ค่าใช้จ่าย|เท่าไร/', $lowerPrompt)) {
            return "✨ สมัครสมาชิกฟรี 100%!\n\nไม่มีค่าใช้จ่ายแอบแฝง ไม่ต้องลงทุน\n\nเริ่มต้นสร้างรายได้ได้ทันที! 🎉";
        }

        // คำถามเกี่ยวกับเวลา
        if (preg_match('/นาน|เร็ว|เวลา|เท่าไร/', $lowerPrompt)) {
            return "⏱️ สมัครเสร็จภายใน 2-3 นาที!\n\nง่ายมาก แค่ 7 ขั้นตอน\n\nเริ่มเลยไหมคะ? 😊";
        }

        // คำถามทั่วไป
        return "สวัสดีค่ะ! ยินดีให้ความช่วยเหลือ 😊\n\nหากมีคำถามเพิ่มเติม สามารถถามได้เลยนะคะ\n\nหรือจะเริ่มสมัครสมาชิกเลยก็ได้ค่ะ! ✨";
    }

    /**
     * Get wealth path content
     */
    public function getWealthPathContent(): array
    {
        return [
            'title' => '🎯 เส้นทางสู่เศรษฐี',
            'subtitle' => 'ระบบ 5 ขั้นตอนสร้างรายได้ออนไลน์',
            'steps' => [
                [
                    'number' => 1,
                    'title' => 'เริ่มต้น - สมัครสมาชิก',
                    'description' => 'สมัครฟรี รับรหัสแนะนำส่วนตัว',
                    'earning' => '0 บาท',
                    'time' => '2 นาที',
                    'icon' => '🚀',
                ],
                [
                    'number' => 2,
                    'title' => 'ระดับเริ่มต้น - แชร์เบื้องต้น',
                    'description' => 'แชร์ลิงก์ ชวน 5-10 คนแรก',
                    'earning' => '500-2,000 บาท',
                    'time' => '1 สัปดาห์',
                    'icon' => '💫',
                ],
                [
                    'number' => 3,
                    'title' => 'ระดับกลาง - สร้างทีม',
                    'description' => 'มีทีมงาน 20-50 คน',
                    'earning' => '5,000-15,000 บาท',
                    'time' => '1 เดือน',
                    'icon' => '⭐',
                ],
                [
                    'number' => 4,
                    'title' => 'ระดับสูง - ผู้นำทีม',
                    'description' => 'ทีมใหญ่ 100+ คน มีทีมย่อย',
                    'earning' => '30,000-100,000 บาท',
                    'time' => '3 เดือน',
                    'icon' => '🌟',
                ],
                [
                    'number' => 5,
                    'title' => 'เศรษฐี - รายได้ Passive',
                    'description' => 'เครือข่าย 1,000+ คน ทำงานอัตโนมัติ',
                    'earning' => '100,000+ บาท',
                    'time' => '6-12 เดือน',
                    'icon' => '👑',
                ],
            ],
            'success_stories' => [
                [
                    'name' => 'คุณสมชาย',
                    'before' => 'พนักงานออฟฟิศ เงินเดือน 15,000',
                    'after' => 'รายได้ 80,000/เดือน ใน 8 เดือน',
                    'quote' => 'ไม่คิดว่าจะทำได้ แต่ลองแล้วติดใจ!',
                ],
                [
                    'name' => 'คุณสมหญิง',
                    'before' => 'แม่บ้าน อยู่บ้านเลี้ยงลูก',
                    'after' => 'รายได้ 50,000/เดือน ในเวลา 1 ปี',
                    'quote' => 'ทำที่บ้านได้ ดูแลลูกไปด้วย',
                ],
            ],
            'tips' => [
                '✅ เริ่มจากคนใกล้ตัว (เพื่อน ครอบครัว)',
                '✅ แชร์เนื้อหาที่มีคุณค่า ไม่ขายแบบหน้าด้าน',
                '✅ เรียนรู้เทคนิคการตลาดออนไลน์',
                '✅ มีเป้าหมายชัดเจน ติดตามผลทุกวัน',
                '✅ อดทน พยายาม ไม่ย่อท้อ',
            ],
        ];
    }
}
