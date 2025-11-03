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
        try {
            $ocrService = new ThaiIdCardOcrService();
            $extractedData = $ocrService->extractData($idCardPath);

            // Validate ID card number if extracted
            if (!empty($extractedData['id_card_number'])) {
                if (!$ocrService->validateIdCardNumber($extractedData['id_card_number'])) {
                    Log::warning('Invalid ID card number checksum: ' . $extractedData['id_card_number']);
                }
            }

            Log::info('OCR extracted data', ['data' => $extractedData]);
        } catch (\Exception $e) {
            Log::error('OCR extraction failed: ' . $e->getMessage());
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

        return redirect()->route('user.kyc.index')
            ->with('success', 'ส่งคำขอยืนยันตัวตนเรียบร้อยแล้ว กรุณารอแอดมินตรวจสอบและอนุมัติ');
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
