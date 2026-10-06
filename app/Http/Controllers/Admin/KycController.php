<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycAccessLog;
use App\Models\KycVerification;
use App\Services\Ekyc\EkycException;
use App\Services\Ekyc\EkycService;
use App\Services\KycAutoCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class KycController extends Controller
{
    /**
     * Display a listing of KYC verifications
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูรายการ
     * 🪪 (2026-10-04) ตัวกรอง "AI ส่งตรวจ" (ai=review) + ช่องทาง (method) · ไม่แสดงรอบ eKYC ที่ผู้ใช้ทำค้าง
     */
    public function index(Request $request)
    {
        // ✅ ตรวจสอบสิทธิ์ในการดูรายการ (ใช้ Policy แทน manual check)
        $this->authorize('viewAny', KycVerification::class);

        $query = KycVerification::with(['user', 'reviewer'])
            // รอบ eKYC ที่ยังทำไม่จบ = ข้อมูลชั่วคราวของผู้ใช้ ไม่ใช่คำขอให้ตรวจ
            ->where(function ($q) {
                $q->where('method', '!=', KycVerification::METHOD_EKYC)
                    ->orWhereNull('method')
                    ->orWhere('status', '!=', 'draft');
            });

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // 🪪 AI ส่งตรวจ = eKYC ที่ AI ไม่มั่นใจและยังรอแอดมิน
        if ($request->get('ai') === 'review') {
            $query->where('method', KycVerification::METHOD_EKYC)
                ->where('status', 'pending');
        }

        if (in_array($request->get('method'), [KycVerification::METHOD_EKYC, KycVerification::METHOD_MANUAL], true)) {
            $query->where('method', $request->get('method'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Pagination
        $perPage = min(100, max(5, (int) $request->get('per_page', 15)));
        $kycVerifications = $query->latest()->paginate($perPage)->withQueryString();

        // Get statistics
        $stats = [
            'pending' => KycVerification::pending()->count(),
            'approved' => KycVerification::approved()->count(),
            'rejected' => KycVerification::rejected()->count(),
            'total' => KycVerification::where(function ($q) {
                $q->where('method', '!=', KycVerification::METHOD_EKYC)->orWhereNull('method')->orWhere('status', '!=', 'draft');
            })->count(),
            'ai_review' => KycVerification::ekyc()->pending()->count(),
            'ai_approved_today' => KycVerification::ekyc()
                ->where('ai_decision', 'approved')
                ->where('processed_at', '>=', now()->startOfDay())
                ->count(),
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

        // 🪪 แถว AI eKYC → หน้าตรวจแบบใหม่ (รูปบัตร ↔ ใบหน้า · คะแนน AI · เหตุผล)
        if ($kycVerification->isEkyc()) {
            $ekyc = app(EkycService::class)->adminSummary($kycVerification);

            // ชื่อบนบัตร ↔ บัญชีธนาคาร / ผู้โอนในสลิป (ตัวช่วยเดิม — ไม่ส่งเลขบัตรเข้าไป ไม่ให้โชว์เลขเต็ม)
            $identityChecks = [];
            try {
                $probe = new KycVerification;
                $probe->setRelation('user', $kycVerification->user);
                $thai = KycAutoCheckService::normalizeName((string) $kycVerification->name_th);
                $parts = $thai !== '' ? explode(' ', $thai, 2) : [];
                $probe->extracted_data = [
                    'thai_first_name' => $parts[0] ?? null,
                    'thai_last_name' => $parts[1] ?? null,
                ];
                $identityChecks = collect(app(KycAutoCheckService::class)->run($probe)['checks'] ?? [])
                    ->whereIn('key', ['name_bank', 'name_slip'])
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::info('Admin eKYC: identity checks skipped', ['error' => class_basename($e)]);
            }

            $queue = KycVerification::ekyc()->pending()
                ->with('user')
                ->orderBy('processed_at')
                ->limit(30)
                ->get();

            $accessLogs = KycAccessLog::with('viewer')
                ->where('kyc_verification_id', $kycVerification->id)
                ->latest('id')
                ->limit(10)
                ->get();

            $aiApprovedToday = KycVerification::ekyc()
                ->where('ai_decision', 'approved')
                ->where('processed_at', '>=', now()->startOfDay())
                ->count();

            return view('admin.kyc.show-ekyc', compact('kycVerification', 'ekyc', 'identityChecks', 'queue', 'accessLogs', 'aiApprovedToday'));
        }

        // 🤖 ตรวจอัตโนมัติชั้นที่ 1 — checksum เลขบัตร / บัตรหมดอายุ /
        //    ชื่อบัตรเทียบบัญชีธนาคาร / ชื่อบัตรเทียบผู้โอนจากสลิปจริง
        //    (คำนวณสดทุกครั้งที่เปิดดู — ข้อมูลบัญชี/สลิปเปลี่ยนได้เรื่อยๆ)
        $autoChecks = app(\App\Services\KycAutoCheckService::class)->run($kycVerification);

        return view('admin.kyc.show', compact('kycVerification', 'autoChecks'));
    }

    /**
     * 🪪 รูปของ eKYC (ถอดรหัส) — แอดมินเท่านั้น · บันทึกการเปิดดูทุกครั้ง (PDPA)
     *
     * @param  string  $kind  card | card_face | best_frame
     */
    public function image(Request $request, KycVerification $kycVerification, string $kind): Response
    {
        $this->authorize('viewImages', $kycVerification);

        if (! $kycVerification->isEkyc() || ! in_array($kind, ['card', 'card_face', 'best_frame'], true)) {
            abort(404);
        }

        $bytes = app(EkycService::class)->decryptImage($kycVerification, $kind);
        if ($bytes === null) {
            abort(404);
        }

        KycAccessLog::record($kycVerification, $request->user(), $kind, $request);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($bytes),
            'Content-Disposition' => 'inline; filename="kyc-'.$kycVerification->id.'-'.$kind.'.jpg"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
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

        // 🪪 eKYC → EkycService (อัปเดตผู้ใช้ + ปิดคำขอค้าง + แจ้งผลแบบ kyc_result)
        if ($kycVerification->isEkyc()) {
            return $this->ekycDecide($request, $kycVerification, 'approved', null, 'อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว');
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
        if (! empty($kycVerification->extracted_data)) {
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
                if (! empty($extractedData[$extractedKey])) {
                    $userData[$userField] = $extractedData[$extractedKey];
                }
            }

            // Also update date_of_birth if not already set
            if (! empty($extractedData['birth_date']) && empty($kycVerification->user->date_of_birth)) {
                $userData['date_of_birth'] = $extractedData['birth_date'];
            }

            // Update name if not already set (use Thai name or English name)
            if (empty($kycVerification->user->name)) {
                if (! empty($extractedData['thai_first_name']) && ! empty($extractedData['thai_last_name'])) {
                    $userData['name'] = $extractedData['thai_first_name'].' '.$extractedData['thai_last_name'];
                } elseif (! empty($extractedData['english_first_name']) && ! empty($extractedData['english_last_name'])) {
                    $userData['name'] = $extractedData['english_first_name'].' '.$extractedData['english_last_name'];
                }
            }
        }

        // Update user's KYC status and profile data
        // ⚠️ ต้องใช้ forceFill() เพราะ kyc_status และ kyc_verified_at เป็น guarded fields
        $kycVerification->user->forceFill($userData)->save();

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

        // 🪪 eKYC → EkycService
        if ($kycVerification->isEkyc()) {
            return $this->ekycDecide($request, $kycVerification, 'rejected', $validated['rejection_reason'], 'ปฏิเสธการยืนยันตัวตนเรียบร้อยแล้ว');
        }

        // Update KYC verification
        $kycVerification->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Update user's KYC status
        // ⚠️ ต้องใช้ forceFill() เพราะ kyc_status เป็น guarded field
        $kycVerification->user->forceFill([
            'kyc_status' => 'rejected',
            'kyc_verified_at' => null,
        ])->save();

        return back()->with('success', 'ปฏิเสธการยืนยันตัวตนเรียบร้อยแล้ว');
    }

    /**
     * 🪪 ขอให้ผู้ใช้ถ่ายบัตร/ใบหน้าใหม่ (เฉพาะ eKYC ที่รอตรวจ)
     */
    public function requestRetake(Request $request, KycVerification $kycVerification)
    {
        $this->authorize('requestRetake', $kycVerification);

        if (! $kycVerification->isEkyc() || $kycVerification->status !== 'pending') {
            return back()->with('error', 'การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว');
        }

        $validated = $request->validate([
            'retake_note' => ['nullable', 'string', 'max:500'],
        ], [
            'retake_note.max' => 'หมายเหตุต้องไม่เกิน 500 ตัวอักษร',
        ]);

        return $this->ekycDecide($request, $kycVerification, 'retake', $validated['retake_note'] ?? null, 'ส่งคำขอให้ผู้ใช้ถ่ายใหม่เรียบร้อยแล้ว');
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

    /**
     * ตัดสินแถว eKYC ผ่าน EkycService แล้วกลับหน้าเดิมพร้อมข้อความไทย
     */
    private function ekycDecide(Request $request, KycVerification $kyc, string $decision, ?string $note, string $success)
    {
        try {
            app(EkycService::class)->adminDecide($kyc, $request->user(), $decision, $note);
        } catch (EkycException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Admin eKYC: decide failed', ['kyc_id' => $kyc->id, 'error' => class_basename($e)]);

            return back()->with('error', 'บันทึกผลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        return back()->with('success', $success);
    }
}
