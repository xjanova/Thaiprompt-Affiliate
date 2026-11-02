<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXLiquidityPool;
use App\Services\TPIX\DEXService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * หน้าเว็บ TPIX DEX ของสมาชิก (ธีมโนวา)
 *
 * ข้อมูลที่แสดงดึงฝั่งเซิร์ฟเวอร์ทั้งหมด — เดิมหน้าเรียก /api/v1/tpix/dex/* ด้วย jQuery
 * ซึ่ง (1) ธีมไม่ได้โหลด jQuery (2) กลุ่ม api ไม่มี session จึงโดน 401 และ (3) หลาย endpoint ไม่มีอยู่จริง
 * ส่วนการทำรายการ (quote/swap/เพิ่ม-ถอนสภาพคล่อง) ส่งผ่าน route เว็บใน routes/user.php
 */
class DEXController extends Controller
{
    public function __construct(
        protected DEXService $dexService
    ) {
        $this->middleware('auth');
    }

    /**
     * หน้าแลกเปลี่ยน Token
     */
    public function swap(Request $request): View
    {
        $pools = $this->activePools();
        $balances = $this->balances();

        $recentSwaps = auth()->user()
            ->swaps()
            ->with(['tokenIn', 'tokenOut'])
            ->latest()
            ->limit(6)
            ->get();

        return view('user.dex.swap', [
            'tokens' => $this->tokenOptions($pools, $balances),
            'pairs' => $this->pairOptions($pools),
            'recentSwaps' => $recentSwaps,
        ]);
    }

    /**
     * รายการพูลสภาพคล่อง
     */
    public function pools(Request $request): View
    {
        $pools = $this->activePools()->sortByDesc(fn ($pool) => (float) $pool->volume_24h)->values();

        $myPoolIds = auth()->user()
            ->liquidityPositions()
            ->where('status', 'active')
            ->pluck('pool_id')
            ->unique()
            ->all();

        $stats = [
            'pools' => $pools->count(),
            'tvl' => $pools->sum(fn ($pool) => (float) $pool->getTVL()),
            'volume_24h' => $pools->sum(fn ($pool) => (float) $pool->volume_24h),
            'fees' => $pools->sum(fn ($pool) => (float) $pool->fees_collected),
        ];

        return view('user.dex.pools', compact('pools', 'myPoolIds', 'stats'));
    }

    /**
     * ฟอร์มเพิ่มสภาพคล่อง
     *
     * รับได้ทั้ง ?pool= และ ?pool_id= (หน้า "ตำแหน่งของฉัน" เดิมส่ง pool_id แต่ controller อ่านแค่ pool
     * ทำให้ปุ่ม "เพิ่ม" ไม่เลือกพูลให้)
     */
    public function addLiquidity(Request $request): View
    {
        $pools = $this->activePools();
        $poolId = (int) ($request->query('pool') ?: $request->query('pool_id'));
        $pool = $poolId ? $pools->firstWhere('id', $poolId) : null;

        $balances = $this->balances();

        // token ที่เลือกได้ = token ในพูลที่เปิดอยู่ + token ที่สมาชิกถืออยู่ (เปิดคู่ใหม่ได้)
        $heldTokens = auth()->user()
            ->tokenBalances()
            ->with('token')
            ->where('available_balance', '>', 0)
            ->get()
            ->pluck('token')
            ->filter();

        $tokens = collect($this->tokenOptions($pools, $balances));
        foreach ($heldTokens as $token) {
            if (! $tokens->contains('id', $token->id)) {
                $tokens->push([
                    'id' => $token->id,
                    'symbol' => $token->symbol,
                    'name' => $token->name,
                    'balance' => (string) ($balances[$token->id] ?? '0'),
                ]);
            }
        }

        return view('user.dex.add-liquidity', [
            'pool' => $pool,
            'pairs' => $this->pairOptions($pools),
            'tokens' => $tokens->sortBy('symbol')->values()->all(),
        ]);
    }

    /**
     * ฟอร์มถอนสภาพคล่อง — ค้นจากความสัมพันธ์ของสมาชิกเอง (ตำแหน่งของคนอื่น = 404)
     */
    public function removeLiquidity(Request $request, int $positionId): View
    {
        $position = auth()->user()
            ->liquidityPositions()
            ->with(['pool.tokenA', 'pool.tokenB'])
            ->where('status', '!=', 'removed')
            ->findOrFail($positionId);

        return view('user.dex.remove-liquidity', compact('position'));
    }

    /**
     * ตำแหน่งสภาพคล่องของฉัน
     */
    public function myPositions(Request $request): View
    {
        $positions = auth()->user()
            ->liquidityPositions()
            ->with(['pool.tokenA', 'pool.tokenB'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('user.dex.my-positions', compact('positions'));
    }

    /**
     * ประวัติการแลกเปลี่ยนของฉัน
     */
    public function swapHistory(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => in_array($request->query('status'), ['completed', 'pending', 'failed'], true) ? $request->query('status') : '',
            'period' => in_array($request->query('period'), ['today', 'week', 'month', 'year'], true) ? $request->query('period') : '',
            'sort' => in_array($request->query('sort'), ['date_asc', 'amount_desc', 'amount_asc'], true) ? $request->query('sort') : 'date_desc',
        ];

        $query = auth()->user()
            ->swaps()
            ->with(['tokenIn', 'tokenOut', 'pool']);

        // ค้นหาด้วยสัญลักษณ์/ชื่อเหรียญ ฝั่งจ่ายหรือฝั่งรับ
        if ($filters['q'] !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('tokenIn', fn ($t) => $t->where('symbol', 'like', $like)->orWhere('name', 'like', $like))
                    ->orWhereHas('tokenOut', fn ($t) => $t->where('symbol', 'like', $like)->orWhere('name', 'like', $like));
            });
        }
        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($since = $this->periodStart($filters['period'])) {
            $query->where('created_at', '>=', $since);
        }

        match ($filters['sort']) {
            'date_asc' => $query->oldest(),
            'amount_desc' => $query->orderByDesc('amount_in'),
            'amount_asc' => $query->orderBy('amount_in'),
            default => $query->latest(),
        };

        $swaps = $query->paginate(20)->withQueryString();

        // สรุปทั้งหมดของสมาชิก (ไม่ขึ้นกับตัวกรอง)
        $summary = auth()->user()->swaps()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(fee), 0) as fees, COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) as this_month', [now()->startOfMonth()])
            ->first();

        return view('user.dex.swap-history', compact('swaps', 'filters', 'summary'));
    }

    /**
     * จุดเริ่มของช่วงเวลาที่กรอง (null = ทุกช่วงเวลา)
     */
    protected function periodStart(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    /**
     * พูลที่เปิดใช้งานพร้อม token ทั้งสองฝั่ง (ข้ามพูลที่ token ถูกลบไปแล้ว)
     */
    protected function activePools(): Collection
    {
        return TPIXLiquidityPool::with(['tokenA', 'tokenB'])
            ->where('is_active', true)
            ->get()
            ->filter(fn ($pool) => $pool->tokenA && $pool->tokenB)
            ->values();
    }

    /**
     * ยอด token ที่ใช้ได้ของสมาชิก แยกตาม token_id
     */
    protected function balances(): Collection
    {
        return auth()->user()->tokenBalances()->pluck('available_balance', 'token_id');
    }

    /**
     * รายการ token ที่มีพูลให้เลือก + ยอดคงเหลือของสมาชิก
     */
    protected function tokenOptions(Collection $pools, Collection $balances): array
    {
        return $pools
            ->flatMap(fn ($pool) => [$pool->tokenA, $pool->tokenB])
            ->unique('id')
            ->sortBy('symbol')
            ->map(fn ($token) => [
                'id' => $token->id,
                'symbol' => $token->symbol,
                'name' => $token->name,
                'balance' => (string) ($balances[$token->id] ?? '0'),
            ])
            ->values()
            ->all();
    }

    /**
     * คู่เหรียญที่มีพูลจริง (ใช้จำกัดตัวเลือกปลายทาง + คำนวณสัดส่วนฝั่งเบราว์เซอร์)
     */
    protected function pairOptions(Collection $pools): array
    {
        return $pools->map(fn ($pool) => [
            'id' => $pool->id,
            'a' => $pool->token_a_id,
            'b' => $pool->token_b_id,
            'symbol_a' => $pool->tokenA->symbol,
            'symbol_b' => $pool->tokenB->symbol,
            'reserve_a' => (string) $pool->reserve_a,
            'reserve_b' => (string) $pool->reserve_b,
            'fee' => (string) $pool->fee_percentage,
        ])->values()->all();
    }
}
