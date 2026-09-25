@extends('layouts.seller-v4')

@section('title', 'ชำระค่าแพ็กเกจ')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    $package = $subscription->package;
    $due = (float) ($totalDue ?? ((float) $subscription->amount + (float) ($package->setup_fee ?? 0)));
    $balance = (float) ($walletBalance ?? 0);
    $enough = $balance + 0.00001 >= $due;
    $shortfall = max(0, round($due - $balance, 2));
    $alreadyPaid = ($subscription->payment_status ?? null) === 'paid';
    $supportEmail = \App\Support\ContactInfo::supportEmail();
@endphp

@section('content')
<div class="sv4-page" style="max-width:860px; margin-inline:auto; width:100%;">

    <x-seller-v4.header title="ชำระค่าแพ็กเกจ" subtitle="ชำระด้วยยอดเงินในกระเป๋า แพ็กเกจเริ่มใช้งานทันทีหลังชำระสำเร็จ" icon="💳" :back="route('seller.packages')" />

    <x-seller-v4.errors />

    {{-- สรุปรายการ --}}
    <div class="tp-card" style="padding:20px;">
        <div class="sv4-h2">🧾 สรุปรายการ</div>
        <div style="margin-top:10px;">
            <div class="sv4-kv"><span>แพ็กเกจ</span><span>{{ $package->display_name ?? '-' }}</span></div>
            <div class="sv4-kv"><span>รอบการชำระ</span>
                <span><span class="sv4-pill" style="{{ SellerUi::pill($subscription->subscription_type === 'yearly' ? SellerUi::OK : SellerUi::INFO) }}">
                    {{ $subscription->subscription_type === 'yearly' ? '📅 รายปี' : '🗓️ รายเดือน' }}</span></span>
            </div>
            @if($subscription->started_at && $subscription->expires_at)
                <div class="sv4-kv"><span>ระยะเวลา</span><span class="tp-num">{{ $subscription->started_at->format('d/m/Y') }} – {{ $subscription->expires_at->format('d/m/Y') }}</span></div>
            @endif
            <div class="sv4-kv"><span>ค่าแพ็กเกจ</span><span class="tp-num">฿{{ number_format((float) $subscription->amount, 2) }}</span></div>
            @if(($package->setup_fee ?? 0) > 0)
                <div class="sv4-kv"><span>ค่าแรกเข้า (ครั้งแรกเท่านั้น)</span><span class="tp-num">฿{{ number_format((float) $package->setup_fee, 2) }}</span></div>
            @endif
            <hr class="sv4-divider" style="margin:8px 0;">
            <div class="sv4-kv" style="font-size:15px;"><span style="color:var(--ink); font-weight:800;">ยอดที่ต้องชำระ</span>
                <span class="tp-num" style="font-size:22px; color:var(--deep1);">฿{{ number_format($due, 2) }}</span>
            </div>
        </div>
    </div>

    @if($alreadyPaid)
        <div class="sv4-note" style="--c:{{ SellerUi::OK }}; font-size:13px;">✅ รายการนี้ชำระเงินแล้ว — <a href="{{ route('seller.dashboard') }}" class="sv4-link">ไปที่แดชบอร์ด</a></div>
    @else
        {{-- ช่องทางชำระเงิน --}}
        <form action="{{ route('seller.packages.process-payment', $subscription->id) }}" method="POST" id="paymentForm"
              class="tp-card" style="padding:20px; display:flex; flex-direction:column; gap:14px;"
              x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <div class="sv4-h2">💳 ช่องทางชำระเงิน</div>

            <label class="sv4-choice on">
                <input type="radio" name="payment_method" value="wallet" checked style="margin-top:3px; accent-color:var(--accent1);">
                <span style="flex:1; min-width:0;">
                    <span style="display:block; font-weight:800;">👛 ยอดเงินในกระเป๋า</span>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); margin-top:2px;">
                        คงเหลือ <b class="tp-num" style="color:{{ $enough ? SellerUi::OK : SellerUi::BAD }};">฿{{ number_format($balance, 2) }}</b>
                    </span>
                </span>
            </label>

            @unless($enough)
                <div class="sv4-note" style="--c:{{ SellerUi::WARN }};">
                    ยอดในกระเป๋ายังไม่พอ ขาดอีก <b class="tp-num">฿{{ number_format($shortfall, 2) }}</b> —
                    เติมเงินด้วย PromptPay หรือโอนเงิน (ระบบตรวจสลิปอัตโนมัติ) แล้วกลับมาชำระที่หน้านี้
                    <div style="margin-top:8px;"><a href="{{ route('user.wallet.topup') }}" class="tp-btn tp-btn-sm tp-btn-primary">➕ เติมเงินเข้ากระเป๋า</a></div>
                </div>
            @endunless

            <div class="sv4-choice off" aria-disabled="true">
                <span style="font-size:18px;">📱</span>
                <span style="flex:1; min-width:0;">
                    <span style="display:block; font-weight:800;">PromptPay / โอนเงิน</span>
                    <span style="display:block; font-size:12.5px; color:var(--ink2);">ใช้เติมเงินเข้ากระเป๋าก่อน แล้วชำระด้วยกระเป๋าเงิน</span>
                </span>
            </div>
            <div class="sv4-choice off" aria-disabled="true">
                <span style="font-size:18px;">💳</span>
                <span style="flex:1; min-width:0;">
                    <span style="display:block; font-weight:800;">บัตรเครดิต/เดบิต</span>
                    <span style="display:block; font-size:12.5px; color:var(--ink2);">เร็วๆ นี้</span>
                </span>
            </div>

            @if(! empty($walletHasPin))
                <div>
                    <label for="pin" class="sv4-label">PIN กระเป๋าเงิน <span class="req">*</span></label>
                    <input type="password" name="pin" id="pin" inputmode="numeric" autocomplete="off" maxlength="20" required
                           class="tp-input tp-num" style="letter-spacing:4px;" placeholder="กรอก PIN เพื่อยืนยันการชำระเงิน">
                    @error('pin')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            @endif

            <label style="display:flex; gap:10px; align-items:flex-start; font-size:12.5px; line-height:1.6; cursor:pointer;">
                <input type="checkbox" name="accept_terms" value="1" required style="margin-top:3px; accent-color:var(--accent1); width:18px; height:18px;">
                <span>ฉันยอมรับ
                    <a href="{{ route('terms-of-service') }}" target="_blank" rel="noopener" class="sv4-link">ข้อกำหนดและเงื่อนไข</a>
                    และ
                    <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener" class="sv4-link">นโยบายความเป็นส่วนตัว</a>
                </span>
            </label>

            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <a href="{{ route('seller.packages') }}" class="tp-btn" style="flex:1 1 140px;">← ย้อนกลับ</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:2 1 220px; height:46px; font-size:14px;" :disabled="busy || {{ $enough ? 'false' : 'true' }}">
                    <span x-show="!busy">✅ ชำระ ฿{{ number_format($due, 2) }}</span>
                    <span x-show="busy" x-cloak>กำลังชำระเงิน…</span>
                </button>
            </div>
        </form>
    @endif

    <div class="tp-card" style="padding:16px 20px; font-size:12.5px; color:var(--ink2); line-height:1.7;">
        🔒 หักเงินจากกระเป๋าของคุณครั้งเดียวตามยอดด้านบน แพ็กเกจเริ่มใช้งานทันทีหลังชำระสำเร็จ
        · มีปัญหาติดต่อทีมงาน <a href="mailto:{{ $supportEmail }}" class="sv4-link">{{ $supportEmail }}</a>
    </div>
</div>
@endsection
