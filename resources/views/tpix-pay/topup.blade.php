{{--
 | 🪙 เติมเงินเข้ากระเป๋าด้วยเหรียญ TPIX — ธีม V4 (frontend-v4)
 | ข้อมูลจาก TpixPayController@topupForm: $spot (ราคาปัจจุบัน|null), $maxPerPayment (บาท, 0 = ไม่จำกัด)
 | ส่งฟอร์ม → ออกใบเสนอยอดเหรียญ → หน้าจ่าย tpix-pay.show
 --}}
@extends('layouts.frontend-v4')

@section('title', 'เติมเงินด้วยเหรียญ TPIX')

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="wallet" />

<main style="flex:1; padding-bottom:40px;"
      x-data="{ amount: @js(old('amount', '')), rate: @js($spot['price_thb'] ?? null),
                get tpix() { const a = parseFloat(this.amount); const r = parseFloat(this.rate);
                             if (!a || !r || a <= 0) return ''; return (Math.ceil(a / r * 10000) / 10000).toLocaleString('th-TH', { maximumFractionDigits: 4 }); } }">
    <section class="sf-wrap" style="padding-top:22px; max-width:620px; text-align:center;">
        <h1 class="sf-h1">เติมเงินด้วยเหรียญ TPIX</h1>
        <p class="tp-muted" style="margin:6px 0 0;">โอน TPIX บนเชน TPIX แล้วได้เงินบาทในกระเป๋าตามราคาตลาดจริง ใช้ซื้อสินค้า/บริการได้ทุกอย่างในระบบ</p>
    </section>

    <section class="sf-wrap sf-stack" style="padding-top:18px; max-width:620px;">
        @if(session('error'))
            <div class="sf-note sf-note-err">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('tpix-pay.topup.store') }}" class="tp-card sf-stack" style="gap:14px;">
            @csrf
            <label for="tpix-topup-amount" style="font-weight:800;">จำนวนเงินที่ต้องการเติม (บาท)</label>
            <input id="tpix-topup-amount" name="amount" type="number" inputmode="decimal" min="20" step="0.01" required x-model="amount"
                   class="tp-input tp-num" style="min-height:52px; padding:0 16px; border-radius:14px; border:1px solid var(--line, rgba(0,0,0,.12)); background:var(--surf); color:var(--ink); font-size:20px; font-weight:800;">
            @error('amount')
                <div class="sf-note sf-note-err" style="font-size:12.5px;">{{ $message }}</div>
            @enderror

            @if($spot)
                <div style="padding:12px 14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
                    <div class="sf-row"><span>ราคาตอนนี้</span><strong class="tp-num">1 TPIX = {{ \App\Services\TpixPay\TpixPriceService::formatRate($spot['price_thb']) }}</strong></div>
                    <div class="sf-row" style="margin-top:6px;"><span>ต้องโอนประมาณ</span><strong class="tp-num" x-text="tpix ? ('≈ ' + tpix + ' TPIX') : '—'"></strong></div>
                    <div class="tp-muted" style="font-size:11.5px; margin-top:8px;">ยอดเหรียญจริง (ตรงทุกหลัก) ออกให้ในหน้าถัดไป — ราคาจากพูล TPIX DEX บนเชน {{ config('tpix_pay.chain_id') }}</div>
                </div>
            @else
                <div class="sf-note sf-note-warn">ยังดึงราคา TPIX จริงไม่ได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง</div>
            @endif

            @if($maxPerPayment > 0)
                <div class="tp-muted" style="font-size:12px;">เติมได้สูงสุด {{ number_format($maxPerPayment, 2) }} บาทต่อรายการ</div>
            @endif

            <button type="submit" class="sf-btn3d is-block" @disabled(! $spot)>ออกยอดเหรียญที่ต้องโอน</button>
        </form>
    </section>
</main>
<x-theme-v4.public-footer />
@endsection
