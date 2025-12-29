<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;

/**
 * ServiceProviderController - จัดการผู้ให้บริการ (Admin)
 *
 * ดู, อนุมัติ, ปฏิเสธ, ระงับผู้ให้บริการ
 */
class ServiceProviderController extends Controller
{
    /**
     * แสดงรายการผู้ให้บริการทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = ServiceProvider::with(['user', 'services']);

        // กรองตามสถานะการยืนยัน
        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        // กรองตามสถานะการใช้งาน
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // ค้นหา
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
                    ->orWhereHas('user', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('email', 'like', "%{$request->search}%");
                    });
            });
        }

        $providers = $query->latest()->paginate(20);

        // สถิติ
        $stats = [
            'total' => ServiceProvider::count(),
            'pending' => ServiceProvider::where('verification_status', 'pending')->count(),
            'approved' => ServiceProvider::where('verification_status', 'approved')->count(),
            'rejected' => ServiceProvider::where('verification_status', 'rejected')->count(),
            'active' => ServiceProvider::where('is_active', true)->count(),
            'inactive' => ServiceProvider::where('is_active', false)->count(),
        ];

        return view('admin.service-providers.index', [
            'providers' => $providers,
            'stats' => $stats,
            'pageTitle' => 'จัดการผู้ให้บริการ',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างผู้ให้บริการใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.service-providers.create', [
            'pageTitle' => 'เพิ่มผู้ให้บริการใหม่',
        ]);
    }

    /**
     * บันทึกผู้ให้บริการใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'id_card_number' => 'nullable|string|max:20',
        ]);

        $validated['verification_status'] = 'approved';
        $validated['is_active'] = true;

        ServiceProvider::create($validated);

        return redirect()
            ->route('admin.service-providers.index')
            ->with('success', 'เพิ่มผู้ให้บริการสำเร็จ');
    }

    /**
     * แสดงรายละเอียดผู้ให้บริการ
     *
     * @return \Illuminate\View\View
     */
    public function show(ServiceProvider $serviceProvider)
    {
        $serviceProvider->load([
            'user',
            'services',
            'bookings' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('admin.service-providers.show', [
            'provider' => $serviceProvider,
            'pageTitle' => 'รายละเอียดผู้ให้บริการ: '.$serviceProvider->name,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขผู้ให้บริการ
     *
     * @return \Illuminate\View\View
     */
    public function edit(ServiceProvider $serviceProvider)
    {
        return view('admin.service-providers.edit', [
            'provider' => $serviceProvider,
            'pageTitle' => 'แก้ไขผู้ให้บริการ: '.$serviceProvider->name,
        ]);
    }

    /**
     * อัพเดทผู้ให้บริการ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ServiceProvider $serviceProvider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $serviceProvider->update($validated);

        return redirect()
            ->route('admin.service-providers.index')
            ->with('success', 'อัพเดทผู้ให้บริการสำเร็จ');
    }

    /**
     * ลบผู้ให้บริการ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ServiceProvider $serviceProvider)
    {
        $serviceProvider->delete();

        return redirect()
            ->route('admin.service-providers.index')
            ->with('success', 'ลบผู้ให้บริการสำเร็จ');
    }

    /**
     * อนุมัติผู้ให้บริการ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request, ServiceProvider $serviceProvider)
    {
        $serviceProvider->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'is_active' => true,
        ]);

        // TODO: ส่ง notification ไปยังผู้ให้บริการ

        return redirect()
            ->back()
            ->with('success', 'อนุมัติผู้ให้บริการสำเร็จ');
    }

    /**
     * ปฏิเสธผู้ให้บริการ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, ServiceProvider $serviceProvider)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $serviceProvider->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        // TODO: ส่ง notification ไปยังผู้ให้บริการ

        return redirect()
            ->back()
            ->with('success', 'ปฏิเสธผู้ให้บริการสำเร็จ');
    }

    /**
     * เปิด/ปิดการใช้งานผู้ให้บริการ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(ServiceProvider $serviceProvider)
    {
        $serviceProvider->update([
            'is_active' => ! $serviceProvider->is_active,
        ]);

        $status = $serviceProvider->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status}ผู้ให้บริการสำเร็จ");
    }
}
