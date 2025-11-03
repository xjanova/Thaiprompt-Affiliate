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
     */
    public function index(Request $request)
    {
        // Check permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('view_kyc_verifications')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการดูข้อมูลการยืนยันตัวตน');
        }

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
     */
    public function show(KycVerification $kycVerification)
    {
        // Check permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('view_kyc_verifications')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการดูข้อมูลการยืนยันตัวตน');
        }

        $kycVerification->load(['user', 'reviewer']);

        return view('admin.kyc.show', compact('kycVerification'));
    }

    /**
     * Approve KYC verification
     */
    public function approve(Request $request, KycVerification $kycVerification)
    {
        // Check permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('approve_kyc')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการอนุมัติการยืนยันตัวตน');
        }

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

        // Update user's KYC status
        $kycVerification->user->update([
            'kyc_status' => 'approved',
            'kyc_verified_at' => now(),
        ]);

        return back()->with('success', 'อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว');
    }

    /**
     * Reject KYC verification
     */
    public function reject(Request $request, KycVerification $kycVerification)
    {
        // Check permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('approve_kyc')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการปฏิเสธการยืนยันตัวตน');
        }

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
     */
    public function destroy(KycVerification $kycVerification)
    {
        // Check permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('manage_kyc')) {
            abort(403, 'คุณไม่มีสิทธิ์ในการลบข้อมูลการยืนยันตัวตน');
        }

        // Delete KYC verification
        $kycVerification->delete();

        return redirect()->route('admin.kyc.index')
            ->with('success', 'ลบข้อมูลการยืนยันตัวตนเรียบร้อยแล้ว');
    }
}
