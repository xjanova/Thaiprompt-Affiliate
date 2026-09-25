{{--
 | รอการชำระเงิน (QR พร้อมเพย์ / โอนธนาคาร) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก CheckoutController@paymentProcessing: $order, $transaction, $paymentData (qr_code, qr_code_image, ref_no), $promptpayInfo
 | ⚠️ ห้ามรีโหลดหน้าเอง (ยอดทศนิยมเฉพาะตัวต้องคงเดิม) — ตรวจสถานะด้วย AJAX GET orders.show (JSON) ทุก 10 วินาที
 --}}
@extends('layouts.frontend-v4')

@section('title', 'รอการชำระเงิน #'.$order->order_number)

@php
    // ⚡ ยอดทศนิยมเฉพาะตัวสำหรับ SMS Checker (สำรองกรณีรายการเก่ายังไม่มี) — คงตรรกะเดิมไว้
    $smsDebug = ['method' => $transaction->payment_method];
    if (in_array($transaction->payment_method, ['promptpay', 'bank_transfer'], true)) {
        $meta = $transaction->metadata ?? [];
        $amountHasDecimal = ($transaction->amount != floor($transaction->amount));
        if (empty($meta['unique_amount_id']) && ! $amountHasDecimal) {
            try {
                $ua = \App\Models\UniquePaymentAmount::generate(
                    $transaction->amount,
                    $transaction->id,
                    $transaction->type ?? 'order',
                    config('smschecker.unique_amount_expiry', 60)
                );
                if ($ua) {
                    $meta['original_amount'] = $transaction->amount;
                    $meta['unique_amount_id'] = $ua->id;
                    $meta['decimal_suffix'] = $ua->decimal_suffix;
                    $transaction->update(['amount' => $ua->unique_amount, 'metadata' => $meta]);
                    $transaction = $transaction->fresh();
                } else {
                    $smsDebug['error'] = 'generate_returned_null';
                    \Illuminate\Support\Facades\Log::warning('SMS Checker BLADE: generate() returned null for txn #'.$transaction->id);
                }
            } catch (\Throwable $e) {
                $smsDebug['error'] = 'generate_failed';
                \Illuminate\Support\Facades\Log::error('SMS Checker BLADE error: '.$e->getMessage());
            }
        }
    }

    $ppMethod = $order->payment_method;
    $ppAmount = (float) $transaction->amount;
    $ppOriginal = isset($transaction->metadata['original_amount']) ? (float) $transaction->metadata['original_amount'] : null;
    $ppQr = $paymentData['qr_code_image'] ?? ($paymentData['qr_code'] ?? null);
    $ppQrIsImage = is_string($ppQr) && (str_starts_with($ppQr, 'data:image/') || preg_match('#^https?://#i', $ppQr));
    $ppExplain = (array) config('smschecker.customer_explanation', []);

    // บัญชีธนาคาร (โอนธนาคาร): ร้าน Enterprise ที่รับเงินตรง → บัญชีร้าน · อื่นๆ → บัญชีแพลตฟอร์ม
    $ppSellerStore = null;
    $ppBankAccounts = collect();
    if ($ppMethod === 'bank_transfer') {
        if ($transaction->store_id) {
            $ppStore = \App\Models\VendorStore::with('package')->find($transaction->store_id);
            if ($ppStore && $ppStore->allowsDirectPayment()) {
                $ppBankAccounts = \App\Models\SmsGatewayBankAccount::where('store_id', $transaction->store_id)
                    ->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order')->get();
                if ($ppBankAccounts->isNotEmpty()) {
                    $ppSellerStore = $ppStore->store_name;
                }
            }
        }
        if ($ppBankAccounts->isEmpty()) {
            $ppBankAccounts = \App\Models\PaymentBankAccount::where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->get();
        }
    }
    $ppDefaultAccount = $ppBankAccounts->firstWhere('is_primary', true) ?? $ppBankAccounts->firstWhere('is_default', true) ?? $ppBankAccounts->first();

    // คำสั่งซื้ออื่นจากการกดสั่งครั้งเดียวกัน (สินค้าหลายร้าน = แยกออเดอร์ละร้าน แต่ละออเดอร์มี QR ของตัวเอง)
    $ppSiblings = $order->checkout_group
        ? \App\Models\Order::where('user_id', $order->user_id)
            ->where('checkout_group', $order->checkout_group)
            ->where('id', '!=', $order->id)
            ->orderBy('id')
            ->get(['id', 'order_number', 'total_amount', 'payment_status', 'payment_method', 'status'])
        : collect();
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="orders" />

<main style="flex:1; padding-bottom:40px;"
      x-data="tpPaymentWait({{ \Illuminate\Support\Js::from([
          'statusUrl' => route('orders.show', $order->id),
          'successUrl' => route('checkout.success', $order->id),
          'expiresAt' => optional($transaction->expired_at)->toIso8601String(),
      ]) }})">
    @if(config('app.debug'))
        <section class="sf-wrap" style="padding-top:12px; max-width:760px;">
            <div class="sf-note sf-note-info tp-num" style="font-size:11.5px;">
                <strong>🔍 ข้อมูลตรวจสอบ (debug)</strong> — รายการ #{{ $transaction->id }} · {{ $transaction->status }} · ฿{{ $transaction->amount }} · ออเดอร์ {{ $order->payment_status }}
                @if(isset($smsDebug['error'])) · ⚠️ {{ $smsDebug['error'] }} @endif
            </div>
        </section>
    @endif

    <section class="sf-wrap" style="padding-top:22px; max-width:760px; text-align:center;">
        <span class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:28px; margin:0 auto;"><i class="fas fa-hourglass-half" style="animation:tpPulse 1.8s ease-in-out infinite;"></i></span>
        <h1 class="sf-h1" style="margin-top:12px;">รอการชำระเงิน</h1>
        <p class="tp-muted" style="margin:6px 0 0;">คำสั่งซื้อ #{{ $order->order_number }}</p>
    </section>

    <section class="sf-wrap sf-stack" style="padding-top:18px; max-width:760px;">
        @if($ppMethod === 'promptpay')
            <div class="tp-card" style="text-align:center; padding:clamp(18px, 4vw, 30px);">
                <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-qrcode" style="color:var(--deep1);"></i> สแกน QR พร้อมเพย์</div>
                <p class="tp-muted" style="margin:0 0 16px; font-size:13px;">เปิดแอปธนาคาร แล้วสแกน QR ด้านล่าง</p>
                <div style="display:inline-block; padding:16px; border-radius:22px; background:var(--on-accent, #fff); box-shadow:var(--card-shadow);">
                    @if($ppQrIsImage)
                        <img src="{{ $ppQr }}" alt="QR พร้อมเพย์ สำหรับคำสั่งซื้อ {{ $order->order_number }}" style="width:min(260px, 64vw); height:auto; display:block;">
                    @else
                        <div style="width:min(260px, 64vw); aspect-ratio:1/1; display:grid; place-items:center; color:var(--ink2); font-size:13px;">สร้าง QR ไม่สำเร็จ กรุณาโหลดหน้าใหม่</div>
                    @endif
                </div>
                <div style="margin-top:16px;">
                    <div class="tp-muted" style="font-size:12.5px;">ยอดที่ต้องโอน (ตรงทุกหลัก)</div>
                    <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;">
                        <span class="tp-num" style="font-size:clamp(30px, 6vw, 40px); font-weight:800; color:var(--deep1);">฿{{ number_format($ppAmount, 2) }}</span>
                        <button type="button" class="tp-btn tp-btn-sm" @click="copy(@js(number_format($ppAmount, 2, '.', '')))"><i class="fas fa-copy"></i> คัดลอกยอด</button>
                    </div>
                    @if($ppOriginal !== null && abs($ppOriginal - $ppAmount) > 0.001)
                        <div class="tp-muted" style="font-size:12px;">ราคาสินค้า ฿{{ number_format($ppOriginal, 2) }} + เศษสตางค์สำหรับยืนยันอัตโนมัติ</div>
                    @endif
                </div>
                @if(! empty($promptpayInfo['promptpay_id']))
                    <div style="margin-top:14px; padding:12px 14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm); text-align:left;">
                        <div class="sf-row"><span>ชื่อบัญชี</span><strong>{{ $promptpayInfo['promptpay_name'] ?? '-' }}</strong></div>
                        <div class="sf-row" style="margin-top:6px;"><span>{{ ($promptpayInfo['promptpay_type'] ?? 'phone') === 'phone' ? 'เบอร์พร้อมเพย์' : 'เลขพร้อมเพย์' }}</span><strong class="tp-num">{{ $promptpayInfo['promptpay_id'] }}</strong></div>
                    </div>
                @endif
                @if(! empty($paymentData['ref_no']))
                    <div class="tp-muted" style="margin-top:10px; font-size:12.5px;">เลขอ้างอิง <span class="tp-num" style="font-weight:800; color:var(--ink);">{{ $paymentData['ref_no'] }}</span></div>
                @endif
            </div>
        @elseif($ppMethod === 'bank_transfer')
            <div class="tp-card sf-stack" style="gap:12px;">
                <div class="tp-section-h"><i class="fas fa-building-columns" style="color:var(--deep1);"></i> โอนเงินผ่านธนาคาร</div>
                <div style="text-align:center;">
                    <div class="tp-muted" style="font-size:12.5px;">ยอดที่ต้องโอน (ตรงทุกหลัก)</div>
                    <div class="tp-num" style="font-size:clamp(30px, 6vw, 40px); font-weight:800; color:var(--deep1);">฿{{ number_format($ppAmount, 2) }}</div>
                    <button type="button" class="tp-btn tp-btn-sm" @click="copy(@js(number_format($ppAmount, 2, '.', '')))"><i class="fas fa-copy"></i> คัดลอกยอด</button>
                </div>
                @if($ppSellerStore)
                    <div class="sf-note sf-note-warn"><i class="fas fa-circle-info"></i> โอนเข้าบัญชีของร้าน {{ $ppSellerStore }} โดยตรง</div>
                @endif
                @if($ppDefaultAccount)
                    <div style="padding:14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
                        <div class="sf-row"><span>ธนาคาร</span><strong>{{ $ppDefaultAccount->bank_name }}</strong></div>
                        <div class="sf-row" style="margin-top:6px;"><span>ชื่อบัญชี</span><strong>{{ $ppDefaultAccount->account_name }}</strong></div>
                        <div class="sf-row" style="margin-top:6px; align-items:center;"><span>เลขบัญชี</span>
                            <span style="display:flex; align-items:center; gap:8px;"><strong class="tp-num" style="font-size:17px;">{{ $ppDefaultAccount->account_number }}</strong>
                                <button type="button" class="tp-btn tp-btn-sm" @click="copy(@js((string) $ppDefaultAccount->account_number))" aria-label="คัดลอกเลขบัญชี"><i class="fas fa-copy"></i></button>
                            </span>
                        </div>
                        @if($ppDefaultAccount->branch)
                            <div class="sf-row" style="margin-top:6px;"><span>สาขา</span><strong>{{ $ppDefaultAccount->branch }}</strong></div>
                        @endif
                    </div>
                    @if($ppBankAccounts->count() > 1)
                        <details>
                            <summary class="tp-muted" style="cursor:pointer; font-size:13px; font-weight:600;">บัญชีอื่น ({{ $ppBankAccounts->count() - 1 }})</summary>
                            <div style="display:flex; flex-direction:column; gap:8px; margin-top:8px;">
                                @foreach($ppBankAccounts->where('id', '!=', $ppDefaultAccount->id) as $acc)
                                    <div style="padding:10px 12px; border-radius:14px; background:var(--surf); box-shadow:var(--raise); font-size:13px;">
                                        <strong>{{ $acc->bank_name }}</strong> · {{ $acc->account_name }} · <span class="tp-num">{{ $acc->account_number }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @else
                    <div class="sf-note sf-note-warn">ยังไม่มีบัญชีธนาคารสำหรับรับโอน กรุณาติดต่อทีมงาน</div>
                @endif
            </div>
        @else
            <div class="tp-card sf-note sf-note-info">คำสั่งซื้อนี้ชำระด้วย {{ \App\Support\Shop\PaymentMethod::labelTh($ppMethod) }} — ระบบกำลังตรวจสอบสถานะให้อัตโนมัติ</div>
        @endif

        <div class="sf-note sf-note-info" style="font-size:12.5px;">
            <strong>💡 {{ $ppExplain['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}</strong><br>
            {{ $ppExplain['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) เพื่อให้ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน' }}
        </div>

        <div class="tp-card" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <span class="tp-tile" style="width:46px; height:46px; font-size:18px;"><i class="fas fa-rotate" :class="checking && 'fa-spin'"></i></span>
            <div style="flex:1; min-width:200px;">
                <div style="font-weight:800; color:var(--ink);">ระบบตรวจสถานะอัตโนมัติทุก 10 วินาที</div>
                <div class="tp-muted" style="font-size:12.5px;">เมื่อเงินเข้า หน้านี้จะพาไปหน้าสั่งซื้อสำเร็จเอง ไม่ต้องรีเฟรช</div>
            </div>
            <div x-show="left" x-cloak style="text-align:right;">
                <div class="tp-muted" style="font-size:11.5px;">หมดอายุใน</div>
                <div class="tp-num" style="font-weight:800; font-size:18px; color:var(--deep2);" x-text="left"></div>
            </div>
        </div>

        @if($ppSiblings->isNotEmpty())
            <div class="tp-card sf-stack" style="gap:10px;">
                <div class="tp-section-h"><i class="fas fa-store" style="color:var(--deep1);"></i> คำสั่งซื้ออื่นจากการสั่งครั้งนี้</div>
                <p class="tp-muted" style="margin:0; font-size:12.5px;">สินค้ามาจากหลายร้าน ระบบแยกเป็นคำสั่งซื้อละร้าน — ชำระให้ครบทุกรายการ</p>
                @foreach($ppSiblings as $sib)
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 12px; border-radius:14px; background:var(--surf); box-shadow:var(--raise);">
                        <span style="flex:1; min-width:160px;"><strong class="tp-num">#{{ $sib->order_number }}</strong> · ฿{{ number_format((float) $sib->total_amount, 2) }}</span>
                        @if($sib->payment_status === 'paid')
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-check"></i> ชำระแล้ว</span>
                        @elseif(in_array($sib->payment_method, ['promptpay', 'bank_transfer'], true))
                            <a href="{{ route('checkout.processing', $sib->id) }}" class="tp-btn tp-btn-sm tp-btn-primary" style="text-decoration:none;">ชำระรายการนี้</a>
                        @else
                            <a href="{{ route('orders.show', $sib->id) }}" class="tp-btn tp-btn-sm" style="text-decoration:none;">ดูคำสั่งซื้อ</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
            <a href="{{ route('orders.show', $order->id) }}" class="sf-btn3d is-soft">ดูคำสั่งซื้อ</a>
            <a href="{{ route('storefront.index') }}" class="sf-btn3d is-soft">กลับไปร้านค้า</a>
        </div>
    </section>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * รอเงินเข้า: โพลสถานะออเดอร์ทุก 10 วิ (ห้ามรีโหลดหน้า — ยอดทศนิยมต้องคงเดิม) + นับถอยหลังหมดอายุ
     */
    function tpPaymentWait(cfg) {
        return {
            checking: false,
            left: '',
            init() {
                setInterval(() => this.check(), 10000);
                if (cfg.expiresAt) {
                    const end = new Date(cfg.expiresAt).getTime();
                    const tick = () => {
                        const d = Math.max(0, end - Date.now());
                        const m = Math.floor(d / 60000);
                        const s = Math.floor(d % 60000 / 1000);
                        this.left = d > 0 ? (String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0')) : 'หมดเวลา';
                    };
                    tick();
                    setInterval(tick, 1000);
                }
                // สำรอง: ถ้าโพลไม่ทำงาน รีเฟรชหน้าหลัง 5 นาที (ระบบไม่สร้างยอดใหม่ถ้ารายการยังไม่หมดอายุ)
                setTimeout(() => window.location.reload(), 300000);
            },
            async check() {
                if (this.checking) { return; }
                this.checking = true;
                try {
                    const res = await fetch(cfg.statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.order && data.order.payment_status === 'paid') {
                            window.location.href = cfg.successUrl;
                        }
                    }
                } catch (e) {
                    // เงียบไว้ รอบถัดไปลองใหม่
                } finally {
                    this.checking = false;
                }
            },
            copy(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => window.tpShop.notify('คัดลอกแล้ว', 'success')).catch(() => {});
                }
            }
        };
    }
</script>
@endpush
