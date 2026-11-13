<?php

namespace App\Services\Fortune;

use App\Models\FortuneWebFunnelEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * สรุปตัวนับเส้นทาง แชทเพจ FB → ห้องแม่หมอบนเว็บจันทรา (อ่านอย่างเดียว — หน้าแอดมิน)
 *
 * ขั้นที่ 1-2 นับ "คน" ด้วย PSID (ยังอยู่ฝั่ง FB) · ขั้นที่ 3-4 นับด้วย users.id (ล็อกอินแล้ว)
 * แต่ละขั้นนับภายในช่วงวันที่เลือก (ไม่ใช่ cohort) — ลูกค้าที่ได้กล่องเมื่อวานแล้วจ่ายวันนี้ นับในวันที่จ่าย
 */
class WebFunnelReport
{
    /** ช่วงวันยาวสุดที่ให้เลือก */
    public const MAX_DAYS = 92;

    /** ชื่อไทยของจุดที่ส่งกล่อง (source) — ตัวที่ไม่รู้จักแสดงรหัสเดิม */
    public const SOURCE_LABELS = [
        'button:WEB_ROOM' => 'กดปุ่ม 🌙 เข้าห้องแม่หมอ',
        'button:WEB_ROOM_HOWTO' => 'กดปุ่ม ❓ เข้ายังไงคะ',
        'button:INVITE_FREE_WEB' => 'กดปุ่มชวนดูฟรีบนเว็บ',
        'button:INVITE_READ_NOW' => 'กดปุ่มดูดวงเลย (ข้อความชวน)',
        'button:GET_STARTED' => 'กดเริ่มต้นแชท',
        'button:get_started' => 'กดเริ่มต้นแชท',
        'web_only_gate' => 'พิมพ์ขอดูดวง/ถามในแชท',
        'gesture_emoji' => 'ส่งอีโมจิ',
        'gesture_sticker' => 'ส่งสติกเกอร์',
        'gesture_like' => 'ส่งไลก์',
        'image_tier_c' => 'ส่งรูปในแชท',
        'slip_tier_c' => 'ส่งรูปสลิปในแชท',
        'slip_tier_c_review' => 'ส่งรูปคล้ายสลิป (รอตรวจ)',
        'affiliate_interest' => 'สนใจเป็นตัวแทน',
        'story_welcome' => 'ทักจากสตอรี่',
        'get_started' => 'เริ่มแชทครั้งแรก',
        'menu_register' => 'เมนูสมัครสมาชิก',
        'menu_referral' => 'เมนูชวนเพื่อน',
        'transfer_stay_confirm' => 'ขอดูในแชทต่อ (ทางถอยเดิม)',
        'invite_free_web' => 'กดชวนดูฟรีบนเว็บ',
    ];

    /** ชื่อไทยของเหตุผลที่ลิงก์ใช้ไม่ได้ */
    public const DEAD_LABELS = [
        'used' => 'ลิงก์ถูกใช้ไปแล้ว (แตะซ้ำ)',
        'expired' => 'ลิงก์หมดอายุ',
        'invalid' => 'ลิงก์เสีย/ถูกแก้',
        'busy' => 'ระบบไม่ว่าง (รอคิวไม่ทัน)',
        'error' => 'สร้างบัญชีไม่สำเร็จ',
        'denied' => 'บัญชีที่ไม่ใช่ลูกค้า',
        'staff_session' => 'ทีมงานกดลิงก์ลูกค้า',
    ];

    /**
     * สรุปทั้งหมดของช่วงวันที่
     *
     * @param  int|null  $pageId  กรองสาขา (null = ทุกสาขา)
     * @return array{ready:bool,from:string,to:string,funnel:array,daily:array,sources:array,dead:array,totals:array,since:?string}
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to, ?int $pageId = null): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        $out = [
            'ready' => Schema::hasTable('fortune_web_funnel_events'),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'funnel' => [],
            'daily' => [],
            'sources' => [],
            'dead' => [],
            'totals' => ['boxes' => 0, 'paid_amount' => 0.0, 'bills' => 0, 'dead' => 0],
            // เวลาแถวแรกสุด — บอกแอดมินว่าตัวนับเริ่มเก็บเมื่อไร (null = ยังไม่มีสักแถว)
            'since' => null,
        ];
        if (! $out['ready']) {
            return $out;
        }
        $out['since'] = FortuneWebFunnelEvent::query()->min('created_at');

        $scope = function (string $event) use ($from, $to, $pageId): Builder {
            return FortuneWebFunnelEvent::query()
                ->where('event', $event)
                ->whereBetween('created_at', [$from, $to])
                ->when($pageId !== null, fn (Builder $q) => $q->where('fortune_page_id', $pageId));
        };

        // ── ขั้นหลัก (คน) ──
        $gotBox = (int) $scope(FortuneWebFunnelEvent::BOX_SENT)->distinct()->count('platform_user_id');
        $pressed = (int) $scope(FortuneWebFunnelEvent::BOX_SENT)->where('source', 'like', 'button:%')->distinct()->count('platform_user_id');
        $opened = (int) $scope(FortuneWebFunnelEvent::LINK_OPENED)->distinct()->count('thaiprompt_user_id');
        $newAccounts = (int) $scope(FortuneWebFunnelEvent::LINK_OPENED)->where('source', 'new_account')->distinct()->count('thaiprompt_user_id');
        $chatted = (int) $scope(FortuneWebFunnelEvent::CHATTED)->distinct()->count('thaiprompt_user_id');
        $paidPeople = (int) $scope(FortuneWebFunnelEvent::PAID)->distinct()->count('thaiprompt_user_id');

        $out['funnel'] = [
            ['key' => 'box', 'label' => 'ได้กล่องลิงก์เข้าห้องแม่หมอ', 'hint' => 'คนในแชทเพจที่บอทส่งกล่องลิงก์ให้', 'people' => $gotBox],
            ['key' => 'pressed', 'label' => 'ในนั้น กดปุ่มขอเข้าห้องเอง', 'hint' => 'กดปุ่ม 🌙 เข้าห้องแม่หมอ / เมนู / ปุ่มในข้อความชวน', 'people' => $pressed, 'sub' => true],
            ['key' => 'opened', 'label' => 'แตะลิงก์ เข้าเว็บได้', 'hint' => 'ระบบล็อกอินให้แล้วส่งไปเว็บจันทรา · บัญชีใหม่ '.$newAccounts.' คน', 'people' => $opened],
            ['key' => 'chatted', 'label' => 'คุยกับแม่หมอบนเว็บ', 'hint' => 'พิมพ์อย่างน้อย 1 ข้อความในห้องแชท', 'people' => $chatted],
            ['key' => 'paid', 'label' => 'ซื้อคำทำนาย', 'hint' => 'บิลที่จ่ายแล้ว (เว็บส่งมาหลังพ้นช่วงคืนเงิน)', 'people' => $paidPeople],
        ];

        $out['totals']['boxes'] = (int) $scope(FortuneWebFunnelEvent::BOX_SENT)->count();
        $out['totals']['bills'] = (int) $scope(FortuneWebFunnelEvent::PAID)->count();
        $out['totals']['paid_amount'] = (float) $scope(FortuneWebFunnelEvent::PAID)->sum('amount');
        $out['totals']['dead'] = (int) $scope(FortuneWebFunnelEvent::LINK_DEAD)->count();

        // ── รายวัน ──
        $rows = FortuneWebFunnelEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($pageId !== null, fn (Builder $q) => $q->where('fortune_page_id', $pageId))
            ->selectRaw('DATE(created_at) as d, event, COUNT(*) as n, COUNT(DISTINCT platform_user_id) as psids, COUNT(DISTINCT thaiprompt_user_id) as users, COUNT(DISTINCT CASE WHEN source LIKE ? THEN platform_user_id END) as pressed, SUM(amount) as amount', ['button:%'])
            ->groupBy('d', 'event')
            ->get();

        $daily = [];
        for ($day = $to->startOfDay(); $day->gte($from); $day = $day->subDay()) {
            $daily[$day->toDateString()] = ['date' => $day->toDateString(), 'boxes' => 0, 'box_people' => 0, 'pressed' => 0, 'opened' => 0, 'dead' => 0, 'chatted' => 0, 'paid' => 0, 'amount' => 0.0];
        }
        foreach ($rows as $r) {
            $d = (string) $r->d;
            if (! isset($daily[$d])) {
                continue;
            }
            match ((string) $r->event) {
                FortuneWebFunnelEvent::BOX_SENT => $daily[$d] = array_merge($daily[$d], ['boxes' => (int) $r->n, 'box_people' => (int) $r->psids, 'pressed' => (int) $r->pressed]),
                FortuneWebFunnelEvent::LINK_OPENED => $daily[$d]['opened'] = (int) $r->users,
                FortuneWebFunnelEvent::LINK_DEAD => $daily[$d]['dead'] = (int) $r->n,
                FortuneWebFunnelEvent::CHATTED => $daily[$d]['chatted'] = (int) $r->users,
                FortuneWebFunnelEvent::PAID => $daily[$d] = array_merge($daily[$d], ['paid' => (int) $r->users, 'amount' => (float) $r->amount]),
                default => null,
            };
        }
        $out['daily'] = array_values($daily);

        // ── กล่องมาจากจุดไหน + จุดไหนพาคนเข้าเว็บได้จริง ──
        $openedPsids = array_flip($scope(FortuneWebFunnelEvent::LINK_OPENED)->distinct()->pluck('platform_user_id')->filter()->all());
        $bySource = [];
        foreach ($scope(FortuneWebFunnelEvent::BOX_SENT)->get(['platform_user_id', 'source']) as $row) {
            $src = (string) ($row->source ?? '');
            $bySource[$src] ??= ['source' => $src, 'label' => self::sourceLabel($src), 'boxes' => 0, 'people' => [], 'opened' => []];
            $bySource[$src]['boxes']++;
            $psid = (string) $row->platform_user_id;
            $bySource[$src]['people'][$psid] = true;
            if (isset($openedPsids[$psid])) {
                $bySource[$src]['opened'][$psid] = true;
            }
        }
        $sources = array_map(fn ($s) => [
            'source' => $s['source'],
            'label' => $s['label'],
            'boxes' => $s['boxes'],
            'people' => count($s['people']),
            'opened' => count($s['opened']),
        ], array_values($bySource));
        usort($sources, fn ($a, $b) => [$b['people'], $b['boxes']] <=> [$a['people'], $a['boxes']]);
        $out['sources'] = $sources;

        // ── ลิงก์ที่ใช้ไม่ได้ แยกเหตุผล ──
        $out['dead'] = $scope(FortuneWebFunnelEvent::LINK_DEAD)
            ->selectRaw('source, COUNT(*) as n')
            ->groupBy('source')
            ->orderByDesc('n')
            ->get()
            ->map(fn ($r) => ['reason' => (string) $r->source, 'label' => self::DEAD_LABELS[(string) $r->source] ?? (string) $r->source, 'count' => (int) $r->n])
            ->all();

        return $out;
    }

    /**
     * ชื่อไทยของ source (รู้จักขึ้นต้น button:/gesture_/referral: ด้วย)
     */
    public static function sourceLabel(string $source): string
    {
        if (isset(self::SOURCE_LABELS[$source])) {
            return self::SOURCE_LABELS[$source];
        }
        if (str_starts_with($source, 'referral:')) {
            return 'มาจากลิงก์แนะนำ (m.me ref)';
        }
        if (str_starts_with($source, 'button:')) {
            return 'กดปุ่ม '.substr($source, 7);
        }
        if (str_starts_with($source, 'gesture_')) {
            return 'ส่ง '.substr($source, 8);
        }

        return $source !== '' ? $source : '(ไม่ระบุ)';
    }
}
