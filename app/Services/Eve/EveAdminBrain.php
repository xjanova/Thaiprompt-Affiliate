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
