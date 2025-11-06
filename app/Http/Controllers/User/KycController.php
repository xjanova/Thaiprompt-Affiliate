<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\OCR\ThaiIdCardOcrService;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    /**
     * Display KYC verification status page
     */
    public function index()
    {
        $user = auth()->user();
        $kycVerification = $user->latestKycVerification;

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

        // Store images with WebP conversion
        $webpService = new WebPService();

        // Convert ID card image to WebP
        $idCardResult = $webpService->convertAndStore(
            $request->file('id_card_image'),
            'kyc/id-cards',
            90 // High quality for ID cards
        );
        $idCardPath = $idCardResult['path'];

        // Convert selfie image to WebP
        $selfieResult = $webpService->convertAndStore(
            $request->file('selfie_image'),
            'kyc/selfies',
            90 // High quality for selfies
        );
        $selfiePath = $selfieResult['path'];

        // Extract data from ID card using OCR
        $extractedData = null;
        $ocrError = null;

        try {
            $ocrService = new ThaiIdCardOcrService();
            $ocrResult = $ocrService->extractData($idCardPath);

            if (!empty($ocrResult['success'])) {
                // OCR succeeded
                $extractedData = $ocrResult;

                // Validate ID card number if extracted
                if (!empty($extractedData['id_card_number'])) {
                    if (!$ocrService->validateIdCardNumber($extractedData['id_card_number'])) {
                        Log::warning('Invalid ID card number checksum: ' . $extractedData['id_card_number']);
                    }
                }

                Log::info('OCR extracted data successfully', ['data' => $extractedData]);
            } else {
                // OCR failed with detailed error
                $ocrError = [
                    'error' => $ocrResult['error'] ?? 'ไม่สามารถอ่านข้อมูลจากบัตรได้',
                    'error_code' => $ocrResult['error_code'] ?? 'UNKNOWN_ERROR',
                    'suggestion' => $ocrResult['suggestion'] ?? 'กรุณาลองถ่ายรูปใหม่'
                ];

                Log::warning('OCR extraction failed', $ocrError);

                // Store partial data if available
                if (!empty($ocrResult['partial_data'])) {
                    $extractedData = $ocrResult['partial_data'];
                    $extractedData['ocr_warning'] = $ocrError;
                }
            }
        } catch (\Exception $e) {
            Log::error('OCR extraction exception: ' . $e->getMessage());
            $ocrError = [
                'error' => 'เกิดข้อผิดพลาดในการประมวลผล',
                'error_code' => 'EXCEPTION',
                'suggestion' => 'กรุณาลองอัพโหลดใหม่อีกครั้ง'
            ];
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

        // Update user's KYC status
        $user->update([
            'kyc_status' => 'pending',
        ]);

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
}
