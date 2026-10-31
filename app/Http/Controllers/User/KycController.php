<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\Kyc\KycDocumentStorage;
use App\Services\OCR\ThaiIdCardOcrService;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KycController extends Controller
{
    /**
     * Display KYC verification status page
     *
     * แสดงหน้าสถานะการยืนยันตัวตน พร้อม sync kyc_status อัตโนมัติ
     *
     * kyc_status enum ในฐานข้อมูล: ['not_submitted', 'draft', 'pending', 'approved', 'rejected', 'expired']
     * - not_submitted = ยังไม่ได้ส่งเอกสาร
     * - draft = กำลัง upload รูป ยังไม่ submit
     * - pending = รอตรวจสอบ
     * - approved = ผ่านการยืนยัน
     * - rejected = ไม่ผ่านการยืนยัน
     * - expired = หมดอายุ
     */
    public function index()
    {
        $user = auth()->user();
        $kycVerification = $user->latestKycVerification;

        // Sync kyc_status จาก KycVerification (แก้ไขข้อมูลเก่าที่ไม่ได้ถูก sync)
        // ⚠️ เฉพาะ status ที่ users.kyc_status รองรับ (ไม่รวม 'draft' เพราะหมายถึงยังไม่ submit)
        // 'draft' จะถูก sync หลังจาก migration เพิ่ม enum value แล้ว
        $syncableStatuses = ['pending', 'approved', 'rejected'];
        if ($kycVerification &&
            in_array($kycVerification->status, $syncableStatuses) &&
            $user->kyc_status !== $kycVerification->status) {
            $user->forceFill(['kyc_status' => $kycVerification->status])->save();
            $user->refresh(); // รีเฟรชข้อมูล user
        }

        return view('user.kyc.index', compact('kycVerification'));
    }

    /**
     * Show KYC submission form
     */
    public function create()
    {
        $user = auth()->user();

        // Check if user already has pending or approved KYC
        if ($user->isKycPending()) {
            return redirect()->route('user.kyc.index')
                ->with('warning', 'คุณมีคำขอยืนยันตัวตนที่กำลังรออนุมัติอยู่แล้ว');
        }

        if ($user->isKycVerified()) {
            return redirect()->route('user.kyc.index')
                ->with('info', 'คุณได้ยืนยันตัวตนเรียบร้อยแล้ว');
        }

        return view('user.kyc.create');
    }

    /**
     * Store KYC submission
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if user already has pending or approved KYC
        if ($user->isKycPending() || $user->isKycVerified()) {
            return back()->with('error', 'ไม่สามารถส่งคำขอใหม่ได้ เนื่องจากมีคำขอที่กำลังดำเนินการหรือได้รับการอนุมัติแล้ว');
        }

        $validated = $request->validate([
            'id_card_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'], // Max 5MB
            'selfie_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'], // Max 5MB
        ], [
            'id_card_image.required' => 'กรุณาอัปโหลดรูปภาพบัตรประชาชน',
            'id_card_image.image' => 'ไฟล์บัตรประชาชนต้องเป็นรูปภาพเท่านั้น',
            'id_card_image.mimes' => 'ไฟล์บัตรประชาชนต้องเป็นไฟล์ jpeg, jpg หรือ png เท่านั้น',
            'id_card_image.max' => 'ขนาดไฟล์บัตรประชาชนต้องไม่เกิน 5MB',
            'selfie_image.required' => 'กรุณาอัปโหลดรูปถ่ายตัวเองพร้อมบัตรประชาชน',
            'selfie_image.image' => 'ไฟล์รูปถ่ายตัวเองต้องเป็นรูปภาพเท่านั้น',
            'selfie_image.mimes' => 'ไฟล์รูปถ่ายตัวเองต้องเป็นไฟล์ jpeg, jpg หรือ png เท่านั้น',
            'selfie_image.max' => 'ขนาดไฟล์รูปถ่ายตัวเองต้องไม่เกิน 5MB',
        ]);

        // ⚠️ IMPORTANT: ต้องทำ OCR ก่อน WebP conversion เพื่อคุณภาพที่ดีที่สุด
        // เก็บรูป original ชั่วคราวสำหรับ OCR
        // 🔒 (2026-10-29) เดิมวางบน public disk ด้วยนามสกุลจากชื่อไฟล์ที่ลูกค้าส่งมา (เช่น .php ที่ข้างในเป็น PNG จริง)
        //    และถ้า OCR โยน \Error ไฟล์ค้างถาวร → ตอนนี้ disk ส่วนตัว + นามสกุลจากเนื้อไฟล์ + ลบใน finally
        $idCardFile = $request->file('id_card_image');
        $tempIdCardPath = 'kyc-temp/'.Str::random(40).'.'.($idCardFile->extension() === 'png' ? 'png' : 'jpg');
        Storage::disk('local')->put($tempIdCardPath, file_get_contents($idCardFile->getRealPath()));

        // Extract data from ID card using OCR (ใช้รูปต้นฉบับ)
        $extractedData = null;
        $ocrError = null;

        try {
            $ocrService = new ThaiIdCardOcrService;
            $ocrResult = $ocrService->extractData($tempIdCardPath, 'local');

            if (! empty($ocrResult['success'])) {
                // OCR succeeded
                $extractedData = $ocrResult;

                // Validate ID card number if extracted
                if (! empty($extractedData['id_card_number'])) {
                    if (! $ocrService->validateIdCardNumber($extractedData['id_card_number'])) {
                        Log::warning('Invalid ID card number checksum', ['user_id' => $user->id, 'id_tail' => substr($extractedData['id_card_number'], -4)]);
                    }
                }

                // 🔒 ไม่ log ค่าในบัตร (เลขบัตร/ชื่อ/ที่อยู่) — เก็บแค่ว่าอ่านช่องไหนได้
                Log::info('OCR extracted data successfully', ['user_id' => $user->id, 'fields' => array_keys(array_filter($extractedData))]);
            } else {
                // OCR failed with detailed error
                $ocrError = [
                    'error' => $ocrResult['error'] ?? 'ไม่สามารถอ่านข้อมูลจากบัตรได้',
                    'error_code' => $ocrResult['error_code'] ?? 'UNKNOWN_ERROR',
                    'suggestion' => $ocrResult['suggestion'] ?? 'กรุณาลองถ่ายรูปใหม่',
                ];

                Log::warning('OCR extraction failed', $ocrError);

                // Store partial data if available
                if (! empty($ocrResult['partial_data'])) {
                    $extractedData = $ocrResult['partial_data'];
                    $extractedData['ocr_warning'] = $ocrError;
                }
            }
        } catch (\Throwable $e) {
            Log::error('OCR extraction exception: '.$e->getMessage());
            $ocrError = [
                'error' => 'เกิดข้อผิดพลาดในการประมวลผล',
                'error_code' => 'EXCEPTION',
                'suggestion' => 'กรุณาลองอัพโหลดใหม่อีกครั้ง',
            ];
        } finally {
            // ลบไฟล์ temp หลัง OCR (ทุกกรณี)
            Storage::disk('local')->delete($tempIdCardPath);
        }

        // Store images with WebP conversion (หลังจาก OCR) — ชื่อไฟล์สุ่มล้วน
        // 🐛 (2026-10-29) เดิมไม่มี try/catch: รูปเสียหาย/แปลงไม่ได้ → 500 และรูปบัตรที่แปลงไปแล้วค้างเป็นไฟล์กำพร้า
        // 🔒 (2026-10-29) เก็บบน private disk (kyc-private/{user_id}) — เดิมอยู่ public disk เปิดจาก /storage/… ได้ทุกคน
        $webpService = new WebPService;
        $documents = app(KycDocumentStorage::class);
        $directory = $documents->directoryFor($user->id);
        $idCardPath = null;

        try {
            $idCardPath = $webpService->convertAndStore($request->file('id_card_image'), $directory, 90, true, KycDocumentStorage::DISK)['path'];
            $selfiePath = $webpService->convertAndStore($request->file('selfie_image'), $directory, 90, true, KycDocumentStorage::DISK)['path'];
        } catch (\Throwable $e) {
            Log::warning('KYC image conversion failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $documents->delete($idCardPath);

            return back()->with('error', 'อ่านไฟล์รูปไม่ได้ กรุณาถ่ายรูปใหม่เป็น JPG หรือ PNG แล้วส่งอีกครั้ง');
        }

        // Create KYC verification record
        $kycVerification = KycVerification::create([
            'user_id' => $user->id,
            'id_card_image' => $idCardPath,
            'selfie_image' => $selfiePath,
            'status' => 'pending',
            'submitted_at' => now(),
            'extracted_data' => $extractedData,
        ]);

        // Update user's KYC status (kyc_status is guarded, use forceFill)
        $user->forceFill([
            'kyc_status' => 'pending',
        ])->save();

        // Prepare success message with OCR warning if applicable
        $successMessage = 'ส่งคำขอยืนยันตัวตนเรียบร้อยแล้ว กรุณารอแอดมินตรวจสอบและอนุมัติ';

        if ($ocrError) {
            // OCR failed, but submission succeeded
            return redirect()->route('user.kyc.index')
                ->with('warning', $successMessage)
                ->with('ocr_error', $ocrError['error'])
                ->with('ocr_suggestion', $ocrError['suggestion']);
        }

        return redirect()->route('user.kyc.index')
            ->with('success', $successMessage)
            ->with('ocr_success', $extractedData ? 'ระบบได้อ่านข้อมูลจากบัตรอัตโนมัติแล้ว' : null);
    }

    /**
     * Show specific KYC verification
     */
    public function show(KycVerification $kycVerification)
    {
        // Ensure user can only view their own KYC
        if ($kycVerification->user_id !== auth()->id()) {
            abort(403);
        }

        return view('user.kyc.show', compact('kycVerification'));
    }

    /**
     * 🔒 รูปบัตร/รูปคู่บัตรของตัวเอง (สตรีมจาก private disk · ห้าม cache)
     *
     * เจ้าของคำขอเท่านั้น — คนอื่น (รวมแอดมิน ที่ต้องดูผ่าน admin.kyc.image ซึ่งบันทึกการเปิดดู) ได้ 404
     * เพื่อไม่บอกว่ามีคำขอเลขนี้อยู่ · แถว AI eKYC = 404 (รูปเข้ารหัส เปิดได้เฉพาะแอดมิน)
     *
     * @param  string  $kind  id_card | selfie
     */
    public function image(KycVerification $kycVerification, string $kind, KycDocumentStorage $documents)
    {
        if ((int) $kycVerification->user_id !== (int) auth()->id() || $kycVerification->isEkyc()) {
            abort(404);
        }

        return $documents->response($kycVerification, $kind);
    }
}
