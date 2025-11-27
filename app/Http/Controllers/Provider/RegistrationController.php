<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * RegistrationController - ลงทะเบียนผู้ให้บริการ
 *
 * จัดการการลงทะเบียนเป็นผู้ให้บริการ, อัพเดทข้อมูล, ยืนยันตัวตน
 */
class RegistrationController extends Controller
{
    /**
     * หน้าลงทะเบียนเป็นผู้ให้บริการ
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showForm()
    {
        $user = auth()->user();

        // ตรวจสอบว่าลงทะเบียนเป็น provider แล้วหรือยัง
        $existingProvider = ServiceProvider::where('user_id', $user->id)->first();

        if ($existingProvider) {
            // ถ้าลงทะเบียนแล้วให้ไปหน้า dashboard
            if ($existingProvider->verification_status === 'approved') {
                return redirect()->route('provider.bookings.index')
                    ->with('info', 'คุณลงทะเบียนเป็นผู้ให้บริการแล้ว');
            }

            // ถ้ารอการอนุมัติให้แสดงหน้าสถานะ
            return view('provider.registration.status', [
                'provider' => $existingProvider,
                'pageTitle' => 'สถานะการลงทะเบียน',
            ]);
        }

        // ดึงข้อมูลสำหรับฟอร์ม
        $categories = ServiceCategory::active()->ordered()->get();
        $areas = ServiceArea::active()->orderBy('province')->get();

        return view('provider.register', [
            'categories' => $categories,
            'areas' => $areas,
            'user' => $user,
            'pageTitle' => 'สมัครเป็นผู้ให้บริการ',
        ]);
    }

    /**
     * บันทึกการลงทะเบียน
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // ตรวจสอบว่าลงทะเบียนแล้วหรือยัง
        if (ServiceProvider::where('user_id', $user->id)->exists()) {
            return redirect()->route('provider.register')
                ->with('error', 'คุณลงทะเบียนเป็นผู้ให้บริการแล้ว');
        }

        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'required|string|max:20',
            'id_card_number' => 'required|string|size:13',
            'id_card_photo' => 'required|image|max:5120', // 5MB max
            'profile_photo' => 'nullable|image|max:2048', // 2MB max
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:service_categories,id',
            'service_areas' => 'required|array|min:1',
            'service_areas.*' => 'exists:service_areas,id',
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:20',
            'bank_account_name' => 'required|string|max:255',
            'auto_accept' => 'boolean',
            'accept_terms' => 'required|accepted',
        ], [
            'display_name.required' => 'กรุณากรอกชื่อที่ใช้แสดง',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'id_card_number.required' => 'กรุณากรอกเลขบัตรประชาชน',
            'id_card_number.size' => 'เลขบัตรประชาชนต้องมี 13 หลัก',
            'id_card_photo.required' => 'กรุณาอัพโหลดรูปบัตรประชาชน',
            'id_card_photo.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'id_card_photo.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
            'categories.required' => 'กรุณาเลือกอย่างน้อย 1 หมวดหมู่',
            'service_areas.required' => 'กรุณาเลือกอย่างน้อย 1 พื้นที่ให้บริการ',
            'bank_name.required' => 'กรุณาเลือกธนาคาร',
            'bank_account_number.required' => 'กรุณากรอกเลขบัญชี',
            'bank_account_name.required' => 'กรุณากรอกชื่อบัญชี',
            'accept_terms.accepted' => 'กรุณายอมรับข้อกำหนดและเงื่อนไข',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated, $user) {
                // อัพโหลดรูปบัตรประชาชน
                $idCardPath = $request->file('id_card_photo')->store('providers/id-cards', 'private');

                // อัพโหลดรูปโปรไฟล์ (ถ้ามี)
                $profilePhotoPath = null;
                if ($request->hasFile('profile_photo')) {
                    $profilePhotoPath = $request->file('profile_photo')->store('providers/profiles', 'public');
                }

                // สร้าง provider
                $provider = ServiceProvider::create([
                    'user_id' => $user->id,
                    'owner_id' => $user->id, // self-owned provider
                    'name' => $validated['display_name'], // ชื่อจริงใช้ display_name
                    'display_name' => $validated['display_name'],
                    'description' => $validated['description'] ?? null,
                    'phone' => $validated['phone'],
                    'profile_photo' => $profilePhotoPath,
                    'id_card_number' => $validated['id_card_number'],
                    'id_card_photo' => $idCardPath,
                    'bank_name' => $validated['bank_name'],
                    'bank_account_number' => $validated['bank_account_number'],
                    'bank_account_name' => $validated['bank_account_name'],
                    'auto_accept' => $validated['auto_accept'] ?? false,
                    'auto_accept_bookings' => $validated['auto_accept'] ?? false,
                    'status' => 'offline',
                    'verification_status' => 'pending',
                    'is_active' => false, // รอการอนุมัติก่อน
                ]);

                // บันทึกหมวดหมู่ที่ให้บริการ
                $provider->categories()->attach($validated['categories']);

                // บันทึกพื้นที่ให้บริการ
                $provider->areas()->attach($validated['service_areas']);

                // บันทึก log
                activity()
                    ->performedOn($provider)
                    ->causedBy($user)
                    ->log('ลงทะเบียนเป็นผู้ให้บริการ');

                return redirect()->route('provider.register.status')
                    ->with('success', 'ลงทะเบียนเป็นผู้ให้บริการสำเร็จ! กรุณารอการตรวจสอบจากทีมงาน');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * หน้าสถานะการลงทะเบียน
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function status()
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register')
                ->with('info', 'คุณยังไม่ได้ลงทะเบียนเป็นผู้ให้บริการ');
        }

        // ถ้าอนุมัติแล้วให้ไป dashboard
        if ($provider->verification_status === 'approved' && $provider->is_active) {
            return redirect()->route('provider.bookings.index');
        }

        return view('provider.registration.status', [
            'provider' => $provider,
            'pageTitle' => 'สถานะการลงทะเบียน',
        ]);
    }

    /**
     * แก้ไขข้อมูลโปรไฟล์
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editProfile()
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register');
        }

        $categories = ServiceCategory::active()->ordered()->get();
        $areas = ServiceArea::active()->orderBy('province')->get();

        return view('provider.profile.edit', [
            'provider' => $provider,
            'categories' => $categories,
            'areas' => $areas,
            'pageTitle' => 'แก้ไขโปรไฟล์ผู้ให้บริการ',
        ]);
    }

    /**
     * อัพเดทข้อมูลโปรไฟล์
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'required|string|max:20',
            'profile_photo' => 'nullable|image|max:2048',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:service_categories,id',
            'service_areas' => 'required|array|min:1',
            'service_areas.*' => 'exists:service_areas,id',
            'auto_accept' => 'boolean',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated, $provider) {
                // อัพโหลดรูปโปรไฟล์ใหม่ (ถ้ามี)
                if ($request->hasFile('profile_photo')) {
                    // ลบรูปเก่า
                    if ($provider->profile_photo) {
                        Storage::disk('public')->delete($provider->profile_photo);
                    }
                    $validated['profile_photo'] = $request->file('profile_photo')->store('providers/profiles', 'public');
                }

                // อัพเดทข้อมูล
                $provider->update([
                    'display_name' => $validated['display_name'],
                    'description' => $validated['description'] ?? null,
                    'phone' => $validated['phone'],
                    'profile_photo' => $validated['profile_photo'] ?? $provider->profile_photo,
                    'auto_accept' => $validated['auto_accept'] ?? false,
                ]);

                // อัพเดทหมวดหมู่
                $provider->categories()->sync($validated['categories']);

                // อัพเดทพื้นที่
                $provider->areas()->sync($validated['service_areas']);

                return redirect()->route('provider.profile.edit')
                    ->with('success', 'อัพเดทข้อมูลสำเร็จ');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * อัพเดทข้อมูลบัญชีธนาคาร
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBankInfo(Request $request)
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:20',
            'bank_account_name' => 'required|string|max:255',
        ]);

        $provider->update($validated);

        return redirect()->back()
            ->with('success', 'อัพเดทข้อมูลบัญชีธนาคารสำเร็จ');
    }

    /**
     * Dashboard ผู้ให้บริการ
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function dashboard()
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider || $provider->verification_status !== 'approved') {
            return redirect()->route('provider.register');
        }

        // สถิติ
        $stats = [
            'total_bookings' => $provider->total_bookings,
            'total_earnings' => $provider->total_earnings ?? 0,
            'average_rating' => $provider->rating ?? 0,
            'total_reviews' => $provider->total_reviews ?? 0,
            'pending_bookings' => $provider->bookings()
                ->whereIn('status', ['notifying_provider', 'waiting_provider'])
                ->count(),
            'active_bookings' => $provider->bookings()
                ->whereIn('status', ['provider_accepted', 'provider_on_way', 'in_progress'])
                ->count(),
        ];

        // รายได้ล่าสุด
        $recentEarnings = $provider->bookings()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return view('provider.dashboard', [
            'provider' => $provider,
            'stats' => $stats,
            'recentEarnings' => $recentEarnings,
            'pageTitle' => 'Dashboard ผู้ให้บริการ',
        ]);
    }

    /**
     * หน้าตั้งค่า Provider
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function settings()
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register')
                ->with('error', 'กรุณาลงทะเบียนเป็นผู้ให้บริการก่อน');
        }

        $categories = ServiceCategory::active()->ordered()->get();
        $areas = ServiceArea::active()->orderBy('province')->get();

        return view('provider.settings.index', [
            'provider' => $provider,
            'categories' => $categories,
            'areas' => $areas,
            'pageTitle' => 'ตั้งค่า Provider',
        ]);
    }

    /**
     * อัพเดทตั้งค่า Provider
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        $provider = ServiceProvider::where('user_id', $user->id)->first();

        if (!$provider) {
            return redirect()->route('provider.register');
        }

        $validated = $request->validate([
            'auto_accept' => 'boolean',
            'notification_email' => 'boolean',
            'notification_line' => 'boolean',
            'notification_sms' => 'boolean',
            'max_distance_km' => 'nullable|numeric|min:1|max:100',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:service_categories,id',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'exists:service_areas,id',
        ]);

        try {
            return DB::transaction(function () use ($validated, $provider) {
                // อัพเดทการตั้งค่าพื้นฐาน
                $provider->update([
                    'auto_accept' => $validated['auto_accept'] ?? false,
                    'notification_email' => $validated['notification_email'] ?? true,
                    'notification_line' => $validated['notification_line'] ?? true,
                    'notification_sms' => $validated['notification_sms'] ?? false,
                    'max_distance_km' => $validated['max_distance_km'] ?? null,
                    'working_hours_start' => $validated['working_hours_start'] ?? null,
                    'working_hours_end' => $validated['working_hours_end'] ?? null,
                ]);

                // อัพเดทหมวดหมู่ถ้ามีการส่งมา
                if (isset($validated['categories'])) {
                    $provider->categories()->sync($validated['categories']);
                }

                // อัพเดทพื้นที่ถ้ามีการส่งมา
                if (isset($validated['service_areas'])) {
                    $provider->areas()->sync($validated['service_areas']);
                }

                return redirect()->route('provider.settings.index')
                    ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
