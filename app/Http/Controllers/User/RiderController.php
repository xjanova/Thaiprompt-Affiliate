<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RiderController - จัดการไรเดอร์สำหรับ User
 *
 * รองรับการ:
 * - ดู Dashboard ไรเดอร์
 * - สมัครเป็นไรเดอร์
 * - ติดตามสถานะการสมัคร
 * - อัพโหลดเอกสาร
 * - ดูงานและรายได้
 */
class RiderController extends Controller
{
    /**
     * หน้า Dashboard ไรเดอร์
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        // ถ้ายังไม่ได้สมัคร ไปหน้าสมัคร
        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        // ถ้ารอตรวจสอบ ไปหน้าติดตามสถานะ
        if ($rider->status === 'pending') {
            return redirect()->route('user.rider.status');
        }

        // ถ้าถูกปฏิเสธ ไปหน้าติดตามสถานะ
        if ($rider->status === 'rejected') {
            return redirect()->route('user.rider.status');
        }

        // สถิติ
        $stats = [
            'total_jobs' => $rider->total_jobs ?? 0,
            'completed_jobs' => $rider->completed_jobs ?? 0,
            'cancelled_jobs' => $rider->cancelled_jobs ?? 0,
            'total_earnings' => $rider->total_earnings ?? 0,
            'rating' => $rider->rating ?? 0,
            'rating_count' => $rider->rating_count ?? 0,
        ];

        // งานล่าสุด
        $recentJobs = RiderJob::where('rider_id', $rider->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('user.rider.dashboard', [
            'rider' => $rider,
            'stats' => $stats,
            'recentJobs' => $recentJobs,
            'pageTitle' => 'Dashboard ไรเดอร์',
        ]);
    }

    /**
     * หน้าสมัครเป็นไรเดอร์
     *
     * @return \Illuminate\View\View
     */
    public function register()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        // ถ้าสมัครแล้ว ไปหน้าติดตามสถานะ
        if ($rider) {
            return redirect()->route('user.rider.status');
        }

        return view('user.rider.register', [
            'pageTitle' => 'สมัครเป็นไรเดอร์',
        ]);
    }

    /**
     * บันทึกการสมัครไรเดอร์
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitRegistration(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'id_card_number' => 'required|string|size:13',
            'birth_date' => 'required|date|before:-18 years',
            'address' => 'required|string|max:500',
            'vehicle_type' => 'required|in:motorcycle,car,bicycle,walk',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:100',
            'vehicle_color' => 'nullable|string|max:50',
        ], [
            'birth_date.before' => 'คุณต้องมีอายุอย่างน้อย 18 ปี',
            'id_card_number.size' => 'เลขบัตรประชาชนต้องมี 13 หลัก',
        ]);

        $user = Auth::user();

        // ตรวจสอบว่าสมัครแล้วหรือยัง
        if (Rider::where('user_id', $user->id)->exists()) {
            return redirect()->route('user.rider.status')
                ->with('warning', 'คุณสมัครเป็นไรเดอร์แล้ว');
        }

        // สร้างข้อมูลไรเดอร์
        $rider = Rider::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'id_card_number' => $request->id_card_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_color' => $request->vehicle_color,
            'status' => 'pending',
        ]);

        // ส่ง notification
        $notificationService = app(NotificationService::class);
        $notificationService->create(
            $user,
            'rider_registration',
            'ส่งใบสมัครไรเดอร์สำเร็จ',
            'ระบบกำลังตรวจสอบข้อมูลของคุณ โปรดรอการอนุมัติ',
            ['rider_id' => $rider->id],
            route('user.rider.status'),
            'ติดตามสถานะ',
            'normal',
            false,
            false,
            'fas fa-motorcycle',
            'blue'
        );

        return redirect()->route('user.rider.documents')
            ->with('success', 'ส่งใบสมัครสำเร็จ กรุณาอัพโหลดเอกสาร');
    }

    /**
     * หน้าติดตามสถานะการสมัคร
     *
     * @return \Illuminate\View\View
     */
    public function status()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        // ถ้าได้รับการอนุมัติแล้ว ไป dashboard
        if ($rider->status === 'approved') {
            return redirect()->route('user.rider.dashboard');
        }

        return view('user.rider.status', [
            'rider' => $rider,
            'pageTitle' => 'ติดตามสถานะการสมัคร',
        ]);
    }

    /**
     * หน้าอัพโหลดเอกสาร
     *
     * @return \Illuminate\View\View
     */
    public function documents()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        return view('user.rider.documents', [
            'rider' => $rider,
            'pageTitle' => 'อัพโหลดเอกสาร',
        ]);
    }

    /**
     * อัพโหลดเอกสาร
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:id_card,driver_license,vehicle_registration,profile',
            'document' => 'required|image|max:10240',
        ]);

        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลไรเดอร์'], 404);
            }

            return redirect()->route('user.rider.register');
        }

        // อัพโหลดไฟล์
        $path = $request->file('document')->store(
            "riders/{$rider->id}/documents",
            'public'
        );

        // อัพเดทข้อมูล
        $columnMap = [
            'id_card' => 'id_card_image',
            'driver_license' => 'driver_license_image',
            'vehicle_registration' => 'vehicle_registration_image',
            'profile' => 'profile_image',
        ];

        $column = $columnMap[$request->document_type];
        $rider->update([$column => $path]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดเอกสารสำเร็จ',
                'path' => $path,
            ]);
        }

        return redirect()->back()->with('success', 'อัพโหลดเอกสารสำเร็จ');
    }

    /**
     * รายการงาน
     *
     * @return \Illuminate\View\View
     */
    public function jobs(Request $request)
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider || $rider->status !== 'approved') {
            return redirect()->route('user.rider.status');
        }

        $query = RiderJob::where('rider_id', $rider->id);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate(15);

        return view('user.rider.jobs', [
            'rider' => $rider,
            'jobs' => $jobs,
            'pageTitle' => 'งานของฉัน',
        ]);
    }

    /**
     * รายละเอียดงาน
     *
     * @return \Illuminate\View\View
     */
    public function showJob(RiderJob $job)
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider || $job->rider_id !== $rider->id) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง');
        }

        return view('user.rider.job-detail', [
            'job' => $job,
            'pageTitle' => 'รายละเอียดงาน #'.$job->job_number,
        ]);
    }

    /**
     * หน้ารายได้
     *
     * @return \Illuminate\View\View
     */
    public function earnings()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider || $rider->status !== 'approved') {
            return redirect()->route('user.rider.status');
        }

        // รายได้รายวัน (7 วันล่าสุด)
        $dailyEarnings = RiderJob::where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(completed_at) as date, SUM(rider_earnings) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // รายได้เดือนนี้
        $monthlyEarnings = RiderJob::where('rider_id', $rider->id)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->sum('rider_earnings');

        return view('user.rider.earnings', [
            'rider' => $rider,
            'dailyEarnings' => $dailyEarnings,
            'monthlyEarnings' => $monthlyEarnings,
            'pageTitle' => 'รายได้ของฉัน',
        ]);
    }

    /**
     * หน้าตั้งค่า
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        return view('user.rider.settings', [
            'rider' => $rider,
            'pageTitle' => 'ตั้งค่าไรเดอร์',
        ]);
    }

    /**
     * อัพเดทตั้งค่า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'vehicle_type' => 'required|in:motorcycle,car,bicycle,walk',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:100',
            'vehicle_color' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        $rider->update([
            'phone' => $request->phone,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_color' => $request->vehicle_color,
        ]);

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
