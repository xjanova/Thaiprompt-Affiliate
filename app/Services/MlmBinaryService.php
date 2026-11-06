<?php

namespace App\Services;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPvTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmBinaryService - จัดการการคำนวณ Binary Commission
 *
 * ⚠️ IMPORTANT: Service นี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 *
 * 🛡️ กติกาเงิน binary (ขั้น 2a — audit F1-F4, เจ้าของตัดสิน M2):
 * - 1 คู่ = binary_pair_unit_pv PV ต่อขา (ค่าเริ่มต้น 100) ไม่ใช่ 1 PV = 1 คู่
 * - เงินคู่ต้องอยู่ใน budget ต่อออเดอร์ (เพดาน % เดียวกับ unilevel) ที่เหลือหลังหักคอมอื่นของออเดอร์นั้นแล้ว
 * - เพดานรายวันต่อคน binary_max_pairs_per_day (ค่าว่าง/0 = ใช้ค่าเริ่มต้น 10 คู่ ไม่ใช่ไม่จำกัด)
 * - เศษ PV หลังจับคู่อยู่ที่เดียว: เปิดยกยอด → carried_* (มีวันหมดอายุ), ปิดยกยอด → leg_pv หลังหัก flush
 * - ล็อกแถว upline ทั้งสาย (เรียง id) ก่อนแก้ PV ขา และไม่ให้ค่าใดติดลบ
 * - สมาชิกที่ไม่ active (ไม่รักษายอด) ไม่สะสม PV ขาและไม่จับคู่
 */
class MlmBinaryService
{
    /** PV ต่อขาที่นับเป็น 1 คู่ เมื่อยังไม่ได้ตั้งค่า binary_pair_unit_pv */
    public const DEFAULT_PAIR_UNIT_PV = 100;

    /** เพดานคู่ต่อวันต่อคน เมื่อ binary_max_pairs_per_day ว่างหรือเป็น 0 */
    public const DEFAULT_MAX_PAIRS_PER_DAY = 10;

    /** ความลึกสูงสุดที่ไล่ขึ้นสาย binary (กัน loop) */
    protected const MAX_TRAVERSE_DEPTH = 100;

    /**
     * คำนวณค่าคอม binary ของออเดอร์ (ลง PV ขา + จับคู่ให้ upline)
     *
     * @param  float|null  $budget  เงินที่ binary ใช้ได้จากออเดอร์นี้ (null = คิดจาก budget ต่อออเดอร์ − คอมที่บันทึกไปแล้ว)
     * @return array<int, MlmCommission> แถว binary_pair ที่สร้าง (บันทึกลง DB แล้ว)
     */
    public function calculateBinaryCommissions(MlmMember $member, Order $order, array $pvData, ?float $budget = null): array
    {
        $plan = $member->plan;

        if (! $plan || $plan->type === 'unilevel') {
            return [];
        }

        $pv = round((float) ($pvData['total_pv'] ?? 0), 2);
        if ($pv <= 0) {
            return [];
        }

        // ทั้งการลง PV และการจับคู่อยู่ใน transaction เดียว เพื่อให้ล็อกแถว upline ค้างไว้จนจบ
        // (ถ้าผู้เรียกเปิด transaction อยู่แล้ว จะเป็น savepoint และล็อกค้างถึง commit ของผู้เรียก)
        // F7: เงินที่ออเดอร์เก็บเข้ากองจริง (มาจาก OrderDistributionService) — ไม่มี = ใช้สูตร PV อย่างเดียว
        $poolCollected = isset($pvData['pv_amount_thb']) ? (float) $pvData['pv_amount_thb'] : null;

        return DB::transaction(function () use ($member, $order, $pv, $plan, $budget, $poolCollected) {
            // 🛡️ กันลง PV ขาซ้ำเมื่อออเดอร์เดียวถูกคำนวณสองรอบ (ตัวกันซ้ำของ engine ดูแค่แถวคอม —
            //    ออเดอร์ที่ยังไม่เกิดคู่ไม่มีแถวคอม จึงผ่านไปลง PV ซ้ำ ขาโตเป็นสองเท่า)
            //    ล็อกแถว PV ของออเดอร์ก่อนแถวสมาชิก · มีรายการลงขาแล้ว = เคยลงแล้ว → ไม่ทำซ้ำ
            $pvRow = MlmPvTransaction::where('order_id', $order->id)
                ->where('mlm_member_id', $member->id)
                ->where('transaction_type', 'purchase')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($pvRow && is_array($pvRow->binary_leg_credits) && $pvRow->binary_leg_credits !== []) {
                Log::info('Binary: ออเดอร์นี้ลง PV ขาไปแล้ว ข้ามการคำนวณซ้ำ', [
                    'order_id' => $order->id,
                    'member_id' => $member->id,
                ]);

                return [];
            }

            $chain = $this->lockBinaryUplineChain($member);

            if ($chain === []) {
                return [];
            }

            // F4: ตรวจรักษายอดครั้งเดียวต่อคน ใช้ทั้งตอนลง PV และตอนจับคู่
            foreach ($chain as $i => $node) {
                $chain[$i]['active'] = MlmRetentionHelper::isMemberActive($node['member']);
            }

            $this->attributePvToBinaryLeg($chain, $pv);
            $this->recordLegCredits($pvRow, $chain, $pv);

            $budgetRemaining = $budget ?? $this->remainingOrderBudget($order, $pv, $poolCollected);

            return $this->calculateBinaryPairCommissions($member, $order, (int) $plan->id, $chain, max(0.0, $budgetRemaining));
        });
    }

    /**
     * จดว่า PV ของออเดอร์นี้ลงขาให้ใครบ้าง (audit F11 / M6) บนแถว purchase ของออเดอร์
     *
     * ใช้ตอนคืนเงิน: หัก PV ขาออกจากคนที่ได้รับจริงเท่านั้น (upline ที่ไม่ active ตอนซื้อไม่ได้รับ — F4)
     *
     * @param  array<int, array{member: MlmMember, side: string, active: bool}>  $chain
     */
    protected function recordLegCredits(?MlmPvTransaction $row, array $chain, float $pv): void
    {
        if (! $row) {
            // ออเดอร์ที่ไม่มีแถว PV (เรียก engine ตรง) → คืนเงินคืนได้เฉพาะ PV ที่คู่ของออเดอร์นี้ใช้
            return;
        }

        $credits = [];
        foreach ($chain as $node) {
            if ($node['active']) {
                $credits[] = ['m' => (int) $node['member']->id, 's' => $node['side'], 'pv' => round($pv, 2)];
            }
        }

        // ไม่มี upline active เลย → ใส่รายการว่าง (m = 0, pv = 0) เป็นเครื่องหมาย "ลงขาแล้ว" กันรอบที่สองลง PV
        // (ตอนคืนเงินรายการนี้ถูกข้าม เพราะไม่มีสมาชิก id 0 และ pv = 0)
        if ($credits === []) {
            $credits[] = ['m' => 0, 's' => 'none', 'pv' => 0];
        }

        $row->binary_leg_credits = $credits;
        $row->save();
    }

    /**
     * ย้อน PV ขา binary ของออเดอร์ที่คืนเงิน + ดึงคู่ที่ใช้ PV ก้อนนั้นไปแล้วคืนตามสัดส่วน (audit F11, เจ้าของตัดสิน M6)
     *
     * ขั้นตอน (ล็อกแถวสมาชิกทุกคนที่เกี่ยวข้องเรียง id):
     *  1. คู่ของออเดอร์นี้เอง ($ownPairRows — ถูกดึงคืนเต็มแถวไปแล้วโดย clawback ของออเดอร์)
     *     → คืน PV ที่คู่นั้นใช้กลับเข้าขาทั้งสองฝั่ง (ส่วนที่เคยถูกย้อนไปแล้วไม่คืนซ้ำ)
     *  2. หัก PV ของออเดอร์ออกจากขาของคนที่ได้รับ: หักจาก PV ที่ยังไม่จับคู่ก่อน (leg → carried)
     *  3. ส่วนที่ขาด = PV ก้อนนี้ถูกจับคู่ไปแล้ว → ดึงคู่ของคนนั้นที่เกิดหลังออเดอร์นี้คืน (ใหม่สุดก่อน)
     *     สัดส่วน = PV ที่ดึงคืน ÷ PV ที่คู่นั้นใช้ฝั่งเดียวกัน · คืน PV อีกฝั่งของคู่นั้นกลับเข้าขา (ยังไม่จับคู่)
     *     ส่วนที่ยังขาดหลังจากนั้น = ถูก flush ไปแล้ว ไม่มีเงินให้ดึง
     *
     * ไม่แตะเงินเอง — คืนรายการ "ต้องดึงเงินคืนเท่าไรจากแถวไหน" ให้ MlmCommissionClawbackService จัดการกระเป๋า/หนี้
     *
     * @param  array<int, array{m: int, s: string, pv: float}>|null  $credits  null = แถวเก่า (ไม่รู้ว่าใครได้ PV)
     * @param  \Illuminate\Support\Collection<int, MlmCommission>|array  $ownPairRows
     * @param  \Carbon\CarbonInterface|null  $since  เวลาที่ PV ของออเดอร์ลงขา (คู่ก่อนหน้านั้นไม่ได้ใช้ PV ก้อนนี้)
     * @return array{legs: array, pair_claws: array<int, array{commission: MlmCommission, amount: float, fully: bool, pv: float}>, unrecovered_pv: float}
     */
    public function reverseOrderLegPv(MlmMember $buyer, Order $order, float $pv, ?array $credits, $ownPairRows, $since = null): array
    {
        $result = ['legs' => [], 'pair_claws' => [], 'unrecovered_pv' => 0.0];
        $pv = round($pv, 2);

        // 🛡️ แถวเก่า (ก่อนมีคอลัมน์ binary_leg_credits = null): ไม่รู้ว่า PV ก้อนนี้เคยลงขาให้ใคร
        //    (ตอนซื้อ binary อาจปิดอยู่ หรือ upline ไม่ active) → ไม่หัก PV ขาของใครเลย แค่บันทึก log
        //    เดิมไล่สายปัจจุบันแล้วหัก PV ที่ยังไม่จับคู่ของทุกคน → ลบ PV ที่มาจากออเดอร์อื่นของคนที่ไม่เกี่ยว
        //    (คู่ของออเดอร์นี้เองยังคืน PV ให้ตามปกติ เพราะรู้แน่ว่าคู่นั้นใช้ PV เท่าไร)
        $knownCredits = is_array($credits);
        if (! $knownCredits) {
            Log::info('Binary refund: แถว PV เก่าไม่มีข้อมูลการลงขา — ข้ามการหัก PV ขา', [
                'order_id' => $order->id,
                'buyer_member_id' => $buyer->id,
                'pv' => $pv,
            ]);
            $credits = [];
        }

        $ownPairRows = collect($ownPairRows)->filter(fn ($c) => $c->type === 'binary_pair')->values();
        $ids = collect($credits)->pluck('m')->merge($ownPairRows->pluck('mlm_member_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        if ($ids === []) {
            return $result;
        }

        return DB::transaction(function () use ($credits, $knownCredits, $ownPairRows, $ids, $order, $since, $result) {
            $members = MlmMember::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // 1. คืน PV ที่คู่ของออเดอร์นี้เองใช้ไป (คู่ถูกดึงคืนแล้ว → PV ทั้งสองฝั่งกลับมาเป็น "ยังไม่จับคู่")
            foreach ($ownPairRows as $row) {
                $m = $members->get((int) $row->mlm_member_id);
                $details = is_array($row->calculation_details) ? $row->calculation_details : [];
                if (! $m || ! isset($details['left_consumed_pv'], $details['right_consumed_pv'])) {
                    continue;
                }
                $unwound = (array) ($details['clawback_pv'] ?? []);
                foreach (['left', 'right'] as $side) {
                    $back = round(max(0.0, (float) $details[$side.'_consumed_pv'] - (float) ($unwound[$side] ?? 0)), 2);
                    $m->{$side.'_leg_pv'} = round((float) $m->{$side.'_leg_pv'} + $back, 2);
                }
                $details['clawback_pv'] = ['left' => (float) $details['left_consumed_pv'], 'right' => (float) $details['right_consumed_pv']];
                $details['pv_restored_by_refund_of_order'] = (int) $order->id;
                $row->forceFill(['calculation_details' => $details])->save();
            }

            // 2-3. หัก PV ของออเดอร์ + ดึงคู่ที่ใช้ PV ก้อนนี้
            foreach ($credits as $credit) {
                $m = $members->get((int) $credit['m']);
                $side = $credit['s'] === 'left' ? 'left' : 'right';
                $amount = round((float) ($credit['pv'] ?? 0), 2);
                if (! $m || $amount <= 0) {
                    continue;
                }

                $fromLeg = min($amount, max(0.0, (float) $m->{$side.'_leg_pv'}));
                $m->{$side.'_leg_pv'} = round((float) $m->{$side.'_leg_pv'} - $fromLeg, 2);
                $fromCarried = min($amount - $fromLeg, max(0.0, (float) $m->{'carried_'.$side.'_pv'}));
                $m->{'carried_'.$side.'_pv'} = round((float) $m->{'carried_'.$side.'_pv'} - $fromCarried, 2);
                if ((float) $m->{'carried_'.$side.'_pv'} <= 0) {
                    $m->{'carried_'.$side.'_pv_expires_at'} = null;
                }

                $deficit = round($amount - $fromLeg - $fromCarried, 2);
                $leg = [
                    'member_id' => (int) $m->id,
                    'side' => $side,
                    'pv' => $amount,
                    'from_unmatched' => round($fromLeg + $fromCarried, 2),
                    'from_pairs' => 0.0,
                    'unrecovered' => 0.0,
                ];

                if ($deficit > 0 && $knownCredits) {
                    foreach ($this->clawablePairRows($m, $ownPairRows, $since) as $row) {
                        if ($deficit <= 0) {
                            break;
                        }
                        $claw = $this->unwindPairRow($row, $m, $side, $deficit, $order);
                        if ($claw === null) {
                            continue;
                        }
                        $deficit = round($deficit - $claw['pv'], 2);
                        $leg['from_pairs'] = round($leg['from_pairs'] + $claw['pv'], 2);
                        $result['pair_claws'][] = $claw;
                    }
                }

                $leg['unrecovered'] = max(0.0, $deficit);
                $result['unrecovered_pv'] = round($result['unrecovered_pv'] + $leg['unrecovered'], 2);
                $result['legs'][] = $leg;
            }

            foreach ($members as $m) {
                // ไม่ให้ค่าใดติดลบ (กันข้อมูลเก่าที่ไม่ตรง)
                foreach (['left_leg_pv', 'right_leg_pv', 'carried_left_pv', 'carried_right_pv'] as $f) {
                    $m->{$f} = round(max(0.0, (float) $m->{$f}), 2);
                }
                $m->save();
            }

            if ($result['unrecovered_pv'] > 0) {
                Log::info('Binary refund: PV บางส่วนถูก flush ไปแล้ว ไม่มีคู่ให้ดึงคืน', [
                    'order_id' => $order->id,
                    'unrecovered_pv' => $result['unrecovered_pv'],
                    'known_credits' => $knownCredits,
                ]);
            }

            return $result;
        });
    }

    /**
     * คู่ของสมาชิกที่อาจใช้ PV ของออเดอร์ที่คืนเงิน: เกิดหลัง PV ลงขา ยังไม่ถูกยกเลิก ใหม่สุดก่อน (ล็อกแถว)
     *
     * @return \Illuminate\Support\Collection<int, MlmCommission>
     */
    protected function clawablePairRows(MlmMember $member, $ownPairRows, $since)
    {
        $exclude = collect($ownPairRows)->pluck('id')->all();

        return MlmCommission::query()
            ->where('mlm_member_id', $member->id)
            ->where('type', 'binary_pair')
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * ย้อนคู่หนึ่งแถวตามสัดส่วน PV ที่ต้องดึงคืนฝั่ง $side (แถวถูกล็อกแล้ว) — คืน PV อีกฝั่ง + บันทึกใน calculation_details
     *
     * @return array{commission: MlmCommission, amount: float, fully: bool, pv: float}|null
     */
    protected function unwindPairRow(MlmCommission $row, MlmMember $member, string $side, float $deficit, Order $order): ?array
    {
        $details = is_array($row->calculation_details) ? $row->calculation_details : [];
        $other = $side === 'left' ? 'right' : 'left';

        // แถวก่อนขั้น 2a ไม่มีข้อมูล PV ที่ใช้ → ไม่เดา
        if (! isset($details[$side.'_consumed_pv'], $details[$other.'_consumed_pv'])) {
            return null;
        }

        $consumed = (float) $details[$side.'_consumed_pv'];
        $unwound = (array) ($details['clawback_pv'] ?? []);
        $available = round($consumed - (float) ($unwound[$side] ?? 0), 2);
        if ($consumed <= 0 || $available <= 0) {
            return null;
        }

        $take = round(min($available, $deficit), 2);
        $fraction = $take / $consumed;
        $original = round((float) ($details['original_commission_amount'] ?? $row->commission_amount), 2);
        $alreadyClawed = round((float) ($details['clawback_amount'] ?? 0), 2);
        $fully = round($available - $take, 2) <= 0;
        // แถวที่ย้อนครบ = ดึงเงินที่เหลือทั้งหมด (กันเศษสตางค์ค้าง)
        $amount = $fully
            ? round($original - $alreadyClawed, 2)
            : round(min($original - $alreadyClawed, $original * $fraction), 2);

        // PV อีกฝั่งของคู่นั้นกลับมาเป็น "ยังไม่จับคู่" (ของจริงที่ยังไม่ถูกคืนเงิน)
        $otherBack = $fully
            ? round((float) $details[$other.'_consumed_pv'] - (float) ($unwound[$other] ?? 0), 2)
            : round((float) $details[$other.'_consumed_pv'] * $fraction, 2);
        $otherBack = max(0.0, $otherBack);
        $member->{$other.'_leg_pv'} = round((float) $member->{$other.'_leg_pv'} + $otherBack, 2);

        $details['original_commission_amount'] = $original;
        $details['clawback_amount'] = round($alreadyClawed + $amount, 2);
        $details['clawback_pv'] = [
            $side => round((float) ($unwound[$side] ?? 0) + $take, 2),
            $other => round((float) ($unwound[$other] ?? 0) + $otherBack, 2),
        ];
        $history = (array) ($details['clawback_history'] ?? []);
        $history[] = ['order_id' => (int) $order->id, 'side' => $side, 'pv' => $take, 'amount' => $amount, 'at' => now()->toIso8601String()];
        $details['clawback_history'] = $history;
        $row->forceFill(['calculation_details' => $details])->save();

        return ['commission' => $row, 'amount' => max(0.0, $amount), 'fully' => $fully, 'pv' => $take];
    }

    /**
     * ไล่หา upline ในผัง binary แล้วล็อกทุกแถว (lockForUpdate เรียงตาม id กัน deadlock)
     *
     * F3: เดิมอ่าน/เขียน PV ขาโดยไม่ล็อก ออเดอร์ที่เข้าพร้อมกันจึงจับคู่ PV ก้อนเดียวกันซ้ำ
     *
     * @return array<int, array{member: MlmMember, side: string}> เรียงจาก parent ใกล้สุดขึ้นไป
     */
    protected function lockBinaryUplineChain(MlmMember $member): array
    {
        $ids = [];
        $sides = [];
        $visited = [$member->id => true];
        $currentParentId = $member->binary_parent_id;
        $currentPosition = $member->binary_position;

        for ($depth = 0; $depth < self::MAX_TRAVERSE_DEPTH && $currentParentId; $depth++) {
            if (isset($visited[$currentParentId])) {
                Log::warning('Circular reference detected in binary tree', [
                    'member_id' => $member->id,
                    'parent_id' => $currentParentId,
                ]);
                break;
            }
            $visited[$currentParentId] = true;

            $parent = MlmMember::query()
                ->select(['id', 'binary_parent_id', 'binary_position'])
                ->find($currentParentId);
            if (! $parent) {
                break;
            }

            $ids[] = (int) $parent->id;
            $sides[(int) $parent->id] = $currentPosition === 'left' ? 'left' : 'right';

            $currentParentId = $parent->binary_parent_id;
            $currentPosition = $parent->binary_position;
        }

        if ($ids === []) {
            return [];
        }

        $locked = MlmMember::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $chain = [];
        foreach ($ids as $id) {
            if ($locked->has($id)) {
                $chain[] = ['member' => $locked->get($id), 'side' => $sides[$id]];
            }
        }

        return $chain;
    }

    /**
     * ลง PV ของออเดอร์เข้าขา binary ของ upline ทุกคน (แถวถูกล็อกแล้ว)
     *
     * แก้ Bug #2: ไม่ increment total_pv ที่นี่ เพราะ MlmCalculationService/OrderDistribution ทำแล้ว
     * F4: upline ที่ไม่ active ไม่ได้รับ PV ขา — กันสะสมไว้แล้วขึ้นเงินก้อนใหญ่ตอนกลับมา active
     *
     * @param  array<int, array{member: MlmMember, side: string, active: bool}>  $chain
     */
    protected function attributePvToBinaryLeg(array $chain, float $pvAmount): void
    {
        if ($pvAmount <= 0) {
            return;
        }

        foreach ($chain as $node) {
            if (! $node['active']) {
                continue;
            }

            /** @var MlmMember $parent */
            $parent = $node['member'];
            $field = $node['side'] === 'left' ? 'left_leg_pv' : 'right_leg_pv';

            // เขียนค่าสุทธิจากแถวที่ล็อกแล้ว ไม่ให้ติดลบ
            $parent->{$field} = round(max(0.0, (float) $parent->{$field} + $pvAmount), 2);
            $parent->save();
        }
    }

    /**
     * จับคู่ขาซ้าย/ขวาให้ upline ทุกคนที่ active แล้วสร้างแถว binary_pair
     *
     * F1: 1 คู่ = pair unit PV ต่อขา, เงินไม่เกิน budget ที่เหลือของออเดอร์, เพดานคู่/เงินรายวัน
     * F2: เศษหลังจับคู่เก็บที่เดียวตามค่า binary_carry_forward_enabled
     *
     * @param  array<int, array{member: MlmMember, side: string, active: bool}>  $chain
     * @return array<int, MlmCommission>
     */
    protected function calculateBinaryPairCommissions(MlmMember $member, Order $order, int $planId, array $chain, float $budgetRemaining): array
    {
        $settings = $this->pairSettings();
        $created = [];

        foreach ($chain as $node) {
            if (! $node['active']) {
                continue;
            }

            /** @var MlmMember $parent */
            $parent = $node['member'];

            // ลบ carried PV ที่หมดอายุก่อนคำนวณ
            $parent->expireCarriedPv();

            $leftPv = round((float) $parent->left_leg_pv + (float) $parent->carried_left_pv, 2);
            $rightPv = round((float) $parent->right_leg_pv + (float) $parent->carried_right_pv, 2);

            $available = $this->calculatePairsAvailable(min($leftPv, $rightPv), max($leftPv, $rightPv), $settings['pairing_type'], $settings['unit']);

            if ($available <= 0) {
                continue;
            }

            // เพดานรายวันต่อคน (แถว upline ถูกล็อกอยู่ ออเดอร์อื่นจึงแทรกนับซ้ำไม่ได้)
            $capPairs = max(0, $settings['max_pairs_per_day'] - (int) $this->getTodayPairsCount($parent));
            if ($settings['max_commission_per_day'] > 0 && $settings['per_pair'] > 0) {
                $commissionRoom = max(0.0, $settings['max_commission_per_day'] - (float) $this->getTodayCommissionAmount($parent));
                $capPairs = min($capPairs, (int) floor($commissionRoom / $settings['per_pair'] + 1e-9));
            }

            $eligible = min($available, $capPairs);
            $funded = $settings['per_pair'] > 0
                ? min($eligible, (int) floor($budgetRemaining / $settings['per_pair'] + 1e-9))
                : 0;
            // คู่ที่ยังไม่ติดเพดานวันนี้แต่ออเดอร์นี้ไม่มีเงินพอ → เก็บ PV ไว้รอออเดอร์ถัดไป (ไม่ flush)
            $reserved = $eligible - $funded;

            $state = $this->resolveLegsAfterMatch($leftPv, $rightPv, $funded, $reserved, $settings);

            $parent->left_leg_pv = $state['left']['leg'];
            $parent->right_leg_pv = $state['right']['leg'];
            $parent->carried_left_pv = $state['left']['carried'];
            $parent->carried_right_pv = $state['right']['carried'];
            // ยอดยกมาเดิมยังอยู่ในก้อน → คงวันหมดอายุเดิม (เดิมต่ออายุใหม่ทุกออเดอร์ PV ยกยอดจึงไม่เคยหมดอายุ)
            foreach (['left', 'right'] as $side) {
                $field = 'carried_'.$side.'_pv_expires_at';
                $existing = $parent->{$field};
                $parent->{$field} = $state[$side]['carried'] > 0
                    ? ($existing && $existing->isFuture() ? $existing : now()->addDays($settings['carry_days']))
                    : null;
            }
            $parent->save();

            if ($funded <= 0) {
                Log::info('Binary pair ไม่ได้จ่ายรอบนี้ (ติดเพดานรายวันหรือ budget ของออเดอร์)', [
                    'member_id' => $parent->id,
                    'order_id' => $order->id,
                    'pairs_available' => $available,
                    'daily_room' => $capPairs,
                    'budget_remaining' => $budgetRemaining,
                ]);

                continue;
            }

            $amount = round($funded * $settings['per_pair'], 2);
            $budgetRemaining = max(0.0, $budgetRemaining - $amount);

            $created[] = MlmCommission::create([
                'mlm_member_id' => $parent->id,
                'mlm_plan_id' => $planId,
                'user_id' => $parent->user_id,
                'from_member_id' => $member->id,
                'source_type' => Order::class,
                'source_id' => $order->id,
                'type' => 'binary_pair',
                'tree_type' => 'binary',
                'left_leg_pv' => $leftPv,
                'right_leg_pv' => $rightPv,
                'pairs_count' => $funded,
                'pv_amount' => $state['left']['consumed'] + $state['right']['consumed'],
                'sales_amount' => $order->total_amount,
                'commission_amount' => $amount,
                'status' => 'pending',
                'calculation_details' => [
                    'pair_unit_pv' => $settings['unit'],
                    'pairing_type' => $settings['pairing_type'],
                    'per_pair' => $settings['per_pair'],
                    'pairs_available' => $available,
                    'pairs_daily_room' => $capPairs,
                    'pairs_reserved_for_budget' => $reserved,
                    'left_consumed_pv' => $state['left']['consumed'],
                    'right_consumed_pv' => $state['right']['consumed'],
                    'left_flushed_pv' => $state['left']['flushed'],
                    'right_flushed_pv' => $state['right']['flushed'],
                    'carry_forward_enabled' => $settings['carry_enabled'],
                    'budget_remaining_after' => round($budgetRemaining, 2),
                ],
            ]);
        }

        return $created;
    }

    /**
     * คำนวณ PV ขาซ้าย/ขวาหลังจับคู่ (ฟังก์ชันล้วน ไม่แตะ DB)
     *
     * - คู่ที่จ่าย ($funded) ถูกหักออกเต็มจำนวนจากทั้งสองขา
     * - คู่ที่สงวนไว้เพราะ budget ออเดอร์ไม่พอ ($reserved) คงไว้ทั้งหมด
     * - ส่วนที่เหลือ (ขาแข็งที่เกิน, เศษไม่ถึง 1 คู่, คู่ที่เกินเพดานรายวัน):
     *     เปิดยกยอด → ย้ายไป carried ทั้งหมด (leg = 0)
     *     ปิดยกยอด → ล้างตาม flush % ที่เหลือคงอยู่ใน leg (carried = 0)
     *   ผลคือเศษอยู่ที่เดียวเสมอ (F2: เดิมคัดลอกไป carried โดยไม่ลบจาก leg จึงนับซ้ำ 2 เท่า)
     *
     * @return array{left: array{leg: float, carried: float, consumed: float, flushed: float}, right: array{leg: float, carried: float, consumed: float, flushed: float}}
     */
    protected function resolveLegsAfterMatch(float $leftPv, float $rightPv, int $funded, int $reserved, array $settings): array
    {
        $ratio = $settings['pairing_type'] === '2:1' ? 2 : 1;
        $weakSide = $leftPv <= $rightPv ? 'left' : 'right';
        $totals = ['left' => $leftPv, 'right' => $rightPv];
        $result = [];

        foreach (['left', 'right'] as $side) {
            $perPairPv = $settings['unit'] * ($side === $weakSide ? 1 : $ratio);
            $consumed = min($totals[$side], $funded * $perPairPv);
            $remaining = max(0.0, $totals[$side] - $consumed);
            $keep = min($remaining, $reserved * $perPairPv);

            if ($settings['carry_enabled']) {
                $leg = 0.0;
                $carried = $remaining;
                $flushed = 0.0;
            } else {
                $surplus = $remaining - $keep;
                $flushed = $surplus * $settings['flush_rate'];
                $leg = $remaining - $flushed;
                $carried = 0.0;
            }

            $result[$side] = [
                'leg' => round(max(0.0, $leg), 2),
                'carried' => round(max(0.0, $carried), 2),
                'consumed' => round($consumed, 2),
                'flushed' => round(max(0.0, $flushed), 2),
            ];
        }

        return $result;
    }

    /**
     * ค่าตั้ง binary ที่ใช้จับคู่ (อ่านครั้งเดียวต่อออเดอร์)
     *
     * @return array{unit: float, per_pair: float, pairing_type: string, max_pairs_per_day: int, max_commission_per_day: float, carry_enabled: bool, carry_days: int, flush_rate: float}
     */
    protected function pairSettings(): array
    {
        $unit = (float) MlmGlobalSetting::get('binary_pair_unit_pv', self::DEFAULT_PAIR_UNIT_PV);
        $maxPairs = (int) MlmGlobalSetting::get('binary_max_pairs_per_day', null);

        $flushRate = match ((string) MlmGlobalSetting::get('binary_flush_mode', 'percentage')) {
            'full' => 1.0,
            'none' => 0.0,
            default => (float) MlmGlobalSetting::get('binary_flush_percentage', 100) / 100,
        };

        return [
            'unit' => $unit > 0 ? $unit : (float) self::DEFAULT_PAIR_UNIT_PV,
            'per_pair' => max(0.0, (float) MlmGlobalSetting::get('binary_pair_commission', 100)),
            'pairing_type' => MlmGlobalSetting::get('binary_pairing_type', '1:1') === '2:1' ? '2:1' : '1:1',
            // ⚠️ ค่าว่าง/0 = ใช้เพดานเริ่มต้น (เจ้าของตัดสิน M2) — ไม่มีโหมดไม่จำกัดอีกแล้ว
            'max_pairs_per_day' => $maxPairs > 0 ? $maxPairs : self::DEFAULT_MAX_PAIRS_PER_DAY,
            'max_commission_per_day' => max(0.0, (float) MlmGlobalSetting::get('binary_max_commission_per_day', null)),
            'carry_enabled' => (bool) MlmGlobalSetting::get('binary_carry_forward_enabled', false),
            'carry_days' => max(1, (int) MlmGlobalSetting::get('binary_carry_forward_days', 30)),
            'flush_rate' => min(1.0, max(0.0, $flushRate)),
        ];
    }

    /**
     * เงินที่ binary ยังใช้ได้จากออเดอร์นี้ = budget ต่อออเดอร์ − คอมของออเดอร์ที่บันทึกแล้ว
     *
     * budget ต่อออเดอร์ = OverpayProtectionService::orderBudget()
     * (min ของ PV × commission_per_pv × max_commission_percentage กับเงินที่ออเดอร์เก็บเข้ากองจริง — audit F7)
     *
     * @param  float|null  $poolCollected  เงินที่ออเดอร์เก็บเข้า mlm_pool จริง (null = ใช้สูตร PV อย่างเดียว)
     */
    public function remainingOrderBudget(Order $order, float $pv, ?float $poolCollected = null): float
    {
        $maxBudget = OverpayProtectionService::orderBudget($pv, $poolCollected);

        if ($maxBudget <= 0) {
            return 0.0;
        }

        $used = (float) MlmCommission::query()
            ->where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('commission_amount');

        return round(max(0.0, $maxBudget - $used), 2);
    }

    /**
     * ประมาณการจำนวนคู่ที่จับได้ตอนนี้ของสมาชิก (สูตรเดียวกับรอบจับคู่จริง)
     *
     * รวม PV คงเหลือ + PV ที่ยกยอดมา (carried) ของแต่ละขา แล้วใช้ calculatePairsAvailable
     * ยังไม่หักเพดานรายวันและ budget ต่อออเดอร์ — ใช้แสดงผลบนหน้าแดชบอร์ดเท่านั้น
     *
     * @return array{left_pv: float, right_pv: float, weak_pv: float, pairs: int, pairing_type: string, pair_unit_pv: float}
     */
    public function estimatePairs(MlmMember $member): array
    {
        $settings = $this->pairSettings();
        $leftPv = (float) $member->left_leg_pv + (float) $member->carried_left_pv;
        $rightPv = (float) $member->right_leg_pv + (float) $member->carried_right_pv;
        $weak = min($leftPv, $rightPv);
        $strong = max($leftPv, $rightPv);

        return [
            'left_pv' => $leftPv,
            'right_pv' => $rightPv,
            'weak_pv' => $weak,
            'pairs' => $this->calculatePairsAvailable($weak, $strong, $settings['pairing_type'], $settings['unit']),
            'pairing_type' => $settings['pairing_type'],
            'pair_unit_pv' => $settings['unit'],
        ];
    }

    /**
     * คำนวณจำนวน pairs ที่สามารถจับคู่ได้ตาม pairing type
     *
     * สำหรับ 1:1: pairs = floor(ขาอ่อน / unit)
     * สำหรับ 2:1: pairs = min(floor(ขาอ่อน / unit), floor(ขาแข็ง / (2 × unit)))
     *
     * @param  float  $weakerLeg  PV ของขาอ่อน
     * @param  float  $strongerLeg  PV ของขาแข็ง
     * @param  string  $pairingType  ประเภท pairing ('1:1' หรือ '2:1')
     * @param  float  $unit  PV ต่อขาที่นับเป็น 1 คู่
     * @return int จำนวน pairs
     */
    protected function calculatePairsAvailable(float $weakerLeg, float $strongerLeg, string $pairingType, float $unit = self::DEFAULT_PAIR_UNIT_PV): int
    {
        if ($unit <= 0 || $weakerLeg <= 0) {
            return 0;
        }

        // + 1e-9 กันทศนิยม float เช่น 299.99999 ถูกปัดลงเหลือ 2 คู่
        $weakPairs = (int) floor($weakerLeg / $unit + 1e-9);

        if ($pairingType === '2:1') {
            return max(0, min($weakPairs, (int) floor($strongerLeg / (2 * $unit) + 1e-9)));
        }

        return max(0, $weakPairs);
    }

    /**
     * Get pair ratio based on pairing type
     * ใช้ค่าจาก Global Settings
     */
    protected function getPairRatio()
    {
        // 1:1 means 1 PV left + 1 PV right = 1 pair
        // 2:1 means 2 PV left + 1 PV right = 1 pair (or vice versa)
        $pairingType = MlmGlobalSetting::get('binary_pairing_type', '1:1');

        return $pairingType === '2:1' ? 2 : 1;
    }

    /**
     * จำนวนคู่ที่สมาชิกได้วันนี้ (ไม่นับแถวที่ถูกยกเลิก/ปฏิเสธ)
     */
    protected function getTodayPairsCount(MlmMember $member)
    {
        return MlmCommission::where('mlm_member_id', $member->id)
            ->where('type', 'binary_pair')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereDate('created_at', today())
            ->sum('pairs_count');
    }

    /**
     * ยอดเงินคู่ที่สมาชิกได้วันนี้ (ไม่นับแถวที่ถูกยกเลิก/ปฏิเสธ)
     */
    protected function getTodayCommissionAmount(MlmMember $member)
    {
        return MlmCommission::where('mlm_member_id', $member->id)
            ->where('type', 'binary_pair')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereDate('created_at', today())
            ->sum('commission_amount');
    }

    /**
     * Find placement position for new member (auto-placement)
     * ใช้ค่าจาก Global Settings แทน per-plan settings
     *
     * รองรับ:
     * - binary_max_depth: ความลึกสูงสุดของ Binary Tree
     * - binary_max_width: จำนวนลูกสูงสุดต่อ node (2 = Binary, 3 = Ternary)
     */
    public function findPlacementPosition(MlmMember $sponsor, $preferredLeg = null)
    {
        $autoPlacementEnabled = MlmGlobalSetting::get('auto_placement_enabled', true);

        if (! $autoPlacementEnabled) {
            return ['parent_id' => $sponsor->id, 'position' => $preferredLeg ?? 'left'];
        }

        // ดึงค่า depth/width limits จาก Global Settings
        $maxDepth = MlmGlobalSetting::get('binary_max_depth', null);
        $maxWidth = MlmGlobalSetting::get('binary_max_width', 2);

        $placementType = MlmGlobalSetting::get('auto_placement_strategy', 'balanced');

        return match ($placementType) {
            'balanced' => $this->findBalancedPlacement($sponsor, $maxDepth, $maxWidth),
            'weak_leg' => $this->findWeakLegPlacement($sponsor, $maxDepth, $maxWidth),
            'fill_by_level', 'fill_level' => $this->findFillByLevelPlacement($sponsor, $maxDepth, $maxWidth),
            'left_first' => $this->findLeftToRightPlacement($sponsor, $maxDepth, $maxWidth),
            'right_first' => $this->findRightToLeftPlacement($sponsor, $maxDepth, $maxWidth),
            'strong_leg' => $this->findStrongLegPlacement($sponsor, $maxDepth, $maxWidth),
            'manual' => ['parent_id' => $sponsor->id, 'position' => $preferredLeg ?? 'left'],
            default => $this->findBalancedPlacement($sponsor, $maxDepth, $maxWidth),
        };
    }

    /**
     * วางสมาชิกใหม่ลง Binary tree แบบปลอดภัย — ศูนย์กลางสำหรับทุก join path
     *
     * 🐛 Fix 2026-07-24: เดิมแต่ละ join path (เว็บ/แอดมิน/Fortune/FreshMarket/mobile)
     * เขียน placement + fallback เองคนละแบบ → บางทางไม่มี fallback (สมาชิกหลุดผัง)
     * บางทาง fallback วางใต้ sponsor ตรงทั้งที่ slot เต็ม (ตำแหน่งซ้ำ)
     *
     * ความสามารถ:
     * - หาตำแหน่งตาม strategy → ถ้าไม่ได้ (null/depth เต็ม/exception) → BFS หา slot ว่างจริง
     * - อัพเดท member (parent/position/path) + สถิติขา parent + team counts ของ upline
     * - กัน race: retry สูงสุด 3 ครั้งเมื่อชน unique constraint (สอง registration แย่ง slot เดียวกัน)
     *
     * @param  MlmMember  $member  สมาชิกที่ยังไม่ถูกวาง (binary_parent_id ต้องยัง null)
     * @param  MlmMember  $sponsor  ผู้แนะนำ (จุดเริ่มค้นหาตำแหน่ง)
     * @param  string|null  $preferredLeg  ขาที่ต้องการ (สำหรับ strategy 'manual')
     * @return array|null ['parent_id' => int, 'position' => string] ที่วางสำเร็จ หรือ null ถ้าวางไม่ได้
     */
    public function placeNewMember(MlmMember $member, MlmMember $sponsor, ?string $preferredLeg = null): ?array
    {
        // เมื่อ slot ที่ strategy เลือกถูกแย่ง/เต็ม → รอบถัดไปบังคับใช้ fallback BFS
        // (สำคัญกับ strategy 'manual' และกรณีปิด auto_placement ที่คืน slot ตายตัวเดิมทุกรอบ)
        $useFallback = false;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            // ดึง sponsor สดใหม่ทุกรอบ กัน relation cache ชี้ slot ที่เพิ่งถูกคนอื่นแย่งไป
            $freshSponsor = $sponsor->fresh();
            if ($freshSponsor) {
                $sponsor = $freshSponsor;
            }

            $placement = null;

            if (! $useFallback) {
                try {
                    $placement = $this->findPlacementPosition($sponsor, $preferredLeg);
                } catch (\Throwable $e) {
                    Log::warning('Binary placement ล้มเหลว — จะใช้ fallback BFS', [
                        'member_id' => $member->id,
                        'sponsor_id' => $sponsor->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback: BFS หา slot ว่างจริงใต้ sponsor (ไม่สน depth cap — กันสมาชิกหลุดผัง)
            if (! is_array($placement) || ! isset($placement['parent_id'])) {
                $placement = $this->findFallbackPosition($sponsor);
            }

            if (! $placement) {
                return null;
            }

            $parent = MlmMember::find($placement['parent_id']);
            if (! $parent) {
                $useFallback = true;

                continue;
            }

            $position = $placement['position'] ?? 'left';

            // ตรวจว่า slot ยังว่างจริงก่อนเขียน (unique index เป็น backstop สุดท้าย)
            $slotTaken = MlmMember::where('binary_parent_id', $parent->id)
                ->where('binary_position', $position)
                ->where('id', '!=', $member->id)
                ->exists();

            if ($slotTaken) {
                $useFallback = true; // slot จาก strategy ใช้ไม่ได้ — รอบหน้าหา slot ว่างจริงแทน

                continue;
            }

            try {
                $member->update([
                    'binary_parent_id' => $parent->id,
                    'binary_position' => $position,
                    'binary_path' => ($parent->binary_path ?? '').'/'.$position,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = duplicate key (ชนกับ registration อื่นพร้อมกัน) → หาตำแหน่งใหม่
                if (($e->errorInfo[0] ?? '') === '23000') {
                    $useFallback = true;

                    continue;
                }
                throw $e;
            }

            // อัพเดทสถิติขาของ parent + team counts ของ upline ทั้งสาย
            $parent->increment($position === 'left' ? 'left_leg_members' : 'right_leg_members');
            $this->incrementUplineTeamCounts($member);

            Log::info('Binary placement สำเร็จ', [
                'member_id' => $member->id,
                'parent_id' => $parent->id,
                'position' => $position,
                'attempt' => $attempt + 1,
            ]);

            return ['parent_id' => $parent->id, 'position' => $position];
        }

        Log::error('Binary placement ล้มเหลวหลัง retry ครบ — member ยังไม่ถูกวางในผัง', [
            'member_id' => $member->id,
            'sponsor_id' => $sponsor->id,
        ]);

        return null;
    }

    /**
     * BFS หา slot ว่างจริงใต้ sponsor โดยไม่สน depth cap
     *
     * ใช้เป็น fallback เมื่อ strategy ปกติหาตำแหน่งไม่ได้ (เช่น binary_max_depth เต็ม)
     * หลัก: การให้สมาชิกอยู่ในผังลึกเกิน cap ดีกว่าหลุดผังไปเลย
     * (ห้าม fallback วางใต้ sponsor ตรงๆ — slot อาจเต็มแล้ว จะได้ตำแหน่งซ้ำ)
     *
     * @return array|null ['parent_id' => int, 'position' => string] หรือ null
     */
    public function findFallbackPosition(MlmMember $sponsor): ?array
    {
        $queue = [$sponsor->id];
        $visited = [];
        $scanned = 0;

        while (! empty($queue) && $scanned < 5000) {
            $currentId = array_shift($queue);

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;
            $scanned++;

            $children = MlmMember::where('binary_parent_id', $currentId)
                ->whereIn('binary_position', ['left', 'right'])
                ->get(['id', 'binary_position']);

            if (! $children->contains(fn ($c) => $c->binary_position === 'left')) {
                return ['parent_id' => $currentId, 'position' => 'left'];
            }

            if (! $children->contains(fn ($c) => $c->binary_position === 'right')) {
                return ['parent_id' => $currentId, 'position' => 'right'];
            }

            foreach ($children as $child) {
                $queue[] = $child->id;
            }
        }

        return null;
    }

    /**
     * เพิ่ม total_team_members ให้ upline ทั้งสาย binary ของสมาชิกใหม่
     *
     * 🐛 แทนที่ updateBinaryUplineTeamCounts เดิมใน RegisterController ที่มีบั๊ก
     * (safety check เทียบ id หลัง assign ทำให้ break หลังวนแค่รอบเดียว)
     */
    protected function incrementUplineTeamCounts(MlmMember $member): void
    {
        $visited = [];
        $current = $member;

        for ($i = 0; $i < 100; $i++) {
            $parentId = $current->binary_parent_id;

            if (! $parentId || isset($visited[$parentId])) {
                break; // ถึงราก หรือเจอ cycle
            }
            $visited[$parentId] = true;

            $parent = MlmMember::find($parentId);
            if (! $parent) {
                break;
            }

            $parent->increment('total_team_members');
            $current = $parent;
        }
    }

    /**
     * นับ depth ของ member จาก root
     */
    protected function getMemberDepth(MlmMember $member): int
    {
        // นับจาก binary_path หรือ traverse up
        if ($member->binary_path) {
            // นับจำนวน '/' ใน path (ยกเว้นตัวแรก)
            return substr_count($member->binary_path, '/');
        }

        // Fallback: traverse up the tree
        $depth = 0;
        $current = $member;
        while ($current->binaryParent) {
            $depth++;
            $current = $current->binaryParent;
            if ($depth > 100) {
                break;
            } // Safety limit
        }

        return $depth;
    }

    /**
     * นับจำนวน children ของ node
     */
    protected function getChildrenCount(MlmMember $member): int
    {
        $count = 0;
        if ($member->binaryLeftChild) {
            $count++;
        }
        if ($member->binaryRightChild) {
            $count++;
        }

        return $count;
    }

    /**
     * ตรวจสอบว่าสามารถวาง child ที่ node นี้ได้หรือไม่
     */
    protected function canPlaceChild(MlmMember $parent, ?int $maxDepth, int $maxWidth): bool
    {
        // ตรวจสอบ width limit
        if ($this->getChildrenCount($parent) >= $maxWidth) {
            return false;
        }

        // ตรวจสอบ depth limit (ถ้ามี)
        if ($maxDepth !== null) {
            $parentDepth = $this->getMemberDepth($parent);
            if ($parentDepth >= $maxDepth) {
                return false;
            }
        }

        return true;
    }

    /**
     * ตรวจ depth limit + คืน depth ปัจจุบันของ node
     *
     * 🐛 Fix 2026-06-12: เดิมทุก DFS strategy เช็ค canPlaceChild() ที่บรรทัดแรก
     * → node ที่มีลูกครบ (width เต็ม) ถูก return null ทันที โค้ด recursion ลง subtree
     * กลายเป็น dead code → sponsor ที่มีลูกครบ 2 ข้างวางสมาชิกใหม่ไม่ได้ตลอดกาล
     * → MlmMember ไม่ถูกสร้าง → คอมมิชชั่นดูดวงไม่จ่ายทั้งระบบ
     *
     * หลักที่ถูก: width เต็ม = วางตรง node นี้ไม่ได้ แต่ subtree ข้างล่างยังวางได้
     * มีแค่ depth limit เท่านั้นที่หยุดการค้นทั้งสาขา
     *
     * @param  MlmMember  $member  node ที่กำลังตรวจ
     * @param  int|null  $maxDepth  ความลึกสูงสุด (null = ไม่จำกัด)
     * @param  int|null  $currentDepth  depth ที่ thread ลงมาจาก recursion (null = คำนวณใหม่)
     * @return array{exceeded: bool, depth: int|null} exceeded = ลึกเกิน limit แล้ว
     */
    protected function resolveDepthGuard(MlmMember $member, ?int $maxDepth, ?int $currentDepth): array
    {
        if ($maxDepth === null) {
            return ['exceeded' => false, 'depth' => null];
        }

        $depth = $currentDepth ?? $this->getMemberDepth($member);

        return ['exceeded' => $depth >= $maxDepth, 'depth' => $depth];
    }

    /**
     * หาช่องว่างที่วางตรง node นี้ได้เลย (ถ้า width ยังไม่เต็ม)
     *
     * @return array|null ['parent_id' => ..., 'position' => ...] หรือ null ถ้า node เต็ม
     */
    protected function findDirectSlot(MlmMember $member, int $maxWidth): ?array
    {
        if ($this->getChildrenCount($member) >= $maxWidth) {
            return null;
        }

        if (! $member->binaryLeftChild) {
            return ['parent_id' => $member->id, 'position' => 'left'];
        }

        if ($maxWidth >= 2 && ! $member->binaryRightChild) {
            return ['parent_id' => $member->id, 'position' => 'right'];
        }

        return null;
    }

    /**
     * Find right-to-left placement
     * รองรับ depth/width limits
     */
    protected function findRightToLeftPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง (ขวาก่อน)
        if ($this->getChildrenCount($member) < $maxWidth) {
            if ($maxWidth >= 2 && ! $member->binaryRightChild) {
                return ['parent_id' => $member->id, 'position' => 'right'];
            }
            if (! $member->binaryLeftChild) {
                return ['parent_id' => $member->id, 'position' => 'left'];
            }
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // node เต็ม → ค้นใน subtree ขวาก่อน แล้วค่อยซ้าย
        if ($member->binaryRightChild) {
            $rightPlacement = $this->findRightToLeftPlacement($member->binaryRightChild, $maxDepth, $maxWidth, $childDepth);
            if ($rightPlacement) {
                return $rightPlacement;
            }
        }

        if ($member->binaryLeftChild) {
            return $this->findRightToLeftPlacement($member->binaryLeftChild, $maxDepth, $maxWidth, $childDepth);
        }

        return null;
    }

    /**
     * Find strong leg placement
     * รองรับ depth/width limits
     */
    protected function findStrongLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่ PV มากกว่าก่อน (strong leg) — ถ้าขานั้นเต็มให้ลองอีกขา
        $strongFirst = $member->left_leg_pv >= $member->right_leg_pv;
        $legs = $strongFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
    }

    /**
     * Find left-to-right placement
     * รองรับ depth/width limits
     */
    protected function findLeftToRightPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง (ซ้ายก่อน)
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // node เต็ม → ค้นใน subtree ซ้ายก่อน แล้วค่อยขวา
        if ($member->binaryLeftChild) {
            $leftPlacement = $this->findLeftToRightPlacement($member->binaryLeftChild, $maxDepth, $maxWidth, $childDepth);
            if ($leftPlacement) {
                return $leftPlacement;
            }
        }

        if ($member->binaryRightChild) {
            return $this->findLeftToRightPlacement($member->binaryRightChild, $maxDepth, $maxWidth, $childDepth);
        }

        return null;
    }

    /**
     * Find balanced placement
     * รองรับ depth/width limits
     */
    protected function findBalancedPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่สมาชิกน้อยกว่าก่อน — ถ้าขานั้นเต็มให้ลองอีกขา
        $leftFirst = $member->left_leg_members <= $member->right_leg_members;
        $legs = $leftFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
    }

    /**
     * Find weak leg placement
     * รองรับ depth/width limits
     */
    protected function findWeakLegPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2, ?int $currentDepth = null)
    {
        $guard = $this->resolveDepthGuard($member, $maxDepth, $currentDepth);
        if ($guard['exceeded']) {
            return null;
        }

        // วางตรง node นี้ได้เลยถ้ามีช่อง
        $direct = $this->findDirectSlot($member, $maxWidth);
        if ($direct) {
            return $direct;
        }

        $childDepth = $guard['depth'] !== null ? $guard['depth'] + 1 : null;

        // เลือกขาที่ PV น้อยกว่าก่อน (weak leg) — ถ้าขานั้นเต็มให้ลองอีกขา
        $weakFirst = $member->left_leg_pv <= $member->right_leg_pv;
        $legs = $weakFirst
            ? [$member->binaryLeftChild, $member->binaryRightChild]
            : [$member->binaryRightChild, $member->binaryLeftChild];

        foreach ($legs as $leg) {
            if ($leg) {
                $placement = $this->findLeftToRightPlacement($leg, $maxDepth, $maxWidth, $childDepth);
                if ($placement) {
                    return $placement;
                }
            }
        }

        return null;
    }

    /**
     * Find fill-by-level placement (BFS - เติมให้เต็มชั้น)
     *
     * เติมสมาชิกใหม่ในแต่ละชั้นให้เต็มก่อน แล้วค่อยไปชั้นถัดไป
     * ใช้ Breadth-First Search (BFS) algorithm
     *
     * รองรับ depth/width limits:
     * - binary_max_depth: ความลึกสูงสุดของ Binary Tree
     * - binary_max_width: จำนวนลูกสูงสุดต่อ node (2 = Binary, 3 = Ternary)
     *
     * @param  MlmMember  $member  จุดเริ่มต้นของ tree
     * @param  int|null  $maxDepth  ความลึกสูงสุด (null = ไม่จำกัด)
     * @param  int  $maxWidth  จำนวนลูกสูงสุดต่อ node
     * @return array|null
     */
    protected function findFillByLevelPlacement(MlmMember $member, ?int $maxDepth = null, int $maxWidth = 2)
    {
        // ใช้ Queue สำหรับ BFS - เก็บ [member, currentDepth]
        $queue = collect([['member' => $member, 'depth' => 0]]);
        $visited = collect();

        while ($queue->isNotEmpty()) {
            $item = $queue->shift();
            $current = $item['member'];
            $currentDepth = $item['depth'];

            // ข้าม node ที่เคย visit แล้ว (ป้องกัน infinite loop)
            if ($visited->contains($current->id)) {
                continue;
            }
            $visited->push($current->id);

            // ตรวจสอบ depth limit
            if ($maxDepth !== null && $currentDepth >= $maxDepth) {
                continue;
            }

            // ตรวจสอบว่า left ว่างไหม
            if (! $current->binaryLeftChild) {
                return ['parent_id' => $current->id, 'position' => 'left'];
            }

            // ตรวจสอบว่า right ว่างไหม (ถ้า maxWidth >= 2)
            if ($maxWidth >= 2 && ! $current->binaryRightChild) {
                return ['parent_id' => $current->id, 'position' => 'right'];
            }

            // สำหรับ maxWidth > 2 (Ternary หรือมากกว่า)
            // ตรวจสอบ additional children positions
            if ($maxWidth > 2) {
                $childCount = $this->getChildrenCount($current);
                if ($childCount < $maxWidth) {
                    // กำหนด position ตามลำดับ
                    $positions = ['left', 'right', 'center', 'far_left', 'far_right'];
                    for ($i = 0; $i < $maxWidth && $i < count($positions); $i++) {
                        $pos = $positions[$i];
                        // ตรวจสอบว่า position นี้ว่างหรือไม่
                        $hasChild = MlmMember::where('binary_parent_id', $current->id)
                            ->where('binary_position', $pos)
                            ->exists();
                        if (! $hasChild) {
                            return ['parent_id' => $current->id, 'position' => $pos];
                        }
                    }
                }
            }

            // เพิ่ม children เข้า queue (ไปชั้นถัดไป)
            if ($current->binaryLeftChild) {
                $queue->push(['member' => $current->binaryLeftChild, 'depth' => $currentDepth + 1]);
            }
            if ($current->binaryRightChild) {
                $queue->push(['member' => $current->binaryRightChild, 'depth' => $currentDepth + 1]);
            }
        }

        // Fallback: ถ้าไม่พบตำแหน่งว่าง (อาจเกิน depth limit)
        Log::warning('Binary tree placement failed - tree is full or depth limit reached', [
            'sponsor_id' => $member->id,
            'max_depth' => $maxDepth,
            'max_width' => $maxWidth,
        ]);

        return null;
    }

    /**
     * Get binary tree structure
     */
    public function getBinaryTree(MlmMember $member, int $maxDepth = 5)
    {
        return $this->buildBinaryTreeRecursive($member, 0, $maxDepth);
    }

    /**
     * Build binary tree recursively
     */
    protected function buildBinaryTreeRecursive(MlmMember $member, int $currentDepth, int $maxDepth)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        // Get retention status from commission service
        $commissionService = app(MlmCommissionService::class);
        $retentionData = $commissionService->getMemberRetentionStatus($member);

        // ดึงข้อมูลเพิ่มเติมสำหรับ v3 genealogy tree
        // ⚠️ MlmMember ไม่มี relation rank() — ต้องอ่าน rank ผ่าน user->currentRank เท่านั้น
        $user = $member->user;
        $rank = $user?->currentRank;

        // คำนวณ retention วันหมดอายุ
        $retentionExpiresAt = $retentionData['expires_at'] ?? null;
        $retentionDaysLeft = null;
        if ($retentionExpiresAt) {
            $retentionDaysLeft = max(0, (int) now()->diffInDays($retentionExpiresAt, false));
        }

        $node = [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $user->name ?? 'Unknown',
            'member_code' => $member->member_code,
            'position' => $member->binary_position,
            'depth' => $currentDepth,
            // ข้อมูลใหม่ v3
            'avatar_url' => $user->profile_picture_url ?? null,
            'email' => $user->email ?? null,
            'rank_name' => $rank->name ?? null,
            'rank_color' => $rank->color ?? '#6B7280',
            'joined_at' => $member->created_at?->format('Y-m-d'),
            'retention_expires_at' => $retentionExpiresAt,
            'retention_days_left' => $retentionDaysLeft,
            'is_qualified' => (bool) ($member->is_qualified ?? false),
            // ข้อมูลเดิม
            'total_pv' => $member->total_pv,
            'monthly_pv' => $retentionData['monthly_pv'],
            // left/right_leg_pv = ยอดคงเหลือหลังจับคู่ (ไม่ใช่ยอดสะสมทั้งหมด)
            'left_leg_pv' => $member->left_leg_pv,
            'right_leg_pv' => $member->right_leg_pv,
            // carried PV = PV ขาแข็งที่ยกยอดไปรอบถัดไป — ต้องแสดงคู่กันไม่งั้นตัวเลขในผัง
            // ไม่ตรงกับที่ engine ใช้จับคู่จริง
            'carried_left_pv' => (float) ($member->carried_left_pv ?? 0),
            'carried_right_pv' => (float) ($member->carried_right_pv ?? 0),
            'status' => $member->status,
            'retention_status' => $retentionData['status'],
            'direct_referrals' => $member->total_direct_referrals ?? 0,
            'left' => null,
            'right' => null,
        ];

        // โหลด children (eager load user+currentRank สำหรับ v3)
        // ⚠️ ห้ามใส่ 'rank' ตรงๆ — MlmMember ไม่มี relation นี้ จะโยน RelationNotFoundException
        $leftChild = $member->binaryLeftChild()->with(['user.currentRank'])->first();
        $rightChild = $member->binaryRightChild()->with(['user.currentRank'])->first();

        if ($leftChild) {
            $node['left'] = $this->buildBinaryTreeRecursive($leftChild, $currentDepth + 1, $maxDepth);
        }

        if ($rightChild) {
            $node['right'] = $this->buildBinaryTreeRecursive($rightChild, $currentDepth + 1, $maxDepth);
        }

        return $node;
    }
}
