<?php

namespace App\Services\Juntra;

use App\Models\FortuneCommission;
use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 🌙 ผังแม่หมอ + ค่าแนะนำ ในมุมที่เว็บ/แอพจันทราแสดง — ที่เดียวที่ประกอบข้อมูลชุดนี้
 *
 * ใช้ร่วมกันสองทาง: token ของลูกค้า (/api/v1/juntra/mlm/*) และตัวตนของเซิร์ฟเวอร์จันทรา
 *   (/api/v1/juntra/server/affiliate/*) — ตัวเลขสองทางจึงตรงกันเสมอ
 *
 * อ่านเฉพาะ fortune_commissions (ไม่มีร้านค้า/NFC/TPIX) · cache ต่อผู้ใช้ 5 นาที
 *   ล้างเมื่อสายงานหรือค่าแนะนำเปลี่ยน (forgetCachesForUpline)
 */
class JuntraMlmReadService
{
    public const DEFAULT_TREE_DEPTH = 5;

    public const MAX_TREE_DEPTH = 10;

    private const CACHE_TTL = 300;

    /** เพดานจำนวนคนที่แสดงในผังหนึ่งครั้ง — รากใหญ่ (ผู้แนะนำเริ่มต้น) ไม่ส่งทั้งฐานสมาชิกกลับไป */
    private const MAX_TREE_NODES = 2000;

    /** เพดานจำนวนสมาชิกที่นับขนาดทีม — เกินนี้ตัวเลขทีมเป็น "อย่างน้อย" (team_size_capped) */
    private const MAX_TEAM_SCAN = 50000;

    /** สถานะที่นับเป็นรายได้ — rejected = ถูกดึงคืน/ยกเลิกแล้ว เงินไม่อยู่ในกระเป๋า */
    private const EARNED_STATUSES = [
        FortuneCommission::STATUS_PENDING,
        FortuneCommission::STATUS_APPROVED,
        FortuneCommission::STATUS_PAID,
    ];

    public function tree(int $userId, int $depth = self::DEFAULT_TREE_DEPTH): array
    {
        $depth = max(1, min($depth, self::MAX_TREE_DEPTH));

        return Cache::remember(
            "juntra.mlm.tree.{$userId}.d{$depth}",
            self::CACHE_TTL,
            fn () => $this->buildTree($userId, $depth)
        );
    }

    public function stats(int $userId): array
    {
        return Cache::remember(
            "juntra.mlm.stats.{$userId}",
            self::CACHE_TTL,
            fn () => $this->buildStats($userId)
        );
    }

    /**
     * ประวัติค่าแนะนำ — ไม่ cache (เปิดหน้าไหนต้องได้ของสด)
     *
     * @param  array{status?: ?string, from?: ?string, to?: ?string}  $filters
     */
    public function commissions(int $userId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $perPage = max(5, min($perPage, 100));

        $q = FortuneCommission::query()
            ->where('user_id', $userId)
            ->with(['fromUser:id,name,email', 'reading:id,facebook_user_name,amount_paid,paid_at,reading_type,bill_reference'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        $paginator = $q->paginate($perPage, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(fn (FortuneCommission $c) => $this->commissionRow($c))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** แถวค่าแนะนำหนึ่งรายการในรูปที่จันทราแสดง */
    public function commissionRow(FortuneCommission $c): array
    {
        return [
            'id' => $c->id,
            'level' => (int) $c->level,
            'amount' => (float) $c->amount,
            'commission_type' => $c->commission_type,
            'commission_rate' => (float) $c->commission_rate,
            'reading_price' => (float) $c->reading_price,
            'status' => $c->status,
            'user' => $c->relationLoaded('user') && $c->user ? [
                'id' => $c->user->id,
                'name' => $c->user->name,
                'email' => $c->user->email,
            ] : null,
            'from_user' => $c->fromUser ? [
                'id' => $c->fromUser->id,
                'name' => $c->fromUser->name,
            ] : null,
            'reading' => $c->reading ? [
                'id' => $c->reading->id,
                'bill_reference' => $c->reading->bill_reference,
                'source' => $c->reading->isJuntraBill() ? 'juntra' : 'maemor',
                'customer' => $c->reading->facebook_user_name,
                'amount' => (float) $c->reading->amount_paid,
                'paid_at' => optional($c->reading->paid_at)->toIso8601String(),
            ] : null,
            'notes' => $c->notes,
            'created_at' => $c->created_at?->toIso8601String(),
            'approved_at' => $c->approved_at?->toIso8601String(),
            'paid_at' => $c->paid_at?->toIso8601String(),
            'rejected_at' => $c->rejected_at?->toIso8601String(),
        ];
    }

    /** ผู้ใช้ที่มีกิจกรรมดูดวง (เคยได้ค่าแนะนำ หรือมีบิล) — ตัวเลือก "ดูข้อมูลของใคร" ของแอดมิน */
    public function users(string $search = '', int $page = 1, int $perPage = 50): array
    {
        $perPage = max(10, min($perPage, 200));

        $q = User::query()
            ->whereIn('id', function ($sub) {
                $sub->select('user_id')->from('fortune_commissions')
                    ->union(
                        DB::table('fortune_readings')->select('user_id')->whereNotNull('user_id')
                    );
            })
            ->select(['id', 'name', 'email']);

        if ($search !== '') {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $q->orderBy('name')->paginate($perPage, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** ล้าง cache ของผู้ใช้คนเดียว (สถิติ + ผังทุกความลึก) */
    public function forgetCachesFor(int $userId): void
    {
        Cache::forget("juntra.mlm.stats.{$userId}");
        for ($d = 1; $d <= self::MAX_TREE_DEPTH; $d++) {
            Cache::forget("juntra.mlm.tree.{$userId}.d{$d}");
        }
    }

    /**
     * ล้าง cache ของสมาชิกคนนี้และทุกคนเหนือขึ้นไปในสายงาน — ทุกคนที่ผังหรือยอดเพิ่งเปลี่ยน
     *
     * เดินตาม unilevel_sponsor_id ไม่ใช่ unilevel_path (path ในระบบมีหลายรูปแบบ)
     */
    public function forgetCachesForUpline(MlmMember $member): void
    {
        $seen = [];
        $current = $member;

        for ($i = 0; $current && $i < 100; $i++) {
            if (isset($seen[$current->id])) {
                break;
            }
            $seen[$current->id] = true;

            if ($current->user_id) {
                $this->forgetCachesFor((int) $current->user_id);
            }

            $current = $current->unilevel_sponsor_id
                ? MlmMember::find($current->unilevel_sponsor_id)
                : null;
        }
    }

    /* ============================================================
       INTERNAL
       ============================================================ */

    /**
     * ผังใต้สมาชิกคนนี้ลงไป $depth ชั้น
     *
     * 🔧 (2026-09-21) เดินผังตาม unilevel_sponsor_id ทีละชั้น — ไม่พึ่ง unilevel_path
     *   เดิม LIKE '{path ของราก}%' แต่ path เก็บแค่บรรพบุรุษ (ไม่มีตัวรากเอง) จึงดึงพี่น้องของราก
     *   ทั้งทีมติดมาด้วย · '/1/5' ไปตรงกับ '/1/57' · รากที่ path ว่างได้ผังเปล่า ·
     *   และ path ในระบบมีหลายรูปแบบ (บางเส้นใส่ user_id บางเส้นไม่มี / นำหน้า)
     */
    private function buildTree(int $rootUserId, int $depth): array
    {
        $root = MlmMember::with('user:id,name,email')->where('user_id', $rootUserId)->first();
        if (! $root) {
            return [
                'user' => $this->slimUser($rootUserId),
                'tree' => null,
                'depth_returned' => 0,
                'total_descendants' => 0,
                'note' => 'user is not enrolled in MLM',
            ];
        }

        $columns = ['id', 'user_id', 'unilevel_sponsor_id', 'unilevel_level', 'total_pv', 'total_team_pv', 'status'];

        /** @var Collection<int, MlmMember> $members */
        $members = collect([$root]);
        $frontier = [$root->id];
        $truncated = false;
        for ($d = 1; $d <= $depth && $frontier !== []; $d++) {
            if ($members->count() >= self::MAX_TREE_NODES) {
                $truncated = true; // ชั้นที่เหลือไม่ส่ง — หน้าเว็บเลือกคนในผังแล้วดูต่อได้
                break;
            }
            $rows = collect();
            foreach (array_chunk($frontier, 1000) as $chunk) {
                $rows = $rows->merge(
                    MlmMember::with('user:id,name,email')->whereIn('unilevel_sponsor_id', $chunk)->get($columns)
                );
            }
            $members = $members->merge($rows);
            $frontier = $rows->pluck('id')->all();
        }

        [$teamSizes, $directCounts, $capped] = $this->teamSizes($root->id);

        $fortuneSums = FortuneCommission::query()
            ->whereIn('mlm_member_id', $members->pluck('id')->all())
            ->whereIn('status', self::EARNED_STATUSES)
            ->select('mlm_member_id', DB::raw('SUM(amount) as total'))
            ->groupBy('mlm_member_id')
            ->pluck('total', 'mlm_member_id');

        // ลูกค้าจันทรา = ย้ายสายจากหลังบ้านจันทราได้ (คนอื่นจัดการที่หลังบ้านแม่หมอ — เว็บใครเว็บมัน)
        $juntraUserIds = JuntraAccount::whereIn('user_id', $members->pluck('user_id')->all())->pluck('user_id')->flip();

        $bySponsor = $members->groupBy('unilevel_sponsor_id');

        $build = function (MlmMember $node, int $level) use (&$build, $bySponsor, $fortuneSums, $teamSizes, $directCounts, $depth, $juntraUserIds) {
            $children = $level < $depth ? $bySponsor->get($node->id, collect()) : collect();

            return [
                'id' => $node->id,
                'user_id' => $node->user_id,
                'name' => $node->user?->name ?? "Member #{$node->id}",
                'level' => $level,
                'status' => $node->status,
                'is_juntra' => $juntraUserIds->has($node->user_id),
                'personal_volume' => (float) $node->total_pv,
                'team_volume' => (float) $node->total_team_pv,
                'fortune_commission' => (float) ($fortuneSums[$node->id] ?? 0),
                // นับสดจากผัง unilevel — ตัวนับที่เก็บไว้ในตาราง (total_team_members) เดินตามสาย binary
                'direct_referrals' => (int) ($directCounts[$node->id] ?? 0),
                'total_team_members' => (int) ($teamSizes[$node->id] ?? 0),
                'children' => $children->map(fn ($c) => $build($c, $level + 1))->values()->all(),
            ];
        };

        return [
            'user' => $this->slimUser($rootUserId),
            'tree' => $build($root, 0),
            'depth_returned' => $truncated ? max(0, $d - 1) : $depth,
            'total_descendants' => (int) ($teamSizes[$root->id] ?? 0),
            'team_size_capped' => $capped,
            'truncated' => $truncated,
        ];
    }

    /**
     * ขนาดทีม (ทุกชั้น) และจำนวนลูกตรงของทุกคนใต้ราก — เดินผัง unilevel ครั้งเดียวแบบเบา (id + sponsor)
     *
     * @return array{0: array<int, int>, 1: array<int, int>, 2: bool} [ขนาดทีม, ลูกตรง, ถูกตัดที่เพดาน]
     */
    private function teamSizes(int $rootMemberId): array
    {
        $parentOf = [];
        $levels = [];
        $frontier = [$rootMemberId];
        $scanned = 0;
        $capped = false;

        while ($frontier !== []) {
            $next = [];
            foreach (array_chunk($frontier, 1000) as $chunk) {
                $rows = MlmMember::whereIn('unilevel_sponsor_id', $chunk)->get(['id', 'unilevel_sponsor_id']);
                foreach ($rows as $row) {
                    if (isset($parentOf[$row->id]) || $row->id === $rootMemberId) {
                        continue; // กันวงวนจากข้อมูลเสีย
                    }
                    $parentOf[$row->id] = (int) $row->unilevel_sponsor_id;
                    $next[] = $row->id;
                }
            }

            $scanned += count($next);
            if ($next !== []) {
                $levels[] = $next;
            }
            if ($scanned >= self::MAX_TEAM_SCAN) {
                $capped = true;
                break;
            }
            $frontier = $next;
        }

        $sizes = [$rootMemberId => 0];
        $direct = [];
        foreach (array_reverse($levels) as $level) {
            foreach ($level as $id) {
                $parent = $parentOf[$id];
                $sizes[$parent] = ($sizes[$parent] ?? 0) + 1 + ($sizes[$id] ?? 0);
                $direct[$parent] = ($direct[$parent] ?? 0) + 1;
            }
        }

        return [$sizes, $direct, $capped];
    }

    private function buildStats(int $userId): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $year = now()->startOfYear();

        $all = FortuneCommission::query()->where('user_id', $userId);
        // 🔧 (2026-09-21) การ์ด "รายได้" ไม่นับรายการที่ถูกดึงคืน (rejected) — เดิมนับทุกสถานะ
        //   บิลที่ถูกยกเลิกแล้วยังโผล่เป็นรายได้ ยอดบนการ์ดเกินเงินที่อยู่ในกระเป๋าจริง
        $earned = (clone $all)->whereIn('status', self::EARNED_STATUSES);

        return [
            'user' => $this->slimUser($userId),
            'totals' => [
                'today' => (float) (clone $earned)->where('created_at', '>=', $today)->sum('amount'),
                'this_month' => (float) (clone $earned)->where('created_at', '>=', $month)->sum('amount'),
                'this_year' => (float) (clone $earned)->where('created_at', '>=', $year)->sum('amount'),
                'all_time' => (float) (clone $earned)->sum('amount'),
                'pending' => (float) (clone $all)->where('status', FortuneCommission::STATUS_PENDING)->sum('amount'),
                'paid' => (float) (clone $all)->where('status', FortuneCommission::STATUS_PAID)->sum('amount'),
                'reversed' => (float) (clone $all)->where('status', FortuneCommission::STATUS_REJECTED)->sum('amount'),
            ],
            'counts' => [
                'commissions_total' => (int) (clone $earned)->count(),
                'unique_customers' => (int) (clone $earned)->distinct('from_user_id')->count('from_user_id'),
            ],
            'monthly_series' => $this->monthlyEarnings($userId, 12),
            'mlm' => $this->memberSummary($userId),
        ];
    }

    /** [{label: 'Jan 26', amount: 1234.5}, ...] สำหรับกราฟรายได้ */
    private function monthlyEarnings(int $userId, int $months): array
    {
        // เริ่มจากวันที่ 1 ก่อนถอยเดือน — ถอยจากวันที่ 29-31 ตรง ๆ จะข้ามเดือน/ได้เดือนซ้ำ
        $from = now()->startOfMonth()->subMonths($months - 1);
        $rows = FortuneCommission::query()
            ->where('user_id', $userId)
            ->whereIn('status', self::EARNED_STATUSES)
            ->where('created_at', '>=', $from)
            ->get(['created_at', 'amount'])
            ->groupBy(fn (FortuneCommission $c) => $c->created_at->format('Y-m'))
            ->map(fn (Collection $g) => (float) $g->sum('amount'));

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $cursor = now()->startOfMonth()->subMonths($months - 1 - $i);
            $out[] = [
                'label' => $cursor->format('M y'),
                'amount' => (float) ($rows->get($cursor->format('Y-m')) ?? 0),
            ];
        }

        return $out;
    }

    private function memberSummary(int $userId): ?array
    {
        $m = MlmMember::where('user_id', $userId)->first();
        if (! $m) {
            return null;
        }

        [$teamSizes, $directCounts, $capped] = $this->teamSizes($m->id);

        return [
            'member_code' => $m->member_code,
            'unilevel_level' => (int) ($m->unilevel_level ?? 0),
            'direct_referrals' => (int) ($directCounts[$m->id] ?? 0),
            'total_team_members' => (int) ($teamSizes[$m->id] ?? 0),
            'team_size_capped' => $capped,
            'total_pv' => (float) $m->total_pv,
            'total_team_pv' => (float) $m->total_team_pv,
            'status' => $m->status,
        ];
    }

    public function slimUser(int $userId): ?array
    {
        $u = User::find($userId);
        if (! $u) {
            return null;
        }

        // referral_code = MlmMember.member_code — จันทรา.online สร้างลิงก์เชิญ (จันทรา.online/r/{code})
        // จากค่านี้ และเส้นต่อสายงานก็แปลงรหัสเดียวกันกลับเป็นผู้แนะนำ · null = ยังไม่เข้าผัง
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'referral_code' => MlmMember::where('user_id', $u->id)->value('member_code'),
        ];
    }
}
