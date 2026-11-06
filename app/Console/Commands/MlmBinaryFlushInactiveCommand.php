<?php

namespace App\Console\Commands;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ล้าง PV ขา binary ของสมาชิกที่ไม่ active (audit F4 / เจ้าของตัดสิน M2)
 *
 * ใช้ล้าง PV ที่สะสมไว้ก่อนแก้ engine (engine ใหม่ไม่ลง PV ให้คนที่ไม่ active แล้ว)
 * กันกรณีคนที่กลับมา active แล้วขึ้นเงินคู่ก้อนใหญ่จาก PV เก่า
 *
 * ⚠️ ค่าเริ่มต้นเป็น dry-run (แสดงรายการอย่างเดียว) — ต้องใส่ --execute จึงจะเขียน DB
 * ⚠️ ไม่ได้ผูกกับ scheduler — ห้ามตั้งให้รันอัตโนมัติบน prod โดยไม่ได้รับอนุมัติจากเจ้าของ
 *
 * ใช้งาน:
 * - php artisan mlm:binary-flush-inactive              ดูว่าใครจะถูกล้างเท่าไร
 * - php artisan mlm:binary-flush-inactive --execute    ล้างจริง
 */
class MlmBinaryFlushInactiveCommand extends Command
{
    protected $signature = 'mlm:binary-flush-inactive
                            {--execute : เขียน DB จริง (ไม่ใส่ = dry-run)}
                            {--member= : จำกัดเฉพาะ mlm_members.id นี้}';

    protected $description = 'ล้าง PV ขา binary (leg + carried) ของสมาชิกที่ไม่รักษายอด — dry-run เป็นค่าเริ่มต้น';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $memberId = $this->option('member');

        $query = MlmMember::query()
            ->where(function ($q) {
                $q->where('left_leg_pv', '>', 0)
                    ->orWhere('right_leg_pv', '>', 0)
                    ->orWhere('carried_left_pv', '>', 0)
                    ->orWhere('carried_right_pv', '>', 0);
            })
            ->orderBy('id');

        if ($memberId) {
            $query->whereKey((int) $memberId);
        }

        $rows = [];
        $flushedCount = 0;

        $query->chunkById(200, function ($members) use ($execute, &$rows, &$flushedCount) {
            foreach ($members as $member) {
                if (MlmRetentionHelper::isMemberActive($member)) {
                    continue;
                }

                $before = [
                    'left_leg_pv' => (float) $member->left_leg_pv,
                    'right_leg_pv' => (float) $member->right_leg_pv,
                    'carried_left_pv' => (float) $member->carried_left_pv,
                    'carried_right_pv' => (float) $member->carried_right_pv,
                ];
                $rows[] = array_merge(['id' => $member->id, 'code' => $member->member_code], $before);

                if (! $execute) {
                    continue;
                }

                DB::transaction(function () use ($member, $before) {
                    // ล็อกแถวก่อนล้าง กันชนกับออเดอร์ที่กำลังจับคู่อยู่
                    $locked = MlmMember::whereKey($member->id)->lockForUpdate()->first();
                    if (! $locked || MlmRetentionHelper::isMemberActive($locked)) {
                        return;
                    }

                    $locked->forceFill([
                        'left_leg_pv' => 0,
                        'right_leg_pv' => 0,
                        'carried_left_pv' => 0,
                        'carried_right_pv' => 0,
                        'carried_left_pv_expires_at' => null,
                        'carried_right_pv_expires_at' => null,
                    ])->save();

                    Log::info('MLM binary: ล้าง PV ขาของสมาชิกที่ไม่ active', [
                        'member_id' => $locked->id,
                        'before' => $before,
                    ]);
                });
                $flushedCount++;
            }
        });

        if ($rows === []) {
            $this->info('ไม่มีสมาชิกที่ไม่ active และมี PV ขาค้าง');

            return self::SUCCESS;
        }

        $this->table(['id', 'code', 'left_leg_pv', 'right_leg_pv', 'carried_left_pv', 'carried_right_pv'], $rows);

        if ($execute) {
            $this->info("ล้างแล้ว {$flushedCount} คน");
        } else {
            $this->warn('dry-run: ยังไม่ได้เขียน DB — ใส่ --execute เพื่อล้างจริง ('.count($rows).' คน)');
        }

        return self::SUCCESS;
    }
}
