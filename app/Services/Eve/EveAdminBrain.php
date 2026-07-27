<?php

namespace App\Services\Eve;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * 🛠️ สมองฝั่งหลังบ้านของน้อง Eve — สรุปงาน/แนวโน้ม/เรื่องด่วน ให้แอดมิน
 *
 * หลักการสำคัญ: **ห้ามให้ AI เดาตัวเลขเอง**
 *   เราคำนวณตัวเลขจริงจาก DB แล้วยัดเข้า prompt เป็นข้อความสำเร็จรูป
 *   AI มีหน้าที่แค่ "เรียบเรียง" ไม่ใช่ "คิดเลข"
 *
 * ⚠️ ระบบนี้มีโมดูลเสริมเยอะมาก (~339 models) และไม่ได้ migrate ครบทุกที่ติดตั้ง
 *    → ทุก query ต้องห่อ Schema::hasTable() + try/catch คืน 0 เสมอ ห้ามให้พังทั้งหน้า
 */
class EveAdminBrain
{
    /** แคชสั้นๆ — แอดมินถามซ้ำใน 1 นาทีไม่ต้องยิง DB ใหม่ทั้งชุด */
    private const TTL = 60;

    /**
     * รวมสัญญาณเร่งด่วน + ตัวเลขสรุป → บล็อกข้อความไทยสำหรับใส่ system prompt
     */
    public function buildBriefingBlock(): string
    {
        $d = $this->snapshot();

        $lines = ['[🛠️ ข้อมูลจริงจากระบบ ณ ตอนนี้ — ใช้ตัวเลขเหล่านี้เท่านั้น ห้ามเดาเลขเอง]'];

        // ── เรื่องด่วน ──
        $urgent = [];
        if ($d['tickets']['critical'] > 0) {
            $urgent[] = "ทิกเก็ตวิกฤต {$d['tickets']['critical']} ใบ";
        }
        if ($d['tickets']['overdue'] > 0) {
            $urgent[] = "ทิกเก็ตเลยกำหนด {$d['tickets']['overdue']} ใบ";
        }
        if ($d['tickets']['unassigned'] > 0) {
            $urgent[] = "ยังไม่มีผู้รับผิดชอบ {$d['tickets']['unassigned']} ใบ";
        }
        if ($d['withdrawals_pending'] > 0) {
            $urgent[] = "คำขอถอนเงินรออนุมัติ {$d['withdrawals_pending']} รายการ";
        }
        if ($d['kyc_pending'] > 0) {
            $urgent[] = "KYC รอตรวจ {$d['kyc_pending']} ราย";
        }
        if ($d['failed_jobs_today'] > 0) {
            $urgent[] = "งานเบื้องหลังล้มเหลววันนี้ {$d['failed_jobs_today']} งาน";
        }
        if ($d['orders']['pending'] > 0) {
            $urgent[] = "ออเดอร์รอดำเนินการ {$d['orders']['pending']} รายการ";
        }

        $lines[] = $urgent
            ? '🚨 ด่วน: '.implode(' · ', $urgent)
            : '✅ ตอนนี้ไม่มีรายการเร่งด่วนค้างอยู่';

        // ── ทิกเก็ต ──
        $lines[] = "🎫 ทิกเก็ต: เปิดอยู่ {$d['tickets']['open']} · กำลังทำ {$d['tickets']['in_progress']} · วิกฤต {$d['tickets']['critical']} · เลยกำหนด {$d['tickets']['overdue']}";

        // ── การขาย ──
        $lines[] = "🛒 ออเดอร์: วันนี้ {$d['orders']['today']} · รอดำเนินการ {$d['orders']['pending']} · ยอดขายวันนี้ ".number_format($d['orders']['revenue_today'], 2).' บาท';

        // ── สมาชิก ──
        $lines[] = "👥 สมาชิก: ทั้งหมด {$d['users']['total']} · สมัครใหม่วันนี้ {$d['users']['today']} · เดือนนี้ {$d['users']['month']}";

        // ── สินค้า ──
        $lines[] = "📦 สินค้า: เผยแพร่ {$d['products']['live']} · สินค้า affiliate {$d['products']['affiliate']}";

        // ── แนวโน้ม (ไม่ใช่พยากรณ์) ──
        if ($d['trend']['users_mom'] !== null) {
            $sign = $d['trend']['users_mom'] >= 0 ? '+' : '';
            $lines[] = "📈 แนวโน้มสมาชิกเทียบเดือนก่อน: {$sign}{$d['trend']['users_mom']}%";
        }
        if ($d['trend']['revenue_mom'] !== null) {
            $sign = $d['trend']['revenue_mom'] >= 0 ? '+' : '';
            $lines[] = "📈 แนวโน้มยอดขายเทียบเดือนก่อน: {$sign}{$d['trend']['revenue_mom']}%";
        }

        $lines[] = '⚠️ ถ้าแอดมินถามตัวเลขที่ไม่มีในรายการนี้ ให้บอกตรงๆ ว่ายังไม่มีข้อมูล และเสนอให้เปิดหน้าหลังบ้านที่เกี่ยวข้อง — ห้ามเดาเด็ดขาด';
        $lines[] = '📌 คำว่า "แนวโน้ม" = เทียบข้อมูลย้อนหลังจริง ไม่ใช่การพยากรณ์ด้วยโมเดล อย่าเรียกว่าพยากรณ์';

        return implode("\n", $lines);
    }

    /**
     * 🔍 บล็อก "ถามเจาะลึก" — ดึงข้อมูลเพิ่มเฉพาะเรื่องที่แอดมินถามถึง
     *
     * ทำไมต้องแยกจาก snapshot: snapshot ยัดเข้า prompt ทุกครั้งอยู่แล้ว ถ้าเอาทุกอย่าง
     * มายัดรวมด้วยจะทั้งช้าและเปลือง token ทั้งที่ 90% ของคำถามไม่ได้ใช้
     * ตัวนี้จึงตรวจเจตนาก่อน แล้วค่อยยิง query เฉพาะหัวข้อนั้น
     *
     * คืนสตริงว่าง = แอดมินไม่ได้ถามเจาะเรื่องไหนเป็นพิเศษ (ใช้ snapshot อย่างเดียวพอ)
     */
    public function buildDeepDiveBlock(string $message): string
    {
        $topics = $this->detectAdminTopics($message);
        if (empty($topics)) {
            return '';
        }

        $lines = [];
        foreach ($topics as $topic) {
            try {
                $lines = array_merge($lines, match ($topic) {
                    'top_products' => $this->topProducts(),
                    'new_members' => $this->newMembers(),
                    'sales_trend' => $this->salesTrend(),
                    'withdrawals' => $this->withdrawalQueue(),
                    'kyc' => $this->kycQueue(),
                    'commissions' => $this->commissionQueue(),
                    'low_stock' => $this->lowStock(),
                    'sellers' => $this->sellerSummary(),
                    default => [],
                });
            } catch (Throwable $e) {
                continue;   // หัวข้อเดียวพังต้องไม่ทำให้ทั้งบล็อกหาย
            }
        }

        if (empty($lines)) {
            return '';
        }

        array_unshift($lines, '[🔍 ข้อมูลเจาะลึกเพิ่มเติมตามที่แอดมินถาม — ตัวเลขจริงจาก DB]');

        return implode("\n", $lines);
    }

    /**
     * ตรวจว่าแอดมินถามเจาะเรื่องไหน
     *
     * @return array<int,string>
     */
    public function detectAdminTopics(string $message): array
    {
        $m = trim($message);
        if ($m === '') {
            return [];
        }

        $topics = [];
        $map = [
            'top_products' => '/(ขายดี|ขายได้ดี|สินค้าเด่น|สินค้าฮิต|ยอดนิยม|top.?(สินค้า|product)|สินค้าไหนดี|คนซื้อเยอะ|คนดูเยอะ|ยอดวิว)/iu',
            'new_members' => '/(สมาชิกใหม่|คนสมัคร|สมัครใหม่|ใครสมัคร|ยอดสมัคร|สมาชิกมาจากไหน|ผู้แนะนำ|อัพไลน์|คนชวน)/u',
            // ⚠️ ต้องผูกกับคำว่า "ยอด/ขาย/รายได้" เสมอ — ถ้าใส่ "สัปดาห์|ย้อนหลัง|กราฟ" ลอยๆ
            //    คำถามอย่าง "สัปดาห์นี้มีสมาชิกใหม่กี่คน" จะลากยอดขายมาด้วยทั้งที่ไม่ได้ถาม
            'sales_trend' => '/(ยอดขาย|ยอดสั่งซื้อ|รายได้|ขายได้เท่าไหร่|ขายดีขึ้น|ยอด.{0,8}(ย้อนหลัง|กราฟ|เทรนด์)|เทียบเดือน|เดือนนี้เทียบ)/u',
            'withdrawals' => '/(ถอนเงิน|คำขอถอน|รออนุมัติถอน|จ่ายเงินออก|โอนออก|withdraw)/iu',
            'kyc' => '/(kyc|ยืนยันตัวตน|บัตรประชาชน|ตรวจเอกสาร)/iu',
            'commissions' => '/(คอมมิ|ค่าคอม|ค่าแนะนำ|ส่วนแบ่ง|รอจ่ายคอม|จ่ายคอม)/u',
            'low_stock' => '/(สต็อก|ของหมด|ใกล้หมด|สินค้าเหลือน้อย|เติมของ|stock)/iu',
            'sellers' => '/(ผู้ขาย|ร้านค้า|ร้านใหม่|vendor|seller|แม่ค้า|พ่อค้า)/iu',
        ];

        foreach ($map as $topic => $pattern) {
            if (preg_match($pattern, $m)) {
                $topics[] = $topic;
            }
        }

        // ถามกว้างๆ ว่า "มีอะไรน่าห่วง/ต้องรีบทำ" → หยิบคิวงานค้างทั้งหมดมาให้
        // ⚠️ ห้ามใส่ "ค้างอยู่" ลอยๆ — "KYC ค้างอยู่กี่ราย" เป็นคำถามเจาะเรื่องเดียว
        //    ไม่ใช่การขอสรุปงานค้างทั้งระบบ (จะลากคิวถอนเงิน/คอมมาให้ทั้งที่ไม่ได้ถาม)
        if (preg_match('/(น่าห่วง|ต้องรีบ|คิวงาน|เร่งด่วน|อะไรบ้างที่ต้อง|งานค้าง|ค้างทั้งหมด|ต้องทำอะไร)/u', $m)) {
            $topics = array_merge($topics, ['withdrawals', 'kyc', 'commissions']);
        }

        return array_values(array_unique($topics));
    }

    /**
     * เก็บตัวเลขทั้งหมดครั้งเดียว (แคช 60 วิ)
     *
     * @return array<string,mixed>
     */
    public function snapshot(): array
    {
        return Cache::remember('eve:admin:snapshot', self::TTL, fn () => [
            'tickets' => $this->tickets(),
            'orders' => $this->orders(),
            'users' => $this->users(),
            'products' => $this->products(),
            'withdrawals_pending' => $this->safeCount('withdrawal_requests', fn ($q) => $q->where('status', 'pending')),
            'kyc_pending' => $this->safeCount('kyc_verifications', fn ($q) => $q->where('status', 'pending')),
            'failed_jobs_today' => $this->safeCount('failed_jobs', fn ($q) => $q->whereDate('failed_at', today())),
            'trend' => $this->trend(),
        ]);
    }

    /**
     * นับแถวแบบปลอดภัย — ตารางไม่มี/คอลัมน์ไม่มี → คืน 0 ไม่โยน exception
     */
    private function safeCount(string $table, ?callable $filter = null): int
    {
        try {
            if (! Schema::hasTable($table)) {
                return 0;
            }
            $q = DB::table($table);
            if ($filter) {
                $filter($q);
            }

            return (int) $q->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array{open:int,in_progress:int,critical:int,overdue:int,unassigned:int}
     */
    private function tickets(): array
    {
        $zero = ['open' => 0, 'in_progress' => 0, 'critical' => 0, 'overdue' => 0, 'unassigned' => 0];

        try {
            // ⚠️ ใช้ตาราง `tickets` (App\Models\Ticket) — ไม่ใช่ support_tickets
            //    (support_tickets เป็นตารางเก่าคู่ขนานที่ไม่มีอะไรอ่านแล้ว)
            if (! Schema::hasTable('tickets')) {
                return $zero;
            }

            return [
                'open' => $this->safeCount('tickets', fn ($q) => $q->where('status', 'open')),
                'in_progress' => $this->safeCount('tickets', fn ($q) => $q->where('status', 'in_progress')),
                'critical' => $this->safeCount('tickets', fn ($q) => $q->whereIn('priority', ['critical', 'urgent'])->whereNotIn('status', ['closed', 'resolved'])),
                'overdue' => Schema::hasColumn('tickets', 'due_at')
                    ? $this->safeCount('tickets', fn ($q) => $q->whereNotNull('due_at')->where('due_at', '<', now())->whereNotIn('status', ['closed', 'resolved']))
                    : 0,
                'unassigned' => Schema::hasColumn('tickets', 'assigned_to')
                    ? $this->safeCount('tickets', fn ($q) => $q->whereNull('assigned_to')->whereNotIn('status', ['closed', 'resolved']))
                    : 0,
            ];
        } catch (Throwable $e) {
            return $zero;
        }
    }

    /**
     * @return array{today:int,pending:int,revenue_today:float}
     */
    private function orders(): array
    {
        try {
            if (! Schema::hasTable('orders')) {
                return ['today' => 0, 'pending' => 0, 'revenue_today' => 0.0];
            }

            $revenue = 0.0;
            if (Schema::hasColumn('orders', 'total_amount')) {
                $revenue = (float) DB::table('orders')
                    ->whereDate('created_at', today())
                    ->whereIn('status', ['paid', 'completed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_amount');
            }

            return [
                'today' => $this->safeCount('orders', fn ($q) => $q->whereDate('created_at', today())),
                'pending' => $this->safeCount('orders', fn ($q) => $q->whereIn('status', ['pending', 'awaiting_payment', 'processing'])),
                'revenue_today' => $revenue,
            ];
        } catch (Throwable $e) {
            return ['today' => 0, 'pending' => 0, 'revenue_today' => 0.0];
        }
    }

    /**
     * @return array{total:int,today:int,month:int}
     */
    private function users(): array
    {
        return [
            'total' => $this->safeCount('users'),
            'today' => $this->safeCount('users', fn ($q) => $q->whereDate('created_at', today())),
            'month' => $this->safeCount('users', fn ($q) => $q->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)),
        ];
    }

    /**
     * @return array{live:int,affiliate:int}
     */
    private function products(): array
    {
        return [
            'live' => $this->safeCount('products', fn ($q) => $q->where('is_active', 1)->where('is_hidden', 0)->whereNull('deleted_at')),
            'affiliate' => Schema::hasColumn('products', 'is_affiliate')
                ? $this->safeCount('products', fn ($q) => $q->where('is_affiliate', 1)->whereNull('deleted_at'))
                : 0,
        ];
    }

    /**
     * แนวโน้มเทียบเดือนก่อน (%) — null = คำนวณไม่ได้ (เดือนก่อนไม่มีข้อมูล)
     *
     * @return array{users_mom:?float,revenue_mom:?float}
     */
    private function trend(): array
    {
        return [
            'users_mom' => $this->momPercent('users', 'created_at'),
            'revenue_mom' => Schema::hasTable('orders') && Schema::hasColumn('orders', 'total_amount')
                ? $this->momPercentSum('orders', 'created_at', 'total_amount')
                : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 🔍 ชุดข้อมูลเจาะลึก — เรียกเฉพาะตอนแอดมินถามถึงเรื่องนั้น
    // ─────────────────────────────────────────────────────────────

    /**
     * สินค้าขายดี/คนดูเยอะ 5 อันดับ
     *
     * @return array<int,string>
     */
    private function topProducts(): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'sales_count')) {
            return [];
        }

        $rows = DB::table('products')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->where('sales_count', '>', 0)
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get(['name', 'price', 'sales_count', 'view_count']);

        if ($rows->isEmpty()) {
            // ยังไม่มีใครซื้อ → ใช้ยอดวิวแทน (บอกความสนใจได้เหมือนกัน)
            $viewed = Schema::hasColumn('products', 'view_count')
                ? DB::table('products')->where('is_active', 1)->whereNull('deleted_at')
                    ->where('view_count', '>', 0)->orderByDesc('view_count')->limit(5)
                    ->get(['name', 'price', 'view_count'])
                : collect();

            if ($viewed->isEmpty()) {
                return ['🏆 สินค้าขายดี: ยังไม่มีสินค้าใดมียอดขายหรือยอดเข้าชมเลย'];
            }

            $lines = ['🏆 ยังไม่มีสินค้าที่ขายได้เลย — เรียงตาม "ยอดเข้าชม" แทน:'];
            foreach ($viewed as $i => $p) {
                $lines[] = sprintf('   %d. %s (%s บาท) — เข้าชม %d ครั้ง', $i + 1, $this->safeText($p->name), number_format((float) $p->price, 2), (int) $p->view_count);
            }

            return $lines;
        }

        $lines = ['🏆 สินค้าขายดี 5 อันดับ:'];
        foreach ($rows as $i => $p) {
            $lines[] = sprintf(
                '   %d. %s (%s บาท) — ขายได้ %d ชิ้น · เข้าชม %d ครั้ง',
                $i + 1,
                $this->safeText($p->name),
                number_format((float) $p->price, 2),
                (int) $p->sales_count,
                (int) ($p->view_count ?? 0)
            );
        }

        return $lines;
    }

    /**
     * สมาชิกใหม่ล่าสุด + มาจากผู้แนะนำหรือสมัครเอง
     *
     * @return array<int,string>
     */
    private function newMembers(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $week = $this->safeCount('users', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)));
        $lines = ["🆕 สมาชิกใหม่ 7 วันล่าสุด: {$week} คน"];

        // มาจากการแนะนำกี่คน (ตัวชี้วัดว่าระบบ affiliate ยังเดินอยู่ไหม)
        if (Schema::hasColumn('users', 'sponsor_id')) {
            $referred = $this->safeCount('users', fn ($q) => $q->where('created_at', '>=', now()->subDays(7))->whereNotNull('sponsor_id'));
            $lines[] = "   • มาจากผู้แนะนำ {$referred} คน · สมัครเอง ".max(0, $week - $referred).' คน';
        }

        $recent = DB::table('users')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['name', 'role', 'created_at']);

        foreach ($recent as $u) {
            // ⚠️ ชื่อเท่านั้น — ไม่ดึงอีเมล/เบอร์เข้า prompt (ส่งออกนอกระบบเราโดยไม่จำเป็น)
            $lines[] = sprintf('   • %s (%s) สมัครเมื่อ %s', $this->safeText($u->name, 30), $u->role ?: 'user', $this->shortDate($u->created_at));
        }

        return $lines;
    }

    /**
     * ยอดขายย้อนหลัง 7 วัน
     *
     * @return array<int,string>
     */
    private function salesTrend(): array
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'total_amount')) {
            return [];
        }

        $rows = DB::table('orders')
            ->whereIn('status', ['paid', 'completed', 'processing', 'shipped', 'delivered'])
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->select(DB::raw('DATE(created_at) AS d'), DB::raw('COUNT(*) AS c'), DB::raw('SUM(total_amount) AS s'))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        if ($rows->isEmpty()) {
            return ['📉 ยอดขาย 7 วันล่าสุด: ไม่มีออเดอร์ที่ชำระเงินแล้วเลยสักรายการ'];
        }

        $lines = ['📊 ยอดขาย 7 วันล่าสุด (เฉพาะออเดอร์ที่ไม่ถูกยกเลิก):'];
        foreach ($rows as $r) {
            $lines[] = sprintf('   • %s — %d ออเดอร์ %s บาท', $this->shortDate($r->d), (int) $r->c, number_format((float) $r->s, 2));
        }

        return $lines;
    }

    /**
     * คิวคำขอถอนเงินที่รออนุมัติ
     *
     * @return array<int,string>
     */
    private function withdrawalQueue(): array
    {
        if (! Schema::hasTable('withdrawal_requests')) {
            return [];
        }

        $pending = DB::table('withdrawal_requests')->where('status', 'pending');
        $count = (clone $pending)->count();

        if ($count === 0) {
            return ['💸 คำขอถอนเงิน: ไม่มีรายการรออนุมัติ'];
        }

        $sum = Schema::hasColumn('withdrawal_requests', 'amount') ? (clone $pending)->sum('amount') : 0;
        $oldest = (clone $pending)->orderBy('created_at')->value('created_at');

        return [
            sprintf('💸 คำขอถอนเงินรออนุมัติ: %d รายการ รวม %s บาท · รายการเก่าสุดค้างมาตั้งแต่ %s', $count, number_format((float) $sum, 2), $this->shortDate($oldest)),
        ];
    }

    /**
     * คิว KYC ที่รอตรวจ
     *
     * @return array<int,string>
     */
    private function kycQueue(): array
    {
        if (! Schema::hasTable('kyc_verifications')) {
            return [];
        }

        $pending = DB::table('kyc_verifications')->where('status', 'pending');
        $count = (clone $pending)->count();

        if ($count === 0) {
            return ['🪪 KYC: ไม่มีรายการรอตรวจ'];
        }

        $oldest = (clone $pending)->orderBy('created_at')->value('created_at');

        return ["🪪 KYC รอตรวจ: {$count} ราย · รายเก่าสุดยื่นมาตั้งแต่ ".$this->shortDate($oldest)];
    }

    /**
     * คอมมิชชั่นที่รออนุมัติ/รอจ่าย ทุกสาย
     *
     * @return array<int,string>
     */
    private function commissionQueue(): array
    {
        $sources = [
            'fortune_commissions' => ['amount', 'สายดูดวง'],
            'mlm_commissions' => ['commission_amount', 'สาย MLM'],
            'marketplace_commissions' => ['commission_amount', 'สายมาร์เก็ตเพลส'],
        ];

        $lines = [];
        foreach ($sources as $table => [$col, $label]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
                continue;
            }

            $q = DB::table($table)->whereIn('status', ['pending', 'approved']);
            $count = (clone $q)->count();
            if ($count === 0) {
                continue;
            }

            $lines[] = sprintf('🤝 คอมมิชชั่น%sรอจ่าย: %d รายการ รวม %s บาท', $label, $count, number_format((float) (clone $q)->sum($col), 2));
        }

        return $lines ?: ['🤝 คอมมิชชั่น: ไม่มีรายการค้างจ่าย'];
    }

    /**
     * สินค้าสต็อกใกล้หมด
     *
     * @return array<int,string>
     */
    private function lowStock(): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'stock_quantity')) {
            return [];
        }

        $q = DB::table('products')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            // สินค้า affiliate ไม่ได้เก็บสต็อกเอง (ของอยู่ที่ Lazada) → นับไปก็ไม่มีความหมาย
            ->when(Schema::hasColumn('products', 'is_affiliate'), fn ($w) => $w->where('is_affiliate', 0))
            ->where('stock_quantity', '<=', 5);

        $count = (clone $q)->count();
        if ($count === 0) {
            return ['📦 สต็อก: ไม่มีสินค้าที่เหลือน้อยกว่า 5 ชิ้น (ไม่นับสินค้า affiliate ที่ไม่ได้เก็บสต็อกเอง)'];
        }

        $lines = ["📦 สินค้าสต็อกเหลือน้อย (≤5 ชิ้น): {$count} รายการ"];
        foreach ((clone $q)->orderBy('stock_quantity')->limit(5)->get(['name', 'stock_quantity']) as $p) {
            $lines[] = sprintf('   • %s — เหลือ %d ชิ้น', $this->safeText($p->name), (int) $p->stock_quantity);
        }

        return $lines;
    }

    /**
     * สรุปฝั่งผู้ขาย/ร้านค้า
     *
     * @return array<int,string>
     */
    private function sellerSummary(): array
    {
        $lines = [];

        if (Schema::hasTable('vendor_stores')) {
            $total = $this->safeCount('vendor_stores');
            $new = $this->safeCount('vendor_stores', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)));
            $lines[] = "🏪 ร้านค้า: ทั้งหมด {$total} ร้าน · เปิดใหม่ใน 30 วัน {$new} ร้าน";
        }

        if (Schema::hasTable('users')) {
            $sellers = $this->safeCount('users', fn ($q) => $q->where('role', 'seller'));
            $lines[] = "👔 บัญชีผู้ขาย: {$sellers} คน";
        }

        return $lines;
    }

    /**
     * 🔒 ล้างข้อความที่ "ผู้ใช้/ผู้ขายพิมพ์เอง" ก่อนยัดเข้า prompt (ชื่อสินค้า / ชื่อสมาชิก)
     *
     * ผู้ขายตั้งชื่อสินค้าเองได้ ถ้าใส่คำสั่งซ้อนบรรทัดไว้ในชื่อ แล้วแอดมินถาม "สินค้าขายดี"
     * ข้อความนั้นจะไหลเข้า prompt ของแอดมินทันที — ยุบบรรทัดและตัดวงเล็บก้ามปูที่ใช้คั่นบล็อกทิ้ง
     */
    private function safeText(?string $text, int $limit = 45): string
    {
        $t = (string) $text;
        $t = preg_replace('/[\r\n\t]+/u', ' ', $t) ?? $t;
        $t = str_replace(['[', ']'], ['(', ')'], $t);
        $t = trim(preg_replace('/\s{2,}/u', ' ', $t) ?? $t);

        return mb_substr($t, 0, $limit);
    }

    /** วันที่แบบสั้น — รับได้ทั้ง string จาก DB และ Carbon */
    private function shortDate($value): string
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

    private function momPercent(string $table, string $dateCol): ?float
    {
        try {
            if (! Schema::hasTable($table)) {
                return null;
            }
            $now = (int) DB::table($table)->whereYear($dateCol, now()->year)->whereMonth($dateCol, now()->month)->count();
            $prev = (int) DB::table($table)->whereYear($dateCol, now()->subMonth()->year)->whereMonth($dateCol, now()->subMonth()->month)->count();

            return $prev > 0 ? round((($now - $prev) / $prev) * 100, 1) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function momPercentSum(string $table, string $dateCol, string $sumCol): ?float
    {
        try {
            $now = (float) DB::table($table)->whereYear($dateCol, now()->year)->whereMonth($dateCol, now()->month)->sum($sumCol);
            $prev = (float) DB::table($table)->whereYear($dateCol, now()->subMonth()->year)->whereMonth($dateCol, now()->subMonth()->month)->sum($sumCol);

            return $prev > 0.0 ? round((($now - $prev) / $prev) * 100, 1) : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
