<?php

namespace App\Services\Eve;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * 👤 สมองฝั่ง "ข้อมูลของฉัน" ของน้อง Eve — ตอบเรื่องบัญชีของคนที่กำลังคุยอยู่ได้จริง
 *
 * เดิม Eve ตอบไม่ได้เลย ("ขออภัยค่ะ ไม่สามารถตรวจสอบข้อมูลส่วนตัวได้") เพราะไม่มีข้อมูลจริง
 * ในมือ — ตัวนี้ดึงตัวเลขจริงจาก DB มาให้ AI แค่ "เรียบเรียง" (หลักเดียวกับ EveAdminBrain)
 *
 * 🔒 กฎเหล็กด้านความปลอดภัย (ห้ามฝ่าฝืนเด็ดขาด):
 *   1. ทุก query scope ด้วย user id ที่มาจาก Auth เท่านั้น — ห้ามอ่าน id จากข้อความลูกค้า
 *      (ไม่งั้นลูกค้าพิมพ์ "ขอดูวอลเลตของ user 5" แล้วได้ข้อมูลคนอื่น)
 *   2. ไม่แตะฟิลด์ความลับใดๆ: pin_hash, two_factor_secret, wallet_address, password
 *   3. ดึงเฉพาะ "หัวข้อที่ลูกค้าถามถึง" เท่านั้น — ไม่ยัดข้อมูลการเงินเข้า prompt ทุกครั้ง
 *      (ข้อมูลที่ส่งเข้า AI provider = ข้อมูลที่หลุดออกนอกระบบเรา ยิ่งส่งน้อยยิ่งดี)
 *   4. ผู้ที่ยังไม่ล็อกอิน = ไม่มีข้อมูลใดๆ ทั้งสิ้น
 *
 * ⚠️ ระบบนี้มีโมดูลเสริมเยอะและไม่ได้ migrate ครบทุกที่ติดตั้ง → ทุก query ต้องห่อ
 *    Schema::hasTable()/hasColumn() + try/catch เสมอ ห้ามให้แชททั้งตัวพังเพราะตารางหาย
 */
class EveMemberBrain
{
    /** แคชสั้นๆ กันลูกค้าถามรัวแล้วยิง DB ซ้ำ (สั้นพอที่ตัวเลขยังสดอยู่) */
    private const TTL = 30;

    /**
     * ตารางคอมมิชชั่นที่รองรับ → [คอลัมน์จำนวนเงิน, ชื่อที่แสดงต่อลูกค้า]
     *
     * ระบบนี้มีคอมหลายสาย (ดูดวง/MLM/มาร์เก็ตเพลส) แต่ละสายคนละตาราง คนละชื่อคอลัมน์
     */
    private const COMMISSION_SOURCES = [
        'fortune_commissions' => ['amount', 'ค่าแนะนำสายดูดวง'],
        'mlm_commissions' => ['commission_amount', 'คอมมิชชั่น MLM'],
        'marketplace_commissions' => ['commission_amount', 'คอมมิชชั่นมาร์เก็ตเพลส'],
    ];

    /** สถานะรายการเงิน → คำไทยที่ลูกค้าเข้าใจ */
    private const STATUS_TH = [
        'pending' => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'paid' => 'จ่ายแล้ว',
        'completed' => 'สำเร็จ',
        'rejected' => 'ถูกปฏิเสธ',
        'cancelled' => 'ยกเลิก',
        'failed' => 'ไม่สำเร็จ',
        'processing' => 'กำลังดำเนินการ',
        'awaiting_payment' => 'รอชำระเงิน',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ส่งถึงแล้ว',
        'refunded' => 'คืนเงินแล้ว',
        'open' => 'เปิดอยู่',
        'in_progress' => 'กำลังดำเนินการ',
        'resolved' => 'แก้ไขแล้ว',
        'closed' => 'ปิดแล้ว',
    ];

    /**
     * สร้างบล็อก "ข้อมูลของคุณ" ตามเจตนาในข้อความ
     *
     * คืนสตริงว่างเมื่อ: ยังไม่ล็อกอิน / ไม่ได้ถามเรื่องบัญชีตัวเอง
     * → Eve ก็คุยปกติเหมือนเดิม ไม่มีข้อมูลการเงินหลุดเข้า prompt โดยไม่จำเป็น
     */
    public function buildBlockFor(EveActor $actor, string $message): string
    {
        $topics = $this->detectTopics($message, $actor);
        if (empty($topics)) {
            return '';
        }

        $user = $actor->user;
        if (! $user || $actor->isGuest()) {
            // ยังไม่ล็อกอิน = ไม่มีข้อมูลให้เด็ดขาด แต่ต้องบอกเหตุผลให้ถูก
            // (ไม่งั้น Eve จะตอบว่า "ดูให้ไม่ได้ค่ะ" ลอยๆ ทั้งที่แค่ยังไม่ได้เข้าสู่ระบบ)
            return '[👤 ลูกค้าถามข้อมูลบัญชีของตัวเอง แต่ "ยังไม่ได้เข้าสู่ระบบ" '
                .'→ ชวนให้เข้าสู่ระบบก่อนอย่างสุภาพ แล้วบอกว่าหลังล็อกอินจะดูให้ได้ทันที '
                .'ห้ามบอกตัวเลขใดๆ ทั้งสิ้น]';
        }

        try {
            $lines = Cache::remember(
                'eve:member:'.$user->id.':'.implode(',', $topics),
                self::TTL,
                fn () => $this->collect($user, $topics)
            );
        } catch (Throwable $e) {
            $lines = $this->collect($user, $topics);   // แคชล่มก็ยังต้องตอบได้
        }

        if (empty($lines)) {
            return '';
        }

        array_unshift($lines, '[👤 ข้อมูลบัญชีของลูกค้าคนที่กำลังคุยอยู่ตอนนี้ — ดึงจากระบบจริง ใช้ตอบได้เลย]');
        $lines[] = '⚠️ ตัวเลขทุกตัวข้างบนเป็นของลูกค้าคนนี้เอง (ระบบยืนยันตัวตนแล้ว) ตอบให้เขาได้ทันที '
            .'แต่ห้ามคิดเลข/เดาตัวเลขเองเด็ดขาด — ถ้าเขาถามอย่างอื่นที่ไม่มีในรายการนี้ '
            .'ให้บอกตรงๆ ว่ายังดูให้ไม่ได้ แล้วชี้หน้าที่เขาไปดูเองได้';

        return implode("\n", $lines);
    }

    /**
     * ตรวจว่าลูกค้าถามถึง "เรื่องของตัวเอง" เรื่องไหนบ้าง
     *
     * @return array<int,string>
     */
    public function detectTopics(string $message, EveActor $actor): array
    {
        $m = trim($message);
        if ($m === '') {
            return [];
        }

        $topics = [];

        // ขอสรุปรวม → ให้ครบทุกเรื่องรวดเดียว
        if (preg_match('/(สรุป(ให้|บัญชี|ยอด)|ข้อมูลของ(ฉัน|ผม|หนู|เรา)|ภาพรวมของ(ฉัน|ผม)|บัญชีของ(ฉัน|ผม)|สถานะของ(ฉัน|ผม)|ของฉันเป็นไง|ฉันมีอะไรบ้าง)/u', $m)) {
            $topics = ['wallet', 'commission', 'order', 'rank'];
        }

        if (preg_match('/(กระเป๋าเงิน|วอลเลต|วอลเล็ต|wallet|ยอดเงิน|เงินคงเหลือ|ยอดคงเหลือ|เครดิตคงเหลือ|เงินในระบบ|ถอนเงิน|เติมเงิน|โอนเงิน|เงินเข้า|เงินออก)/iu', $m)) {
            $topics[] = 'wallet';
        }
        if (preg_match('/(ค่าคอม|คอมมิ|commission|ค่าแนะนำ|ส่วนแบ่ง|รายได้|ค่าตอบแทน|เงินปันผล|ทีมงานของฉัน|ดาวน์ไลน์)/iu', $m)) {
            $topics[] = 'commission';
        }
        if (preg_match('/(ออเดอร์|คำสั่งซื้อ|ที่สั่งไป|ของที่สั่ง|พัสดุ|เลขติดตาม|เลขพัสดุ|ส่งของ(ถึงไหน|ยัง)|ของถึงไหน|สถานะการสั่งซื้อ|ซื้อไปแล้ว)/u', $m)) {
            $topics[] = 'order';
        }
        if (preg_match('/(แรงค์|แรงก์|rank|ระดับของ|ยศ|เลเวล|ขั้นของ|แต้ม|คะแนนสะสม|อัพระดับ)/iu', $m)) {
            $topics[] = 'rank';
        }
        if (preg_match('/(ทิกเก็ต|ticket|เรื่องที่แจ้ง|แจ้งปัญหา|เคสของ|ร้องเรียน|ที่เคยแจ้ง)/iu', $m)) {
            $topics[] = 'ticket';
        }

        // เรื่องร้านค้า — เฉพาะผู้ขายเท่านั้น
        if ($actor->tier === EveActor::TIER_SELLER
            && preg_match('/(ร้านของ|ร้านฉัน|ยอดขาย|ขายได้|สินค้าของ(ฉัน|ผม|ร้าน)|ออเดอร์ร้าน|ลูกค้าสั่ง|สต็อก|ของหมด|สรุปร้าน|ร้านเป็นไง)/u', $m)) {
            $topics[] = 'store';
        }

        return array_values(array_unique($topics));
    }

    /**
     * ดึงข้อมูลตามหัวข้อที่ขอ
     *
     * @param  array<int,string>  $topics
     * @return array<int,string>
     */
    private function collect(User $user, array $topics): array
    {
        $lines = [];

        foreach ($topics as $topic) {
            try {
                $lines = array_merge($lines, match ($topic) {
                    'wallet' => $this->walletLines($user->id),
                    'commission' => $this->commissionLines($user->id),
                    'order' => $this->orderLines($user->id),
                    'rank' => $this->rankLines($user),
                    'ticket' => $this->ticketLines($user->id),
                    'store' => $this->storeLines($user->id),
                    default => [],
                });
            } catch (Throwable $e) {
                // หัวข้อเดียวพังต้องไม่ทำให้หัวข้ออื่นหาย
                continue;
            }
        }

        return $lines;
    }

    /**
     * 💰 กระเป๋าเงิน + รายการล่าสุด
     *
     * @return array<int,string>
     */
    private function walletLines(int $userId): array
    {
        if (! Schema::hasTable('wallets')) {
            return ['💰 กระเป๋าเงิน: ระบบกระเป๋าเงินยังไม่เปิดใช้งานในเว็บนี้'];
        }

        // ⚠️ เลือกเฉพาะคอลัมน์ที่ต้องใช้ — ห้ามดึงทั้งแถว เพราะตารางนี้มี pin_hash /
        //    two_factor_secret / wallet_address ปนอยู่ ซึ่งห้ามเข้าใกล้ prompt เด็ดขาด
        $w = DB::table('wallets')
            ->where('user_id', $userId)
            ->when(Schema::hasColumn('wallets', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->first(['balance', 'currency', 'status', 'total_income', 'total_expense', 'last_transaction_at']);

        if (! $w) {
            return ['💰 กระเป๋าเงิน: ลูกค้ายังไม่มีกระเป๋าเงินในระบบ (เปิดใช้ได้ที่หน้ากระเป๋าเงิน)'];
        }

        $cur = $w->currency ?: 'THB';
        $lines = [
            sprintf(
                '💰 กระเป๋าเงิน: คงเหลือ %s %s · รายรับสะสม %s · รายจ่ายสะสม %s · สถานะ %s',
                number_format((float) $w->balance, 2),
                $cur,
                number_format((float) ($w->total_income ?? 0), 2),
                number_format((float) ($w->total_expense ?? 0), 2),
                $this->th((string) ($w->status ?? 'active'))
            ),
        ];

        if (Schema::hasTable('wallet_transactions')) {
            $tx = DB::table('wallet_transactions')
                ->where('user_id', $userId)
                ->when(Schema::hasColumn('wallet_transactions', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['type', 'amount', 'status', 'created_at']);

            foreach ($tx as $t) {
                $lines[] = sprintf(
                    '   • %s %s บาท (%s) เมื่อ %s',
                    $this->th((string) $t->type),
                    number_format((float) $t->amount, 2),
                    $this->th((string) $t->status),
                    $this->thaiDate($t->created_at)
                );
            }

            if ($tx->isEmpty()) {
                $lines[] = '   • ยังไม่มีรายการเคลื่อนไหวในกระเป๋าเงิน';
            }
        }

        return $lines;
    }

    /**
     * 🤝 คอมมิชชั่นทุกสาย (ดูดวง / MLM / มาร์เก็ตเพลส)
     *
     * @return array<int,string>
     */
    private function commissionLines(int $userId): array
    {
        $lines = [];
        $grandTotal = 0.0;
        $found = false;

        foreach (self::COMMISSION_SOURCES as $table => [$amountCol, $label]) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'user_id')
                || ! Schema::hasColumn($table, $amountCol)) {
                continue;
            }

            $rows = DB::table($table)
                ->where('user_id', $userId)
                ->when(Schema::hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->select('status', DB::raw('COUNT(*) AS c'), DB::raw("SUM({$amountCol}) AS s"))
                ->groupBy('status')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $found = true;
            $parts = [];
            foreach ($rows as $r) {
                $parts[] = sprintf('%s %d รายการ %s บาท', $this->th((string) $r->status), (int) $r->c, number_format((float) $r->s, 2));
                // นับเฉพาะเงินที่ได้จริง — รออนุมัติ/ถูกปฏิเสธ ยังไม่ใช่เงินของเขา
                if (in_array($r->status, ['paid', 'approved', 'completed'], true)) {
                    $grandTotal += (float) $r->s;
                }
            }

            // เดือนนี้ได้เท่าไหร่
            $thisMonth = DB::table($table)
                ->where('user_id', $userId)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->whereIn('status', ['paid', 'approved', 'completed'])
                ->sum($amountCol);

            $lines[] = sprintf(
                '🤝 %s: %s · เดือนนี้ได้ %s บาท',
                $label,
                implode(' · ', $parts),
                number_format((float) $thisMonth, 2)
            );
        }

        if (! $found) {
            return ['🤝 คอมมิชชั่น: ยังไม่มีรายการคอมมิชชั่นในบัญชีนี้เลย'];
        }

        $lines[] = '🤝 รวมคอมมิชชั่นที่ได้รับจริงทุกสาย: '.number_format($grandTotal, 2).' บาท';

        return $lines;
    }

    /**
     * 🛒 คำสั่งซื้อของลูกค้า
     *
     * @return array<int,string>
     */
    private function orderLines(int $userId): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        $base = fn () => DB::table('orders')
            ->where('user_id', $userId)
            ->when(Schema::hasColumn('orders', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));

        $total = (clone $base())->count();
        if ($total === 0) {
            return ['🛒 คำสั่งซื้อ: ลูกค้ายังไม่เคยสั่งซื้อในเว็บนี้'];
        }

        $byStatus = $base()
            ->select('status', DB::raw('COUNT(*) AS c'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => $this->th((string) $r->status).' '.(int) $r->c)
            ->implode(' · ');

        $lines = ["🛒 คำสั่งซื้อทั้งหมด {$total} รายการ ({$byStatus})"];

        $recent = $base()->orderByDesc('created_at')->limit(3)->get([
            'order_number', 'status', 'total_amount', 'tracking_number', 'created_at',
        ]);

        foreach ($recent as $o) {
            $track = trim((string) ($o->tracking_number ?? ''));
            $lines[] = sprintf(
                '   • %s — %s %s บาท (%s)%s',
                $this->safeText($o->order_number, 30) ?: 'ไม่มีเลขที่',
                $this->th((string) $o->status),
                number_format((float) $o->total_amount, 2),
                $this->thaiDate($o->created_at),
                $track !== '' ? ' เลขพัสดุ '.$this->safeText($track, 40) : ''
            );
        }

        return $lines;
    }

    /**
     * 🏅 ระดับ/แรงค์ + แต้ม + ต้องอีกเท่าไหร่ถึงระดับถัดไป
     *
     * @return array<int,string>
     */
    private function rankLines(User $user): array
    {
        if (! Schema::hasTable('ranks') || ! Schema::hasColumn('users', 'current_rank_id')) {
            return [];
        }

        $points = (float) ($user->rank_points ?? 0);
        $rank = $user->current_rank_id
            ? DB::table('ranks')->where('id', $user->current_rank_id)->first(['name', 'name_th', 'level', 'min_points'])
            : null;

        if (! $rank) {
            return ['🏅 ระดับสมาชิก: ยังไม่ได้จัดระดับ (แต้มสะสม '.number_format($points).' แต้ม)'];
        }

        $name = $rank->name_th ?: $rank->name;
        $lines = ["🏅 ระดับปัจจุบัน: {$name} (ระดับที่ {$rank->level}) · แต้มสะสม ".number_format($points).' แต้ม'];

        // ระดับถัดไป — บอกว่าอีกกี่แต้ม (แรงจูงใจจริง ไม่ใช่คำพูดลอยๆ)
        $next = DB::table('ranks')
            ->where('level', '>', $rank->level)
            ->when(Schema::hasColumn('ranks', 'is_active'), fn ($q) => $q->where('is_active', 1))
            ->orderBy('level')
            ->first(['name', 'name_th', 'min_points']);

        if ($next) {
            $needed = max(0, (float) $next->min_points - $points);
            $nextName = $next->name_th ?: $next->name;
            $lines[] = $needed > 0
                ? "   • ระดับถัดไปคือ {$nextName} — ต้องการอีก ".number_format($needed).' แต้ม'
                : "   • แต้มถึงเกณฑ์ {$nextName} แล้ว รอระบบอัปเดตระดับ";
        }

        return $lines;
    }

    /**
     * 🎫 ทิกเก็ตที่ลูกค้าเปิดไว้
     *
     * @return array<int,string>
     */
    private function ticketLines(int $userId): array
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'user_id')) {
            return [];
        }

        $open = DB::table('tickets')
            ->where('user_id', $userId)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();

        $latest = DB::table('tickets')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first(['ticket_number', 'subject', 'status', 'created_at']);

        if (! $latest) {
            return ['🎫 ทิกเก็ต: ลูกค้ายังไม่เคยแจ้งเรื่องเข้ามา'];
        }

        return [
            "🎫 ทิกเก็ตที่ยังไม่ปิด {$open} ใบ",
            sprintf(
                '   • ล่าสุด %s "%s" — %s (%s)',
                $this->safeText($latest->ticket_number, 30),
                $this->safeText($latest->subject),
                $this->th((string) $latest->status),
                $this->thaiDate($latest->created_at)
            ),
        ];
    }

    /**
     * 🏪 สรุปร้านค้า (เฉพาะผู้ขาย) — ยอดขาย/ออเดอร์/สินค้า/ของใกล้หมด
     *
     * @return array<int,string>
     */
    private function storeLines(int $userId): array
    {
        $lines = [];

        $store = Schema::hasTable('vendor_stores')
            ? DB::table('vendor_stores')->where('user_id', $userId)->first(['id', 'store_name'])
            : null;

        if ($store) {
            $lines[] = '🏪 ร้าน: '.($this->safeText($store->store_name, 60) ?: 'ยังไม่ได้ตั้งชื่อร้าน');
        }

        // ── สินค้าในร้าน ──
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'seller_id')) {
            $p = fn () => DB::table('products')
                ->where('seller_id', $userId)
                ->when(Schema::hasColumn('products', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));

            $live = (clone $p())->where('is_active', 1)->count();
            $all = (clone $p())->count();
            $lines[] = "📦 สินค้าในร้าน: ทั้งหมด {$all} รายการ · เปิดขายอยู่ {$live} รายการ";

            if (Schema::hasColumn('products', 'stock_quantity')) {
                $low = (clone $p())->where('is_active', 1)->where('stock_quantity', '<=', 5)->count();
                if ($low > 0) {
                    $lines[] = "   • ⚠️ สินค้าที่สต็อกเหลือน้อย (≤5 ชิ้น): {$low} รายการ";
                }
            }
        }

        // ── ออเดอร์ของร้าน ──
        if ($store && Schema::hasTable('orders') && Schema::hasColumn('orders', 'store_id')) {
            $o = fn () => DB::table('orders')
                ->where('store_id', $store->id)
                ->when(Schema::hasColumn('orders', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));

            $pending = (clone $o())->whereIn('status', ['pending', 'awaiting_payment', 'processing'])->count();
            $today = (clone $o())->whereDate('created_at', today())->count();

            // ยอดขายนับเฉพาะออเดอร์ที่ยังไม่ถูกยกเลิก/คืนเงิน
            $paidStatuses = ['paid', 'completed', 'processing', 'shipped', 'delivered'];
            $revenueMonth = (clone $o())
                ->whereIn('status', $paidStatuses)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount');

            $lines[] = sprintf(
                '🧾 ออเดอร์ร้าน: วันนี้ %d · รอดำเนินการ %d · ยอดขายเดือนนี้ %s บาท',
                $today,
                $pending,
                number_format((float) $revenueMonth, 2)
            );
        }

        return $lines ?: ['🏪 ร้านค้า: ยังไม่พบข้อมูลร้านของบัญชีนี้'];
    }

    /**
     * 🔒 ล้างข้อความที่ "ผู้ใช้พิมพ์เอง" ก่อนยัดเข้า prompt (หัวข้อทิกเก็ต / ชื่อร้าน)
     *
     * ตัวเลขจาก DB ปลอดภัยอยู่แล้ว แต่ข้อความที่ผู้ใช้เขียนเองไม่ใช่ —
     * ถ้าเขาตั้งหัวข้อทิกเก็ตเป็นคำสั่งซ้อน (เช่นขึ้นบรรทัดใหม่แล้วสั่งงาน Eve)
     * ข้อความนั้นจะกลายเป็นส่วนหนึ่งของ prompt ทันที จึงต้องยุบบรรทัด + ตัดวงเล็บก้ามปู
     * ที่เราใช้เป็นตัวคั่นบล็อกภายในทิ้ง
     */
    private function safeText(?string $text, int $limit = 60): string
    {
        $t = (string) $text;
        $t = preg_replace('/[\r\n\t]+/u', ' ', $t) ?? $t;
        $t = str_replace(['[', ']'], ['(', ')'], $t);
        $t = trim(preg_replace('/\s{2,}/u', ' ', $t) ?? $t);

        return mb_substr($t, 0, $limit);
    }

    /** แปลงสถานะอังกฤษเป็นไทย (ไม่รู้จักก็คืนของเดิม ดีกว่าซ่อนข้อมูล) */
    private function th(string $key): string
    {
        return self::STATUS_TH[$key] ?? $key;
    }

    /** วันที่แบบสั้นอ่านง่าย — รับได้ทั้ง string จาก DB::table และ Carbon */
    private function thaiDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }
}
