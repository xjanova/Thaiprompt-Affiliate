<?php

namespace App\Services;

use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\TarotCommission;
use App\Models\TarotReading;
use App\Models\TarotReadingCategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TarotCommissionService - จัดการการหักค่าบริการและแบ่งคอมมิชชั่นจากการทำนายไพ่
 *
 * ขั้นตอนการทำงาน:
 * 1. หักเงินจาก Wallet ของผู้ใช้ (ถ้าชำระผ่าน wallet)
 * 2. คำนวณ Platform Fee และ PV
 * 3. แบ่งคอมมิชชั่นให้ upline ตาม MLM settings
 * 4. บันทึกประวัติ commission และ PV transaction
 */
class TarotCommissionService
{
    protected WalletService $walletService;

    /**
     * Constructor
     */
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * ประมวลผลการชำระเงินและแบ่งคอมมิชชั่น
     *
     * @param  TarotReading  $reading  การอ่านไพ่ที่ต้องการประมวลผล
     * @param  string  $paymentMethod  วิธีการชำระเงิน (wallet, promptpay, credit_card, bank_transfer)
     * @param  int|null  $paymentTransactionId  ID ของ payment transaction
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function processPayment(TarotReading $reading, string $paymentMethod, ?int $paymentTransactionId = null): array
    {
        // ข้ามถ้าเป็นการอ่านฟรี
        if ($reading->is_free || $reading->amount_paid <= 0) {
            return [
                'success' => true,
                'message' => 'การอ่านฟรี - ไม่มีการหักเงินหรือแบ่งคอมมิชชั่น',
                'data' => [],
            ];
        }

        try {
            return DB::transaction(function () use ($reading, $paymentMethod, $paymentTransactionId) {
                $category = $reading->category;
                $user = $reading->user;
                $amount = $reading->amount_paid;

                // 1. คำนวณค่าต่างๆ
                $platformFee = $this->calculatePlatformFee($amount, $category);
                $pvAmount = $this->calculatePvAmount($amount, $category);
                $netAmount = $amount - $platformFee;

                // 2. หักเงินจาก Wallet (ถ้าชำระผ่าน wallet)
                if ($paymentMethod === 'wallet' && $user) {
                    $deductResult = $this->deductFromWallet($user, $amount, $reading);
                    if (! $deductResult['success']) {
                        throw new \Exception($deductResult['message']);
                    }
                }

                // 3. อัพเดทข้อมูลใน reading
                $reading->update([
                    'platform_fee' => $platformFee,
                    'pv_amount' => $pvAmount,
                    'payment_method' => $paymentMethod,
                    'payment_transaction_id' => $paymentTransactionId,
                    'commission_status' => 'pending',
                ]);

                // 4. แบ่งคอมมิชชั่น (ถ้าเปิดใช้งาน)
                $commissions = [];
                if ($category->commission_enabled && $user && $pvAmount > 0) {
                    $commissions = $this->distributeCommissions($reading, $user, $pvAmount);
                }

                // 5. อัพเดทสถานะ commission
                $totalCommission = collect($commissions)->sum('commission_amount');
                $reading->update([
                    'total_commission' => $totalCommission,
                    'commission_status' => 'processed',
                ]);

                Log::info('Tarot payment processed', [
                    'reading_id' => $reading->id,
                    'amount' => $amount,
                    'platform_fee' => $platformFee,
                    'pv_amount' => $pvAmount,
                    'total_commission' => $totalCommission,
                    'commissions_count' => count($commissions),
                ]);

                return [
                    'success' => true,
                    'message' => 'ประมวลผลการชำระเงินสำเร็จ',
                    'data' => [
                        'amount' => $amount,
                        'platform_fee' => $platformFee,
                        'pv_amount' => $pvAmount,
                        'net_amount' => $netAmount,
                        'total_commission' => $totalCommission,
                        'commissions' => $commissions,
                    ],
                ];
            });
        } catch (\Exception $e) {
            Log::error('Tarot payment processing failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * คำนวณค่าบริการแพลตฟอร์ม
     *
     * @param  float  $amount  ยอดเงิน
     * @param  TarotReadingCategory  $category  หมวดหมู่
     */
    public function calculatePlatformFee(float $amount, TarotReadingCategory $category): float
    {
        $feePercentage = $category->platform_fee_percentage ?? 10.00;

        return round(($amount * $feePercentage) / 100, 2);
    }

    /**
     * คำนวณ PV
     *
     * @param  float  $amount  ยอดเงิน
     * @param  TarotReadingCategory  $category  หมวดหมู่
     */
    public function calculatePvAmount(float $amount, TarotReadingCategory $category): float
    {
        $pvPercentage = $category->pv_percentage ?? 10.00;

        return round(($amount * $pvPercentage) / 100, 2);
    }

    /**
     * หักเงินจาก Wallet ของผู้ใช้
     *
     * @param  User  $user  ผู้ใช้
     * @param  float  $amount  ยอดเงิน
     * @param  TarotReading  $reading  การอ่านไพ่
     */
    protected function deductFromWallet(User $user, float $amount, TarotReading $reading): array
    {
        $wallet = $user->wallet;

        if (! $wallet) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Wallet ของผู้ใช้',
            ];
        }

        if ($wallet->balance < $amount) {
            return [
                'success' => false,
                'message' => 'ยอดเงินใน Wallet ไม่เพียงพอ (ต้องการ ฿'.number_format($amount, 2).' มี ฿'.number_format($wallet->balance, 2).')',
            ];
        }

        try {
            // หักเงินผ่าน WalletService
            $this->walletService->deduct(
                $wallet,
                $amount,
                'tarot_reading',
                'ค่าบริการทำนายไพ่ทาโร่ต์ #'.$reading->id,
                [
                    'reading_id' => $reading->id,
                    'category' => $reading->category->name_th ?? 'ไม่ระบุ',
                ]
            );

            return [
                'success' => true,
                'message' => 'หักเงินสำเร็จ',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถหักเงินได้: '.$e->getMessage(),
            ];
        }
    }

    /**
     * แบ่งคอมมิชชั่นให้ upline
     *
     * @param  TarotReading  $reading  การอ่านไพ่
     * @param  User  $user  ผู้ใช้ที่ซื้อบริการ
     * @param  float  $pvAmount  PV ที่จะแบ่ง
     * @return array รายการคอมมิชชั่นที่แบ่งไป
     */
    protected function distributeCommissions(TarotReading $reading, User $user, float $pvAmount): array
    {
        $commissions = [];

        // หา MLM Member ของผู้ใช้
        $mlmMember = MlmMember::where('user_id', $user->id)->first();

        if (! $mlmMember) {
            Log::info('User is not MLM member, no commission distribution', [
                'user_id' => $user->id,
                'reading_id' => $reading->id,
            ]);

            return $commissions;
        }

        // ดึงค่าคอมมิชชั่นแต่ละ level จาก Global Settings
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);

        if (empty($unilevelLevels)) {
            // ใช้ค่า default ถ้าไม่มีการตั้งค่า
            $unilevelLevels = [
                'level_1' => 5,  // 5%
                'level_2' => 3,  // 3%
                'level_3' => 2,  // 2%
            ];
        }

        // หา upline path
        $currentMember = $mlmMember;
        $level = 1;
        $maxLevels = count($unilevelLevels);

        while ($level <= $maxLevels && $currentMember->sponsor_id) {
            $sponsor = MlmMember::find($currentMember->sponsor_id);

            if (! $sponsor || ! $sponsor->user) {
                break;
            }

            // ดึงอัตราคอมมิชชั่นของ level นี้
            $commissionRate = $this->resolveUnilevelRate($unilevelLevels, $level);

            if ($commissionRate > 0) {
                // คำนวณคอมมิชชั่น
                $commissionAmount = round(($pvAmount * $commissionRate) / 100, 2);
                $pvForUpline = round(($pvAmount * $commissionRate) / 100, 2);

                // สร้าง commission record
                $commission = TarotCommission::create([
                    'tarot_reading_id' => $reading->id,
                    'user_id' => $sponsor->user_id,
                    'mlm_member_id' => $sponsor->id,
                    'level' => $level,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'pv_amount' => $pvForUpline,
                    'status' => 'approved', // อนุมัติทันที
                ]);

                // เพิ่มเงินเข้า wallet ของ upline
                $this->addCommissionToWallet($sponsor->user, $commissionAmount, $reading, $level);

                $commissions[] = [
                    'user_id' => $sponsor->user_id,
                    'user_name' => $sponsor->user->name ?? 'Unknown',
                    'level' => $level,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'pv_amount' => $pvForUpline,
                ];

                Log::info('Tarot commission distributed', [
                    'reading_id' => $reading->id,
                    'recipient_user_id' => $sponsor->user_id,
                    'level' => $level,
                    'commission_amount' => $commissionAmount,
                ]);
            }

            $currentMember = $sponsor;
            $level++;
        }

        return $commissions;
    }

    /**
     * แปลงค่า unilevel_levels เป็นอัตรา % ของ level ที่กำหนด
     *
     * 🐛 Fix 2026-06-12 (Bug #12 ตกหล่นไฟล์นี้): ระบบกลางเก็บ unilevel_levels
     * เป็น array of objects [{level: N, percentage: X}] (ตาม MlmCommissionService)
     * แต่ไฟล์นี้เคยอ่านแบบ string key 'level_N' → อัตราเป็น 0 เงียบทุก level
     *
     * รองรับทั้ง 2 format เพื่อ backward compatibility:
     * 1. array of objects: [{level: 1, percentage: 5}, ...]
     * 2. legacy string keys: ['level_1' => 5, ...] (ค่า default ของไฟล์นี้)
     *
     * @param  array  $unilevelLevels  ค่าจาก MlmGlobalSetting
     * @param  int  $level  ระดับที่ต้องการอัตรา
     * @return float อัตราคอมมิชชั่น (%)
     */
    protected function resolveUnilevelRate(array $unilevelLevels, int $level): float
    {
        // Format ใหม่: array of objects [{level: N, percentage: X}]
        $levelConfig = collect($unilevelLevels)->firstWhere('level', $level);
        if (is_array($levelConfig)) {
            return (float) ($levelConfig['percentage'] ?? 0);
        }

        // Legacy format: ['level_N' => X]
        $legacy = $unilevelLevels["level_{$level}"] ?? null;
        if (is_numeric($legacy)) {
            return (float) $legacy;
        }

        // Fallback: index-based [X, Y, Z] (เผื่อเก็บเป็น array ตัวเลขล้วน)
        $indexed = $unilevelLevels[$level - 1] ?? null;
        if (is_numeric($indexed)) {
            return (float) $indexed;
        }

        return 0.0;
    }

    /**
     * เพิ่มคอมมิชชั่นเข้า Wallet ของ upline
     *
     * @param  User  $user  ผู้รับคอมมิชชั่น
     * @param  float  $amount  จำนวนคอมมิชชั่น
     * @param  TarotReading  $reading  การอ่านไพ่
     * @param  int  $level  ระดับ MLM
     */
    protected function addCommissionToWallet(User $user, float $amount, TarotReading $reading, int $level): void
    {
        try {
            $wallet = $user->wallet;

            if (! $wallet) {
                // สร้าง wallet ใหม่ถ้ายังไม่มี
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => 'THB',
                ]);
            }

            $this->walletService->deposit(
                $wallet,
                $amount,
                'tarot_commission',
                "คอมมิชชั่นไพ่ทาโร่ต์ Level {$level} จากการทำนาย #{$reading->id}",
                [
                    'reading_id' => $reading->id,
                    'level' => $level,
                    'buyer_user_id' => $reading->user_id,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to add tarot commission to wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * คำนวณรายได้สุทธิของระบบ (หลังหักคอมมิชชั่น)
     */
    public function calculateNetRevenue(TarotReading $reading): float
    {
        $amount = $reading->amount_paid;
        $platformFee = $reading->platform_fee ?? 0;
        $totalCommission = $reading->total_commission ?? 0;

        // Platform Fee คือรายได้ของระบบ (หลังจากจ่ายคอมมิชชั่นแล้ว)
        return $platformFee;
    }

    /**
     * ดึงสรุปคอมมิชชั่นของผู้ใช้
     *
     * @param  string|null  $period  today|week|month|all
     */
    public function getUserCommissionSummary(int $userId, ?string $period = 'all'): array
    {
        $query = TarotCommission::where('user_id', $userId);

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
        }

        $commissions = $query->get();

        return [
            'total_count' => $commissions->count(),
            'total_commission' => $commissions->sum('commission_amount'),
            'total_pv' => $commissions->sum('pv_amount'),
            'pending' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'approved' => $commissions->where('status', 'approved')->sum('commission_amount'),
            'paid' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'by_level' => $commissions->groupBy('level')->map(function ($items, $level) {
                return [
                    'level' => $level,
                    'count' => $items->count(),
                    'total' => $items->sum('commission_amount'),
                ];
            })->values()->toArray(),
        ];
    }
}
