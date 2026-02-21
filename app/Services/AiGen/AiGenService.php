<?php

namespace App\Services\AiGen;

use App\Models\AiGenGeneration;
use App\Models\AiGenPromotion;
use App\Models\AiGenProvider;
use App\Models\AiGenSubscription;
use App\Models\AiGenUsageLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AiGenService
{
    protected AiGenQuotaService $quotaService;

    protected AiGenSubscriptionService $subscriptionService;

    protected WalletService $walletService;

    public function __construct()
    {
        $this->quotaService = new AiGenQuotaService;
        $this->subscriptionService = new AiGenSubscriptionService;
        $this->walletService = new WalletService;
    }

    /**
     * สร้างเนื้อหา (ภาพหรือวิดีโอ)
     */
    public function generate(
        User $user,
        string $providerSlug,
        string $type,
        string $prompt,
        array $parameters = []
    ): array {
        // ตรวจสอบประเภท
        if (! in_array($type, ['image', 'video'])) {
            return [
                'success' => false,
                'error' => 'ประเภทไม่ถูกต้อง ต้องเป็น "image" หรือ "video"',
            ];
        }

        // ดึง provider
        $provider = AiGenProvider::where('slug', $providerSlug)
            ->where('is_active', true)
            ->first();

        if (! $provider) {
            return [
                'success' => false,
                'error' => 'ไม่พบ Provider หรือ Provider ไม่ได้เปิดใช้งาน',
            ];
        }

        // สร้าง provider instance
        $providerInstance = AiGenProviderFactory::createFromModel($provider);

        if (! $providerInstance) {
            return [
                'success' => false,
                'error' => 'ไม่พบ implementation ของ Provider',
            ];
        }

        if (! $providerInstance->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Provider ยังไม่ได้ตั้งค่า',
            ];
        }

        // ตรวจสอบพื้นที่เก็บภาพ
        if (! $this->checkStorageAvailable()) {
            return [
                'success' => false,
                'error' => 'พื้นที่เก็บภาพเต็มแล้ว กรุณาติดต่อผู้ดูแลระบบ หรือลบภาพเก่าเพื่อเพิ่มพื้นที่',
            ];
        }

        // ตรวจสอบสิทธิ์ผู้ใช้
        $canGenerate = $this->checkUserCanGenerate($user, $type, $provider, $parameters['promotion_code'] ?? null);

        if (! $canGenerate['can_generate']) {
            return [
                'success' => false,
                'error' => $canGenerate['reason'],
            ];
        }

        // เริ่มสร้าง
        try {
            DB::beginTransaction();

            // หักเงินจาก wallet (ถ้าจ่ายด้วย wallet)
            $walletTransaction = null;
            if ($canGenerate['payment_method'] === 'wallet') {
                $wallet = $this->walletService->getOrCreateWallet($user);
                $walletCost = $canGenerate['wallet_cost'];
                $walletDescription = 'AI Gen - ' . ($type === 'image' ? 'สร้างภาพ' : 'สร้างวิดีโอ') . ' (' . $provider->name . ')';

                $walletTransaction = $this->walletService->deductForService(
                    $wallet,
                    $walletCost,
                    $walletDescription,
                    'ai_gen',
                    null,
                    [
                        'provider' => $providerSlug,
                        'type' => $type,
                        'prompt' => mb_substr($prompt, 0, 200),
                        'promotion_id' => $canGenerate['promotion_id'] ?? null,
                        'original_cost' => $canGenerate['original_cost'] ?? $walletCost,
                        'discount' => ($canGenerate['original_cost'] ?? $walletCost) - $walletCost,
                    ]
                );
            }

            // เก็บข้อมูลโปรโมชั่นใน parameters
            if (isset($canGenerate['promotion_id'])) {
                $parameters['promotion_id'] = $canGenerate['promotion_id'];
            }

            // สร้าง usage log
            $usageLog = AiGenUsageLog::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
                'subscription_id' => $canGenerate['subscription_id'] ?? null,
                'generation_type' => $type,
                'prompt' => $prompt,
                'parameters' => $parameters,
                'credits_used' => 1,
                'is_free_quota' => $canGenerate['is_free_quota'],
                'status' => 'pending',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'started_at' => Carbon::now(),
            ]);

            // เรียก API ของ provider
            if ($type === 'image') {
                $result = $providerInstance->generateImage($prompt, $parameters);
            } else {
                $result = $providerInstance->generateVideo($prompt, $parameters);
            }

            if (! $result['success']) {
                // ถ้าล้มเหลวและจ่ายด้วย wallet ให้คืนเงิน
                if ($walletTransaction) {
                    $wallet = $this->walletService->getOrCreateWallet($user);
                    $this->walletService->deposit(
                        $wallet,
                        $canGenerate['wallet_cost'],
                        'AI Gen - คืนเงิน (สร้างไม่สำเร็จ)',
                        'ai_gen_refund',
                        $walletTransaction->id,
                        ['reason' => 'generation_failed', 'original_transaction_id' => $walletTransaction->id]
                    );
                }

                $usageLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'completed_at' => Carbon::now(),
                ]);

                DB::commit();

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'สร้างไม่สำเร็จ',
                ];
            }

            // อัพเดท usage log
            $usageLog->update([
                'status' => $result['status'] ?? 'completed',
                'result_data' => $result,
                'completed_at' => $result['status'] === 'completed' ? Carbon::now() : null,
            ]);

            // สร้าง generation record
            $generation = AiGenGeneration::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
                'usage_log_id' => $usageLog->id,
                'type' => $type,
                'prompt' => $prompt,
                'settings' => $parameters,
                'status' => $result['status'] ?? 'pending',
                'external_id' => $result['generation_id'] ?? null,
                'external_data' => $result['data'] ?? null,
            ]);

            // ถ้าสำเร็จ บันทึกข้อมูลไฟล์
            if ($result['status'] === 'completed') {
                $this->updateGenerationWithResult($generation, $result);
            }

            // หักเครดิตจาก subscription (ถ้าจ่ายด้วย subscription)
            if ($canGenerate['payment_method'] === 'subscription' && $canGenerate['subscription_id']) {
                $subscription = AiGenSubscription::find($canGenerate['subscription_id']);
                if ($subscription) {
                    $subscription->useCredits($type, 1);
                }
            }

            // เพิ่มจำนวนการใช้โปรโมชั่น
            if (isset($canGenerate['promotion_id'])) {
                $promotion = AiGenPromotion::find($canGenerate['promotion_id']);
                if ($promotion) {
                    $promotion->incrementUsage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'generation_id' => $generation->id,
                'external_id' => $result['generation_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'data' => $generation,
                'payment_method' => $canGenerate['payment_method'],
                'wallet_cost' => $canGenerate['wallet_cost'] ?? null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ตรวจสอบสิทธิ์ผู้ใช้ในการสร้าง
     *
     * ลำดับการตรวจสอบ:
     * 1. Admin → อนุญาตเสมอ
     * 2. Subscription credits → ใช้ได้ถ้ามีเครดิตเหลือ
     * 3. Free quota → ใช้ได้ถ้ามีโควต้าฟรี
     * 4. Wallet payment → หักจาก wallet (ถ้าเปิดใช้งาน)
     */
    protected function checkUserCanGenerate(
        User $user,
        string $type,
        ?AiGenProvider $provider = null,
        ?string $promotionCode = null
    ): array {
        // Admin สร้างได้ตลอด
        if ($user->is_admin || $user->is_super_admin) {
            return [
                'can_generate' => true,
                'is_free_quota' => true,
                'is_admin' => true,
                'payment_method' => 'admin',
            ];
        }

        // 1. ตรวจสอบ subscription credits
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if ($subscription && $subscription->hasCredits($type)) {
            return [
                'can_generate' => true,
                'is_free_quota' => false,
                'subscription_id' => $subscription->id,
                'payment_method' => 'subscription',
            ];
        }

        // 2. ตรวจสอบ free quota
        if ($this->quotaService->canUseFreeQuota($user, $type)) {
            return [
                'can_generate' => true,
                'is_free_quota' => true,
                'payment_method' => 'free_quota',
            ];
        }

        // 3. ตรวจสอบ wallet payment
        $walletEnabled = Setting::get('ai_gen_wallet_enabled', false);

        if ($walletEnabled && $provider) {
            $walletResult = $this->checkWalletPayment($user, $type, $provider, $promotionCode);
            if ($walletResult['can_generate']) {
                return $walletResult;
            }
        }

        return [
            'can_generate' => false,
            'reason' => 'ไม่มีเครดิตหรือโควต้าเหลือ กรุณาซื้อแพ็กเกจหรือเติมเงินใน Wallet',
        ];
    }

    /**
     * ตรวจสอบการจ่ายเงินจาก wallet
     */
    protected function checkWalletPayment(
        User $user,
        string $type,
        AiGenProvider $provider,
        ?string $promotionCode = null
    ): array {
        // คำนวณราคา (ใช้ราคาของ provider ก่อน ถ้าไม่มีใช้ราคา default)
        $cost = $type === 'image'
            ? ($provider->wallet_cost_per_image ?? Setting::get('ai_gen_wallet_cost_image', 0))
            : ($provider->wallet_cost_per_video ?? Setting::get('ai_gen_wallet_cost_video', 0));

        if ($cost <= 0) {
            return ['can_generate' => false, 'reason' => 'ไม่ได้ตั้งราคาสำหรับการจ่ายด้วย Wallet'];
        }

        $originalCost = $cost;
        $promotionId = null;

        // ตรวจสอบโปรโมชั่น
        if ($promotionCode) {
            $promotion = AiGenPromotion::active()
                ->forType($type)
                ->where('code', $promotionCode)
                ->first();

            if ($promotion && $promotion->canBeUsedBy($user)) {
                $cost = $promotion->calculateDiscountedCost($cost);
                $promotionId = $promotion->id;
            }
        } else {
            // ตรวจสอบโปรโมชั่นอัตโนมัติ (ไม่ต้องกรอกโค้ด)
            $autoPromotion = AiGenPromotion::active()
                ->forType($type)
                ->whereNull('code')
                ->where(function ($q) use ($provider) {
                    $q->whereNull('provider_id')->orWhere('provider_id', $provider->id);
                })
                ->orderBy('value', 'desc')
                ->first();

            if ($autoPromotion && $autoPromotion->canBeUsedBy($user)) {
                $cost = $autoPromotion->calculateDiscountedCost($cost);
                $promotionId = $autoPromotion->id;
            }
        }

        // ถ้า cost = 0 (โปรโมชั่นฟรี)
        if ($cost <= 0) {
            return [
                'can_generate' => true,
                'is_free_quota' => false,
                'payment_method' => 'wallet',
                'wallet_cost' => 0,
                'original_cost' => $originalCost,
                'promotion_id' => $promotionId,
            ];
        }

        // ตรวจสอบยอดเงินใน wallet
        try {
            $wallet = $this->walletService->getOrCreateWallet($user);

            if (! $wallet->isActive()) {
                return ['can_generate' => false, 'reason' => 'Wallet ไม่สามารถใช้งานได้'];
            }

            if ($wallet->balance < $cost) {
                return [
                    'can_generate' => false,
                    'reason' => 'ยอดเงินใน Wallet ไม่เพียงพอ (ต้องการ ฿' . number_format($cost, 2) . ' มี ฿' . number_format($wallet->balance, 2) . ')',
                ];
            }

            return [
                'can_generate' => true,
                'is_free_quota' => false,
                'payment_method' => 'wallet',
                'wallet_cost' => $cost,
                'original_cost' => $originalCost,
                'promotion_id' => $promotionId,
            ];
        } catch (\Exception $e) {
            return ['can_generate' => false, 'reason' => 'ไม่สามารถตรวจสอบ Wallet ได้'];
        }
    }

    /**
     * ตรวจสอบว่าพื้นที่เก็บภาพยังเพียงพอหรือไม่
     *
     * @return bool true ถ้ายังมีพื้นที่เพียงพอ
     */
    protected function checkStorageAvailable(): bool
    {
        $maxStorageMb = (int) Setting::get('ai_gen_max_storage_mb', 500);

        // ถ้าตั้ง 0 = ไม่จำกัดพื้นที่
        if ($maxStorageMb <= 0) {
            return true;
        }

        try {
            $disk = Storage::disk('public');
            $basePath = 'ai-gen';
            $totalBytes = 0;

            if ($disk->exists($basePath)) {
                $files = $disk->allFiles($basePath);
                foreach ($files as $file) {
                    $totalBytes += $disk->size($file);
                }
            }

            $usedMb = $totalBytes / 1024 / 1024;

            return $usedMb < $maxStorageMb;
        } catch (\Exception $e) {
            // ถ้าเช็คไม่ได้ ให้อนุญาตไปก่อน
            return true;
        }
    }

    /**
     * อัพเดทข้อมูลผลลัพธ์ของ generation
     */
    protected function updateGenerationWithResult(AiGenGeneration $generation, array $result): void
    {
        $updateData = [
            'status' => 'completed',
        ];

        if (isset($result['images']) && is_array($result['images']) && ! empty($result['images'])) {
            $updateData['file_url'] = $result['images'][0]['url'] ?? null;
            $updateData['thumbnail_url'] = $result['images'][0]['thumbnail'] ?? null;
        }

        if (isset($result['video_url'])) {
            $updateData['file_url'] = $result['video_url'];
        }

        $generation->update($updateData);
    }

    /**
     * ตรวจสอบสถานะ generation
     */
    public function checkGenerationStatus(int $generationId, User $user): array
    {
        $generation = AiGenGeneration::where('id', $generationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $generation) {
            return [
                'success' => false,
                'error' => 'ไม่พบผลงาน',
            ];
        }

        // ถ้าเสร็จแล้ว ส่งข้อมูลกลับเลย
        if ($generation->status === 'completed' || $generation->status === 'failed') {
            return [
                'success' => true,
                'status' => $generation->status,
                'data' => $generation,
            ];
        }

        // ตรวจสอบกับ provider
        if ($generation->external_id) {
            $providerInstance = AiGenProviderFactory::createFromModel($generation->provider);

            if ($providerInstance) {
                $result = $providerInstance->checkStatus($generation->external_id);

                if ($result['success']) {
                    $generation->update([
                        'status' => $result['status'],
                        'external_data' => $result['data'],
                    ]);

                    if ($result['status'] === 'completed') {
                        $this->updateGenerationWithResult($generation, $result['data']);
                    }
                }
            }
        }

        return [
            'success' => true,
            'status' => $generation->status,
            'data' => $generation,
        ];
    }

    /**
     * ดึงผลงานของผู้ใช้
     */
    public function getUserGenerations(User $user, array $filters = [])
    {
        $query = AiGenGeneration::where('user_id', $user->id);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', $filters['is_favorite']);
        }

        return $query->with(['provider', 'usageLog'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * ดึงข้อมูล dashboard ของผู้ใช้
     */
    public function getUserDashboard(User $user): array
    {
        // ข้อมูล wallet (ถ้าเปิดใช้งาน)
        $walletData = null;
        $walletEnabled = Setting::get('ai_gen_wallet_enabled', false);

        if ($walletEnabled) {
            try {
                $wallet = $this->walletService->getOrCreateWallet($user);
                $walletData = [
                    'enabled' => true,
                    'balance' => $wallet->balance,
                    'cost_per_image' => Setting::get('ai_gen_wallet_cost_image', 0),
                    'cost_per_video' => Setting::get('ai_gen_wallet_cost_video', 0),
                ];
            } catch (\Exception $e) {
                $walletData = ['enabled' => true, 'balance' => 0, 'error' => true];
            }
        }

        // ดึงโปรโมชั่นที่ใช้ได้
        $activePromotions = AiGenPromotion::active()->get()->map(function ($promo) {
            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'value' => $promo->value,
                'code' => $promo->code,
                'applies_to' => $promo->applies_to,
                'expires_at' => $promo->expires_at?->toDateTimeString(),
            ];
        });

        return [
            'quota' => $this->quotaService->getQuotaSummary($user),
            'subscription' => $this->subscriptionService->getSubscriptionSummary($user),
            'wallet' => $walletData,
            'promotions' => $activePromotions,
            'stats' => [
                'total_generations' => AiGenGeneration::where('user_id', $user->id)->count(),
                'completed_generations' => AiGenGeneration::where('user_id', $user->id)
                    ->where('status', 'completed')->count(),
                'this_month' => AiGenGeneration::where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)->count(),
            ],
            'providers' => AiGenProviderFactory::getAvailableProviders(),
        ];
    }
}
