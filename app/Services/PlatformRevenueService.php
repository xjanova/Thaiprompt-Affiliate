<?php

namespace App\Services;

use App\Models\PlatformWallet;
use App\Models\PlatformTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * PlatformRevenueService
 *
 * จัดการรายได้ Platform - เก็บค่า Fee, VAT, MLM Pool
 */
class PlatformRevenueService
{
    /**
     * เก็บค่า Platform Fee
     *
     * @param float $amount จำนวนเงิน
     * @param string $sourceType ที่มา (Order, ServiceBooking)
     * @param int $sourceId ID ที่มา
     * @param int|null $relatedUserId Seller/Provider ID
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return PlatformTransaction
     */
    public function collectPlatformFee(
        float $amount,
        string $sourceType,
        int $sourceId,
        ?int $relatedUserId = null,
        array $metadata = []
    ): PlatformTransaction {
        $wallet = PlatformWallet::getFeeWallet();

        $transaction = $wallet->addFunds(
            $amount,
            'order_fee',
            $sourceType,
            $sourceId,
            $metadata
        );

        $transaction->update([
            'related_user_id' => $relatedUserId,
            'description' => "ค่าธรรมเนียมจาก {$sourceType} #{$sourceId}",
        ]);

        return $transaction;
    }

    /**
     * เก็บภาษี VAT
     *
     * @param float $amount จำนวนภาษี
     * @param string $sourceType ที่มา
     * @param int $sourceId ID ที่มา
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return PlatformTransaction
     */
    public function collectVat(
        float $amount,
        string $sourceType,
        int $sourceId,
        array $metadata = []
    ): PlatformTransaction {
        $wallet = PlatformWallet::getVatWallet();

        $transaction = $wallet->addFunds(
            $amount,
            'vat_collection',
            $sourceType,
            $sourceId,
            $metadata
        );

        $transaction->update([
            'description' => "ภาษี VAT จาก {$sourceType} #{$sourceId}",
        ]);

        return $transaction;
    }

    /**
     * เก็บเงินเข้ากองทุน MLM Commission
     *
     * @param float $amount จำนวนเงิน
     * @param string $sourceType ที่มา
     * @param int $sourceId ID ที่มา
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return PlatformTransaction
     */
    public function collectMlmPool(
        float $amount,
        string $sourceType,
        int $sourceId,
        array $metadata = []
    ): PlatformTransaction {
        $wallet = PlatformWallet::getMlmPoolWallet();

        $transaction = $wallet->addFunds(
            $amount,
            'mlm_commission_pool',
            $sourceType,
            $sourceId,
            $metadata
        );

        $transaction->update([
            'description' => "เงินกองทุน MLM จาก {$sourceType} #{$sourceId}",
        ]);

        return $transaction;
    }

    /**
     * บันทึกรายได้เข้ากระเป๋าที่ระบุ
     *
     * @param string $walletSlug ชื่อ slug ของกระเป๋า (admin_shop, admin_services, etc.)
     * @param float $amount จำนวนเงิน
     * @param string $subType ประเภทย่อย
     * @param string $description คำอธิบาย
     * @param string|null $sourceType ที่มา
     * @param int|null $sourceId ID ที่มา
     * @param int|null $relatedUserId User ID ที่เกี่ยวข้อง
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return PlatformTransaction|null
     */
    public function recordIncome(
        string $walletSlug,
        float $amount,
        string $subType,
        string $description,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $relatedUserId = null,
        array $metadata = []
    ): ?PlatformTransaction {
        // ดึงกระเป๋าตาม slug
        $wallet = PlatformWallet::findBySlug($walletSlug);

        if (!$wallet) {
            // ลองสร้างกระเป๋าใหม่ถ้าไม่พบ
            $wallet = match ($walletSlug) {
                'admin_shop' => PlatformWallet::getAdminShopWallet(),
                'admin_services' => PlatformWallet::getAdminServicesWallet(),
                'fee' => PlatformWallet::getFeeWallet(),
                'vat' => PlatformWallet::getVatWallet(),
                'mlm_pool' => PlatformWallet::getMlmPoolWallet(),
                'refund_pool' => PlatformWallet::getRefundPoolWallet(),
                default => null,
            };
        }

        if (!$wallet) {
            return null;
        }

        $transaction = $wallet->addFunds(
            $amount,
            $subType,
            $sourceType,
            $sourceId,
            $metadata
        );

        $transaction->update([
            'description' => $description,
            'related_user_id' => $relatedUserId,
        ]);

        return $transaction;
    }

    /**
     * จ่ายคอมมิชชัน MLM จากกองทุน
     *
     * @param float $amount จำนวนเงิน
     * @param int $userId ผู้รับ
     * @param string $sourceType ที่มา
     * @param int $sourceId ID ที่มา
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return PlatformTransaction
     */
    public function payMlmCommission(
        float $amount,
        int $userId,
        string $sourceType,
        int $sourceId,
        array $metadata = []
    ): PlatformTransaction {
        $wallet = PlatformWallet::getMlmPoolWallet();

        $transaction = $wallet->deductFunds(
            $amount,
            'mlm_payout',
            $sourceType,
            $sourceId,
            $metadata
        );

        $transaction->update([
            'related_user_id' => $userId,
            'description' => "จ่ายคอมมิชชัน MLM ให้ User #{$userId}",
        ]);

        return $transaction;
    }

    /**
     * ดึงสรุปรายได้ Platform
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getRevenueSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = PlatformTransaction::query()->completed();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $income = (clone $query)->income()->sum('amount');
        $expense = (clone $query)->expense()->sum('amount');

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_profit' => $income - $expense,
            'profit_margin' => $income > 0 ? (($income - $expense) / $income) * 100 : 0,
        ];
    }

    /**
     * ดึงสรุปรายได้แยกตามประเภท
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return Collection
     */
    public function getRevenueByType(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = PlatformTransaction::query()
            ->completed()
            ->income()
            ->selectRaw('sub_type, SUM(amount) as total')
            ->groupBy('sub_type');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * ดึงยอดรายได้รายวัน (สำหรับ Chart)
     *
     * @param int $days จำนวนวัน
     * @return Collection
     */
    public function getDailyRevenue(int $days = 30): Collection
    {
        return PlatformTransaction::query()
            ->completed()
            ->income()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * ดึงยอดรายได้รายเดือน (สำหรับ Chart)
     *
     * @param int $months จำนวนเดือน
     * @return Collection
     */
    public function getMonthlyRevenue(int $months = 12): Collection
    {
        return PlatformTransaction::query()
            ->completed()
            ->income()
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * ดึงยอดคงเหลือทุกกระเป๋า
     *
     * @return Collection
     */
    public function getAllWalletBalances(): Collection
    {
        return PlatformWallet::where('is_active', true)
            ->orderBy('type')
            ->get(['id', 'name', 'slug', 'type', 'balance', 'total_income', 'total_expense']);
    }

    /**
     * ดึง transactions ล่าสุด
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentTransactions(int $limit = 20): Collection
    {
        return PlatformTransaction::with(['wallet', 'relatedUser'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสถิติสำหรับ Dashboard
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();

        return [
            'today' => [
                'income' => PlatformTransaction::income()->completed()->where('created_at', '>=', $today)->sum('amount'),
                'expense' => PlatformTransaction::expense()->completed()->where('created_at', '>=', $today)->sum('amount'),
            ],
            'this_month' => [
                'income' => PlatformTransaction::income()->completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
                'expense' => PlatformTransaction::expense()->completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
            ],
            'this_year' => [
                'income' => PlatformTransaction::income()->completed()->where('created_at', '>=', $thisYear)->sum('amount'),
                'expense' => PlatformTransaction::expense()->completed()->where('created_at', '>=', $thisYear)->sum('amount'),
            ],
            'all_time' => [
                'income' => PlatformTransaction::income()->completed()->sum('amount'),
                'expense' => PlatformTransaction::expense()->completed()->sum('amount'),
            ],
            'wallets' => $this->getAllWalletBalances(),
        ];
    }

    /**
     * โอนเงินระหว่างกระเป๋า
     *
     * @param PlatformWallet $fromWallet
     * @param PlatformWallet $toWallet
     * @param float $amount
     * @param string|null $description
     * @param int|null $processedBy
     * @return array [from_transaction, to_transaction]
     */
    public function transferBetweenWallets(
        PlatformWallet $fromWallet,
        PlatformWallet $toWallet,
        float $amount,
        ?string $description = null,
        ?int $processedBy = null
    ): array {
        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description, $processedBy) {
            // หักจากกระเป๋าต้นทาง
            $fromTransaction = $fromWallet->deductFunds(
                $amount,
                'transfer_out',
                'PlatformWallet',
                $toWallet->id,
                ['transfer_to' => $toWallet->slug]
            );

            $fromTransaction->update([
                'type' => 'transfer_out',
                'description' => $description ?? "โอนไปยัง {$toWallet->name}",
                'processed_by' => $processedBy,
            ]);

            // เพิ่มเข้ากระเป๋าปลายทาง
            $toTransaction = $toWallet->addFunds(
                $amount,
                'transfer_in',
                'PlatformWallet',
                $fromWallet->id,
                ['transfer_from' => $fromWallet->slug]
            );

            $toTransaction->update([
                'type' => 'transfer_in',
                'description' => $description ?? "รับโอนจาก {$fromWallet->name}",
                'processed_by' => $processedBy,
            ]);

            return [
                'from_transaction' => $fromTransaction,
                'to_transaction' => $toTransaction,
            ];
        });
    }
}
