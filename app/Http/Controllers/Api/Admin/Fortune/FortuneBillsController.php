<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Admin\FortuneBillsController as WebFortuneBillsController;
use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Services\AdminApp\FortuneBillBuckets;
use App\Services\AdminApp\FortuneBillPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Mobile API: บิลดูดวง (แอปไทยพร้อม แอดมิน)
 *
 * อ่านอย่างเดียว — ปุ่มจัดการทุกตัวยิงไป endpoint เดิม fortune/readings/{id}/mark-paid|refund|cancel
 * นิยามกองสถานะอยู่ที่ App\Services\AdminApp\FortuneBillBuckets ที่เดียว
 */
class FortuneBillsController extends Controller
{
    /**
     * GET /api/admin/fortune/bills?status=&search=&platform=&package=&date_from=&date_to=&page=&per_page=
     */
    public function index(Request $request, FortuneBillPresenter $presenter): JsonResponse
    {
        $data = $request->validate([
            'status' => 'nullable|in:all,'.implode(',', FortuneBillBuckets::STATUSES),
            'search' => 'nullable|string|max:100',
            'platform' => 'nullable|in:facebook,line,telegram,other',
            'package' => 'nullable|in:deep,celtic,free_card,basic,juntra',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $status = (string) ($data['status'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 20);

        $query = $this->filtered(FortuneReading::query(), $data);
        FortuneBillBuckets::applyStatus($query, $status);

        // เรียงตามกอง: กองรอตรวจ = รอนานสุดก่อน (คิวงาน) · จ่ายแล้ว = จ่ายล่าสุดก่อน · อื่น ๆ = อัปเดตล่าสุดก่อน (เหมือนเว็บ)
        match ($status) {
            'awaiting' => $query->orderByRaw('COALESCE(slip_received_at, transfer_reported_at, paid_at, updated_at) ASC')->orderBy('id'),
            'paid' => $query->orderByDesc('paid_at')->orderByDesc('id'),
            default => $query->orderByDesc('updated_at')->orderByDesc('id'),
        };

        // เลือกเฉพาะคอลัมน์ของรายการ (ไม่โหลดคำทำนายยาว ๆ) — count ของ paginate ไม่โดนผล เพราะ aggregate แทนคอลัมน์เอง
        $page = FortuneBillPresenter::selectListColumns($query)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $presenter->presentMany($page->getCollection()),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/fortune/bills/stats
     *
     * จำนวนต่อกอง (คิวรีเดียว) + รายได้บิลที่จ่ายวันนี้
     */
    public function stats(): JsonResponse
    {
        $now = now();
        $selects = [];
        foreach (FortuneBillBuckets::STATUSES as $s) {
            $selects[] = 'COALESCE(SUM(CASE WHEN '.FortuneBillBuckets::statusSql($s, $now)." THEN 1 ELSE 0 END), 0) AS c_{$s}";
        }
        $selects[] = 'COUNT(*) AS c_all';
        // ยอดต่อใบเดียวกับที่รายการโชว์ (amount_paid > 0 ไม่งั้นยอดทศนิยมจาก UPA) — ผลรวมจะตรงกับรายการ
        $selects[] = 'COALESCE(SUM(CASE WHEN '.FortuneBillBuckets::statusSql('awaiting', $now)
            .' THEN '.FortuneBillBuckets::billAmountSql().' ELSE 0 END), 0) AS awaiting_amount';

        $row = FortuneBillBuckets::billedScope(FortuneReading::query())
            ->selectRaw(implode(', ', $selects))
            ->toBase()
            ->first();

        $counts = [];
        foreach (FortuneBillBuckets::STATUSES as $s) {
            $counts[$s] = (int) ($row->{'c_'.$s} ?? 0);
        }
        $counts['all'] = (int) ($row->c_all ?? 0);

        $paidToday = self::paidTodayQuery($now->copy()->startOfDay(), $now);

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => $counts,
                'awaiting_amount_thb' => round((float) ($row->awaiting_amount ?? 0), 2),
                'paid_today' => [
                    'count' => (clone $paidToday)->count(),
                    'revenue_thb' => round((float) (clone $paidToday)
                        ->selectRaw('COALESCE(SUM(COALESCE(amount_received, amount_paid)), 0) AS s')
                        ->value('s'), 2),
                ],
                'generated_at' => $now->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/admin/fortune/bills/{reading}/slip
     *
     * สตรีมรูปสลิป — PDPA: มีชื่อผู้โอน/เลขบัญชี จึงต้องผ่าน token แอดมิน + ห้าม cache สาธารณะ
     */
    public function slip(FortuneReading $reading): Response
    {
        $path = FortuneBillPresenter::resolveSlipPath($reading);

        if ($path === null) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรูปสลิปของบิลนี้ (อาจถูกลบตามรอบ 30 วัน)',
                'error_code' => 'SLIP_NOT_FOUND',
            ], 404);
        }

        $disk = Storage::disk('local');

        return $disk->response($path, null, [
            'Content-Type' => $disk->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * บิลดูดวงที่ "กลายเป็นจ่ายแล้ว" ในช่วงเวลา — นิยามรายได้ดูดวงของแอป (ใช้ร่วมกับ ops/summary)
     *
     * ตัดแถวที่ไม่เคยออกบิล · ตัดบิลเว็บจันทรา (จันทราเก็บเงินเอง) · บิลยกเลิก/คืนเงิน is_paid=0 จึงไม่นับเอง
     */
    public static function paidTodayQuery(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): Builder
    {
        return FortuneReading::query()
            ->exceptNeverBilled()
            ->withoutJuntra()
            ->where('is_paid', true)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<=', $to);
    }

    /**
     * ขอบเขตบิลจริง + ตัวกรองที่ไม่ใช่สถานะ
     *
     * @param  array<string, mixed>  $data
     */
    private function filtered(Builder $query, array $data): Builder
    {
        FortuneBillBuckets::billedScope($query);

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            // ช่องค้นหาชุดเดียวกับศูนย์รวมบิลบนเว็บ (ชื่อ / เลขบิล / PSID / LINE id / #id)
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('bill_reference', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%");
                $id = ltrim($search, '#');
                if (ctype_digit($id)) {
                    $q->orWhere('id', (int) $id);
                }
            });
        }

        $platform = (string) ($data['platform'] ?? '');
        if ($platform === 'other') {
            $query->where(fn ($q) => $q->whereNull('platform')->orWhereNotIn('platform', ['facebook', 'line', 'telegram']));
        } elseif ($platform !== '') {
            $query->where('platform', $platform);
        }

        $package = (string) ($data['package'] ?? '');
        if ($package === 'juntra') {
            $query->where('reading_type', FortuneReading::READING_TYPE_JUNTRA);
        } elseif (isset(WebFortuneBillsController::PACKAGES[$package])) {
            $query->where('reading_type', WebFortuneBillsController::PACKAGES[$package][1]);
        }

        if (! empty($data['date_from'])) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }
        if (! empty($data['date_to'])) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        return $query;
    }
}
