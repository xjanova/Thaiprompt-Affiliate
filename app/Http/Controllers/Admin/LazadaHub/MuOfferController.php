<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\FortuneProductOffer;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * 🛒 ตั้งค่า "แม่หมอเสนอสินค้า" — ใครได้การ์ดบ้าง · หน่วงกี่นาที
 *
 * เจตนา (owner 2026-08-27): "ส่งให้เฉพาะพวกที่ส่งภาพ กับส่งลิ้งค์ เท่านั้น
 * แต่อยากให้ทำเป็นเปิดปิดได้ ว่าจะส่งไปให้ใครบ้าง ดีเลย์กี่นาทีถึงจะส่ง"
 *
 * 🎚️ หน้านี้เขียนลง `marketplace_settings` 3 คีย์เท่านั้น:
 *   - `fortune_mu_offer_enabled`  สวิตช์ใหญ่
 *   - `fortune_mu_offer_triggers` จุดยิงที่เปิด (คั่นด้วยจุลภาค)
 *   - `fortune_mu_offer_delays`   ตารางหน่วงเวลารายจุด (JSON)
 *
 * ⚠️ ห้ามบันทึก `fortune_mu_offer_triggers` เป็นค่าว่างเด็ดขาด —
 *    ในโค้ดฝั่ง service "ว่าง = เปิดทุกจุด" ⇒ แอดมินที่ติ๊กออกหมดเพราะอยากปิดทุกอย่าง
 *    จะได้ผลกลับหัวคือเปิดหมดทุกจุด. ไม่ติ๊กอะไรเลย = ต้องปิดสวิตช์ใหญ่ให้แทน
 */
class MuOfferController extends Controller
{
    /** หน่วงได้นานสุดกี่นาที (24 ชม.) — เกินนี้ลูกค้าลืมบริบทไปแล้ว */
    private const MAX_DELAY_MINUTES = 1440;

    public function index()
    {
        $triggers = FortuneProductOffer::configurableTriggers();
        $enabledList = $this->enabledTriggers();
        $delays = $this->delayMap();

        return view('admin.lazada-hub.mu-offer.index', [
            'pageTitle' => 'แม่หมอเสนอสินค้า',
            'masterEnabled' => filter_var(MarketplaceSetting::get('fortune_mu_offer_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'triggers' => $triggers,
            'enabledList' => $enabledList,
            'delays' => $delays,
            'dailyCap' => (int) MarketplaceSetting::get('fortune_mu_offer_daily_cap', 1),
            'paidEndCap' => (int) MarketplaceSetting::get('fortune_mu_offer_paid_end_daily_cap', 1),
            'muteDays' => (int) MarketplaceSetting::get('fortune_mu_offer_mute_days', 7),
            'stats' => $this->stats(array_keys($triggers)),
            'pool' => $this->pool(),
        ]);
    }

    /**
     * บันทึกค่าตั้ง
     */
    public function update(Request $request)
    {
        $known = array_keys(FortuneProductOffer::configurableTriggers());

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'triggers' => ['nullable', 'array'],
            'triggers.*' => ['string', 'in:'.implode(',', $known)],
            'delays' => ['nullable', 'array'],
            'delays.*' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_DELAY_MINUTES],
            'daily_cap' => ['required', 'integer', 'min:0', 'max:20'],
            'paid_end_cap' => ['required', 'integer', 'min:0', 'max:20'],
            'mute_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $picked = array_values(array_intersect($known, $data['triggers'] ?? []));
        $master = (bool) ($data['enabled'] ?? false);

        // 🚨 ว่าง = "เปิดทุกจุด" ในฝั่ง service — กลับหัวกับที่แอดมินตั้งใจ
        //    ไม่ติ๊กอะไรเลย ⇒ ปิดสวิตช์ใหญ่ให้ แล้วคงรายการเดิมไว้ไม่ให้ค่าว่างหลุดลง DB
        if (empty($picked)) {
            $master = false;
            $picked = $this->enabledTriggers();
        }

        // 🚨 ต้องเขียน "ทุกจุด" รวมเลข 0 ด้วย — ห้ามตัดศูนย์ทิ้งให้ตารางสั้นลง
        //    ฝั่ง service ถ้าไม่เจอคีย์ daily_free จะถอยไปอ่านคีย์เดิม (ค่าปริยาย 60 นาที)
        //    ⇒ แอดมินตั้ง 0 เพราะอยากให้ส่งทันที แต่จะได้ 60 นาทีกลับมาแทน
        $delays = [];
        foreach ($known as $trigger) {
            $delays[$trigger] = min(self::MAX_DELAY_MINUTES, max(0, (int) ($data['delays'][$trigger] ?? 0)));
        }

        MarketplaceSetting::set('fortune_mu_offer_enabled', $master ? 'true' : 'false', 'boolean', 'เปิด/ปิดการเสนอสินค้าของบอทแม่หมอ');
        MarketplaceSetting::set('fortune_mu_offer_triggers', implode(',', $picked), 'string', 'จุดยิงที่เปิดอยู่');
        MarketplaceSetting::set('fortune_mu_offer_delays', json_encode($delays, JSON_UNESCAPED_UNICODE), 'json', 'หน่วงกี่นาทีก่อนส่ง แยกรายจุดยิง');
        // เขียนคีย์เดิมให้ตรงกันด้วย — ถ้าวันไหนต้อง rollback โค้ด ค่าที่ตั้งไว้จะไม่หาย
        MarketplaceSetting::set('fortune_mu_offer_daily_free_delay_minutes', (string) ($delays[FortuneProductOffer::TRIGGER_DAILY_FREE] ?? 60), 'integer', 'ระยะห่างการ์ดสินค้าจากกล่องดวงฟรี (นาที)');
        MarketplaceSetting::set('fortune_mu_offer_daily_cap', (string) $data['daily_cap'], 'integer', 'บอทเสนอเองได้กี่ครั้ง/คน/วัน');
        MarketplaceSetting::set('fortune_mu_offer_paid_end_daily_cap', (string) $data['paid_end_cap'], 'integer', 'โควตาแยกของท้ายบิลที่จ่ายเงินแล้ว');
        MarketplaceSetting::set('fortune_mu_offer_mute_days', (string) $data['mute_days'], 'integer', 'ลูกค้าบอกรำคาญ → เงียบกี่วัน');

        $msg = $master
            ? 'บันทึกแล้ว — เปิดอยู่ '.count($picked).' จุด'
            : 'บันทึกแล้ว — ปิดสวิตช์ใหญ่ (ไม่ส่งการ์ดให้ใครเลย)';

        return redirect()
            ->route('admin.lazada-hub.mu-offer.index')
            ->with('success', $msg);
    }

    /**
     * จุดยิงที่เปิดอยู่ตอนนี้
     *
     * แปลคำเดิม `gesture` (รวมทุกท่า) ให้เป็น 3 ตัวย่อย เพื่อให้ช่องติ๊กในฟอร์ม
     * ตรงกับสิ่งที่บอททำจริงอยู่ ไม่ใช่โชว์ว่าปิดทั้งที่ยังยิงอยู่
     *
     * @return array<int,string>
     */
    private function enabledTriggers(): array
    {
        $raw = trim((string) MarketplaceSetting::get('fortune_mu_offer_triggers', ''));
        $known = array_keys(FortuneProductOffer::configurableTriggers());

        // ว่าง = service ถือว่าเปิดทุกจุด ⇒ ฟอร์มต้องแสดงให้ตรงกัน
        if ($raw === '') {
            return $known;
        }

        $list = array_filter(array_map('trim', explode(',', $raw)));

        if (in_array(FortuneProductOffer::TRIGGER_GESTURE, $list, true)) {
            $list = array_merge($list, [
                FortuneProductOffer::TRIGGER_GESTURE_IMAGE,
                FortuneProductOffer::TRIGGER_GESTURE_LINK,
                FortuneProductOffer::TRIGGER_GESTURE_STICKER,
            ]);
        }

        return array_values(array_intersect($known, array_unique($list)));
    }

    /**
     * ตารางหน่วงเวลารายจุด (นาที)
     *
     * @return array<string,int>
     */
    private function delayMap(): array
    {
        $map = MarketplaceSetting::get('fortune_mu_offer_delays', null);
        if (is_string($map)) {
            $map = json_decode($map, true);
        }

        $out = [];
        foreach (array_keys(FortuneProductOffer::configurableTriggers()) as $trigger) {
            $out[$trigger] = is_array($map) && isset($map[$trigger])
                ? max(0, (int) $map[$trigger])
                : 0;
        }

        // คีย์เดิมของสายดวงฟรี — ยังใช้อยู่ถ้า JSON ไม่มีคีย์นี้
        if (! (is_array($map) && array_key_exists(FortuneProductOffer::TRIGGER_DAILY_FREE, $map))) {
            $out[FortuneProductOffer::TRIGGER_DAILY_FREE] =
                max(0, (int) MarketplaceSetting::get('fortune_mu_offer_daily_free_delay_minutes', 60));
        }

        return $out;
    }

    /**
     * ส่งไปแล้วกี่ใบ/กี่คน ใน 7 วัน แยกตามจุดยิง
     *
     * ⚠️ รวมแถวเก่าที่บันทึกเป็น `gesture` เข้ากับ `gesture_sticker` ไม่ได้ —
     *    ของเดิมไม่ได้แยกท่าไว้ จึงโชว์เป็นแถวรวมของตัวเองต่างหาก
     *
     * @param  array<int,string>  $triggers
     * @return array<string,array{cards:int,people:int}>
     */
    private function stats(array $triggers): array
    {
        if (! Schema::hasTable('fortune_product_offers')) {
            return [];
        }

        $rows = FortuneProductOffer::query()
            ->where('sent_at', '>=', now()->subDays(7))
            ->selectRaw('`trigger` as t, COUNT(*) as cards, COUNT(DISTINCT platform_user_id) as people')
            ->groupBy('t')
            ->get()
            ->keyBy('t');

        $out = [];
        foreach (array_merge($triggers, [FortuneProductOffer::TRIGGER_GESTURE]) as $trigger) {
            $out[$trigger] = [
                'cards' => (int) ($rows[$trigger]->cards ?? 0),
                'people' => (int) ($rows[$trigger]->people ?? 0),
            ];
        }

        return $out;
    }

    /**
     * มีของให้ส่งจริงกี่ชิ้น — ตั้งค่าสวยแค่ไหนก็ไร้ผลถ้าคลังว่าง
     *
     * @return array{total:int,sendable:int,groups:array<string,int>}
     */
    private function pool(): array
    {
        if (! Schema::hasColumn('marketplace_products', 'mu_group')) {
            return ['total' => 0, 'sendable' => 0, 'groups' => []];
        }

        $sendable = MarketplaceProduct::query()->mu()->offerable()->sendableInChat();

        return [
            'total' => MarketplaceProduct::query()->mu()->count(),
            'sendable' => (clone $sendable)->count(),
            'groups' => (clone $sendable)
                ->selectRaw('mu_group, COUNT(*) as n')
                ->groupBy('mu_group')
                ->orderByDesc('n')
                ->pluck('n', 'mu_group')
                ->map(fn ($n) => (int) $n)
                ->all(),
        ];
    }
}
