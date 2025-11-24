<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Http\Request;

class KycController extends Controller
{
    /**
     * Display a listing of KYC verifications
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูรายการ
     */
    public function index(Request $request)
    {
        // ✅ ตรวจสอบสิทธิ์ในการดูรายการ (ใช้ Policy แทน manual check)
        $this->authorize('viewAny', KycVerification::class);

        $query = KycVerification::with(['user', 'reviewer']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $kycVerifications = $query->latest()->paginate($perPage)->withQueryString();

        // Get statistics
        $stats = [
            'pending' => KycVerification::pending()->count(),
            'approved' => KycVerification::approved()->count(),
            'rejected' => KycVerification::rejected()->count(),
            'total' => KycVerification::count(),
        ];

        return view('admin.kyc.index', compact('kycVerifications', 'stats'));
    }

    /**
     * Display the specified KYC verification
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูข้อมูล
     */
    public function show(KycVerification $kycVerification)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนดู (ใช้ Policy แทน manual check)
        $this->authorize('view', $kycVerification);

        $kycVerification->load(['user', 'reviewer']);

        return view('admin.kyc.show', compact('kycVerification'));
    }

    /**
     * Approve KYC verification
     *
     * ⚠️ CRITICAL: การอนุมัติ KYC เป็นเรื่องสำคัญมาก
     */
    public function approve(Request $request, KycVerification $kycVerification)
    {
        // ✅ ตรวจสอบสิทธิ์ในการอนุมัติ (ใช้ Policy แทน manual check)
        $this->authorize('approve', $kycVerification);

        // Check if already processed
        if ($kycVerification->status !== 'pending') {
            return back()->with('error', 'การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว');
        }

        // Update KYC verification
        $kycVerification->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        // Prepare user data update
        $userData = [
            'kyc_status' => 'approved',
            'kyc_verified_at' => now(),
        ];

        // Auto-fill profile from extracted OCR data if available
        if (!empty($kycVerification->extracted_data)) {
            $extractedData = $kycVerification->extracted_data;

            // Map extracted data to user fields
            $fieldMapping = [
                'id_card_number' => 'id_card_number',
                'thai_first_name' => 'thai_first_name',
                'thai_last_name' => 'thai_last_name',
                'english_first_name' => 'english_first_name',
                'english_last_name' => 'english_last_name',
                'birth_date' => 'id_card_birth_date',
                'religion' => 'id_card_religion',
                'address' => 'id_card_address',
                'issue_date' => 'id_card_issue_date',
                'expiry_date' => 'id_card_expiry_date',
            ];

            foreach ($fieldMapping as $extractedKey => $userField) {
                if (!empty($extractedData[$extractedKey])) {
                    $userData[$userField] = $extractedData[$extractedKey];
                }
            }

            // Also update date_of_birth if not already set
            if (!empty($extractedData['birth_date']) && empty($kycVerification->user->date_of_birth)) {
                $userData['date_of_birth'] = $extractedData['birth_date'];
            }

            // Update name if not already set (use Thai name or English name)
            if (empty($kycVerification->user->name)) {
                if (!empty($extractedData['thai_first_name']) && !empty($extractedData['thai_last_name'])) {
                    $userData['name'] = $extractedData['thai_first_name'] . ' ' . $extractedData['thai_last_name'];
                } elseif (!empty($extractedData['english_first_name']) && !empty($extractedData['english_last_name'])) {
                    $userData['name'] = $extractedData['english_first_name'] . ' ' . $extractedData['english_last_name'];
                }
            }
        }

        // Update user's KYC status and profile data
        $kycVerification->user->update($userData);

        return back()->with('success', 'อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว และข้อมูลโปรไฟล์ได้ถูกอัปเดตอัตโนมัติ');
    }

    /**
     * Reject KYC verification
     *
     * ⚠️ CRITICAL: การปฏิเสธ KYC ต้องมีเหตุผล
     */
    public function reject(Request $request, KycVerification $kycVerification)
    {
        // ✅ ตรวจสอบสิทธิ์ในการปฏิเสธ (ใช้ Policy แทน manual check)
        $this->authorize('reject', $kycVerification);

        // Check if already processed
        if ($kycVerification->status !== 'pending') {
            return back()->with('error', 'การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => 'กรุณาระบุเหตุผลในการปฏิเสธ',
            'rejection_reason.max' => 'เหตุผลต้องไม่เกิน 1000 ตัวอักษร',
        ]);

        // Update KYC verification
        $kycVerification->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Update user's KYC status
        $kycVerification->user->update([
            'kyc_status' => 'rejected',
            'kyc_verified_at' => null,
        ]);

        return back()->with('success', 'ปฏิเสธการยืนยันตัวตนเรียบร้อยแล้ว');
    }

    /**
     * Delete KYC verification
     *
     * ⚠️ SECURITY: ป้องกัน IDOR - ตรวจสอบสิทธิ์ก่อนลบ
     */
    public function destroy(KycVerification $kycVerification)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ (ใช้ Policy แทน manual check)
        $this->authorize('delete', $kycVerification);

        // Delete KYC verification
        $kycVerification->delete();

        return redirect()->route('admin.kyc.index')
            ->with('success', 'ลบข้อมูลการยืนยันตัวตนเรียบร้อยแล้ว');
    }
}
