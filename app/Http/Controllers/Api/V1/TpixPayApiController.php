<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TpixPayment;
use App\Services\TpixPay\TpixAmount;
use App\Services\TpixPay\TpixPaymentPresenter;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🪙 API เหรียญ TPIX สำหรับแอป (ราคา + สถานะ/ส่งเลขธุรกรรมของใบเสนอยอด)
 *
 * การ "เริ่มจ่าย" ใช้เส้นเดิมของแอป: POST /cart/checkout หรือ /payment/order ด้วย payment_method=tpix
 * (เติมเงิน: /wallet/topup) — ผลลัพธ์มีก้อน tpix (ยอด/กระเป๋า/ลิงก์หน้าจ่าย)
 */
class TpixPayApiController extends Controller
{
    /**
     * GET /api/v1/tpix/price — ราคา TPIX ปัจจุบัน (public, แคช 60 วิ)
     */
    public function price(TpixPriceService $prices, TpixPaySettings $settings): JsonResponse
    {
        $spot = $prices->spot();

        if ($spot === null) {
            return response()->json([
                'success' => false,
                'code' => 'TPIX_PRICE_UNAVAILABLE',
                'message' => 'ยังดึงราคา TPIX จริงไม่ได้ในขณะนี้',
                'data' => [
                    'payments_enabled' => false,
                    'show_prices' => $settings->showPrices(),
                ],
            ], 503)->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'symbol' => 'TPIX',
                'price_usd' => $spot['price_usd'],
                'price_thb' => $spot['price_thb'],
                'price_thb_display' => TpixPriceService::formatRate($spot['price_thb']),
                'usd_thb' => $spot['usd_thb'],
                'source' => 'TPIX DEX (WTPIX/USDT) · chain '.config('tpix_pay.chain_id'),
                'pool' => [
                    'pair' => config('tpix_pay.dex.pair'),
                    'reserve_tpix' => TpixAmount::display($spot['reserve_tpix'], 4),
                    'reserve_usdt' => TpixAmount::display($spot['reserve_usdt'], 2),
                ],
                'updated_at' => date(DATE_ATOM, $spot['at']),
                'chain_id' => (int) config('tpix_pay.chain_id'),
                'payments_enabled' => $settings->paymentsEnabled(),
                'show_prices' => $settings->showPrices(),
            ],
        ])->header('Cache-Control', 'public, max-age=30');
    }

    /**
     * GET /api/v1/tpix/payments/{reference} — สถานะใบเสนอยอด (ตรวจบนเชนให้ทันทีถ้าถึงรอบ)
     */
    public function show(string $reference, TpixPaymentService $payments): JsonResponse
    {
        $payment = $this->find($reference);
        if (! $payment) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND', 'message' => 'ไม่พบรายการชำระเงิน'], 404);
        }

        $payment = $payments->refresh($payment);

        return response()->json(['success' => true, 'data' => TpixPaymentPresenter::present($payment)])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * POST /api/v1/tpix/payments/{reference}/submit {tx_hash}
     */
    public function submit(Request $request, string $reference, TpixPaymentService $payments): JsonResponse
    {
        $payment = $this->find($reference);
        if (! $payment) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND', 'message' => 'ไม่พบรายการชำระเงิน'], 404);
        }

        $hash = trim((string) $request->input('tx_hash', ''));
        if ($hash === '') {
            return response()->json(['success' => false, 'code' => 'VALIDATION_ERROR', 'message' => 'กรุณาวางเลขธุรกรรม (Transaction Hash)'], 422);
        }

        $result = $payments->submitTx($payment, $hash);

        return response()->json([
            'success' => $result['status'] !== 'rejected',
            'result' => $result['status'],
            'message' => $result['message'],
            'data' => TpixPaymentPresenter::present($payment->fresh() ?? $payment),
        ], $result['status'] === 'rejected' ? 422 : 200);
    }

    private function find(string $reference): ?TpixPayment
    {
        if (! preg_match('/^TPX[A-Z0-9]{21}$/', $reference)) {
            return null;
        }

        return TpixPayment::where('reference', $reference)->first();
    }
}
