<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlipVerificationLog;
use Illuminate\Http\Request;

/**
 * ประวัติการตรวจสลิป (SlipOK) — audit log
 *
 * แอดมินดูได้ว่า: ส่งไป SlipOK จริงไหม / สลิปซ้ำไหม / บิลไหน / ผลเป็นอย่างไร
 * อ่านจากตาราง slip_verification_logs (บันทึกทุกการตรวจ ไม่ใช่แค่สำเร็จ)
 */
class FortuneSlipLogController extends Controller
{
    /**
     * แสดงรายการประวัติการตรวจสลิป + ตัวกรอง
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // โหลดบิลที่จับคู่มาด้วย (เฉพาะคอลัมน์ที่ใช้)
        $query = SlipVerificationLog::query()
            ->with('reading:id,facebook_user_name,bill_reference,platform');

        // 🔍 ค้นหา: user id / transRef / ชื่อผู้โอน / เลขบิล / id
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('chat_user_id', 'LIKE', "%{$search}%")
                    ->orWhere('trans_ref', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%")
                    ->orWhere('fortune_reading_id', $search)
                    ->orWhere('id', $search);
            });
        }

        // กรองตามผล
        if ($decision = $request->input('decision')) {
            $query->where('decision', $decision);
        }

        // กรองตาม platform
        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        // เฉพาะสลิปซ้ำ
        if ($request->boolean('dup_only')) {
            $query->where('is_duplicate', true);
        }

        // เฉพาะที่ส่งไป SlipOK จริง
        if ($request->boolean('sent_only')) {
            $query->where('sent_to_slipok', true);
        }

        // ช่วงวันที่
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        // 📊 สถิติภาพรวม
        $stats = [
            'total' => SlipVerificationLog::count(),
            'today' => SlipVerificationLog::whereDate('created_at', today())->count(),
            'sent_slipok' => SlipVerificationLog::where('sent_to_slipok', true)->count(),
            'duplicates' => SlipVerificationLog::where('is_duplicate', true)->count(),
            'approved' => SlipVerificationLog::where('decision', 'approve')->count(),
        ];

        // รายการ decision ที่มีจริง (สำหรับ dropdown filter)
        $decisions = SlipVerificationLog::query()
            ->select('decision')->distinct()->whereNotNull('decision')
            ->orderBy('decision')->pluck('decision');

        return view('admin.fortune.slip-logs.index', [
            'logs' => $logs,
            'stats' => $stats,
            'decisions' => $decisions,
            'pageTitle' => 'ประวัติการตรวจสลิป (SlipOK)',
            'filters' => [
                'search' => $search ?? '',
                'decision' => $decision ?? '',
                'platform' => $platform ?? '',
                'dup_only' => $request->boolean('dup_only'),
                'sent_only' => $request->boolean('sent_only'),
                'date_from' => $dateFrom ?? '',
                'date_to' => $dateTo ?? '',
            ],
        ]);
    }
}
