<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\EveProductWish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "ของที่ลูกค้าอยากได้" — คิวคำขอจากการคุยกับน้อง Eve
 *
 * ╔══════════════════════════════════════════════════════════════════════════════╗
 * ║ ทำไมต้องมีหน้านี้                                                             ║
 * ║ Eve ค้นสินค้าจาก Lazada สดๆ ด้วยคำค้นไม่ได้ — searchSkuOffer ต้องใช้คุกกี้      ║
 * ║ httpOnly ในเบราว์เซอร์ที่ล็อกอินค้าง เซิร์ฟเวอร์ยิงเองไม่ได้                      ║
 * ║ ดังนั้นเมื่อลูกค้าขอของที่คลังเรายังไม่มี Eve จะ "จดไว้" แทนการไปหาเอง          ║
 * ║ แล้ว owner มาดูหน้านี้ เลือกว่าจะเติมอะไร ตอนที่พร้อม                          ║
 * ╚══════════════════════════════════════════════════════════════════════════════╝
 *
 * หัวใจของหน้านี้คือ "จัดกลุ่มคำขอที่ซ้ำกัน" — คำที่ถูกขอ 20 ครั้งสำคัญกว่าคำที่ขอครั้งเดียว
 * ถ้าโชว์เรียงตามเวลาเฉยๆ ของที่คนอยากได้จริงจะจมอยู่ในรายการยาวเหยียด
 */
class ProductWishController extends Controller
{
    /** สถานะที่ยอมรับ (ตรงกับคอลัมน์ status varchar(20)) */
    public const STATUSES = ['pending', 'searching', 'fulfilled', 'none'];

    /**
     * รายการคำขอ — โหมดจัดกลุ่ม (ค่าเริ่มต้น) และโหมดรายการดิบ
     */
    public function index(Request $request)
    {
        $mode = $request->get('mode') === 'raw' ? 'raw' : 'grouped';
        $status = in_array($request->get('status'), self::STATUSES, true) ? $request->get('status') : null;
        $search = is_scalar($request->get('q')) ? trim((string) $request->get('q')) : '';

        // ── สรุปหัวข้อ ──
        $stats = [
            'total' => EveProductWish::count(),
            'pending' => EveProductWish::where('status', 'pending')->count(),
            'fulfilled' => EveProductWish::where('status', 'fulfilled')->count(),
            // คำที่ "ไม่เจอของเลย" = ช่องว่างในคลังที่ชัดที่สุด
            'not_found' => EveProductWish::where('results_found', 0)->count(),
            'unique_queries' => EveProductWish::distinct()->count('query'),
            'last_7d' => EveProductWish::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        if ($mode === 'grouped') {
            // จัดกลุ่มตามคำค้น (ตัวพิมพ์เล็ก/ตัดช่องว่างหัวท้าย) แล้วเรียงตามจำนวนครั้งที่ถูกขอ
            $query = EveProductWish::query()
                ->selectRaw('LOWER(TRIM(`query`)) as q_key')
                ->selectRaw('MIN(`query`) as sample_query')
                ->selectRaw('COUNT(*) as times_asked')
                ->selectRaw('COUNT(DISTINCT user_id) as distinct_users')
                ->selectRaw('MIN(budget) as budget_min')
                ->selectRaw('MAX(budget) as budget_max')
                ->selectRaw('MAX(created_at) as last_asked_at')
                ->selectRaw('SUM(CASE WHEN results_found = 0 THEN 1 ELSE 0 END) as times_empty')
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                ->groupBy('q_key');

            if ($status) {
                $query->havingRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) > 0", [$status]);
            }
            if ($search !== '') {
                $query->havingRaw('LOWER(MIN(`query`)) LIKE ?', ['%'.mb_strtolower($search).'%']);
            }

            $rows = $query->orderByDesc('times_asked')->orderByDesc('last_asked_at')->paginate(30)->withQueryString();
        } else {
            $q = EveProductWish::query()->with('user:id,name,email');
            if ($status) {
                $q->where('status', $status);
            }
            if ($search !== '') {
                $q->where('query', 'like', '%'.$search.'%');
            }
            $rows = $q->latest('created_at')->paginate(30)->withQueryString();
        }

        return view('admin.lazada-hub.wishes.index', [
            'rows' => $rows,
            'stats' => $stats,
            'mode' => $mode,
            'status' => $status,
            'search' => $search,
            'statuses' => self::STATUSES,
            'pageTitle' => 'ของที่ลูกค้าอยากได้',
        ]);
    }

    /**
     * เปลี่ยนสถานะคำขอ (ทั้งกลุ่มที่คำเหมือนกัน หรือรายการเดียว)
     */
    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', self::STATUSES),
            'id' => 'nullable|integer',
            'query_key' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);

        if (empty($data['id']) && empty($data['query_key'])) {
            return back()->with('error', 'ต้องระบุรายการที่จะเปลี่ยนสถานะ');
        }

        $payload = ['status' => $data['status']];
        if (! empty($data['note'])) {
            $payload['note'] = $data['note'];
        }

        if (! empty($data['id'])) {
            $affected = EveProductWish::where('id', $data['id'])->update($payload);
        } else {
            // อัปเดตทั้งกลุ่มคำเดียวกัน — ปกติ owner ตัดสินใจทีเดียวต่อ "คำ" ไม่ใช่ต่อครั้งที่ถูกถาม
            $affected = EveProductWish::whereRaw('LOWER(TRIM(`query`)) = ?', [mb_strtolower(trim($data['query_key']))])
                ->update($payload);
        }

        return back()->with('success', "อัปเดตสถานะแล้ว {$affected} รายการ");
    }

    /**
     * ดาวน์โหลด CSV — เอาไปใช้เป็นคำค้นตอนเก็บสินค้าเข้าคลังรอบถัดไป
     */
    public function export(Request $request)
    {
        $rows = DB::table('eve_product_wishes')
            ->selectRaw('MIN(`query`) as q, COUNT(*) as times, COUNT(DISTINCT user_id) as users, MAX(budget) as budget_max, MAX(created_at) as last_asked')
            ->groupByRaw('LOWER(TRIM(`query`))')
            ->orderByDesc('times')
            ->limit(2000)
            ->get();

        $filename = 'eve-product-wishes-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM ให้ Excel ไทยอ่านภาษาไทยไม่เพี้ยน
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['คำที่ลูกค้าขอ', 'จำนวนครั้ง', 'จำนวนคน', 'งบสูงสุด', 'ขอครั้งล่าสุด']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->q, $r->times, $r->users, $r->budget_max, $r->last_asked]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
