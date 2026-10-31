<?php

namespace App\Services\Crypto;

use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\User;
use App\Services\NotificationService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * คิวถอน TPIX ฝั่งแอดมิน
 *
 * ⚠️ service นี้ไม่ได้ส่งเหรียญบนเชน แอดมินโอนจากกระเป๋าของระบบเองก่อน แล้วค่อยบันทึก tx hash
 * ระบบตรวจ hash บนเชนไม่ได้ เพราะ RPC ของกระเป๋า TPIX ยังใช้งานไม่ได้
 *
 * ยอดถูกหักจาก crypto_wallets ตั้งแต่ตอนสมาชิกส่งคำขอ ขั้นยืนยันจึงไม่แตะยอดอีก
 * ส่วนการปฏิเสธต้องคืน amount + fee ตรงกับที่หักไป และคืนได้ครั้งเดียว
 */
class TpixWithdrawalQueueService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * รายการถอน TPIX ทั้งหมดที่คิวนี้ดูแล
     *
     * @return Builder<CryptoTransaction>
     */
    public function withdrawals(): Builder
    {
        return CryptoTransaction::query()
            ->where('type', 'withdrawal')
            ->where('network', 'tpix')
            ->where('crypto_currency_id', CryptoCurrency::where('code', 'TPIX')->value('id'));
    }

    /**
     * บันทึกว่าแอดมินส่งเหรียญแล้ว
     *
     * @throws DomainException ข้อความภาษาไทยที่แสดงให้แอดมินเห็นได้
     */
    public function confirmSent(int $id, User $admin, string $txHash): CryptoTransaction
    {
        // เก็บรูปเดียว กัน hash เดียวกันเข้าซ้ำเพราะตัวพิมพ์เล็กใหญ่ต่างกัน
        $txHash = strtolower($txHash);

        try {
            $withdrawal = DB::transaction(function () use ($id, $admin, $txHash) {
                $withdrawal = $this->lockPending($id);

                // รวมแถวที่ลบแบบ soft delete ด้วย เพราะ unique index ยังนับแถวพวกนั้น
                if (CryptoTransaction::withTrashed()->where('tx_hash', $txHash)->exists()) {
                    throw new DomainException('tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');
                }

                $now = now();
                $withdrawal->update([
                    'status' => 'confirmed',
                    'tx_hash' => $txHash,
                    'confirmed_at' => $now,
                    'completed_at' => $now,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => $now,
                ]);

                return $withdrawal;
            });
        } catch (UniqueConstraintViolationException) {
            // แอดมินสองคนกรอก hash เดียวกันพร้อมกัน ผ่านการเช็คด้านบนได้ทั้งคู่ ฐานข้อมูลจะกันคนที่สองไว้
            throw new DomainException('tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');
        }

        Log::info('TPIX withdrawal confirmed', [
            'transaction_id' => $withdrawal->id,
            'admin_id' => $admin->id,
            'tx_hash' => $txHash,
        ]);

        $this->notifyMember(
            $withdrawal,
            'ส่งเหรียญ TPIX แล้ว',
            'รายการถอน '.$this->display($withdrawal->amount).' TPIX ของคุณส่งแล้ว',
            'normal'
        );

        return $withdrawal;
    }

    /**
     * ปฏิเสธคำขอและคืนยอดเข้ากระเป๋า
     *
     * กดซ้ำหรือกดพร้อมกันต้องคืนครั้งเดียว: คนที่สองจะรอ lock แล้วเจอสถานะที่เปลี่ยนไปแล้ว
     *
     * @throws DomainException ข้อความภาษาไทยที่แสดงให้แอดมินเห็นได้
     */
    public function reject(int $id, User $admin, string $reason): CryptoTransaction
    {
        $withdrawal = DB::transaction(function () use ($id, $admin, $reason) {
            $withdrawal = $this->lockPending($id);

            // กระเป๋าที่ถูกลบหรือถูกระงับยังต้องได้ยอดคืน เพราะเป็นเงินของสมาชิก
            $wallet = CryptoWallet::withTrashed()
                ->whereKey($withdrawal->crypto_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', bcadd((string) $withdrawal->amount, (string) $withdrawal->fee, 18));

            $withdrawal->update([
                'status' => 'cancelled',
                'error_message' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $withdrawal;
        });

        Log::info('TPIX withdrawal rejected', [
            'transaction_id' => $withdrawal->id,
            'admin_id' => $admin->id,
        ]);

        $this->notifyMember(
            $withdrawal,
            'รายการถอน TPIX ถูกปฏิเสธ',
            'รายการถอน '.$this->display($withdrawal->amount).' TPIX ถูกปฏิเสธ ระบบคืนยอดเข้ากระเป๋าแล้ว เหตุผล: '.$reason,
            'high'
        );

        return $withdrawal;
    }

    /**
     * ล็อกแถวและยืนยันว่ายังรอดำเนินการอยู่ ต้องเรียกใน DB::transaction เท่านั้น
     */
    private function lockPending(int $id): CryptoTransaction
    {
        $withdrawal = $this->withdrawals()->whereKey($id)->lockForUpdate()->first();

        if (! $withdrawal) {
            throw new DomainException('ไม่พบรายการถอนนี้');
        }

        if ($withdrawal->status !== 'pending') {
            throw new DomainException('รายการนี้ดำเนินการไปแล้ว');
        }

        return $withdrawal;
    }

    /**
     * แจ้งสมาชิกหลัง commit แล้ว แจ้งไม่สำเร็จต้องไม่ทำให้ผลของรายการเปลี่ยน
     */
    private function notifyMember(CryptoTransaction $withdrawal, string $title, string $message, string $priority): void
    {
        try {
            $member = $withdrawal->user;

            if ($member) {
                $this->notifications->create(
                    $member,
                    'withdrawal',
                    $title,
                    $message,
                    ['crypto_transaction_id' => $withdrawal->id],
                    route('user.tpix.withdrawal'),
                    'ดูรายการถอน',
                    $priority
                );
            }
        } catch (Throwable $e) {
            Log::warning('TPIX withdrawal member notification failed', [
                'transaction_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function display(string $amount): string
    {
        return rtrim(rtrim($amount, '0'), '.');
    }
}
