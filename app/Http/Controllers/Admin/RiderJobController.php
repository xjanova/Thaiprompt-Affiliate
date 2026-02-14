<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RiderJobController - จัดการงานของไรเดอร์ในระบบ Admin
 *
 * รองรับการ:
 * - ดูรายการงานทั้งหมด พร้อมสถิติ
 * - กรองตามสถานะ, ประเภท, ไรเดอร์
 * - ดูรายละเอียดงาน
 * - ยกเลิกงาน
 * - มอบหมายงานใหม่
 */
class RiderJobController extends Controller
{
    /**
     * แสดงรายการงานทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = RiderJob::with(['rider', 'customer']);

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('rider', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามประเภท
        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        // กรองตามไรเดอร์
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // สถิติรวม
        $stats = [
            'total' => RiderJob::count(),
            'pending' => RiderJob::where('status', 'pending')->count(),
            'in_progress' => RiderJob::whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])->count(),
            'completed' => RiderJob::where('status', 'completed')->count(),
            'cancelled' => RiderJob::where('status', 'cancelled')->count(),
            'total_earnings' => RiderJob::where('status', 'completed')->sum('total_fee'),
        ];

        // ดึงข้อมูลพร้อม pagination
        $jobs = $query->latest()->paginate(20);

        // รายชื่อไรเดอร์สำหรับ filter
        $riders = Rider::where('status', 'approved')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('admin.rider-jobs.index', [
            'jobs' => $jobs,
            'stats' => $stats,
            'riders' => $riders,
            'pageTitle' => 'จัดการงานไรเดอร์',
        ]);
    }

    /**
     * แสดงรายละเอียดงาน
     *
     * @return \Illuminate\View\View
     */
    public function show(RiderJob $job)
    {
        $job->load(['rider', 'customer', 'locations']);

        // ประวัติตำแหน่งของงานนี้
        $locationHistory = $job->locations()
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.rider-jobs.show', [
            'job' => $job,
            'locationHistory' => $locationHistory,
            'pageTitle' => 'รายละเอียดงาน: #'.$job->job_number,
        ]);
    }

    /**
     * ยกเลิกงาน
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, RiderJob $job)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // ตรวจสอบว่างานยังไม่เสร็จ
        if (in_array($job->status, ['completed', 'cancelled'])) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถยกเลิกงานที่เสร็จสิ้นหรือถูกยกเลิกแล้วได้',
                ], 400);
            }

            return redirect()->back()->with('error', 'ไม่สามารถยกเลิกงานที่เสร็จสิ้นหรือถูกยกเลิกแล้วได้');
        }

        DB::beginTransaction();
        try {
            $job->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancellation_reason' => $request->reason,
            ]);

            // TODO: ส่ง notification ให้ไรเดอร์และลูกค้า

            // คืนสถานะไรเดอร์เป็น online
            if ($job->rider) {
                $job->rider->update(['availability' => 'online']);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ยกเลิกงานเรียบร้อย',
                ]);
            }

            return redirect()->back()->with('success', 'ยกเลิกงานเรียบร้อย');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * มอบหมายงานให้ไรเดอร์ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reassign(Request $request, RiderJob $job)
    {
        $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);

        // ตรวจสอบว่างานยังไม่เสร็จ
        if (in_array($job->status, ['completed', 'cancelled'])) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถมอบหมายงานที่เสร็จสิ้นหรือถูกยกเลิกแล้วได้',
                ], 400);
            }

            return redirect()->back()->with('error', 'ไม่สามารถมอบหมายงานที่เสร็จสิ้นหรือถูกยกเลิกแล้วได้');
        }

        $newRider = Rider::find($request->rider_id);
        if (! $newRider || $newRider->status !== 'approved') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไรเดอร์ไม่พร้อมให้บริการ',
                ], 400);
            }

            return redirect()->back()->with('error', 'ไรเดอร์ไม่พร้อมให้บริการ');
        }

        DB::beginTransaction();
        try {
            $oldRider = $job->rider;

            // คืนสถานะไรเดอร์เก่า
            if ($oldRider) {
                $oldRider->update(['availability' => 'online']);
            }

            // อัพเดทงาน
            $job->update([
                'rider_id' => $newRider->id,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // อัพเดทสถานะไรเดอร์ใหม่
            $newRider->update(['availability' => 'busy']);

            // TODO: ส่ง notification

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'มอบหมายงานให้ '.$newRider->full_name.' เรียบร้อย',
                ]);
            }

            return redirect()->back()->with('success', 'มอบหมายงานเรียบร้อย');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * แสดงสถิติรายวัน/รายเดือน
     *
     * @return \Illuminate\View\View
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // สถิติรายวัน
        if ($period === 'daily') {
            $stats = RiderJob::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_fee ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN rider_earnings ELSE 0 END) as rider_earnings')
            )
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // สถิติรายเดือน
            $stats = RiderJob::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_fee ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN rider_earnings ELSE 0 END) as rider_earnings')
            )
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();
        }

        // ไรเดอร์ยอดเยี่ยม
        $topRiders = Rider::select('riders.*')
            ->withCount(['jobs as completed_jobs_count' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            }])
            ->having('completed_jobs_count', '>', 0)
            ->orderBy('completed_jobs_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.rider-jobs.statistics', [
            'stats' => $stats,
            'topRiders' => $topRiders,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pageTitle' => 'สถิติงานไรเดอร์',
        ]);
    }
}
