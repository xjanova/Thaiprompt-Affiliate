<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * DeveloperRegistrationController
 *
 * จัดการการลงทะเบียนนักพัฒนาและ KYC
 */
class DeveloperRegistrationController extends Controller
{
    /**
     * แสดงฟอร์มลงทะเบียนนักพัฒนา
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegistrationForm()
    {
        $user = Auth::user();

        // ตรวจสอบว่าลงทะเบียนเป็นนักพัฒนาแล้วหรือยัง
        if ($user->developerProfile) {
            return redirect()
                ->route('developer.dashboard')
                ->with('info', 'คุณได้ลงทะเบียนเป็นนักพัฒนาแล้ว');
        }

        return view('developer.register', [
            'pageTitle' => 'ลงทะเบียนนักพัฒนา',
            'user' => $user,
        ]);
    }

    /**
     * บันทึกการลงทะเบียนนักพัฒนา
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $user = Auth::user();

        // ตรวจสอบว่าลงทะเบียนเป็นนักพัฒนาแล้วหรือยัง
        if ($user->developerProfile) {
            return redirect()
                ->route('developer.dashboard')
                ->with('error', 'คุณได้ลงทะเบียนเป็นนักพัฒนาแล้ว');
        }

        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            // ข้อมูลพื้นฐาน
            'display_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // ข้อมูล KYC
            'facebook_profile' => 'required|url|max:255',
            'line_id' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',

            // ข้อมูลบริษัท (optional)
            'company_registration_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',

            // เอกสาร KYC
            'id_card_image' => 'required|image|mimes:jpeg,png,jpg,pdf|max:5120',
            'company_registration_doc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], [
            // Custom error messages (Thai)
            'display_name.required' => 'กรุณากรอกชื่อที่แสดง',
            'facebook_profile.required' => 'กรุณากรอกลิงก์โปรไฟล์ Facebook',
            'facebook_profile.url' => 'ลิงก์ Facebook ไม่ถูกต้อง',
            'line_id.required' => 'กรุณากรอก LINE ID',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'id_card_image.required' => 'กรุณาอัพโหลดรูปบัตรประชาชน',
            'id_card_image.image' => 'ไฟล์บัตรประชาชนต้องเป็นรูปภาพ',
            'id_card_image.max' => 'ไฟล์บัตรประชาชนต้องไม่เกิน 5MB',
        ]);

        try {
            DB::beginTransaction();

            // อัพโหลดไฟล์
            $avatarUrl = null;
            $idCardImagePath = null;
            $companyDocPath = null;

            if ($request->hasFile('avatar')) {
                $avatarUrl = $request->file('avatar')->store('developer-avatars', 'public');
            }

            if ($request->hasFile('id_card_image')) {
                $idCardImagePath = $request->file('id_card_image')->store('developer-kyc', 'public');
            }

            if ($request->hasFile('company_registration_doc')) {
                $companyDocPath = $request->file('company_registration_doc')->store('developer-kyc', 'public');
            }

            // สร้าง Developer Profile
            $developerProfile = DeveloperProfile::create([
                'user_id' => $user->id,
                'display_name' => $validated['display_name'],
                'company_name' => $validated['company_name'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'website' => $validated['website'] ?? null,
                'avatar_url' => $avatarUrl,
                'facebook_profile' => $validated['facebook_profile'],
                'line_id' => $validated['line_id'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'company_registration_number' => $validated['company_registration_number'] ?? null,
                'tax_id' => $validated['tax_id'] ?? null,
                'company_address' => $validated['company_address'] ?? null,
                'id_card_image' => $idCardImagePath,
                'company_registration_doc' => $companyDocPath,
                'status' => DeveloperProfile::STATUS_PENDING,
                'commission_rate' => DeveloperProfile::DEFAULT_COMMISSION_RATE,
                'is_verified' => false,
                'is_active' => true,
            ]);

            DB::commit();

            // Log activity
            activity()
                ->performedOn($developerProfile)
                ->causedBy($user)
                ->log('ลงทะเบียนเป็นนักพัฒนา');

            return redirect()
                ->route('developer.pending')
                ->with('success', 'ลงทะเบียนเป็นนักพัฒนาสำเร็จ! รอการอนุมัติจากแอดมิน');

        } catch (\Exception $e) {
            DB::rollBack();

            // ลบไฟล์ที่อัพโหลด (ถ้ามี)
            if ($avatarUrl) {
                Storage::disk('public')->delete($avatarUrl);
            }
            if ($idCardImagePath) {
                Storage::disk('public')->delete($idCardImagePath);
            }
            if ($companyDocPath) {
                Storage::disk('public')->delete($companyDocPath);
            }

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * แสดงหน้ารอการอนุมัติ
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function pending()
    {
        $user = Auth::user();
        $developerProfile = $user->developerProfile;

        if (! $developerProfile) {
            return redirect()
                ->route('developer.register')
                ->with('error', 'กรุณาลงทะเบียนเป็นนักพัฒนาก่อน');
        }

        if ($developerProfile->isApproved()) {
            return redirect()
                ->route('developer.dashboard')
                ->with('info', 'บัญชีของคุณได้รับการอนุมัติแล้ว');
        }

        return view('developer.pending', [
            'pageTitle' => 'รอการอนุมัติ',
            'developerProfile' => $developerProfile,
        ]);
    }

    /**
     * แสดง Dashboard นักพัฒนา
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function dashboard()
    {
        $user = Auth::user();
        $developerProfile = $user->developerProfile;

        if (! $developerProfile) {
            return redirect()
                ->route('developer.register')
                ->with('error', 'กรุณาลงทะเบียนเป็นนักพัฒนาก่อน');
        }

        if ($developerProfile->isPending()) {
            return redirect()
                ->route('developer.pending')
                ->with('info', 'รอการอนุมัติจากแอดมิน');
        }

        if ($developerProfile->isRejected()) {
            return redirect()
                ->route('developer.register')
                ->with('error', 'บัญชีของคุณถูกปฏิเสธ: '.$developerProfile->rejection_reason);
        }

        // ดึงข้อมูลสถิติ
        $stats = [
            'total_products' => $developerProfile->total_products,
            'total_sales' => $developerProfile->total_sales,
            'total_earnings' => $developerProfile->total_earnings,
            'pending_products' => $developerProfile->products()->where('status', 'pending')->count(),
        ];

        // ดึงสินค้าล่าสุด
        $recentProducts = $developerProfile->products()
            ->latest()
            ->take(5)
            ->get();

        return view('developer.dashboard', [
            'pageTitle' => 'Developer Dashboard',
            'developerProfile' => $developerProfile,
            'stats' => $stats,
            'recentProducts' => $recentProducts,
        ]);
    }
}
