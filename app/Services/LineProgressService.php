<?php

namespace App\Services;

use App\Models\MlmProspect;
use Illuminate\Support\Facades\Cache;

/**
 * LINE Signup Progress Tracking Service
 *
 * Manages signup progress tracking with real-time updates
 * - Step completion tracking
 * - Progress percentage calculation
 * - Time estimation
 * - Step validation
 * - Auto-save progress
 */
class LineProgressService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'line:progress:';

    // Signup steps in order
    private const STEPS = [
        'welcome' => ['order' => 0, 'weight' => 5, 'icon' => '👋', 'required' => false],
        'name' => ['order' => 1, 'weight' => 15, 'icon' => '📝', 'required' => true],
        'username' => ['order' => 2, 'weight' => 15, 'icon' => '👤', 'required' => true],
        'email' => ['order' => 3, 'weight' => 15, 'icon' => '📧', 'required' => true],
        'otp' => ['order' => 4, 'weight' => 20, 'icon' => '🔐', 'required' => true],
        'phone' => ['order' => 5, 'weight' => 10, 'icon' => '📱', 'required' => true],
        'password' => ['order' => 6, 'weight' => 15, 'icon' => '🔒', 'required' => true],
        'confirm' => ['order' => 7, 'weight' => 5, 'icon' => '✅', 'required' => true],
    ];

    /**
     * Update progress for a prospect
     */
    public function updateProgress(MlmProspect $prospect, string $currentStep, array $data = []): void
    {
        $progress = $this->calculateProgress($prospect, $currentStep);

        // Update prospect model
        $prospect->update([
            'conversation_updated_at' => now(),
            'conversation_progress_percent' => $progress['percent'],
            'conversation_total_steps' => $progress['totalSteps'],
            'conversation_completed_steps' => $progress['completedSteps'],
        ]);

        // Cache progress data
        $cacheKey = $this->getCacheKey($prospect->id);
        $cacheData = [
            'current_step' => $currentStep,
            'progress' => $progress,
            'data' => $data,
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $cacheData, now()->addSeconds(self::CACHE_TTL));
    }

    /**
     * Get current progress
     */
    public function getProgress(MlmProspect $prospect): array
    {
        $cacheKey = $this->getCacheKey($prospect->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($prospect) {
            $currentStep = $this->detectCurrentStep($prospect);
            $progress = $this->calculateProgress($prospect, $currentStep);

            return [
                'current_step' => $currentStep,
                'progress' => $progress,
                'data' => [],
                'updated_at' => $prospect->conversation_updated_at?->toIso8601String(),
            ];
        });
    }

    /**
     * Mark step as completed
     */
    public function completeStep(MlmProspect $prospect, string $step): void
    {
        $progress = $this->getProgress($prospect);
        $completedSteps = $progress['data']['completed_steps'] ?? [];

        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        $data = array_merge($progress['data'], ['completed_steps' => $completedSteps]);
        $nextStep = $this->getNextStep($step);

        $this->updateProgress($prospect, $nextStep, $data);
    }

    /**
     * Calculate progress percentage
     */
    public function calculateProgress(MlmProspect $prospect, string $currentStep): array
    {
        $completedSteps = $this->getCompletedSteps($prospect, $currentStep);
        $totalWeight = array_sum(array_column(self::STEPS, 'weight'));
        $completedWeight = 0;

        foreach ($completedSteps as $step) {
            $completedWeight += self::STEPS[$step]['weight'] ?? 0;
        }

        $percent = $totalWeight > 0 ? (int) (($completedWeight / $totalWeight) * 100) : 0;

        return [
            'percent' => min(100, $percent),
            'totalSteps' => count(self::STEPS),
            'completedSteps' => count($completedSteps),
            'currentStep' => $currentStep,
            'currentStepInfo' => $this->getStepInfo($currentStep),
            'nextStep' => $this->getNextStep($currentStep),
            'isComplete' => $percent >= 100,
            'estimatedTimeLeft' => $this->estimateTimeLeft($percent, $prospect),
        ];
    }

    /**
     * Get completed steps
     */
    private function getCompletedSteps(MlmProspect $prospect, string $currentStep): array
    {
        $completed = [];
        $currentStepOrder = self::STEPS[$currentStep]['order'] ?? 999;

        foreach (self::STEPS as $step => $info) {
            if ($info['order'] < $currentStepOrder) {
                $completed[] = $step;
            }
        }

        return $completed;
    }

    /**
     * Detect current step from prospect data
     */
    private function detectCurrentStep(MlmProspect $prospect): string
    {
        // Check cached progress first
        $cacheKey = $this->getCacheKey($prospect->id);
        $cached = Cache::get($cacheKey);

        if ($cached && isset($cached['current_step'])) {
            return $cached['current_step'];
        }

        // Detect from data
        if (!$prospect->clicked_at) {
            return 'welcome';
        }

        $data = $prospect->data ?? [];

        if (empty($data['name'])) {
            return 'name';
        }

        if (empty($data['username'])) {
            return 'username';
        }

        if (empty($data['email'])) {
            return 'email';
        }

        if (empty($data['email_verified'])) {
            return 'otp';
        }

        if (empty($data['phone'])) {
            return 'phone';
        }

        if (empty($data['password'])) {
            return 'password';
        }

        return 'confirm';
    }

    /**
     * Get next step
     */
    private function getNextStep(string $currentStep): string
    {
        $currentOrder = self::STEPS[$currentStep]['order'] ?? 0;
        $nextStep = null;
        $minOrder = 999;

        foreach (self::STEPS as $step => $info) {
            if ($info['order'] > $currentOrder && $info['order'] < $minOrder) {
                $nextStep = $step;
                $minOrder = $info['order'];
            }
        }

        return $nextStep ?? 'confirm';
    }

    /**
     * Get step information
     */
    public function getStepInfo(string $step): array
    {
        $info = self::STEPS[$step] ?? self::STEPS['welcome'];

        return [
            'step' => $step,
            'order' => $info['order'],
            'weight' => $info['weight'],
            'icon' => $info['icon'],
            'required' => $info['required'],
            'name' => $this->getStepName($step),
            'description' => $this->getStepDescription($step),
            'tips' => $this->getStepTips($step),
        ];
    }

    /**
     * Estimate time left (in minutes)
     */
    private function estimateTimeLeft(int $percentComplete, MlmProspect $prospect): int
    {
        if ($percentComplete >= 100) {
            return 0;
        }

        $elapsed = $prospect->conversation_started_at
            ? now()->diffInMinutes($prospect->conversation_started_at)
            : 0;

        if ($elapsed === 0 || $percentComplete === 0) {
            return 5; // Default estimate
        }

        // Calculate based on current pace
        $timePerPercent = $elapsed / $percentComplete;
        $remainingPercent = 100 - $percentComplete;
        $estimated = (int) ceil($timePerPercent * $remainingPercent);

        return max(1, min(10, $estimated)); // Between 1-10 minutes
    }

    /**
     * Get step name in Thai
     */
    private function getStepName(string $step): string
    {
        return match($step) {
            'welcome' => 'ยินดีต้อนรับ',
            'name' => 'กรอกชื่อ-นามสกุล',
            'username' => 'ตั้งชื่อผู้ใช้',
            'email' => 'กรอกอีเมล',
            'otp' => 'ยืนยัน OTP',
            'phone' => 'กรอกเบอร์โทร',
            'password' => 'ตั้งรหัสผ่าน',
            'confirm' => 'ยืนยันข้อมูล',
            default => 'กรอกข้อมูล',
        };
    }

    /**
     * Get step description
     */
    private function getStepDescription(string $step): string
    {
        return match($step) {
            'welcome' => 'เริ่มต้นการสมัครสมาชิก',
            'name' => 'กรอกชื่อจริงและนามสกุลของคุณ',
            'username' => 'เลือกชื่อผู้ใช้ที่คุณต้องการ (ภาษาอังกฤษและตัวเลข)',
            'email' => 'กรอกอีเมลของคุณเพื่อรับ OTP',
            'otp' => 'กรอกรหัส OTP 6 หลักที่ส่งไปยังอีเมลของคุณ',
            'phone' => 'กรอกเบอร์โทรศัพท์มือถือของคุณ',
            'password' => 'ตั้งรหัสผ่านที่มีความปลอดภัย (อย่างน้อย 8 ตัวอักษร)',
            'confirm' => 'ตรวจสอบข้อมูลและยืนยันการสมัคร',
            default => 'กรอกข้อมูลตามคำแนะนำ',
        };
    }

    /**
     * Get step tips
     */
    private function getStepTips(string $step): array
    {
        return match($step) {
            'name' => [
                'ใช้ชื่อจริงของคุณ',
                'สะกดชื่อให้ถูกต้อง',
                'ตัวอย่าง: สมชาย ใจดี',
            ],
            'username' => [
                'ตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น',
                'ความยาว 4-20 ตัวอักษร',
                'ไม่สามารถเปลี่ยนได้ภายหลัง',
            ],
            'email' => [
                'ตรวจสอบอีเมลให้ถูกต้อง',
                'เช็คกล่องขาเข้าและ Spam',
                'OTP จะหมดอายุใน 10 นาที',
            ],
            'otp' => [
                'กรอกรหัส OTP 6 หลัก',
                'ตรวจสอบใน email ของคุณ',
                'สามารถขอ OTP ใหม่ได้หลัง 1 นาที',
            ],
            'phone' => [
                'กรอกเบอร์มือถือที่ใช้งานจริง',
                'รูปแบบ: 081-234-5678 หรือ 0812345678',
                'ต้องไม่ซ้ำกับผู้ใช้อื่น',
            ],
            'password' => [
                'อย่างน้อย 8 ตัวอักษร',
                'ควรมีตัวพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข',
                'ไม่ควรใช้รหัสผ่านที่ง่ายเกินไป',
            ],
            'confirm' => [
                'ตรวจสอบข้อมูลทั้งหมด',
                'กดยืนยันเมื่อข้อมูลถูกต้อง',
                'สามารถแก้ไขข้อมูลได้ในภายหลัง',
            ],
            default => [],
        };
    }

    /**
     * Reset progress
     */
    public function resetProgress(MlmProspect $prospect): void
    {
        Cache::forget($this->getCacheKey($prospect->id));

        $prospect->update([
            'conversation_progress_percent' => 0,
            'conversation_completed_steps' => 0,
            'conversation_updated_at' => now(),
        ]);
    }

    /**
     * Get cache key
     */
    private function getCacheKey(int $prospectId): string
    {
        return self::CACHE_PREFIX . $prospectId;
    }

    /**
     * Get all steps
     */
    public function getAllSteps(): array
    {
        return array_map(
            fn($step, $info) => array_merge(['step' => $step], $info, [
                'name' => $this->getStepName($step),
                'description' => $this->getStepDescription($step),
            ]),
            array_keys(self::STEPS),
            self::STEPS
        );
    }

    /**
     * Validate step data
     */
    public function validateStepData(string $step, $data): array
    {
        return match($step) {
            'name' => $this->validateName($data),
            'username' => $this->validateUsername($data),
            'email' => $this->validateEmail($data),
            'otp' => $this->validateOtp($data),
            'phone' => $this->validatePhone($data),
            'password' => $this->validatePassword($data),
            default => ['valid' => true, 'errors' => []],
        };
    }

    /**
     * Validate name
     */
    private function validateName($name): array
    {
        $errors = [];

        if (empty($name) || strlen(trim($name)) < 2) {
            $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
        }

        if (strlen($name) > 100) {
            $errors[] = 'ชื่อยาวเกินไป (สูงสุด 100 ตัวอักษร)';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate username
     */
    private function validateUsername($username): array
    {
        $errors = [];

        if (empty($username) || strlen($username) < 4) {
            $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร';
        }

        if (strlen($username) > 20) {
            $errors[] = 'ชื่อผู้ใช้ยาวเกินไป (สูงสุด 20 ตัวอักษร)';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'ใช้ได้เฉพาะภาษาอังกฤษ ตัวเลข และ _';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate email
     */
    private function validateEmail($email): array
    {
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate OTP
     */
    private function validateOtp($otp): array
    {
        $errors = [];

        if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
            $errors[] = 'OTP ต้องเป็นตัวเลข 6 หลัก';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate phone
     */
    private function validatePhone($phone): array
    {
        $errors = [];

        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (!preg_match('/^0[6-9]\d{8}$/', $cleaned)) {
            $errors[] = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Validate password
     */
    private function validatePassword($password): array
    {
        $errors = [];

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        }

        if (strlen($password) > 50) {
            $errors[] = 'รหัสผ่านยาวเกินไป (สูงสุด 50 ตัวอักษร)';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
