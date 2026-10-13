<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use App\Services\TpixPay\TpixPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 🪙 หลังบ้าน: รับชำระด้วยเหรียญ TPIX — ตั้งค่า (เปิด/ปิด, กระเป๋ารับเงิน, confirmation, เพดาน) + รายการชำระ + ตรวจซ้ำบนเชน
 */
class TpixPayAdminController extends Controller
{
    public function index(Request $request, TpixPaySettings $settings, TpixPriceService $prices): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $payments = TpixPayment::with('user:id,name,email')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('tx_hash', 'like', '%'.strtolower($search).'%')
                        ->orWhere('from_address', 'like', '%'.strtolower($search).'%')
                        ->orWhere('meta->order_number', $search);
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $since = now()->subDays(30);
        $stats = [
            'paid_count' => TpixPayment::whereIn('status', TpixPayment::SETTLED_STATUSES)->where('paid_at', '>=', $since)->count(),
            'paid_thb' => (float) TpixPayment::whereIn('status', TpixPayment::SETTLED_STATUSES)->where('paid_at', '>=', $since)->sum('amount_thb'),
            'paid_tpix' => (string) TpixPayment::whereIn('status', TpixPayment::SETTLED_STATUSES)->where('paid_at', '>=', $since)->sum('amount_tpix'),
            'open_count' => TpixPayment::whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])->where('expires_at', '>', now())->count(),
            'attention_count' => TpixPayment::whereIn('status', [TpixPayment::STATUS_LATE, TpixPayment::STATUS_CONFIRMING])->where('updated_at', '<', now()->subMinutes(10))->count(),
            // ไม่ใช้ meta->needs_review = true: MariaDB เทียบ JSON boolean กับ 1 ไม่ตรง (json_extract คืน 'true')
            'review_count' => TpixPayment::where('status', TpixPayment::STATUS_LATE)->where('meta', 'like', '%"needs_review":true%')->count(),
        ];

        return view('admin.tpix-pay.index', [
            'settings' => $settings->all(),
            'paymentsEnabled' => $settings->paymentsEnabled(),
            'spot' => $prices->spot(),
            'twap' => $prices->twapThb(),
            'lastSnapshot' => TpixPriceSnapshot::latest('id')->first(),
            'payments' => $payments,
            'stats' => $stats,
            'status' => $status,
            'search' => $search,
            'statuses' => [
                TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING, TpixPayment::STATUS_PAID,
                TpixPayment::STATUS_CREDITED_WALLET, TpixPayment::STATUS_LATE, TpixPayment::STATUS_EXPIRED,
                TpixPayment::STATUS_CANCELLED, TpixPayment::STATUS_FAILED,
            ],
        ]);
    }

    public function update(Request $request, TpixPaySettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'show_prices' => ['nullable', 'boolean'],
            'receive_address' => ['nullable', 'string', 'regex:/^0x[0-9a-fA-F]{40}$/'],
            'confirmations' => ['required', 'integer', 'min:1', 'max:64'],
            'max_per_payment_thb' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'max_per_user_day_thb' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'max_pool_ratio' => ['required', 'numeric', 'min:0', 'max:1000'],
        ], [
            'receive_address.regex' => 'ที่อยู่กระเป๋าต้องขึ้นต้น 0x ตามด้วยตัวอักษร 0-9 a-f 40 ตัว',
        ]);

        $enabled = $request->boolean('enabled');
        $address = trim((string) ($validated['receive_address'] ?? ''));

        if ($enabled && ! TpixPaySettings::isValidAddress($address)) {
            return back()->withInput()->with('error', 'เปิดรับชำระไม่ได้ — ต้องใส่กระเป๋ารับเงิน TPIX ก่อน');
        }

        $settings->save([
            'enabled' => $enabled,
            'show_prices' => $request->boolean('show_prices'),
            'receive_address' => $address,
            'confirmations' => (int) $validated['confirmations'],
            'max_per_payment_thb' => (float) $validated['max_per_payment_thb'],
            'max_per_user_day_thb' => (float) $validated['max_per_user_day_thb'],
            'max_pool_ratio' => (float) $validated['max_pool_ratio'],
        ]);

        return back()->with('success', 'บันทึกการตั้งค่าชำระด้วย TPIX แล้ว');
    }

    /**
     * อนุมัติเงิน TPIX ที่เข้ามาแล้วแต่ติดเพดาน (โอนช้า/โอนซ้ำ) → เติมเข้ากระเป๋าผู้ใช้ตามราคาปัจจุบัน
     */
    public function approve(int $id, TpixPaymentService $service): RedirectResponse
    {
        $result = $service->approveHeld(TpixPayment::findOrFail($id));

        return back()->with($result['status'] === TpixPayment::STATUS_CREDITED_WALLET ? 'success' : 'error', $result['message']);
    }

    /**
     * ตรวจรายการนี้กับเชนอีกครั้ง (หรือผูกเลขธุรกรรมที่ลูกค้าแจ้งมาทางแชท — ตรวจด้วยกฎเดียวกับลูกค้า)
     */
    public function recheck(Request $request, int $id, TpixPaymentService $service): RedirectResponse
    {
        $payment = TpixPayment::findOrFail($id);
        $hash = trim((string) $request->input('tx_hash', ''));

        if ($hash !== '') {
            $result = $service->submitTx($payment, $hash);

            return back()->with($result['status'] === 'rejected' ? 'error' : 'success', 'ผลตรวจ: '.$result['message']);
        }

        \Illuminate\Support\Facades\Cache::forget('tpix_pay:refresh:'.$payment->id);
        $fresh = $service->refresh($payment);

        return back()->with('success', 'ตรวจบนเชนแล้ว — สถานะ: '.$fresh->statusLabel());
    }
}
