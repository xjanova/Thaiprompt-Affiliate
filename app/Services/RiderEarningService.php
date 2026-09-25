<?php

namespace App\Services;

use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * เคลียร์เงินของงานไรเดอร์ที่เสร็จแล้ว (เรียกซ้ำได้ ไม่จ่ายซ้ำ)
 *
 * งานจ่ายล่วงหน้า (cod_amount = 0):
 *   ผู้ซื้อจ่ายค่าส่งไว้กับแพลตฟอร์มแล้ว → เครดิต rider_earnings เข้าวอลเลตไรเดอร์ (deposit, ref 'rider_job')
 *   + บันทึก platform_fee เข้ากระเป๋าค่าธรรมเนียมแพลตฟอร์ม (sub_type 'rider_delivery_fee')
 *
 * งานเก็บเงินปลายทาง (cod_amount > 0):
 *   ไรเดอร์ถือเงินสด cod_amount (ค่าสินค้า + ค่าส่ง) ซึ่งรวมรายได้ของตัวเองแล้ว
 *   → หักวอลเลตไรเดอร์ (cod_amount − rider_earnings) ครั้งเดียว (fee, ref 'rider_job_cod')
 *   (ถ้าได้ติดลบ = แพลตฟอร์มต้องจ่ายเพิ่ม → deposit ส่วนต่าง ref 'rider_job')
 *   + บันทึก platform_fee เข้ากระเป๋าค่าธรรมเนียม หลังหักเงินไรเดอร์สำเร็จ
 *   ถ้ายอดวอลเลตไม่พอ (ไรเดอร์ถอนออกระหว่างวิ่ง) → ไม่หัก ไม่ปิด cod_settled_at
 *   แจ้งแอดมิน แล้ว rider:sweep-pending จะลองเคลียร์ซ้ำ
 *   หักสำเร็จ (cod_settled_at ถูกตั้ง) → เรียก onRiderCodSettled() ของออเดอร์ต้นทาง ให้ตั้งจ่ายแล้ว
 *   (ออเดอร์ COD จะไม่ถูกแบ่งเงินให้ร้าน/จ่าย cashback จนกว่าเงินจะเข้าระบบจริง)
 *   ระหว่างมีภาระ COD ค้าง: ถอน/โอนเงินออกเกินส่วนที่ไม่ติดภาระไม่ได้ (RiderJob::codReserveForUser)
 *
 * ทุกกรณี: นับ completed_jobs + total_earnings ของไรเดอร์ครั้งเดียว (earnings_settled_at)
 */
class RiderEarningService
{
    public const REF_EARNING = 'rider_job';

    public const REF_COD = 'rider_job_cod';

    public const PLATFORM_SUB_TYPE = 'rider_delivery_fee';

    public function __construct(
        private readonly WalletService $wallets,
        private readonly RiderNotificationService $notifier,
    ) {}

    /**
     * เคลียร์เงินของงาน (idempotent — เรียกกี่ครั้งก็ได้ผลเท่าเดิม)
     */
    public function settle(RiderJob $job): void
    {
        $codShortfall = null;

        DB::transaction(function () use ($job, &$codShortfall) {
            /** @var RiderJob|null $locked */
            $locked = RiderJob::whereKey($job->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'completed' || ! $locked->rider_id) {
                return;
            }

            /** @var Rider|null $rider */
            $rider = Rider::withTrashed()->whereKey($locked->rider_id)->lockForUpdate()->first();
            // withTrashed: ไรเดอร์ที่ลบบัญชี (soft delete) ยังต้องเคลียร์เงิน COD ได้ ไม่งั้น sweep ล้มทุกนาทีและเงินหายถาวร
            $user = $rider ? User::withTrashed()->find($rider->user_id) : null;

            if (! $rider || ! $user) {
                Log::error('RiderEarning: job has no rider user, cannot settle', ['job_id' => $locked->id]);

                return;
            }

            $wallet = $this->wallets->getOrCreateWallet($user);

            $earnings = round((float) $locked->rider_earnings, 2);
            $cod = round((float) $locked->cod_amount, 2);
            $platformFee = round((float) $locked->platform_fee, 2);

            $moneyDone = true;
            $codJustSettled = false;

            if ($cod > 0) {
                if ($locked->cod_settled_at === null) {
                    $net = round($cod - $earnings, 2);

                    if ($net > 0) {
                        $moneyDone = $this->debitCod($wallet, $locked, $net, $codShortfall);
                    } elseif ($net < 0) {
                        $this->creditOnce($wallet, $locked, round(-$net, 2), 'ค่าส่งไรเดอร์ (ส่วนต่าง COD) งาน #'.$locked->job_number);
                    }

                    if ($moneyDone) {
                        $locked->cod_settled_at = now();
                        $codJustSettled = true;
                    }
                }
            } elseif ($earnings > 0) {
                $this->creditOnce($wallet, $locked, $earnings, 'ค่าส่งไรเดอร์ งาน #'.$locked->job_number);
            }

            // ค่าธรรมเนียมแพลตฟอร์ม: จ่ายล่วงหน้า = มีเงินแล้ว / COD = หลังหักเงินไรเดอร์สำเร็จ
            if ($platformFee > 0 && ($cod <= 0 || $locked->cod_settled_at !== null)) {
                $this->recordPlatformFeeOnce($locked, $platformFee);
            }

            // สถิติไรเดอร์ นับครั้งเดียวต่องาน
            if ($locked->earnings_settled_at === null) {
                $rider->increment('completed_jobs');
                $rider->increment('total_earnings', $earnings);
                $locked->earnings_settled_at = now();
            }

            $locked->save();

            // เงิน COD เข้าระบบแล้วจริง → แจ้งออเดอร์ต้นทางให้ตั้งจ่ายแล้ว (ใน transaction เดียวกัน)
            // ล้ม = rollback ทั้งการเคลียร์เงิน แล้ว sweep ลองใหม่ (ไม่มีทางที่เงินเข้าแต่ออเดอร์ไม่รู้)
            if ($codJustSettled) {
                $this->notifySourceCodSettled($locked);
            }
        });

        if ($codShortfall !== null) {
            $this->notifyCodShortfall($job, $codShortfall);
        }
    }

    /**
     * สรุปรายได้ไรเดอร์ตามช่วงเวลา (ใช้กับ GET /api/v1/rider/earnings)
     *
     * @param  string  $period  today|week|month|all
     * @return array{period: string, from: ?string, to: string, completed_jobs: int, gross_earnings: float, cod_jobs: int, cod_collected: float, cod_remitted: float, unsettled_jobs: int, wallet_balance: float, total_earnings_all_time: float, daily: array<int, array{date: string, jobs: int, earnings: float}>}
     */
    public function summary(Rider $rider, string $period = 'today'): array
    {
        $period = in_array($period, ['today', 'week', 'month', 'all'], true) ? $period : 'today';
        $from = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => null,
        };

        $base = RiderJob::query()
            ->where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('completed_at', '>=', $from));

        $rows = (clone $base)->get(['id', 'completed_at', 'rider_earnings', 'cod_amount']);

        $daily = [];
        if ($period !== 'all') {
            foreach ($rows as $row) {
                $date = $row->completed_at?->toDateString() ?? now()->toDateString();
                $daily[$date] ??= ['date' => $date, 'jobs' => 0, 'earnings' => 0.0];
                $daily[$date]['jobs']++;
                $daily[$date]['earnings'] = round($daily[$date]['earnings'] + (float) $row->rider_earnings, 2);
            }
            ksort($daily);
        }

        $codRemitted = 0.0;
        $user = User::find($rider->user_id);
        if ($user) {
            $walletId = Wallet::where('user_id', $user->id)->value('id');
            if ($walletId) {
                $codRemitted = round((float) WalletTransaction::where('wallet_id', $walletId)
                    ->where('reference_type', self::REF_COD)
                    ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                    ->sum('amount'), 2);
            }
        }

        $unsettled = RiderJob::query()
            ->where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNull('earnings_settled_at')
                    ->orWhere(function ($q2) {
                        $q2->where('cod_amount', '>', 0)->whereNull('cod_settled_at');
                    });
            })
            ->count();

        return [
            'period' => $period,
            'from' => $from?->toIso8601String(),
            'to' => now()->toIso8601String(),
            'completed_jobs' => $rows->count(),
            'gross_earnings' => round((float) $rows->sum(fn ($r) => (float) $r->rider_earnings), 2),
            'cod_jobs' => $rows->filter(fn ($r) => (float) $r->cod_amount > 0)->count(),
            'cod_collected' => round((float) $rows->sum(fn ($r) => (float) $r->cod_amount), 2),
            'cod_remitted' => $codRemitted,
            'unsettled_jobs' => $unsettled,
            'wallet_balance' => $rider->walletBalance(),
            'total_earnings_all_time' => round((float) $rider->total_earnings, 2),
            'daily' => array_values($daily),
        ];
    }

    /**
     * งานที่เสร็จแล้วแต่ยังเคลียร์เงินไม่ครบ (ให้ sweep ลองใหม่)
     *
     * @return \Illuminate\Support\Collection<int, RiderJob>
     */
    public function unsettledJobs(int $limit = 50)
    {
        return RiderJob::query()
            ->where('status', 'completed')
            ->whereNotNull('rider_id')
            ->where(function ($q) {
                $q->whereNull('earnings_settled_at')
                    ->orWhere(function ($q2) {
                        $q2->where('cod_amount', '>', 0)->whereNull('cod_settled_at');
                    });
            })
            ->orderBy('completed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * เรียก hook เสริม onRiderCodSettled(RiderJob) ของออเดอร์ต้นทาง (ถ้ามี)
     *
     * ออเดอร์ร้านค้า/ตลาดสดใช้ตั้ง payment_status = paid หลังเงิน COD เข้าระบบจริงเท่านั้น
     */
    private function notifySourceCodSettled(RiderJob $job): void
    {
        $source = $job->deliverableSource();

        if ($source && method_exists($source, 'onRiderCodSettled')) {
            $source->onRiderCodSettled($job);
        }
    }

    /**
     * เครดิตวอลเลตไรเดอร์ครั้งเดียวต่องาน (เช็ครายการเดิมก่อนเสมอ)
     */
    private function creditOnce(Wallet $wallet, RiderJob $job, float $amount, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $exists = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference_type', self::REF_EARNING)
            ->where('reference_id', $job->id)
            ->where('type', 'deposit')
            ->exists();

        if ($exists) {
            return;
        }

        $this->wallets->deposit($wallet, $amount, $description, self::REF_EARNING, (int) $job->id, [
            'job_number' => $job->job_number,
            'kind' => 'rider_delivery_earning',
            'source_type' => $job->source_type,
            'source_id' => $job->source_id,
        ]);
    }

    /**
     * หักเงิน COD ครั้งเดียวต่องาน — คืน false ถ้ายอดไม่พอ (ยังไม่เคลียร์)
     */
    private function debitCod(Wallet $wallet, RiderJob $job, float $amount, ?float &$shortfall): bool
    {
        $exists = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference_type', self::REF_COD)
            ->where('reference_id', $job->id)
            ->where('type', 'fee')
            ->exists();

        if ($exists) {
            return true;
        }

        $balance = round((float) Wallet::whereKey($wallet->id)->lockForUpdate()->value('balance'), 2);
        if ($balance < $amount) {
            $shortfall = round($amount - $balance, 2);
            Log::warning('RiderEarning: COD remittance short', [
                'job_id' => $job->id,
                'required' => $amount,
                'balance' => $balance,
            ]);

            return false;
        }

        $this->wallets->deductForService(
            $wallet->fresh(),
            $amount,
            'นำส่งเงินเก็บปลายทาง (หักรายได้ค่าส่งแล้ว) งาน #'.$job->job_number,
            self::REF_COD,
            (int) $job->id,
            [
                'job_number' => $job->job_number,
                'cod_amount' => round((float) $job->cod_amount, 2),
                'rider_earnings' => round((float) $job->rider_earnings, 2),
                'kind' => 'rider_cod_remittance',
            ]
        );

        return true;
    }

    /**
     * บันทึกรายได้แพลตฟอร์มจากค่าส่งครั้งเดียวต่องาน
     */
    private function recordPlatformFeeOnce(RiderJob $job, float $amount): void
    {
        $exists = PlatformTransaction::where('source_type', self::REF_EARNING)
            ->where('source_id', $job->id)
            ->where('sub_type', self::PLATFORM_SUB_TYPE)
            ->exists();

        if ($exists) {
            return;
        }

        PlatformWallet::getFeeWallet()->addFunds($amount, self::PLATFORM_SUB_TYPE, self::REF_EARNING, (int) $job->id, [
            'job_number' => $job->job_number,
            'total_fee' => round((float) $job->total_fee, 2),
            'rider_earnings' => round((float) $job->rider_earnings, 2),
            'cod' => (float) $job->cod_amount > 0,
        ]);
    }

    /**
     * แจ้งไรเดอร์ + แอดมินเมื่อยอดวอลเลตไม่พอหักเงิน COD
     */
    private function notifyCodShortfall(RiderJob $job, float $shortfall): void
    {
        // sweep ลองเคลียร์ทุกนาที → แจ้งซ้ำได้ไม่เกินชั่วโมงละครั้งต่องาน
        if (! Cache::add('rider_cod_shortfall_notified:'.$job->id, true, now()->addHour())) {
            return;
        }

        $job->loadMissing('rider');

        $this->notifier->notifyRider(
            $job->rider,
            $job,
            'ยอดวอลเลตไม่พอเคลียร์เงินปลายทาง',
            'กรุณาเติมเงิน ฿'.number_format($shortfall, 2).' เพื่อนำส่งเงินเก็บปลายทางของงาน #'.$job->job_number,
            'cod_shortfall'
        );

        $this->notifier->notifyAdmins(
            'ไรเดอร์ค้างนำส่งเงิน COD',
            'งาน #'.$job->job_number.' ขาดอีก ฿'.number_format($shortfall, 2).' (ไรเดอร์ #'.$job->rider_id.')',
            ['job_id' => (int) $job->id, 'shortfall' => $shortfall]
        );
    }
}
