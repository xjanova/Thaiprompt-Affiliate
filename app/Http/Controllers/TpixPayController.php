<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TpixPayment;
use App\Services\Payment\PaymentService;
use App\Services\TpixPay\TpixPaymentPresenter;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use App\Support\Shop\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * 🪙 หน้าจ่ายด้วยเหรียญ TPIX (เว็บ + แอปเปิดผ่านเบราว์เซอร์)
 *
 * หน้าจ่ายเปิดด้วยรหัสอ้างอิงแบบสุ่ม 24 ตัว (ลิงก์ใบแจ้งหนี้) ไม่ต้องล็อกอิน — แอปเปิดในเบราว์เซอร์ได้ทันที
 * ปลอดภัยเพราะ: หน้าไม่มีข้อมูลส่วนตัว และการส่งเลขธุรกรรมทำได้แค่ "จ่าย" ใบนี้ (ต้องโอนยอดตรงตัวเข้ากระเป๋าร้านจริง)
 */
class TpixPayController extends Controller
{
    public function __construct(
        private readonly TpixPaymentService $payments,
        private readonly TpixPaySettings $settings,
    ) {}

    /**
     * GET /tpix-pay/{reference}
     */
    public function show(string $reference): View
    {
        $payment = $this->find($reference);
        $payment = $this->payments->refresh($payment);

        $order = $payment->purpose === TpixPayment::PURPOSE_ORDER ? Order::find($payment->payable_id) : null;

        // คำสั่งซื้ออื่นจากการกดสั่งครั้งเดียวกัน (หลายร้าน = หลายใบ)
        $siblings = collect();
        if ($order && $order->checkout_group) {
            $siblings = Order::where('user_id', $order->user_id)
                ->where('checkout_group', $order->checkout_group)
                ->where('id', '!=', $order->id)
                ->orderBy('id')
                ->get(['id', 'order_number', 'total_amount', 'payment_status', 'payment_method', 'status'])
                ->map(function (Order $sib) {
                    $open = TpixPayment::where('purpose', TpixPayment::PURPOSE_ORDER)
                        ->where('payable_id', $sib->id)
                        ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])
                        ->where('expires_at', '>', now())
                        ->latest('id')
                        ->value('reference');
                    $sib->setAttribute('tpix_reference', $open);

                    return $sib;
                });
        }

        return view('tpix-pay.show', [
            'payment' => $payment,
            'data' => TpixPaymentPresenter::present($payment),
            'order' => $order,
            'siblings' => $siblings,
            'canRequote' => $order !== null
                && $order->payment_status !== 'paid'
                && ! in_array($order->status, ['cancelled', 'refunded'], true)
                && auth()->id() === (int) $order->user_id,
        ]);
    }

    /**
     * GET /tpix-pay/{reference}/status (JSON — โพลทุก 5 วิ)
     */
    public function status(string $reference): JsonResponse
    {
        $payment = $this->payments->refresh($this->find($reference));

        return response()->json(['success' => true, 'data' => TpixPaymentPresenter::present($payment)])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * POST /tpix-pay/{reference}/submit — ลูกค้าส่งเลขธุรกรรม (หลังโอนจาก MetaMask หรือวางเอง)
     */
    public function submit(Request $request, string $reference): JsonResponse
    {
        $validated = $request->validate([
            'tx_hash' => ['required', 'string', 'max:80'],
        ], [
            'tx_hash.required' => 'กรุณาวางเลขธุรกรรม (Transaction Hash)',
        ]);

        $payment = $this->find($reference);
        $result = $this->payments->submitTx($payment, (string) $validated['tx_hash']);

        return response()->json([
            'success' => $result['status'] !== 'rejected',
            'result' => $result['status'],
            'message' => $result['message'],
            'data' => TpixPaymentPresenter::present($payment->fresh() ?? $payment),
        ], $result['status'] === 'rejected' ? 422 : 200);
    }

    /**
     * GET /tpix-pay/{reference}/qr.svg — QR ลิงก์ EIP-681 (กระเป๋ามือถือสแกนแล้วกรอกที่อยู่+ยอดให้)
     */
    public function qr(string $reference): Response
    {
        $payment = $this->find($reference);

        return response(TpixPaymentPresenter::qrSvg($payment), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * POST /tpix-pay/order/{id} — ออกยอดเหรียญใหม่ให้คำสั่งซื้อของตัวเอง (ใบเก่าหมดเวลา / เปลี่ยนมาจ่าย TPIX)
     */
    public function startForOrder(Request $request, int $id): RedirectResponse
    {
        $order = Order::where('user_id', $request->user()->id)->whereKey($id)->first();
        if (! $order) {
            abort(404);
        }

        $result = $this->payments->openQuoteForOrder($order, 'web');

        return $result['reference']
            ? redirect()->route('tpix-pay.show', $result['reference'])
            : redirect()->route('orders.show', $order->id)->with($order->payment_status === 'paid' ? 'success' : 'error', $result['error']);
    }

    /**
     * GET /tpix-pay/topup — ฟอร์มเติมเงินเข้ากระเป๋าด้วย TPIX
     */
    public function topupForm(TpixPriceService $prices): View|RedirectResponse
    {
        if (! $this->settings->paymentsEnabled()) {
            return redirect()->back()->with('error', 'ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }

        return view('tpix-pay.topup', [
            'spot' => $prices->spot(),
            'maxPerPayment' => $this->settings->maxPerPaymentThb(),
        ]);
    }

    /**
     * POST /tpix-pay/topup
     */
    public function topupStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:20', 'max:1000000'],
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.min' => 'เติมขั้นต่ำ 20 บาท',
        ]);

        if (! $this->settings->paymentsEnabled()) {
            return back()->with('error', 'ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }

        try {
            $paymentService = app(PaymentService::class);
            $transaction = $paymentService->createWalletTopup($request->user(), round((float) $validated['amount'], 2), PaymentMethod::TPIX, [
                'metadata' => ['source' => 'web'],
            ]);
            $result = $paymentService->processPayment($transaction, []);
        } catch (\Throwable $e) {
            Log::error('TpixPay: topup failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
            $result = ['success' => false, 'message' => ''];
        }

        $reference = $result['data']['tpix']['reference'] ?? null;
        if (! ($result['success'] ?? false) || ! $reference) {
            $message = (string) ($result['message'] ?? '');

            return back()->withInput()->with('error', str_contains($message, 'TPIX')
                ? $message
                : 'ออกยอดเติมเงินด้วย TPIX ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        return redirect()->route('tpix-pay.show', $reference);
    }

    private function find(string $reference): TpixPayment
    {
        if (! preg_match('/^TPX[A-Z0-9]{21}$/', $reference)) {
            abort(404);
        }

        return TpixPayment::where('reference', $reference)->firstOrFail();
    }
}
