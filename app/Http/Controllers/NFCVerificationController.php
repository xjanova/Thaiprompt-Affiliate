<?php

namespace App\Http\Controllers;

use App\Models\NFCCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

/**
 * NFCVerificationController
 *
 * จัดการระบบตรวจสอบความถูกต้องของบัตร NFC แบบ Public
 * ผู้ใช้ทั่วไปสามารถเข้าถึงได้โดยไม่ต้อง login
 */
class NFCVerificationController extends Controller
{
    /**
     * แสดงหน้าตรวจสอบบัตร NFC
     *
     * @param string|null $cardNumber หมายเลขบัตร (optional)
     * @return \Illuminate\View\View
     */
    public function show($cardNumber = null)
    {
        $card = null;

        if ($cardNumber) {
            // ดึงข้อมูลบัตร (ไม่แสดงข้อมูลที่เป็นความลับ)
            $card = NFCCard::where('card_number', $cardNumber)
                ->where('status', 'active')
                ->select([
                    'id',
                    'card_number',
                    'card_type',
                    'member_id',
                    'issued_date',
                    'expiry_date',
                    'status',
                    'nfc_written_at'
                ])
                ->first();
        }

        return view('nfc.verify', [
            'cardNumber' => $cardNumber,
            'card' => $card,
            'pageTitle' => 'ตรวจสอบบัตร NFC',
        ]);
    }

    /**
     * ตรวจสอบความถูกต้องของบัตร NFC (API)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        // ตรวจสอบข้อมูลที่ส่งมา
        $validated = $request->validate([
            'card_number' => 'required|string',
            'anti_counterfeit_code' => 'required|string',
            'signature' => 'required|string',
            'uid' => 'nullable|string',
        ]);

        $cardNumber = $validated['card_number'];
        $receivedCode = $validated['anti_counterfeit_code'];
        $receivedSignature = $validated['signature'];
        $uid = $validated['uid'] ?? null;

        // ค้นหาบัตรในระบบ
        $card = NFCCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return $this->logAndRespond(
                null,
                $cardNumber,
                'full',
                false,
                false,
                false,
                'ไม่พบบัตรในระบบ',
                $request
            );
        }

        // ตรวจสอบสถานะบัตร
        if ($card->status !== 'active') {
            return $this->logAndRespond(
                $card->id,
                $cardNumber,
                'full',
                false,
                false,
                false,
                'บัตรไม่อยู่ในสถานะใช้งาน',
                $request
            );
        }

        // ตรวจสอบวันหมดอายุ
        if ($card->expiry_date && now()->isAfter($card->expiry_date)) {
            return $this->logAndRespond(
                $card->id,
                $cardNumber,
                'full',
                false,
                false,
                false,
                'บัตรหมดอายุแล้ว',
                $request
            );
        }

        // ตรวจสอบ Digital Signature
        $secret = config('app.key');
        $expectedSignature = hash_hmac('sha256', $receivedCode, $secret);
        $signatureValid = hash_equals($expectedSignature, $receivedSignature);

        // ตรวจสอบ UID (ถ้ามี)
        $uidMatch = true;
        if ($uid && $card->nfc_uid) {
            $uidMatch = ($uid === $card->nfc_uid);
        }

        // สรุปผลการตรวจสอบ
        $verified = $signatureValid && $uidMatch;
        $errorMessage = null;

        if (!$signatureValid) {
            $errorMessage = 'รหัสป้องกันปลอมไม่ถูกต้อง';
        } elseif (!$uidMatch) {
            $errorMessage = 'UID ของบัตรไม่ตรงกับข้อมูลในระบบ';
        }

        return $this->logAndRespond(
            $card->id,
            $cardNumber,
            'full',
            $verified,
            $signatureValid,
            $uidMatch,
            $errorMessage,
            $request,
            [
                'card_type' => $card->card_type,
                'issued_date' => $card->issued_date?->format('Y-m-d'),
                'expiry_date' => $card->expiry_date?->format('Y-m-d'),
                'member_id' => $card->member_id,
            ]
        );
    }

    /**
     * ตรวจสอบด่วน (Quick Verification) - เช็คเฉพาะ signature
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickVerify(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'anti_counterfeit_code' => 'required|string',
            'signature' => 'required|string',
        ]);

        $cardNumber = $validated['card_number'];
        $receivedCode = $validated['anti_counterfeit_code'];
        $receivedSignature = $validated['signature'];

        // ค้นหาบัตร
        $card = NFCCard::where('card_number', $cardNumber)
            ->where('status', 'active')
            ->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'ไม่พบบัตรในระบบ',
            ]);
        }

        // ตรวจสอบ Signature
        $secret = config('app.key');
        $expectedSignature = hash_hmac('sha256', $receivedCode, $secret);
        $signatureValid = hash_equals($expectedSignature, $receivedSignature);

        // Log verification
        $this->logVerification(
            $card->id,
            $cardNumber,
            'signature',
            $signatureValid,
            $signatureValid,
            null,
            null,
            null,
            $request
        );

        return response()->json([
            'success' => true,
            'verified' => $signatureValid,
            'message' => $signatureValid ? 'บัตรถูกต้อง' : 'บัตรไม่ถูกต้อง',
            'card_number' => $cardNumber,
        ]);
    }

    /**
     * บันทึก log การตรวจสอบและส่ง response
     *
     * @param int|null $cardId
     * @param string $cardNumber
     * @param string $verificationType
     * @param bool $verified
     * @param bool $signatureValid
     * @param bool|null $uidMatch
     * @param string|null $errorMessage
     * @param Request $request
     * @param array $additionalData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function logAndRespond(
        $cardId,
        $cardNumber,
        $verificationType,
        $verified,
        $signatureValid,
        $uidMatch,
        $errorMessage,
        Request $request,
        array $additionalData = []
    ) {
        // บันทึก log
        $this->logVerification(
            $cardId,
            $cardNumber,
            $verificationType,
            $verified,
            $signatureValid,
            $uidMatch,
            $errorMessage,
            $additionalData,
            $request
        );

        // เตรียม response
        $response = [
            'success' => true,
            'verified' => $verified,
            'card_number' => $cardNumber,
            'checks' => [
                'signature_valid' => $signatureValid,
                'uid_match' => $uidMatch,
            ],
        ];

        if ($verified) {
            $response['message'] = '✅ บัตรถูกต้อง - ผ่านการตรวจสอบความถูกต้อง';
            $response['card'] = $additionalData;
        } else {
            $response['message'] = '❌ บัตรไม่ถูกต้อง - ' . ($errorMessage ?? 'ไม่ผ่านการตรวจสอบ');
        }

        return response()->json($response);
    }

    /**
     * บันทึก log การตรวจสอบ
     *
     * @param int|null $cardId
     * @param string $cardNumber
     * @param string $verificationType
     * @param bool $verified
     * @param bool|null $signatureValid
     * @param bool|null $uidMatch
     * @param string|null $errorMessage
     * @param array|null $additionalData
     * @param Request $request
     * @return void
     */
    protected function logVerification(
        $cardId,
        $cardNumber,
        $verificationType,
        $verified,
        $signatureValid,
        $uidMatch,
        $errorMessage,
        $additionalData,
        Request $request
    ) {
        // ดึงข้อมูล User Agent
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        // เตรียม metadata
        $metadata = [
            'verification_data' => $additionalData,
            'timestamp' => now()->toISOString(),
        ];

        // บันทึกลง database
        DB::table('nfc_verification_logs')->insert([
            'nfc_card_id' => $cardId,
            'card_number' => $cardNumber,
            'verification_type' => $verificationType,
            'verified' => $verified,
            'signature_valid' => $signatureValid,
            'uid_match' => $uidMatch,
            'verification_data' => json_encode($additionalData),
            'error_message' => $errorMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isTablet() ? 'tablet' : 'mobile'),
            'browser' => $agent->browser(),
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }

    /**
     * แสดงสถิติการตรวจสอบ (สำหรับ Admin)
     *
     * @param string $cardNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics($cardNumber)
    {
        $card = NFCCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบบัตรในระบบ',
            ], 404);
        }

        // ดึงสถิติการตรวจสอบ
        $stats = DB::table('nfc_verification_logs')
            ->where('nfc_card_id', $card->id)
            ->select([
                DB::raw('COUNT(*) as total_verifications'),
                DB::raw('SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as successful_verifications'),
                DB::raw('SUM(CASE WHEN verified = 0 THEN 1 ELSE 0 END) as failed_verifications'),
                DB::raw('MAX(created_at) as last_verification'),
            ])
            ->first();

        // ดึงประเทศที่มีการตรวจสอบมากที่สุด
        $topCountries = DB::table('nfc_verification_logs')
            ->where('nfc_card_id', $card->id)
            ->whereNotNull('country')
            ->select('country', DB::raw('COUNT(*) as count'))
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'card_number' => $cardNumber,
            'statistics' => [
                'total_verifications' => $stats->total_verifications ?? 0,
                'successful' => $stats->successful_verifications ?? 0,
                'failed' => $stats->failed_verifications ?? 0,
                'last_verification' => $stats->last_verification,
                'top_countries' => $topCountries,
            ],
        ]);
    }
}
