<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXStake;
use App\Models\TPIXStakingPool;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * หน้าเว็บ TPIX Staking ของสมาชิก (ธีมโนวา)
 *
 * ข้อมูลที่แสดงดึงฝั่งเซิร์ฟเวอร์ด้วยชื่อคอลัมน์จริงของตาราง tpix_stakes / tpix_staking_pools
 * (amount, unlock_at, current_stakers) — เดิมหน้า index แสดง "แผนลงทุน" ตัวอย่างที่เขียนตายตัวใน JS
 * ไม่ใช่พูลจริง และหน้าอื่นเรียก API ผ่าน jQuery ที่ธีมไม่ได้โหลด
 */
class StakingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * พูล Stake ที่เปิดอยู่ + รายการ Stake ของฉัน
     */
    public function index(Request $request): View
    {
        $pools = TPIXStakingPool::with('token')
            ->where('status', 'active')
            ->orderBy('apy', 'desc')
            ->get()
            ->filter(fn ($pool) => $pool->token)
            ->values();

        $myStakes = auth()->user()
            ->stakes()
            ->with(['pool.token'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('user.staking.index', compact('pools', 'myStakes'));
    }

    /**
     * รายละเอียดพูล — พูลฉบับร่างยังไม่เปิดให้สมาชิกเห็น (404)
     */
    public function show(Request $request, int $poolId): View
    {
        $pool = $this->visiblePool($poolId);

        $myStake = auth()->user()
            ->stakes()
            ->where('pool_id', $poolId)
            ->where('status', 'active')
            ->first();

        // ความเคลื่อนไหวล่าสุดในพูล: ส่งเฉพาะจำนวน/สถานะ/เวลา ไม่ส่งตัวตนของสมาชิกคนอื่น
        $recentStakes = TPIXStake::where('pool_id', $poolId)
            ->latest()
            ->limit(10)
            ->get(['id', 'amount', 'status', 'created_at']);

        return view('user.staking.show', compact('pool', 'myStake', 'recentStakes'));
    }

    /**
     * ฟอร์ม Stake
     */
    public function stakeForm(Request $request, int $poolId): View
    {
        $pool = $this->visiblePool($poolId);

        // ยอด token ของสมาชิก (ตาราง tpix_token_balances ใช้คอลัมน์ balance / available_balance)
        $balance = auth()->user()
            ->tokenBalances()
            ->where('token_id', $pool->token_id)
            ->first();

        return view('user.staking.stake', compact('pool', 'balance'));
    }

    /**
     * ประวัติการ Stake ของฉัน
     */
    public function history(Request $request): View
    {
        $filters = [
            'status' => in_array($request->query('status'), ['active', 'unstaked', 'slashed'], true) ? $request->query('status') : '',
            'period' => in_array($request->query('period'), ['today', 'week', 'month', 'year'], true) ? $request->query('period') : '',
            'sort' => in_array($request->query('sort'), ['date_asc', 'amount_desc', 'amount_asc'], true) ? $request->query('sort') : 'date_desc',
        ];

        $query = auth()->user()->stakes()->with(['pool.token']);

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        $since = match ($filters['period']) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->startOfYear(),
            default => null,
        };
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        match ($filters['sort']) {
            'date_asc' => $query->oldest(),
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            default => $query->latest(),
        };

        $stakes = $query->paginate(20)->withQueryString();

        // สรุปทั้งหมดของสมาชิก (ไม่ขึ้นกับตัวกรอง)
        $summary = auth()->user()->stakes()
            ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active, COALESCE(SUM(rewards_earned), 0) as rewards")
            ->first();

        return view('user.staking.history', compact('stakes', 'filters', 'summary'));
    }

    /**
     * พูลที่สมาชิกเปิดดูได้ (ไม่ใช่ฉบับร่าง และ token ยังอยู่)
     */
    protected function visiblePool(int $poolId): TPIXStakingPool
    {
        $pool = TPIXStakingPool::with('token')
            ->where('status', '!=', 'draft')
            ->findOrFail($poolId);

        abort_unless($pool->token, 404);

        return $pool;
    }
}
