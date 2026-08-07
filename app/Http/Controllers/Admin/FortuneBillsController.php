<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * 🧾 ศูนย์รวมบิลดูดวง (2026-08-07)
 *
 * ยุบ 3 หน้าที่ทับซ้อนกันมาไว้ที่เดียว:
 *   - admin/fortune/billing      (รายได้ + บิลลอย + Stripe)
 *   - admin/fortune/readings     (ประวัติคำทำนาย + ค้นหา + แก้ไข)
 *   - admin/fortune/celtic-cross (รายการบิล Celtic + ปุ่มจัดการเฉพาะทาง)
 *
 * เจ้าของสั่ง: "มันคือการจัดการบิลดูดวงทั้งคู่ แค่คนละแพคเกจ แยกกันแล้วมันงง จัดการยาก
 *              และต้องรู้ว่ามาจากแพลตฟอร์มไหนด้วย"
 *
 * ⚠️ หลักการที่ห้ามพัง:
 *   - ไม่แตะ endpoint จัดการเดิมเลยสักตัว — หน้านี้แค่ยิงไปที่ route เดิม
 *     (billing.* / celtic-cross.* / readings.*) ของเดิมจึงทำงานต่อได้ทันที
 *   - นิยาม "รอชำระ / ยกเลิก" ใช้ของกลางจากโมเดล ห้ามตั้งลิสต์ใหม่
 *     ([[rule_fortune_cancelled_bills_are_completed_status]])
 */
class FortuneBillsController extends Controller
{
    /** แพคเกจที่เลือกกรองได้ — key => [label, reading_type] */
    public const PACKAGES = [
        'deep' => ['ดูพื้นดวง 39฿', FortuneReading::READING_TYPE_DEEP],
        'celtic' => ['Celtic 99฿', FortuneReading::READING_TYPE_CELTIC_CROSS],
        'free_card' => ['ไพ่ฟรี', 'free_card'],
        'basic' => ['พื้นฐาน', 'basic'],
    ];

    /** บิลรอชำระที่ยัง "ลุ้นได้เงิน" = สร้างมาไม่เกินกี่วัน (เกินนี้ถือเป็นซากบิล) */
    public const PENDING_FRESH_DAYS = 7;

    /**
     * ช่องทางที่ลูกค้าเข้ามา — [ชื่อ, สี, คลาสไอคอนเต็ม]
     *
     * ⚠️ ต้องเก็บ prefix มาด้วย: โลโก้แบรนด์อยู่ในชุด `fab` (Font Awesome Brands)
     *    ไม่ใช่ `fas` (Solid) — ถ้าใส่ `fas fa-facebook-f` จะไม่มี glyph
     *    เบราว์เซอร์เลยวาดเป็นสี่เหลี่ยม/กากบาทแทน
     */
    public const PLATFORMS = [
        'facebook' => ['Facebook', '#1877f2', 'fab fa-facebook-f'],
        'line' => ['LINE', '#06c755', 'fab fa-line'],
        'other' => ['อื่น ๆ / เว็บ', '#8c8c96', 'fas fa-globe'],
    ];

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'package' => (string) $request->input('package', ''),
            'platform' => (string) $request->input('platform', ''),
            'status' => (string) $request->input('status', ''),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'ai_provider' => (string) $request->input('ai_provider', ''),
            'category' => (string) $request->input('category', ''),
        ];

        $bills = $this->applyFilters(FortuneReading::query()->with('user'), $filters)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.fortune.bills.index', [
            'bills' => $bills,
            'stats' => $this->buildStats($filters),
            'filters' => $filters,
            'packages' => self::PACKAGES,
            'platforms' => self::PLATFORMS,
            // รายชื่อ AI provider ที่มีจริงในข้อมูล — ไม่ hardcode เพราะเพิ่ม/เปลี่ยนได้ตลอด
            'aiProviders' => FortuneReading::query()
                ->whereNotNull('ai_provider')
                ->where('ai_provider', '!=', '')
                ->distinct()
                ->orderBy('ai_provider')
                ->pluck('ai_provider'),
            'pageTitle' => 'ศูนย์รวมบิลดูดวง',
        ]);
    }

    /**
     * 📤 Export CSV — ใช้ตัวกรองชุดเดียวกับหน้าจอเป๊ะ
     *
     * ⚠️ จงใจไม่ยืม readings.export เดิม — ตัวนั้นรู้จักแค่
     *    ai_provider / is_paid / reading_type / date_from / date_to
     *    ถ้าส่ง package/platform/status/search ไปมันจะ **เงียบ ๆ ไม่กรองให้**
     *    แล้วแอดมินได้ไฟล์ที่ไม่ตรงกับที่เห็นบนจอโดยไม่มีอะไรเตือน
     */
    public function export(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'package' => (string) $request->input('package', ''),
            'platform' => (string) $request->input('platform', ''),
            'status' => (string) $request->input('status', ''),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'ai_provider' => (string) $request->input('ai_provider', ''),
            'category' => (string) $request->input('category', ''),
        ];

        $filename = 'fortune_bills_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            // BOM — ไม่มีตัวนี้ Excel เปิดภาษาไทยเป็นขยะ
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'ID', 'เลขบิล', 'วันที่สร้าง', 'ช่องทาง', 'ลูกค้า', 'Platform User ID',
                'แพคเกจ', 'สถานะ', 'ชำระแล้ว', 'ยอดบิล', 'ยอดที่ได้รับจริง', 'วันที่ชำระ',
            ]);

            // chunk — ตารางนี้มีหมื่นกว่าแถวและ conversation_state เป็น JSON ก้อนใหญ่
            $this->applyFilters(FortuneReading::query(), $filters)
                ->orderByDesc('created_at')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->id,
                            $r->bill_reference ?? '',
                            $r->created_at?->format('Y-m-d H:i'),
                            $r->platform ?? 'other',
                            $r->facebook_user_name ?? '',
                            $r->platform_user_id ?? $r->facebook_user_id ?? '',
                            $r->getReadingTypeLabel(),
                            $r->conversation_status,
                            $r->is_paid ? 'จ่ายแล้ว' : ($r->isCancelled() ? 'ยกเลิก' : 'ยังไม่จ่าย'),
                            (float) $r->amount_paid,
                            $r->amount_received !== null ? (float) $r->amount_received : '',
                            $r->paid_at?->format('Y-m-d H:i') ?? '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * ใส่เงื่อนไขกรองทั้งหมดลง query
     *
     * @param  array<string, mixed>  $filters
     * @param  bool  $withStatus  false = ข้ามตัวกรองสถานะ (ใช้ตอนคำนวณ KPI ให้เป็น "ขอบเขต")
     */
    protected function applyFilters(Builder $query, array $filters, bool $withStatus = true): Builder
    {
        // 🔍 ค้นหา — รวมทุกช่องที่ 3 หน้าเดิมเคยค้นได้ (ชื่อ / เลขบิล / PSID / LINE id / #id)
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'LIKE', "%{$search}%")
                    ->orWhere('bill_reference', 'LIKE', "%{$search}%")
                    ->orWhere('facebook_user_id', 'LIKE', "%{$search}%")
                    ->orWhere('platform_user_id', 'LIKE', "%{$search}%")
                    ->orWhere('id', ltrim($search, '#'));
            });
        }

        // 📦 แพคเกจ
        if (isset(self::PACKAGES[$filters['package']])) {
            $query->where('reading_type', self::PACKAGES[$filters['package']][1]);
        }

        // 📱 แพลตฟอร์ม — 'other' = ไม่ใช่ facebook/line (รวม null)
        if ($filters['platform'] === 'other') {
            $query->where(function ($q) {
                $q->whereNull('platform')->orWhereNotIn('platform', ['facebook', 'line']);
            });
        } elseif (in_array($filters['platform'], ['facebook', 'line'], true)) {
            $query->where('platform', $filters['platform']);
        }

        // 🤖 AI provider + หมวดคำทำนาย — ยกมาจากหน้า readings เดิม (ห้ามให้ความสามารถหาย)
        if (! empty($filters['ai_provider'])) {
            $query->where('ai_provider', $filters['ai_provider']);
        }
        if (! empty($filters['category'])) {
            $query->whereJsonContains('categories', $filters['category']);
        }

        // 📅 ช่วงวันที่ — ไม่มี default (ว่าง = ทุกช่วงเวลา ตามที่แก้ไว้ตอน audit 2026-07-05)
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($withStatus) {
            $this->applyStatusFilter($query, $filters['status']);
        }

        return $query;
    }

    /**
     * ตัวกรองสถานะ — รวมของทั้ง 3 หน้าเดิมไว้ที่เดียว
     */
    protected function applyStatusFilter(Builder $query, string $status): void
    {
        switch ($status) {
            case 'paid':
                // 🐛 (2026-08-07) เดิมเขียน where('is_floating', false) → **คืน 0 แถวเสมอ**
                //   prod: is_floating เป็น NULL ทั้ง 10,853 แถว (ไม่มี 0/1 เลยสักแถว)
                //   MySQL: NULL != 0 → เงื่อนไขนี้ตัดทุกแถวทิ้ง แอดมินกด "จ่ายแล้ว" แล้วไม่เจออะไร
                //   (ยกมาจาก FortuneBillingController เดิมซึ่งมีบั๊กเดียวกันแฝงอยู่)
                //   ต้องเทียบแบบ "ไม่ใช่ floating" = NULL หรือ false
                $query->where('is_paid', true)
                    ->where(function ($q) {
                        $q->whereNull('is_floating')->orWhere('is_floating', false);
                    });
                break;

            case 'pending':
                // 💤 รอชำระทั้งหมด (ครอบ awaiting_payment_method ที่เป็นก้อนใหญ่สุด)
                $query->where('is_paid', false)
                    ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES);
                break;

            case 'pending_fresh':
                // ⏱️ รอชำระที่ยังลุ้นได้เงินจริง — นี่คือกองที่ควรตาม
                $query->where('is_paid', false)
                    ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)
                    ->where('created_at', '>=', now()->subDays(self::PENDING_FRESH_DAYS));
                break;

            case 'pending_stale':
                // 🪦 ซากบิล — ค้างในสถานะรอจ่ายนานเกิน 7 วัน ไม่มีใครมาจ่ายแล้ว
                $query->where('is_paid', false)
                    ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)
                    ->where('created_at', '<', now()->subDays(self::PENDING_FRESH_DAYS));
                break;

            case 'cancelled':
                // ❌ ล้อ FortuneReading::isCancelled() เป๊ะ — บิลยกเลิกเก็บเป็น completed ไม่ใช่ status='cancelled'
                $query->where('is_paid', false)
                    ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->whereNotNull('conversation_state->cancellation_reason');
                break;

            case 'abandoned':
                // 🕳️ ปิดเงียบ = **เคยออกบิลจริง** (มียอดเงิน) แต่ไม่ได้จ่าย และไม่มีเหตุผลยกเลิก
                //   ⚠️ ต้องมี amount_paid > 0 ไม่งั้นเหมารวมผิดมหาศาล:
                //      prod มี completed+ไม่จ่าย+ไม่มีเหตุผล 8,258 ใบ แต่ **มียอดบิลจริงแค่ 309 ใบ**
                //      ที่เหลือ 7,949 คือคนเข้ามาคุยแล้วหายไปตั้งแต่ก่อนออกบิล = ไม่ใช่บิลที่เสียไป
                //      ถ้านับรวมกัน แอดมินจะเห็น "บิลหลุด" เกินจริง ~26 เท่า
                $query->where('is_paid', false)
                    ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->whereNull('conversation_state->cancellation_reason')
                    ->where('amount_paid', '>', 0);
                break;

            case 'no_bill':
                // 💬 คุยแล้วหายไปก่อนออกบิล — ไม่เคยมียอดเงิน ไม่ถือเป็นบิลที่เสียไป
                $query->where('is_paid', false)
                    ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->whereNull('conversation_state->cancellation_reason')
                    ->where(function ($q) {
                        $q->whereNull('amount_paid')->orWhere('amount_paid', '<=', 0);
                    });
                break;

            case 'floating':
                $query->where('is_floating', true);
                break;

            case 'free':
                $query->where('is_paid', false)->whereIn('reading_type', ['basic', 'free_card']);
                break;

            case 'stuck_deep':
                // 🧊 Deep จ่ายแล้วแต่ AI ไม่ออกคำทำนาย
                $query->where('is_paid', true)
                    ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
                    ->whereNull('deep_response');
                break;

            case 'stuck_celtic':
                // 🧊 Celtic จ่ายแล้วแต่เปิดไพ่ไม่ครบ 10 ใบ
                //   จำนวนไพ่อยู่ใน conversation_state (JSON) นับด้วย SQL ตรง ๆ ไม่ได้
                //   ⚠️ ต้อง chunk + select 2 คอลัมน์ — conversation_state เป็นก้อนใหญ่
                //      โหลดรวดเดียวชน memory_limit 128M จริง (เจอตอนทดสอบบน prod)
                $query->whereIn('id', $this->stuckCelticIds() ?: [0]);
                break;

            case 'unpaid':
                $query->where('is_paid', false);
                break;
        }
    }

    /**
     * id ของบิล Celtic ที่จ่ายแล้วแต่ไพ่ไม่ครบ 10 ใบ
     *
     * @return array<int, int>
     */
    protected function stuckCelticIds(): array
    {
        $ids = [];

        FortuneReading::where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->select(['id', 'conversation_state'])
            ->chunk(200, function ($rows) use (&$ids) {
                foreach ($rows as $r) {
                    if ($r->getCelticPickedCount() < 10) {
                        $ids[] = $r->id;
                    }
                }
            });

        return $ids;
    }

    /**
     * KPI — คำนวณจาก "ขอบเขต" (แพคเกจ/แพลตฟอร์ม/วันที่/ค้นหา) แต่ **ไม่รวมตัวกรองสถานะ**
     *
     * ทำแบบนี้เพราะ KPI ทำหน้าที่เป็นปุ่มนำทาง (กดแล้วไปกรองสถานะนั้น)
     * ถ้าเอาสถานะมาคิดด้วย ตัวเลขจะกลายเป็น "จำนวนของสิ่งที่กรองอยู่" ซึ่งซ้ำกับตาราง
     *
     * 🩹 แก้ปัญหาเดิมที่ KPI กับตารางไม่ตรงกันจนดูเหมือนบิลหาย (audit 2026-07-05 D1)
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildStats(array $filters): array
    {
        $scope = fn () => $this->applyFilters(FortuneReading::query(), $filters, withStatus: false);

        return [
            'total' => $scope()->count(),
            'paid' => $scope()->where('is_paid', true)->count(),
            'pending' => $scope()->where('is_paid', false)
                ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)
                ->count(),
            // ⏱️ (2026-08-07) แยก "ยังลุ้นได้เงินจริง" ออกจาก "ซากบิล"
            //   ตรวจ prod แล้วพบว่าบิล "รอชำระ" ทั้ง 417 ใบ **เก่ากว่า 30 วันทั้งหมด** ไม่มีสักใบในรอบเดือน
            //   (awaiting_payment_method ไม่เคยถูกเก็บกวาด) → โชว์ 417 เฉย ๆ คือตัวเลขหลอกตา
            //   แอดมินเห็นแล้วนึกว่ามีงานต้องตาม ทั้งที่ไม่มีอะไรให้ตามเลย
            'pending_fresh' => $scope()->where('is_paid', false)
                ->whereIn('conversation_status', FortuneReading::PENDING_DISPLAY_STATUSES)
                ->where('created_at', '>=', now()->subDays(self::PENDING_FRESH_DAYS))
                ->count(),
            'floating' => $scope()->where('is_floating', true)->count(),
            // 💰 เงินที่ได้รับจริง — บิลจ่ายแล้วบางใบไม่มี amount_received (ตัดผ่าน SMS/แอดมิน)
            //    ต้อง fallback เป็นยอดบิล ไม่งั้นยอดหาย
            'revenue' => (float) $scope()->where('is_paid', true)
                ->selectRaw('COALESCE(SUM(COALESCE(amount_received, amount_paid)), 0) AS s')
                ->value('s'),
        ];
    }
}
