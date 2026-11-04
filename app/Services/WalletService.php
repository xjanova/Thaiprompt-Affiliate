<?php

namespace App\Services;

use App\Exceptions\WalletAdminException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdminAdjustment;
use App\Models\WalletLog;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Create a new wallet for user
     */
    public function createWallet(User $user, string $currency = 'THB'): Wallet
    {
        try {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'currency' => $currency,
                'balance' => 0.00,
                'status' => 'active',
            ]);

            $this->logAction($wallet, 'settings_changed', 'Wallet created', 'info');

            return $wallet;
        } catch (Exception $e) {
            Log::error('Failed to create wallet: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        $wallet = $user->wallet ?? Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            $wallet = $this->createWallet($user);
        }

        return $wallet;
    }

    /**
     * ประเภทรายการที่ใช้กับ "เงินเข้า" ได้ (ตรงกับ enum wallet_transactions.type)
     */
    public const CREDIT_TYPES = ['deposit', 'commission', 'refund', 'bonus', 'transfer_in'];

    /**
     * ประเภทรายการที่ใช้กับ "เงินออกโดยระบบ" ได้ (ตรงกับ enum wallet_transactions.type)
     */
    public const DEBIT_TYPES = ['fee', 'withdrawal', 'transfer_out'];

    /**
     * Deposit money to wallet
     *
     * @param  string  $type  ประเภทรายการ (deposit|commission|refund|bonus|transfer_in) — ค่าเริ่มต้น deposit
     *                        (2026-09-25) เพิ่มเพื่อให้คืนเงิน/จ่ายคอมบันทึกประเภทถูกต้องโดยไม่ต้อง insert เอง
     */
    public function deposit(
        Wallet $wallet,
        float $amount,
        string $description = 'Deposit',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = [],
        string $type = 'deposit'
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (! in_array($type, self::CREDIT_TYPES, true)) {
            throw new Exception('Invalid credit transaction type');
        }

        if (! $wallet->isActive()) {
            throw new Exception('Wallet is not active');
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId, $metadata, $type) {
            // Lock wallet เพื่อป้องกัน race condition: อ่าน balance ล่าสุดจาก DB
            $wallet = Wallet::lockForUpdate()->find($wallet->id);
            $balanceBefore = $wallet->balance;
            $balanceAfter = round((float) $balanceBefore + $amount, 2);

            // Create transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'completed',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $balanceAfter,
                'total_income' => round((float) ($wallet->total_income ?? 0) + $amount, 2),
                'last_transaction_at' => now(),
            ]);

            // Log action
            $this->logAction(
                $wallet,
                'transaction_success',
                "Deposit of {$amount} {$wallet->currency}",
                'info',
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(
        Wallet $wallet,
        float $amount,
        string $pin,
        string $description = 'Withdrawal',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (! $wallet->isActive()) {
            throw new Exception('Wallet is not active');
        }

        if ($wallet->balance < $amount) {
            $this->logAction($wallet, 'transaction_failed', 'Insufficient balance', 'warning');
            throw new Exception('Insufficient balance');
        }

        // Verify PIN
        if ($wallet->hasPIN() && ! $wallet->verifyPIN($pin)) {
            $wallet->incrementFailedAttempts();
            $this->logAction($wallet, 'pin_failed', 'Invalid PIN attempt', 'warning');
            throw new Exception('Invalid PIN');
        }

        $wallet->resetFailedAttempts();

        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId, $metadata) {
            // Lock wallet เพื่อป้องกัน race condition: อ่าน balance ล่าสุดจาก DB
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            // Re-check balance หลัง lock (balance อาจเปลี่ยนระหว่าง pre-check กับ lock)
            if ($wallet->balance < $amount) {
                throw new Exception('ยอดเงินไม่เพียงพอ (ถูกใช้ไประหว่างรอ)');
            }

            // ไรเดอร์ที่ถือเงินสด COD อยู่ ถอนออกได้เฉพาะส่วนที่ไม่ติดภาระนำส่ง
            $this->assertCodReserveAllows($wallet, $amount);

            $balanceBefore = $wallet->balance;
            $balanceAfter = round((float) $balanceBefore - $amount, 2);

            // Create transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'completed',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $balanceAfter,
                'total_expense' => round((float) ($wallet->total_expense ?? 0) + $amount, 2),
                'last_transaction_at' => now(),
            ]);

            // Log action
            $this->logAction(
                $wallet,
                'transaction_success',
                "Withdrawal of {$amount} {$wallet->currency}",
                'info',
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });
    }

    /**
     * หักเงินจาก wallet สำหรับบริการของระบบ (ไม่ต้องใช้ PIN)
     *
     * ใช้สำหรับ: AI Gen, Service Booking ฯลฯ ที่ผู้ใช้ยืนยันแล้วผ่านหน้า UI
     *
     * @param  Wallet  $wallet  กระเป๋าเงิน
     * @param  float  $amount  จำนวนเงิน
     * @param  string  $description  คำอธิบาย
     * @param  string|null  $referenceType  ประเภทอ้างอิง (เช่น ai_gen, service_booking)
     * @param  int|null  $referenceId  ID อ้างอิง
     * @param  array  $metadata  ข้อมูลเพิ่มเติม
     *
     * @throws Exception
     */
    public function deductForService(
        Wallet $wallet,
        float $amount,
        string $description = 'Service payment',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = [],
        string $type = 'fee'
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (! in_array($type, self::DEBIT_TYPES, true)) {
            throw new Exception('Invalid debit transaction type');
        }

        if (! $wallet->isActive()) {
            throw new Exception('Wallet is not active');
        }

        if ($wallet->balance < $amount) {
            throw new Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId, $metadata, $type) {
            // Lock wallet เพื่อป้องกัน race condition
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            if ($wallet->balance < $amount) {
                throw new Exception('ยอดเงินไม่เพียงพอ (ถูกใช้ไประหว่างรอ)');
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = round((float) $balanceBefore - $amount, 2);

            // สร้างรายการธุรกรรม
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'completed',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            // อัพเดทยอดเงินใน wallet
            $wallet->update([
                'balance' => $balanceAfter,
                'total_expense' => round((float) ($wallet->total_expense ?? 0) + $amount, 2),
                'last_transaction_at' => now(),
            ]);

            $this->logAction(
                $wallet,
                'transaction_success',
                "Service deduction: {$amount} {$wallet->currency} - {$description}",
                'info',
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });
    }

    /**
     * Transfer money between wallets
     */
    public function transfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $pin,
        string $description = 'Transfer'
    ): array {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (! $fromWallet->isActive() || ! $toWallet->isActive()) {
            throw new Exception('One or both wallets are not active');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new Exception('Cannot transfer to the same wallet');
        }

        if ($fromWallet->balance < $amount) {
            $this->logAction($fromWallet, 'transaction_failed', 'Insufficient balance for transfer', 'warning');
            throw new Exception('Insufficient balance');
        }

        // Verify PIN
        if ($fromWallet->hasPIN() && ! $fromWallet->verifyPIN($pin)) {
            $fromWallet->incrementFailedAttempts();
            $this->logAction($fromWallet, 'pin_failed', 'Invalid PIN attempt for transfer', 'warning');
            throw new Exception('Invalid PIN');
        }

        $fromWallet->resetFailedAttempts();

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // Lock ทั้ง 2 wallets ตามลำดับ ID เพื่อป้องกัน deadlock
            $lockIds = [$fromWallet->id, $toWallet->id];
            sort($lockIds);
            $lockedWallets = Wallet::whereIn('id', $lockIds)->lockForUpdate()->get()->keyBy('id');
            $fromWallet = $lockedWallets[$fromWallet->id];
            $toWallet = $lockedWallets[$toWallet->id];

            // Re-check balance หลัง lock
            if ($fromWallet->balance < $amount) {
                throw new Exception('ยอดเงินไม่เพียงพอ (ถูกใช้ไประหว่างรอ)');
            }

            // ไรเดอร์ที่ถือเงินสด COD อยู่ โอนออกได้เฉพาะส่วนที่ไม่ติดภาระนำส่ง
            $this->assertCodReserveAllows($fromWallet, $amount);

            // Deduct from sender
            $fromBalanceBefore = $fromWallet->balance;
            $fromBalanceAfter = $fromBalanceBefore - $amount;

            $outTransaction = WalletTransaction::create([
                'wallet_id' => $fromWallet->id,
                'user_id' => $fromWallet->user_id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'currency' => $fromWallet->currency,
                'description' => $description,
                'related_wallet_id' => $toWallet->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $fromWallet->update([
                'balance' => $fromBalanceAfter,
                'total_expense' => ($fromWallet->total_expense ?? 0) + $amount,
                'last_transaction_at' => now(),
            ]);

            // Add to receiver
            $toBalanceBefore = $toWallet->balance;
            $toBalanceAfter = $toBalanceBefore + $amount;

            $inTransaction = WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'user_id' => $toWallet->user_id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'currency' => $toWallet->currency,
                'description' => $description,
                'related_wallet_id' => $fromWallet->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $toWallet->update([
                'balance' => $toBalanceAfter,
                'total_income' => ($toWallet->total_income ?? 0) + $amount,
                'last_transaction_at' => now(),
            ]);

            // Log actions
            $this->logAction($fromWallet, 'transaction_success', "Transfer out of {$amount} {$fromWallet->currency}", 'info');
            $this->logAction($toWallet, 'transaction_success', "Transfer in of {$amount} {$toWallet->currency}", 'info');

            return [
                'out_transaction' => $outTransaction,
                'in_transaction' => $inTransaction,
            ];
        });
    }

    /**
     * ไรเดอร์ที่ถือเงินเก็บปลายทาง (COD) ของลูกค้าอยู่ ถอน/โอนออกได้ไม่เกินยอดที่ไม่ติดภาระนำส่ง
     *
     * ภาระ = RiderJob::codReserveForUser() (งาน COD ที่ยังวิ่งอยู่ + ส่งแล้วแต่ยังหักไม่ได้)
     * ผู้ใช้ที่ไม่ใช่ไรเดอร์ได้ภาระ 0 → ไม่กระทบ · ต้องเรียกหลังล็อกแถววอลเลตแล้วเท่านั้น
     *
     * @throws Exception ข้อความภาษาไทยบอกยอดที่ถอน/โอนได้
     */
    protected function assertCodReserveAllows(Wallet $wallet, float $amount): void
    {
        $reserve = \App\Models\RiderJob::codReserveForUser((int) $wallet->user_id);
        if ($reserve <= 0) {
            return;
        }

        $free = round((float) $wallet->balance - $reserve, 2);
        if (round($amount, 2) > $free) {
            $this->logAction($wallet, 'transaction_failed', 'Blocked by rider COD reserve', 'warning', [
                'reserve' => $reserve,
                'amount' => round($amount, 2),
            ]);

            throw new Exception(
                'ยอด '.number_format($reserve, 2).' บาท ถูกกันไว้สำหรับนำส่งเงินเก็บปลายทางของงานไรเดอร์ '
                .'ตอนนี้ถอน/โอนได้สูงสุด '.number_format(max(0.0, $free), 2).' บาท'
            );
        }
    }

    /**
     * Add commission to wallet
     */
    public function addCommission(
        Wallet $wallet,
        float $amount,
        int $commissionId,
        string $description = 'Commission'
    ): WalletTransaction {
        return $this->deposit(
            $wallet,
            $amount,
            $description,
            'commission',
            $commissionId,
            ['type' => 'affiliate_commission']
        );
    }

    /**
     * แอดมินรีเซ็ต PIN กระเป๋าเงิน → ล้าง PIN + ปลดล็อกจากการกรอกผิด (ผู้ใช้ตั้ง PIN ใหม่เองได้) + บันทึก log ระดับ warning
     *
     * ใช้ร่วมกันระหว่างหลังบ้านเว็บ (Admin\WalletController::resetUserPin) และแอปแอดมิน
     * ⚠️ ผู้เรียกต้องตรวจสิทธิ์เอง (super admin เท่านั้น — ตามหน้าเว็บ)
     */
    public function adminResetPin(Wallet $wallet, User $admin): void
    {
        $wallet->update([
            'pin_hash' => null,
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->logAction(
            $wallet,
            'pin_changed',
            'PIN ถูกรีเซ็ตโดยแอดมิน: '.$admin->name,
            'warning'
        );
    }

    /**
     * Log wallet action
     */
    public function logAction(
        Wallet $wallet,
        string $action,
        string $description,
        string $severity = 'info',
        array $metadata = []
    ): WalletLog {
        return WalletLog::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'action' => $action,
            'description' => $description,
            'severity' => $severity,
            'metadata' => $metadata,
        ]);
    }

    /**
     * สรุปสถิติกระเป๋าเงิน
     *
     * ⚠️ ห้ามคืน Eloquent model ดิบในอาเรย์นี้เด็ดขาด
     *    ผู้เรียกบางรายส่งค่าไปให้ Blade echo ตรงๆ (`{{ $stat }}`) และบางรายส่งลง JSON API
     *    ถ้าเป็น model จะถูกแปลงเป็น JSON ทั้งก้อน → หลุด `ip_address` / `user_agent`
     *    ของลูกค้าขึ้นหน้าจอ (เคยเกิดจริงที่การ์ด "Last Transaction" หน้ากระเป๋าเงิน)
     *    คืนเป็นอาเรย์ที่คัดเฉพาะฟิลด์ที่ปลอดภัยเท่านั้น
     *
     * @return array<string, mixed>
     */
    public function getWalletStatistics(Wallet $wallet): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $monthStart = now()->startOfMonth();

        // ประเภทธุรกรรมฝั่งรายรับ / รายจ่าย (ใช้ซ้ำหลายคิวรี)
        $incomeTypes = ['deposit', 'transfer_in', 'commission', 'bonus'];
        $expenseTypes = ['withdrawal', 'transfer_out', 'fee'];

        $latest = $wallet->transactions()->latest()->first();

        return [
            'balance' => $wallet->balance,
            'total_income' => $wallet->total_income,
            'total_expense' => $wallet->total_expense,
            'transactions_count' => $wallet->transactions()->count(),
            'last_30_days_income' => $wallet->transactions()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereIn('type', $incomeTypes)
                ->sum('amount'),
            'last_30_days_expense' => $wallet->transactions()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereIn('type', $expenseTypes)
                ->sum('amount'),
            // เดือนนี้ — MobileApiController::getWallet() อ่าน 2 คีย์นี้อยู่
            // ก่อนหน้านี้ไม่เคยถูกสร้าง แอปมือถือจึงได้ 0 เสมอ
            'this_month_income' => $wallet->transactions()
                ->where('created_at', '>=', $monthStart)
                ->whereIn('type', $incomeTypes)
                ->sum('amount'),
            'this_month_expense' => $wallet->transactions()
                ->where('created_at', '>=', $monthStart)
                ->whereIn('type', $expenseTypes)
                ->sum('amount'),
            // สรุปธุรกรรมล่าสุดแบบย่อ (ไม่มี ip_address / user_agent / metadata)
            'last_transaction' => $latest ? [
                'transaction_id' => $latest->transaction_id,
                'type' => $latest->type,
                'type_label' => $latest->type_label,
                'amount' => $latest->amount,
                'formatted_amount' => $latest->formatted_amount,
                'status' => $latest->status,
                'status_label' => $latest->status_label,
                'created_at' => $latest->created_at?->toDateTimeString(),
            ] : null,
        ];
    }

    /**
     * Get all wallets for admin
     */
    public function getAllWallets(array $filters = [], int $perPage = 20)
    {
        $query = Wallet::with(['user', 'transactions' => function ($q) {
            $q->latest()->limit(5);
        }]);

        // Filter by user
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by balance
        if (! empty($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (! empty($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        // Search by wallet address or user name
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('wallet_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get system-wide wallet statistics
     */
    public function getSystemStatistics(): array
    {
        $totalWallets = Wallet::count();
        $activeWallets = Wallet::where('status', 'active')->count();
        $totalBalance = Wallet::sum('balance');
        $totalIncome = Wallet::sum('total_income');
        $totalExpense = Wallet::sum('total_expense');

        $today = today();
        $todayTransactions = WalletTransaction::whereDate('created_at', $today)->count();
        $todayVolume = WalletTransaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('amount');

        $last30Days = now()->subDays(30);
        $monthlyTransactions = WalletTransaction::where('created_at', '>=', $last30Days)->count();
        $monthlyVolume = WalletTransaction::where('created_at', '>=', $last30Days)
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'total_wallets' => $totalWallets,
            'active_wallets' => $activeWallets,
            'suspended_wallets' => Wallet::where('status', 'suspended')->count(),
            'locked_wallets' => Wallet::where('status', 'locked')->count(),
            'total_balance' => $totalBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'today_transactions' => $todayTransactions,
            'today_volume' => $todayVolume,
            'monthly_transactions' => $monthlyTransactions,
            'monthly_volume' => $monthlyVolume,
            'average_balance' => $totalWallets > 0 ? $totalBalance / $totalWallets : 0,
        ];
    }

    /**
     * Transfer with fee calculation
     */
    public function transferWithFee(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $pin,
        string $description = 'Transfer'
    ): array {
        // Calculate transfer fee
        $fee = \App\Models\WalletSetting::calculateTransferFee($amount);
        $totalAmount = $amount + $fee;

        // Validate amount
        $errors = \App\Models\WalletSetting::validateTransferAmount($amount);
        if (! empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        if ($fromWallet->balance < $totalAmount) {
            throw new Exception('ยอดเงินไม่เพียงพอ (รวมค่าธรรมเนียม '.number_format($fee, 2).' บาท)');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $fee, $totalAmount, $pin, $description) {
            // Transfer main amount
            $result = $this->transfer($fromWallet, $toWallet, $amount, $pin, $description);

            // Deduct fee if applicable
            if ($fee > 0) {
                $feeTransaction = $this->withdraw(
                    $fromWallet,
                    $fee,
                    $pin,
                    'ค่าธรรมเนียมการโอน',
                    'transfer_fee',
                    $result['out_transaction']->id
                );

                $result['fee_transaction'] = $feeTransaction;
                $result['total_amount'] = $totalAmount;
                $result['fee'] = $fee;
            }

            return $result;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 🛡️ แอดมินจัดการยอดเงินในกระเป๋า (เติม / หัก / คืนเงิน / ย้อนรายการ / โอน)
    |--------------------------------------------------------------------------
    | (2026-11-04) เขียนใหม่ทั้งชุด — ของเดิมมีปัญหา:
    |  - หักเงิน / ย้อนรายการ / โอนโดยแอดมิน อ่านยอดจาก model เก่า ไม่ lock แถว
    |    → แอดมิน 2 คนกดพร้อมกัน หรือผู้ใช้จ่ายเงินระหว่างนั้น ยอดเพี้ยน/ทับกัน
    |  - ไม่มีกันกดซ้ำ — กดยืนยัน 2 ที = เติม/หัก 2 รอบ
    |  - ย้อนรายการเดิมซ้ำได้ไม่จำกัด และ log action 'transaction_rollback' ไม่อยู่ใน enum → พังบน MySQL
    |  - ไม่มีบันทึกตรวจสอบว่าแอดมินคนไหนทำ ยอดก่อน/หลังเท่าไร
    |
    | กติกาใหม่ (ทุกเส้นทางผ่าน writeAdminEntry()):
    |  1. DB transaction + lockForUpdate แถวกระเป๋า (โอน = lock ทั้งสองใบเรียงตาม id กัน deadlock)
    |  2. idempotency_key ต่อกระเป๋า — ส่งซ้ำได้ผลเดิม ไม่แตะเงินซ้ำ (เช็คหลัง lock + unique index สำรอง)
    |  3. expected_balance — ยอดที่แอดมินเห็นตอนกดยืนยัน ถ้ายอดจริงเปลี่ยนไปแล้วให้ยืนยันใหม่
    |  4. ทุกรายการลง wallet_admin_adjustments (ใคร/เมื่อไร/ยอดก่อน-หลัง/เหตุผล/IP)
    |  5. แอดมินทั่วไปแตะกระเป๋าตัวเองไม่ได้ (super admin เท่านั้น)
    */

    /** ยอดสูงสุดต่อการทำรายการหนึ่งครั้งโดยแอดมิน (กันพิมพ์เลขศูนย์เกิน) */
    public const ADMIN_MAX_AMOUNT = 1000000;

    /**
     * แอดมินเติมเงิน (credit) หรือหักเงิน (debit) กระเป๋าผู้ใช้
     *
     * @param  string  $direction  credit = เติมเงิน · debit = หักเงิน
     * @param  string|null  $idempotencyKey  โทเคนฟอร์ม (UUID) — ส่งซ้ำจะได้ธุรกรรมเดิมกลับมา (wasRecentlyCreated = false)
     * @param  float|null  $expectedBalance  ยอดที่แอดมินเห็นตอนยืนยัน (null = ไม่ตรวจ)
     *
     * @throws WalletAdminException
     */
    public function adminAdjust(
        Wallet $wallet,
        string $direction,
        float $amount,
        string $reason,
        User $admin,
        ?string $idempotencyKey = null,
        ?float $expectedBalance = null
    ): WalletTransaction {
        if (! in_array($direction, ['credit', 'debit'], true)) {
            throw new WalletAdminException('ประเภทรายการไม่ถูกต้อง');
        }

        $amount = $this->normalizeAdminAmount($amount);
        $reason = $this->normalizeAdminReason($reason);

        return $this->runAdminChange($idempotencyKey, function () use ($wallet, $direction, $amount, $reason, $admin, $idempotencyKey, $expectedBalance) {
            $locked = $this->lockWalletForAdmin($wallet->id);

            if ($replay = $this->findAdminReplay($locked->id, $idempotencyKey)) {
                return $replay;
            }

            $this->assertAdminMayTouch($locked, $admin);
            $this->assertExpectedBalance($locked, $expectedBalance);

            $isCredit = $direction === 'credit';

            return $this->writeAdminEntry($locked, $admin, [
                'action' => $direction,
                'tx_type' => $isCredit ? 'deposit' : 'fee',
                'amount' => $amount,
                'reason' => $reason,
                'description' => ($isCredit ? 'แอดมินเติมเงิน: ' : 'แอดมินหักเงิน: ').$reason,
                'reference_type' => 'admin_adjustment',
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * ปรับยอดแบบเดิม (จำนวนบวก = เติม · ลบ = หัก) — คงไว้ให้แอปแอดมิน (API) เรียกได้เหมือนเดิม
     *
     * @throws WalletAdminException
     */
    public function adjustBalance(
        Wallet $wallet,
        float $amount,
        string $reason,
        User $admin,
        ?string $idempotencyKey = null,
        ?float $expectedBalance = null
    ): WalletTransaction {
        if ($amount == 0) {
            throw new WalletAdminException('จำนวนเงินต้องไม่เป็น 0');
        }

        return $this->adminAdjust(
            $wallet,
            $amount > 0 ? 'credit' : 'debit',
            abs($amount),
            $reason,
            $admin,
            $idempotencyKey,
            $expectedBalance
        );
    }

    /**
     * แอดมินคืนเงินเข้ากระเป๋า (บันทึกเป็นประเภท refund)
     *
     * @throws WalletAdminException
     */
    public function refund(
        Wallet $wallet,
        float $amount,
        string $reason,
        User $admin,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $idempotencyKey = null,
        ?float $expectedBalance = null
    ): WalletTransaction {
        $amount = $this->normalizeAdminAmount($amount);
        $reason = $this->normalizeAdminReason($reason);

        return $this->runAdminChange($idempotencyKey, function () use ($wallet, $amount, $reason, $admin, $referenceType, $referenceId, $idempotencyKey, $expectedBalance) {
            $locked = $this->lockWalletForAdmin($wallet->id);

            if ($replay = $this->findAdminReplay($locked->id, $idempotencyKey)) {
                return $replay;
            }

            $this->assertAdminMayTouch($locked, $admin);
            $this->assertExpectedBalance($locked, $expectedBalance);

            return $this->writeAdminEntry($locked, $admin, [
                'action' => 'refund',
                'tx_type' => 'refund',
                'amount' => $amount,
                'reason' => $reason,
                'description' => "คืนเงิน: {$reason}",
                'reference_type' => $referenceType ?: 'admin_refund',
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'metadata' => ['refunded_by' => $admin->id],
            ]);
        });
    }

    /**
     * แอดมินย้อนรายการ (สร้างรายการกลับทิศ ไม่ลบรายการเดิม)
     *
     * - ย้อนได้ครั้งเดียวต่อรายการ
     * - ไม่ย้อนรายการโอน (จะเกิดเงินฝั่งเดียว) → ให้ใช้ "โอนเงินโดยแอดมิน" กลับแทน
     * - ไม่ย้อนรายการที่เป็นการย้อนอยู่แล้ว
     *
     * @throws WalletAdminException
     */
    public function rollbackTransaction(
        WalletTransaction $transaction,
        string $reason,
        User $admin,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        $reason = $this->normalizeAdminReason($reason);

        return $this->runAdminChange($idempotencyKey, function () use ($transaction, $reason, $admin, $idempotencyKey) {
            $locked = $this->lockWalletForAdmin($transaction->wallet_id);

            if ($replay = $this->findAdminReplay($locked->id, $idempotencyKey)) {
                return $replay;
            }

            $this->assertAdminMayTouch($locked, $admin);

            // อ่านรายการเดิมใหม่หลัง lock (สถานะอาจเปลี่ยนระหว่างรอ)
            $original = WalletTransaction::whereKey($transaction->id)->first();
            if (! $original || $original->status !== 'completed') {
                throw new WalletAdminException('ย้อนได้เฉพาะรายการที่สำเร็จแล้วเท่านั้น');
            }

            if (in_array($original->type, ['transfer_in', 'transfer_out'], true)) {
                throw new WalletAdminException('รายการโอนย้อนฝั่งเดียวไม่ได้ (เงินจะเกิด/หายฝั่งเดียว) — ใช้ "โอนเงินโดยแอดมิน" โอนกลับแทน');
            }

            if ($original->reference_type === 'rollback') {
                throw new WalletAdminException('รายการนี้เป็นการย้อนรายการอยู่แล้ว ย้อนซ้ำไม่ได้');
            }

            $alreadyRolledBack = WalletAdminAdjustment::where('action', 'rollback')
                ->where('original_transaction_id', $original->id)
                ->exists()
                || WalletTransaction::where('reference_type', 'rollback')
                    ->where('reference_id', $original->id)
                    ->exists();
            if ($alreadyRolledBack) {
                throw new WalletAdminException('รายการนี้ถูกย้อนไปแล้ว');
            }

            // รายการเงินเข้า → ย้อนด้วยการหัก · รายการเงินออก → ย้อนด้วยการคืน
            $wasCredit = in_array($original->type, ['deposit', 'commission', 'refund', 'bonus', 'cashback'], true);

            return $this->writeAdminEntry($locked, $admin, [
                'action' => 'rollback',
                'direction' => $wasCredit ? 'debit' : 'credit',
                'tx_type' => $wasCredit ? 'fee' : 'refund',
                'amount' => round((float) $original->amount, 2),
                'reason' => $reason,
                'description' => "ย้อนรายการ: {$reason} (ธุรกรรมเดิม: {$original->transaction_id})",
                'reference_type' => 'rollback',
                'reference_id' => $original->id,
                'original_transaction_id' => $original->id,
                'idempotency_key' => $idempotencyKey,
                'metadata' => ['original_transaction_id' => $original->id, 'original_type' => $original->type],
            ]);
        });
    }

    /**
     * โอนเงินระหว่างกระเป๋าโดย Admin (ไม่ต้องใช้ PIN)
     *
     * @return array{out_transaction: WalletTransaction, in_transaction: WalletTransaction|null, from_wallet: Wallet, to_wallet: Wallet, replayed: bool}
     *
     * @throws WalletAdminException
     */
    public function adminTransfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $reason,
        User $admin,
        ?string $idempotencyKey = null,
        ?float $expectedBalance = null
    ): array {
        if ($fromWallet->id === $toWallet->id) {
            throw new WalletAdminException('ไม่สามารถโอนเข้ากระเป๋าใบเดียวกันได้');
        }

        $amount = $this->normalizeAdminAmount($amount);
        $reason = $this->normalizeAdminReason($reason);

        return $this->runAdminChange($idempotencyKey, function () use ($fromWallet, $toWallet, $amount, $reason, $admin, $idempotencyKey, $expectedBalance) {
            // lock ทั้งสองใบเรียงตาม id เสมอ กัน deadlock กับการโอนสวนทาง
            $ids = [$fromWallet->id, $toWallet->id];
            sort($ids);
            $lockedWallets = Wallet::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $from = $lockedWallets->get($fromWallet->id);
            $to = $lockedWallets->get($toWallet->id);
            if (! $from || ! $to) {
                throw new WalletAdminException('ไม่พบกระเป๋าเงิน');
            }

            if ($replay = $this->findAdminReplay($from->id, $idempotencyKey)) {
                $inTx = WalletAdminAdjustment::where('idempotency_key', $idempotencyKey)
                    ->where('wallet_id', $to->id)->first()?->walletTransaction;

                return ['out_transaction' => $replay, 'in_transaction' => $inTx, 'from_wallet' => $from, 'to_wallet' => $to, 'replayed' => true];
            }

            $this->assertAdminMayTouch($from, $admin);
            $this->assertAdminMayTouch($to, $admin);
            $this->assertExpectedBalance($from, $expectedBalance);

            $common = ['amount' => $amount, 'reason' => $reason, 'reference_type' => 'admin_transfer', 'idempotency_key' => $idempotencyKey];

            $out = $this->writeAdminEntry($from, $admin, $common + [
                'action' => 'transfer_out',
                'tx_type' => 'transfer_out',
                'description' => "โอนเงินโดยแอดมิน: {$reason}",
                'related_wallet_id' => $to->id,
            ]);

            $in = $this->writeAdminEntry($to, $admin, $common + [
                'action' => 'transfer_in',
                'tx_type' => 'transfer_in',
                'description' => "รับโอนเงินโดยแอดมิน: {$reason}",
                'related_wallet_id' => $from->id,
            ]);

            return ['out_transaction' => $out, 'in_transaction' => $in, 'from_wallet' => $from->fresh(), 'to_wallet' => $to->fresh(), 'replayed' => false];
        });
    }

    /**
     * รัน closure ใน DB transaction — ถ้าชน unique index ของ idempotency_key (กดซ้ำพร้อมกันเป๊ะ)
     * ให้ถือว่าเป็นการส่งซ้ำ แล้วคืนผลของรายการแรก
     */
    private function runAdminChange(?string $idempotencyKey, callable $work)
    {
        try {
            return DB::transaction($work);
        } catch (QueryException $e) {
            $isDuplicate = in_array((string) ($e->errorInfo[1] ?? ''), ['1062', '19'], true)
                || str_contains($e->getMessage(), 'waa_idem_wallet_unique');
            if ($idempotencyKey && $isDuplicate) {
                $first = WalletAdminAdjustment::where('idempotency_key', $idempotencyKey)->orderBy('id')->first();
                if ($first?->walletTransaction) {
                    return $first->walletTransaction;
                }
            }
            throw $e;
        }
    }

    /**
     * lock แถวกระเป๋า (ต้องเรียกภายใน DB transaction)
     */
    private function lockWalletForAdmin(int $walletId): Wallet
    {
        $wallet = Wallet::whereKey($walletId)->lockForUpdate()->first();
        if (! $wallet) {
            throw new WalletAdminException('ไม่พบกระเป๋าเงิน');
        }

        return $wallet;
    }

    /**
     * หา "ผลเดิม" ของโทเคนนี้บนกระเป๋านี้ (เรียกหลัง lock แล้ว) — ใช้ locking read ให้เห็นแถวที่เพิ่ง commit
     */
    private function findAdminReplay(int $walletId, ?string $idempotencyKey): ?WalletTransaction
    {
        if (! $idempotencyKey) {
            return null;
        }

        $existing = WalletAdminAdjustment::where('idempotency_key', $idempotencyKey)
            ->where('wallet_id', $walletId)
            ->lockForUpdate()
            ->first();

        return $existing?->walletTransaction;
    }

    /**
     * แอดมินทั่วไปห้ามแตะกระเป๋าตัวเอง (กันเติมเงินให้ตัวเอง) — super admin ทำได้และถูกบันทึกไว้
     */
    private function assertAdminMayTouch(Wallet $wallet, User $admin): void
    {
        if ((int) $wallet->user_id === (int) $admin->id && ! $admin->isSuperAdmin()) {
            throw new WalletAdminException('แอดมินจัดการยอดเงินในกระเป๋าของตัวเองไม่ได้ — ให้ Super Admin ทำแทน');
        }
    }

    /**
     * ยอดจริงต้องตรงกับยอดที่แอดมินเห็นตอนกดยืนยัน (ป้องกันยืนยันจากตัวเลขเก่า)
     */
    private function assertExpectedBalance(Wallet $locked, ?float $expectedBalance): void
    {
        if ($expectedBalance === null) {
            return;
        }

        if (abs(round((float) $locked->balance, 2) - round($expectedBalance, 2)) >= 0.005) {
            throw new WalletAdminException(
                'ยอดเงินในกระเป๋าเปลี่ยนไประหว่างที่คุณกำลังยืนยัน (ตอนนี้ ฿'.number_format((float) $locked->balance, 2)
                .') — ยังไม่ได้ทำรายการ กรุณาตรวจสอบแล้วยืนยันใหม่'
            );
        }
    }

    private function normalizeAdminAmount(float $amount): float
    {
        $amount = round($amount, 2);

        if (! is_finite($amount) || $amount <= 0) {
            throw new WalletAdminException('จำนวนเงินต้องมากกว่า 0');
        }

        if ($amount > self::ADMIN_MAX_AMOUNT) {
            throw new WalletAdminException('จำนวนเงินเกินเพดานต่อครั้ง (฿'.number_format(self::ADMIN_MAX_AMOUNT, 2).')');
        }

        return $amount;
    }

    private function normalizeAdminReason(string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new WalletAdminException('กรุณาระบุเหตุผล');
        }

        return mb_substr($reason, 0, 500);
    }

    /**
     * เขียนรายการเงิน 1 ขา บนกระเป๋าที่ lock แล้ว: ธุรกรรม + ยอดกระเป๋า + log + audit
     *
     * @param  array{action: string, tx_type: string, amount: float, reason: string, description: string, direction?: string, reference_type?: string, reference_id?: int|null, related_wallet_id?: int|null, original_transaction_id?: int|null, idempotency_key?: string|null, metadata?: array}  $entry
     */
    private function writeAdminEntry(Wallet $locked, User $admin, array $entry): WalletTransaction
    {
        $amount = (float) $entry['amount'];
        $isCredit = ($entry['direction'] ?? null) === 'credit'
            || (! isset($entry['direction']) && in_array($entry['action'], WalletAdminAdjustment::CREDIT_ACTIONS, true));

        $balanceBefore = round((float) $locked->balance, 2);
        $balanceAfter = round($isCredit ? $balanceBefore + $amount : $balanceBefore - $amount, 2);

        if ($balanceAfter < 0) {
            throw new WalletAdminException(
                'ยอดเงินไม่พอ (คงเหลือ ฿'.number_format($balanceBefore, 2).' · ต้องการหัก ฿'.number_format($amount, 2).') — ไม่ยอมให้ยอดติดลบ'
            );
        }

        $metadata = array_merge($entry['metadata'] ?? [], [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'reason' => $entry['reason'],
            'admin_action' => $entry['action'],
        ]);

        $transaction = WalletTransaction::create([
            'wallet_id' => $locked->id,
            'user_id' => $locked->user_id,
            'type' => $entry['tx_type'],
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'currency' => $locked->currency,
            'description' => $entry['description'],
            'reference_type' => $entry['reference_type'] ?? 'admin_adjustment',
            'reference_id' => $entry['reference_id'] ?? $admin->id,
            'related_wallet_id' => $entry['related_wallet_id'] ?? null,
            'status' => 'completed',
            'metadata' => $metadata,
            'completed_at' => now(),
        ]);

        $locked->update([
            'balance' => $balanceAfter,
            'total_income' => $isCredit ? round((float) ($locked->total_income ?? 0) + $amount, 2) : $locked->total_income,
            'total_expense' => $isCredit ? $locked->total_expense : round((float) ($locked->total_expense ?? 0) + $amount, 2),
            'last_transaction_at' => now(),
        ]);

        $this->logAction(
            $locked,
            'transaction_success',
            (WalletAdminAdjustment::ACTION_LABELS[$entry['action']] ?? $entry['action'])
                .' ฿'.number_format($amount, 2).' โดยแอดมิน '.$admin->name.': '.$entry['reason'],
            'warning',
            ['transaction_id' => $transaction->id, 'admin_id' => $admin->id]
        );

        WalletAdminAdjustment::create([
            'wallet_id' => $locked->id,
            'user_id' => $locked->user_id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'action' => $entry['action'],
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reason' => $entry['reason'],
            'wallet_transaction_id' => $transaction->id,
            'original_transaction_id' => $entry['original_transaction_id'] ?? null,
            'idempotency_key' => $entry['idempotency_key'] ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 1000) ?: null,
            'metadata' => $entry['metadata'] ?? null,
        ]);

        // ให้ผู้เรียกแยกได้ว่า "ทำรายการใหม่" (true) หรือ "ส่งซ้ำได้ผลเดิม" (false)
        $transaction->wasRecentlyCreated = true;

        return $transaction;
    }

    /**
     * ระงับการใช้งานกระเป๋า (Suspend)
     *
     * @param  Wallet  $wallet  กระเป๋าที่ต้องการระงับ
     * @param  string  $reason  เหตุผล
     * @param  User  $admin  แอดมินที่ทำรายการ
     */
    public function suspendWallet(Wallet $wallet, string $reason, User $admin): Wallet
    {
        $wallet->update([
            'status' => 'suspended',
            'locked_until' => null,
        ]);

        $this->logAction(
            $wallet,
            'wallet_locked',
            "กระเป๋าถูกระงับโดยแอดมิน: {$reason}",
            'critical',
            ['admin_id' => $admin->id, 'reason' => $reason]
        );

        return $wallet->fresh();
    }

    /**
     * ยกเลิกการระงับกระเป๋า (Unsuspend)
     *
     * @param  Wallet  $wallet  กระเป๋าที่ต้องการยกเลิกการระงับ
     * @param  string  $reason  เหตุผล
     * @param  User  $admin  แอดมินที่ทำรายการ
     */
    public function unsuspendWallet(Wallet $wallet, string $reason, User $admin): Wallet
    {
        $wallet->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_attempts' => 0,
        ]);

        $this->logAction(
            $wallet,
            'wallet_unlocked',
            "ยกเลิกการระงับกระเป๋าโดยแอดมิน: {$reason}",
            'info',
            ['admin_id' => $admin->id, 'reason' => $reason]
        );

        return $wallet->fresh();
    }

    /**
     * ดึงธุรกรรมทั้งหมดในระบบ
     *
     * @param  array  $filters  ตัวกรอง
     * @param  int  $perPage  จำนวนต่อหน้า
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllTransactions(array $filters = [], int $perPage = 20)
    {
        $query = WalletTransaction::with(['wallet.user', 'relatedWallet.user']);

        // กรองตาม wallet_id
        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        // กรองตาม user_id
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // กรองตามประเภท
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // กรองตามสถานะ
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // กรองตามวันที่
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // กรองตามจำนวนเงิน
        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // ค้นหา
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('wallet.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ดึง logs ทั้งหมดในระบบ
     *
     * @param  array  $filters  ตัวกรอง
     * @param  int  $perPage  จำนวนต่อหน้า
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllLogs(array $filters = [], int $perPage = 20)
    {
        $query = WalletLog::with(['wallet.user']);

        // กรองตาม wallet_id
        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        // กรองตาม action
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // กรองตาม severity
        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // กรองตามวันที่
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // ค้นหา
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('wallet.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ดึงรายการกระเป๋าสำหรับ dropdown
     *
     * @param  string|null  $exclude  Wallet ID ที่ต้องการยกเว้น
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWalletsForDropdown(?int $excludeId = null)
    {
        $query = Wallet::with('user:id,name,email')
            ->where('status', 'active')
            ->select(['id', 'user_id', 'wallet_address', 'balance', 'currency']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
