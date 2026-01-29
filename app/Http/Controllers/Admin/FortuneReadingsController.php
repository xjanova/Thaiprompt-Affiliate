<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use Illuminate\Http\Request;

/**
 * Fortune Readings Controller
 *
 * จัดการประวัติการทำนาย
 */
class FortuneReadingsController extends Controller
{
    /**
     * แสดงรายการการทำนาย
     */
    public function index(Request $request)
    {
        $query = FortuneReading::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        // กรองตามสถานะชำระเงิน
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->is_paid);
        }

        // กรองตาม AI provider
        if ($request->filled('ai_provider')) {
            $query->where('ai_provider', $request->ai_provider);
        }

        // กรองตามประเภทคำทำนาย (basic/deep)
        if ($request->filled('reading_type')) {
            $query->where('reading_type', $request->reading_type);
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $readings = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => FortuneReading::count(),
            'today' => FortuneReading::today()->count(),
            'deep' => FortuneReading::deep()->count(),
            'basic' => FortuneReading::basic()->count(),
            'paid' => FortuneReading::paid()->count(),
            'free' => FortuneReading::free()->count(),
        ];

        return view('admin.fortune.readings.index', [
            'readings' => $readings,
            'stats' => $stats,
            'pageTitle' => 'ประวัติการทำนาย',
        ]);
    }

    /**
     * แสดงรายละเอียดการทำนาย
     */
    public function show(FortuneReading $reading)
    {
        $reading->load('user');
        $reading->incrementViewCount();

        return view('admin.fortune.readings.show', [
            'reading' => $reading,
            'pageTitle' => 'รายละเอียดการทำนาย #' . $reading->id,
        ]);
    }

    /**
     * ลบการทำนาย
     */
    public function destroy(FortuneReading $reading)
    {
        $reading->delete();

        return redirect()
            ->route('admin.fortune.readings.index')
            ->with('success', 'ลบการทำนายสำเร็จ');
    }

    /**
     * ส่งออกข้อมูลเป็น CSV
     */
    public function export(Request $request)
    {
        $readings = FortuneReading::with('user')
            ->when($request->filled('ai_provider'), fn($q) => $q->where('ai_provider', $request->ai_provider))
            ->when($request->filled('is_paid'), fn($q) => $q->where('is_paid', $request->is_paid))
            ->when($request->filled('reading_type'), fn($q) => $q->where('reading_type', $request->reading_type))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'fortune_readings_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($readings) {
            $file = fopen('php://output', 'w');
            
            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Header
            fputcsv($file, ['ID', 'วันที่', 'ชื่อผู้ใช้', 'Facebook ID', 'คำถาม', 'ประเภทคำทำนาย', 'AI Provider', 'สถานะชำระเงิน', 'ราคา']);

            // Data
            foreach ($readings as $reading) {
                fputcsv($file, [
                    $reading->id,
                    $reading->created_at->format('Y-m-d H:i:s'),
                    $reading->facebook_user_name,
                    $reading->facebook_user_id,
                    implode(', ', $reading->questions),
                    $reading->reading_type === 'deep' ? 'เชิงลึก' : 'พื้นฐาน',
                    $reading->ai_provider,
                    $reading->is_paid ? 'ชำระแล้ว' : 'ฟรี',
                    $reading->amount_paid,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
