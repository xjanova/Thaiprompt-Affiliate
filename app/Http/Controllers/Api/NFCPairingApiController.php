<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NFCCard;
use App\Models\User;
use App\Services\NFC\NFCCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * NFC Card Pairing API Controller
 *
 * API endpoints สำหรับการจับคู่บัตร NFC ผ่านทาง QR Code
 * - สร้างโทเค็นสำหรับจับคู่
 * - ยืนยันโทเค็นจับคู่
 * - จับคู่บัตรกับผู้ใช้
 * - แสดงรายการบัตรที่ยังไม่ได้จับคู่
 *
 * @version 1.0.0
 * @since 2025-02-22
 */
class NFCPairingApiController extends Controller
{
    protected $nfcCardService;

    public function __construct(NFCCardService $nfcCardService)
    {
        $this->nfcCardService = $nfcCardService;
    }

    /**
     * Generate a QR pairing token for an NFC card
     *
     * สร้างโทเค็นสำหรับจับคู่บัตร NFC ผ่าน QR Code
     * - โทเค็นมีอายุ 15 นาที
     * - เก็บข้อมูลการจับคู่ใน Cache
     *
     * POST /api/v1/nfc/pairing/generate
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePairingToken(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string',
        ]);

        $card = NFCCard::where('card_number', $request->card_number)->first();

        if (!$card) {
            return response()->json([
                'success' => false,
                'error' => 'Card not found',
            ], 404);
        }

        if ($card->isPaired()) {
            return response()->json([
                'success' => false,
                'error' => 'Card is already paired with a user',
            ], 422);
        }

        // Generate pairing token (valid 15 minutes)
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(15);

        Cache::put("nfc_pairing:{$token}", [
            'card_id' => $card->id,
            'card_number' => $card->card_number,
            'card_type' => $card->card_type,
            'created_by' => $request->user()?->id,
        ], $expiresAt);

        return response()->json([
            'success' => true,
            'data' => [
                'card_number' => $card->card_number,
                'card_number_masked' => $card->card_number_masked ?? substr_replace($card->card_number, '****', 4, -4),
                'card_type' => $card->card_type,
                'pairing_token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_minutes' => 15,
                'qr_payload' => json_encode([
                    'card_number' => $card->card_number,
                    'pairing_token' => $token,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'card_type' => $card->card_type,
                ]),
            ],
        ]);
    }

    /**
     * Verify a QR pairing token
     *
     * ยืนยันโทเค็นสำหรับจับคู่
     * - ตรวจสอบโทเค็นว่ายังใช้ได้หรือไม่
     * - ตรวจสอบหมายเลขบัตรตรงกันหรือไม่
     *
     * POST /api/v1/nfc/pairing/verify
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPairingToken(Request $request)
    {
        $request->validate([
            'pairing_token' => 'required|string',
            'card_number' => 'required|string',
        ]);

        $cached = Cache::get("nfc_pairing:{$request->pairing_token}");

        if (!$cached) {
            return response()->json([
                'success' => false,
                'error' => 'Pairing token expired or invalid',
            ], 422);
        }

        if ($cached['card_number'] !== $request->card_number) {
            return response()->json([
                'success' => false,
                'error' => 'Card number does not match pairing token',
            ], 422);
        }

        $card = NFCCard::find($cached['card_id']);

        if (!$card) {
            return response()->json([
                'success' => false,
                'error' => 'Card not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'card_id' => $card->id,
                'card_number_masked' => $card->card_number_masked ?? substr_replace($card->card_number, '****', 4, -4),
                'card_type' => $card->card_type,
                'card_type_label' => $card->card_type_label ?? ucfirst($card->card_type),
                'status' => $card->status,
                'balance' => $card->balance,
                'is_paired' => $card->isPaired(),
            ],
        ]);
    }

    /**
     * Pair an NFC card with a user via QR token
     *
     * จับคู่บัตร NFC กับผู้ใช้ผ่านโทเค็นที่ได้รับจาก QR Code
     * - ตรวจสอบโทเค็นและหมายเลขบัตร
     * - เรียก NFCCardService เพื่อจับคู่บัตร
     * - ลบโทเค็นจาก Cache หลังสำเร็จ
     *
     * POST /api/v1/nfc/pairing/pair
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pairCard(Request $request)
    {
        $request->validate([
            'pairing_token' => 'required|string',
            'card_number' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $cached = Cache::get("nfc_pairing:{$request->pairing_token}");

        if (!$cached) {
            return response()->json([
                'success' => false,
                'error' => 'Pairing token expired or invalid',
            ], 422);
        }

        if ($cached['card_number'] !== $request->card_number) {
            return response()->json([
                'success' => false,
                'error' => 'Card number does not match pairing token',
            ], 422);
        }

        $card = NFCCard::find($cached['card_id']);
        $user = User::find($request->user_id);

        if (!$card || !$user) {
            return response()->json([
                'success' => false,
                'error' => 'Card or user not found',
            ], 404);
        }

        if ($card->isPaired()) {
            return response()->json([
                'success' => false,
                'error' => 'Card is already paired',
            ], 422);
        }

        try {
            $pairedBy = $request->user()?->id ?? ($cached['created_by'] ?? null);
            $result = $this->nfcCardService->pairCardWithUser($card, $user, $pairedBy);

            if ($result) {
                // Invalidate the pairing token
                Cache::forget("nfc_pairing:{$request->pairing_token}");

                $card->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'Card paired successfully',
                    'data' => [
                        'card_id' => $card->id,
                        'card_number_masked' => $card->card_number_masked ?? substr_replace($card->card_number, '****', 4, -4),
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'paired_at' => $card->paired_at,
                        'status' => $card->status,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to pair card',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Pairing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List unpaired cards available for QR pairing
     *
     * แสดงรายการบัตรที่ยังไม่ได้จับคู่และพร้อมสำหรับจับคู่
     * - แสดงเฉพาะบัตรที่เปิดใช้งาน
     * - แสดงเฉพาะบัตรที่สถานะ active หรือ pending
     *
     * GET /api/v1/nfc/pairing/available-cards
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableCards(Request $request)
    {
        $cards = NFCCard::where('is_paired', false)
            ->whereIn('status', ['active', 'pending'])
            ->where('is_enabled', true)
            ->select(['id', 'card_number', 'card_name', 'card_type', 'status', 'balance', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'card_number_masked' => $card->card_number_masked ?? substr_replace($card->card_number, '****', 4, -4),
                    'card_name' => $card->card_name,
                    'card_type' => $card->card_type,
                    'card_type_label' => $card->card_type_label ?? ucfirst($card->card_type),
                    'status' => $card->status,
                    'balance' => $card->balance,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cards,
            'count' => $cards->count(),
        ]);
    }
}
