<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // ค้นหาตามชื่อ / รหัสบิล / platform user id
        // รองรับ:
        //   - ชื่อลูกค้า (facebook_user_name)
        //   - รหัสบิล เช่น FTU-260425-T4022 (bill_reference)
        //   - Platform user ID (FB PSID / LINE userId)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_name', 'like', "%{$search}%")
                    ->orWhere('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('bill_reference', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดคำทำนาย
        if ($request->filled('category')) {
            $category = $request->category;
            $query->whereJsonContains('categories', $category);
        }

        // กรองตามสถานะ Conversation
        if ($request->filled('conversation_status')) {
            $query->where('conversation_status', $request->conversation_status);
        }

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
            // ⚠️ จำนวนบิลที่ชำระแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ
            'stuck_paid' => FortuneReading::where('is_paid', true)
                ->where('reading_type', 'deep')
                ->whereNull('deep_response')
                ->count(),
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
            'pageTitle' => 'รายละเอียดการทำนาย #'.$reading->id,
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
     * สถานะ reading สำหรับ polling จากหน้า show.blade
     *
     * Client poll endpoint นี้ทุก 3 วินาทีหลังกดปุ่ม "สร้างคำทำนาย"
     * เมื่อ ready=true → reload หน้า show ครั้งเดียว เพื่อดูคำทำนายเต็ม
     */
    public function status(FortuneReading $reading)
    {
        return response()->json([
            'id' => $reading->id,
            'conversation_status' => $reading->conversation_status,
            'has_deep_response' => ! empty($reading->deep_response),
            'is_paid' => (bool) $reading->is_paid,
            'updated_at' => $reading->updated_at?->toIso8601String(),
            // ready = สร้างคำทำนายเสร็จแล้ว (ไม่ว่าจะ status เป็น completed หรือยัง)
            'ready' => ! empty($reading->deep_response),
        ]);
    }

    /**
     * สร้างคำทำนายเชิงลึกใหม่ + ส่งให้ลูกค้า (Manual Retry)
     *
     * ใช้กรณี: ลูกค้าชำระเงินแล้ว แต่ระบบส่งคำทำนายไม่สำเร็จ
     * (เช่น background job ล้มเหลว, process crash, timeout)
     */
    public function retryDeepReading(FortuneReading $reading)
    {
        // ตรวจสอบเงื่อนไข: ต้องเป็น deep reading ที่ชำระเงินแล้ว
        if (! $reading->is_paid || $reading->reading_type !== 'deep') {
            return redirect()->back()->with('error', 'ไม่สามารถดำเนินการได้: ต้องเป็น deep reading ที่ชำระเงินแล้ว');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        if (empty($userId)) {
            return redirect()->back()->with('error', 'ไม่พบ user ID สำหรับส่งข้อความ');
        }

        // ถ้ามี deep_response อยู่แล้ว → clear + ตั้ง status=PAID เพื่อให้ banner "AI กำลังสร้าง..." แสดง
        // (Artisan command จะข้ามถ้ามี deep_response + status=completed)
        $reading->update([
            'deep_response' => null,
            'ai_response' => null,
            'conversation_status' => FortuneReading::STATUS_PAID,
        ]);

        Log::info('Admin: Manual retry deep reading queued', [
            'reading_id' => $reading->id,
            'platform' => $platform,
            'user_id' => $userId,
            'admin' => auth()->user()?->name,
        ]);

        // 🚀 Defer dispatch ไปหลัง response ส่งกลับ — กัน fastcgi_finish_request() ใน
        //    ProcessDeepFortuneReadingJob::dispatchSmart() ทำ response body ว่างเปล่า
        //    (เคสเดิม: คลิกปุ่ม → หน้าขาว เพราะ fastcgi_finish_request ถูกเรียกก่อน redirect)
        $readingId = $reading->id;
        register_shutdown_function(function () use ($readingId, $platform, $userId) {
            try {
                ProcessDeepFortuneReadingJob::dispatchSmart($readingId, null, $platform, $userId);
            } catch (\Throwable $e) {
                Log::error('Admin retry: dispatch failed in shutdown', [
                    'reading_id' => $readingId,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        // Redirect ไปหน้า show — banner "AI กำลังสร้าง..." + auto-reload จะ kick in ทันที
        return redirect()
            ->route('admin.fortune.readings.show', $reading)
            ->with('success', '🔮 เริ่มสร้างคำทำนายเชิงลึก... หน้าจะอัปเดตอัตโนมัติเมื่อเสร็จ');
    }

    /**
     * ส่งคำทำนายเชิงลึกที่มีอยู่แล้วซ้ำให้ลูกค้า (Manual Resend)
     *
     * ใช้กรณี: มีคำทำนายอยู่แล้ว แต่ส่งให้ลูกค้าไม่สำเร็จ
     * (เช่น Messenger/LINE error, ข้อความไม่ถึง)
     */
    public function resendDeepReading(FortuneReading $reading)
    {
        // ตรวจสอบว่ามี deep_response
        if (empty($reading->deep_response)) {
            return redirect()->back()->with('error', 'ไม่มีคำทำนายเชิงลึก กรุณาใช้ปุ่ม "สร้างคำทำนายใหม่" แทน');
        }

        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform ?: ((preg_match('/^U[0-9a-f]{32}$/i', $userId ?? '')) ? 'line' : 'facebook');

        if (empty($userId)) {
            return redirect()->back()->with('error', 'ไม่พบ user ID สำหรับส่งข้อความ');
        }

        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);

            // ส่ง Birth Chart ก่อน (ถ้ามี)
            if ($reading->reading_image_url) {
                try {
                    $platformService = $channelManager->getPlatform($platform);
                    if ($platformService) {
                        $platformService->sendImage($userId, $reading->reading_image_url);
                        usleep(500000); // รอ 0.5 วินาที
                    }
                } catch (\Exception $imgErr) {
                    Log::warning('Admin Resend: ส่ง chart image ไม่สำเร็จ', [
                        'error' => $imgErr->getMessage(),
                    ]);
                }
            }

            // ส่งคำทำนายเชิงลึก
            $channelManager->sendResponse($platform, $userId, [
                'action' => 'resend',
                'message' => $reading->deep_response,
            ], ['from_admin' => true]);

            // อัพเดท status เป็น completed ถ้ายังไม่ได้
            if ($reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }

            Log::info('Admin: Manual resend deep reading สำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'user_id' => $userId,
                'admin' => auth()->user()?->name,
            ]);

            return redirect()->back()->with('success', '✅ ส่งคำทำนายเชิงลึกให้ลูกค้าสำเร็จ!');

        } catch (\Exception $e) {
            Log::error('Admin: Manual resend deep reading ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'ส่งไม่สำเร็จ: '.$e->getMessage());
        }
    }

    /**
     * ส่งออกข้อมูลเป็น CSV
     */
    public function export(Request $request)
    {
        $readings = FortuneReading::with('user')
            ->when($request->filled('ai_provider'), fn ($q) => $q->where('ai_provider', $request->ai_provider))
            ->when($request->filled('is_paid'), fn ($q) => $q->where('is_paid', $request->is_paid))
            ->when($request->filled('reading_type'), fn ($q) => $q->where('reading_type', $request->reading_type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'fortune_readings_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($readings) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

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
