@extends('layouts.seller-v4')

@section('title', 'เริ่มต้นเปิดร้านค้า')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    $subscriptionService = app(\App\Services\VendorSubscriptionService::class);
    $gpPromoActive = app(\App\Services\Pricing\PricingEngine::class)->gpPromoActive();
    $supportEmail = \App\Support\ContactInfo::supportEmail();
    $steps = [1 => ['🪪', 'ยืนยันตัวตน'], 2 => ['🏪', 'ตั้งค่าร้าน & แพ็กเกจ'], 3 => ['🚀', 'พร้อมขาย']];
    $packageCards = $packages->map(fn ($p) => [
        'id' => (int) $p->id,
        'free' => $subscriptionService->isFree($p),
        'custom' => $subscriptionService->isCustomPricing($p),
        'yearly' => $p->yearly_price !== null && (float) $p->yearly_price > 0,
    ])->values();
    $onboardingConfig = [
        'storeName' => $store?->store_name ?? '',
        'hasStore' => (bool) $store,
        'packages' => $packageCards,
    ];
@endphp

@section('content')
<div class="sv4-page" style="max-width:1100px; margin-inline:auto; width:100%;">

    <x-seller-v4.header title="เริ่มต้นเปิดร้านค้า" subtitle="3 ขั้นตอนง่ายๆ ก็เริ่มขายสินค้าได้" icon="🏪" crumb="แผงผู้ขาย" />

    <x-seller-v4.errors />

    {{-- ขั้นตอน --}}
    <div class="tp-card" style="padding:18px 20px;">
        <div style="display:flex; align-items:center; gap:8px;">
            @foreach($steps as $n => [$icon, $label])
                @php
                    $done = $currentStep > $n || ($n === 3 && $currentStep >= 3);
                    $active = $currentStep === $n;
                @endphp
                <div style="display:flex; flex-direction:column; align-items:center; gap:6px; flex:0 0 auto; min-width:70px;">
                    <span style="width:44px; height:44px; border-radius:50%; display:grid; place-items:center; font-size:18px; font-weight:800;
                        {{ $done ? 'color:var(--tp-on-accent, #fff); background:' . SellerUi::OK . ';' : ($active ? 'color:var(--tp-on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:var(--raise);' : 'color:var(--ink2); box-shadow:var(--inset-sm);') }}">
                        {{ $done ? '✓' : $icon }}
                    </span>
                    <span style="font-size:11.5px; font-weight:700; text-align:center; color:{{ $active || $done ? 'var(--ink)' : 'var(--ink2)' }};">{{ $label }}</span>
                </div>
                @if(! $loop->last)
                    <span style="flex:1; height:4px; border-radius:4px; margin-bottom:20px; background:{{ $currentStep > $n ? SellerUi::OK : 'color-mix(in srgb, var(--ink2) 20%, transparent)' }};"></span>
                @endif
            @endforeach
        </div>
    </div>

    @if($currentStep == 1)
        {{-- ── ขั้นที่ 1: KYC ─────────────────────────────────── --}}
        <div class="tp-card" style="padding:26px 22px; text-align:center;">
            <span class="tp-tile" style="width:74px; height:74px; border-radius:22px; font-size:34px; margin:0 auto;">🪪</span>
            <h2 style="font-size:20px; font-weight:800; margin:14px 0 4px;">ยืนยันตัวตน (KYC)</h2>
            <div style="font-size:13px; color:var(--ink2);">เพื่อความปลอดภัยของผู้ซื้อและร้านค้า กรุณายืนยันตัวตนก่อนเปิดร้าน</div>

            @if($kycStatus['status'] === 'pending')
                <div style="margin-top:18px;">
                    <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::WARN) }} font-size:13px; padding:8px 14px;">⏳ กำลังรอตรวจสอบเอกสาร</span>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:10px;">ทีมงานตรวจสอบภายใน 1–3 วันทำการ</div>
                    @if($kycStatus['latest_submission'] && $kycStatus['latest_submission']->submitted_at)
                        <div class="sv4-hint">ส่งเอกสารเมื่อ {{ $kycStatus['latest_submission']->submitted_at->format('d/m/Y H:i') }}</div>
                    @endif
                    <a href="{{ route('user.kyc.index') }}" class="tp-btn" style="margin-top:14px;">👁️ ดูสถานะ KYC</a>
                </div>
            @elseif($kycStatus['status'] === 'rejected')
                <div style="margin-top:18px;">
                    <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::BAD) }} font-size:13px; padding:8px 14px;">✕ เอกสารไม่ผ่านการตรวจสอบ</span>
                    @if($kycStatus['latest_submission'] && $kycStatus['latest_submission']->rejection_reason)
                        <div class="sv4-note" style="--c:{{ SellerUi::BAD }}; margin:12px auto 0; max-width:460px; text-align:left;">
                            <b>เหตุผล:</b> {{ $kycStatus['latest_submission']->rejection_reason }}
                        </div>
                    @endif
                    <a href="{{ route('user.kyc.create') }}" class="tp-btn tp-btn-primary" style="margin-top:14px; height:46px;">🔄 ส่งเอกสารใหม่</a>
                </div>
            @else
                <div class="sv4-grid" style="max-width:620px; margin:18px auto 0;">
                    <div class="sv4-well">🛡️<div style="font-size:12.5px; margin-top:4px;">ข้อมูลปลอดภัย เข้ารหัส</div></div>
                    <div class="sv4-well">⏱️<div style="font-size:12.5px; margin-top:4px;">ตรวจภายใน 1–3 วัน</div></div>
                    <div class="sv4-well">🔒<div style="font-size:12.5px; margin-top:4px;">ไม่เปิดเผยต่อผู้อื่น</div></div>
                </div>
                <a href="{{ route('user.kyc.create') }}" class="tp-btn tp-btn-primary" style="margin-top:18px; height:48px; padding:0 26px; font-size:14px;">📤 เริ่มยืนยันตัวตน</a>
            @endif
        </div>

    @elseif($currentStep == 2)
        {{-- ── ขั้นที่ 2: ตั้งค่าร้าน + เลือกแพ็กเกจ ─────────────── --}}
        <div x-data="sellerOnboarding(@js($onboardingConfig))" style="display:flex; flex-direction:column; gap:18px;">

            @if($store)
                <div class="tp-card" style="padding:18px 20px; display:flex; align-items:center; gap:14px;">
                    @if($store->logo_url)
                        <img src="{{ $store->logo_url }}" alt="โลโก้ {{ $store->store_name }}" style="width:54px; height:54px; border-radius:16px; object-fit:cover; box-shadow:var(--inset-sm);">
                    @else
                        <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:24px;">🏪</span>
                    @endif
                    <div style="min-width:0;">
                        <div style="font-size:17px; font-weight:800; overflow-wrap:anywhere;">{{ $store->store_name }}</div>
                        <div style="font-size:12.5px; color:var(--ink2);">ร้านของคุณพร้อมแล้ว เลือกแพ็กเกจเพื่อเริ่มขาย</div>
                    </div>
                </div>
            @else
                <div class="tp-card" style="padding:18px 20px;">
                    <label for="onb_store_name" class="sv4-label">ชื่อร้านค้า <span class="req">*</span></label>
                    <input type="text" id="onb_store_name" x-model="storeName" maxlength="255" class="tp-input" placeholder="เช่น ร้านผ้าไหมคุณแม่">
                    <div class="sv4-hint">แก้ไขภายหลังได้ที่หน้าตั้งค่าร้าน</div>
                </div>
            @endif

            @if($gpPromoActive)
                <div class="sv4-note" style="--c:{{ SellerUi::OK }};">🎉 ช่วงเปิดตัวไม่เก็บค่า GP ทุกแพ็กเกจ</div>
            @endif

            <div style="display:flex; justify-content:center;">
                <div class="sv4-tabs" style="display:inline-flex;">
                    <button type="button" class="sv4-tab" :class="subscriptionType === 'monthly' && 'on'" @click="subscriptionType = 'monthly'">รายเดือน</button>
                    <button type="button" class="sv4-tab" :class="subscriptionType === 'yearly' && 'on'" @click="subscriptionType = 'yearly'">รายปี (ประหยัดกว่า)</button>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; padding-top:8px;">
                @foreach($packages as $package)
                    @php
                        $isCustom = $subscriptionService->isCustomPricing($package);
                        $isFree = $subscriptionService->isFree($package);
                        $gpRate = rtrim(rtrim(number_format((float) $package->commission_rate, 2), '0'), '.');
                    @endphp
                    <div class="sv4-choice" style="position:relative; flex-direction:column; gap:10px; padding:20px; {{ $isCustom ? 'cursor:default;' : '' }}"
                         :class="selected === {{ (int) $package->id }} && 'on'"
                         @if(! $isCustom) @click="select({{ (int) $package->id }})" role="button" tabindex="0" @keydown.enter.prevent="select({{ (int) $package->id }})" @endif>
                        @if($package->badge)
                            <span class="sv4-pill tp-pill-gold" style="position:absolute; top:-10px; right:16px; color:var(--tp-on-accent, #fff);">{{ $package->badge }}</span>
                        @endif
                        <div class="sv4-row" style="width:100%;">
                            <div style="font-size:17px; font-weight:800;">{{ $package->display_name }}</div>
                            @unless($isCustom)
                                <span style="width:22px; height:22px; border-radius:50%; display:grid; place-items:center; font-size:12px; box-shadow:var(--inset-sm);"
                                      :style="selected === {{ (int) $package->id }} ? 'background:var(--accent1); color:var(--tp-on-accent, #fff); box-shadow:none;' : ''">
                                    <span x-show="selected === {{ (int) $package->id }}">✓</span>
                                </span>
                            @endunless
                        </div>
                        @if($package->description)
                            <div style="font-size:12px; color:var(--ink2); line-height:1.5;">{{ $package->description }}</div>
                        @endif
                        <div>
                            @if($isCustom)
                                <div style="font-size:20px; font-weight:800;">ราคาพิเศษ</div>
                            @elseif($isFree)
                                <div class="tp-num" style="font-size:26px; font-weight:800; color:{{ SellerUi::OK }};">ฟรี</div>
                            @else
                                <div x-show="subscriptionType === 'monthly'"><span class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $package->price) }}</span><span style="font-size:12px; color:var(--ink2);"> /เดือน</span></div>
                                <div x-show="subscriptionType === 'yearly'" x-cloak>
                                    @if($package->yearly_price)
                                        <span class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $package->yearly_price) }}</span><span style="font-size:12px; color:var(--ink2);"> /ปี</span>
                                    @else
                                        <span style="font-size:13px; color:var(--ink2);">มีเฉพาะรายเดือน</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($package->trial_days > 0 && ! $isCustom)
                            <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::INFO) }}">🎁 ทดลองใช้ฟรี {{ $package->trial_days }} วัน</span>
                        @endif
                        @if(! empty($package->features))
                            <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:5px; font-size:12.5px;">
                                @foreach((array) $package->features as $feature)
                                    <li style="display:flex; gap:7px;"><span style="color:{{ SellerUi::OK }}; font-weight:800;">✓</span><span>{{ is_string($feature) ? $feature : '' }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="sv4-well" style="padding:8px 12px; font-size:12px; width:100%;">
                            ค่า GP: @if($gpPromoActive)<b style="color:{{ SellerUi::OK }};">ฟรีช่วงเปิดตัว</b> <span style="color:var(--ink2);">(ปกติ {{ $gpRate }}%)</span>@else<b class="tp-num">{{ $gpRate }}%</b>@endif
                        </div>
                        @if($isCustom)
                            <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('สนใจแพ็กเกจ ' . $package->display_name) }}" class="tp-btn tp-btn-sm sv4-btn-block">📞 ติดต่อทีมงาน</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="sv4-note" style="--c:{{ SellerUi::INFO }};" x-show="selectedIsPaid()" x-cloak>
                💳 แพ็กเกจเสียเงิน: ระบบเปิดร้านด้วยแพ็กเกจฟรีให้ก่อน แล้วพาไปหน้าชำระเงิน (ชำระด้วยยอดในกระเป๋า) แพ็กเกจเปลี่ยนทันทีหลังชำระสำเร็จ
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                <form action="{{ route('seller.onboarding.create-store') }}" method="POST" @submit="if (!canSubmit()) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <input type="hidden" name="store_name" :value="storeName">
                    <input type="hidden" name="package_id" :value="selected">
                    <input type="hidden" name="subscription_type" :value="subscriptionType">
                    <button type="submit" class="tp-btn tp-btn-primary" style="height:48px; padding:0 26px; font-size:14px;" :disabled="!canSubmit() || busy">
                        <span x-show="!busy" x-text="selectedIsPaid() ? '🚀 ดำเนินการต่อ (ไปชำระเงิน)' : '🚀 เริ่มใช้งานร้านค้า'"></span>
                        <span x-show="busy" x-cloak>กำลังดำเนินการ…</span>
                    </button>
                </form>
                <form action="{{ route('seller.onboarding.skip-package') }}" method="POST" x-data="{ b: false }" @submit="b = true">
                    @csrf
                    <button type="submit" class="tp-btn" style="height:48px; padding:0 22px;" :disabled="b">⏭️ ใช้แพ็กเกจฟรีก่อน</button>
                </form>
            </div>
            <div class="sv4-hint" style="text-align:center;" x-show="!hasStore && !storeName.trim()">กรอกชื่อร้านและเลือกแพ็กเกจก่อนกดเริ่มใช้งาน</div>
        </div>

    @else
        {{-- ── ขั้นที่ 3: พร้อมใช้งาน ─────────────────────────── --}}
        <div class="tp-card" style="padding:30px 22px; text-align:center;">
            <span style="width:84px; height:84px; border-radius:50%; margin:0 auto; display:grid; place-items:center; font-size:40px; color:var(--tp-on-accent, #fff); background:{{ SellerUi::OK }}; box-shadow:var(--raise);">✓</span>
            <h2 style="font-size:22px; font-weight:800; margin:16px 0 6px;">ร้านของคุณพร้อมขายแล้ว!</h2>
            <div style="font-size:13px; color:var(--ink2);">เพิ่มสินค้าแรก แล้วแชร์ลิงก์หน้าร้านให้ลูกค้าได้เลย</div>
            <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:18px;">
                <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-primary" style="height:48px; padding:0 24px;">➕ เพิ่มสินค้าแรก</a>
                <a href="{{ route('seller.dashboard') }}" class="tp-btn" style="height:48px; padding:0 24px;">📊 ไปแดชบอร์ด</a>
            </div>
        </div>
    @endif

    {{-- ความช่วยเหลือ --}}
    <div class="tp-card" style="padding:18px 20px;">
        <div class="sv4-h2">🙋 ต้องการความช่วยเหลือ?</div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
            <a href="{{ route('seller.user-guide.index') }}" class="tp-btn tp-btn-sm">📘 คู่มือการใช้งาน</a>
            <a href="mailto:{{ $supportEmail }}" class="tp-btn tp-btn-sm">✉️ ติดต่อทีมงาน</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * ขั้นตอนเปิดร้าน: เลือกแพ็กเกจ + รอบการชำระ แล้วส่งไปสร้างร้าน
 */
function sellerOnboarding(cfg) {
    return {
        storeName: cfg.storeName || '',
        hasStore: !!cfg.hasStore,
        packages: cfg.packages || [],
        selected: null,
        subscriptionType: 'monthly',
        busy: false,
        pkg() {
            return this.packages.find((p) => p.id === this.selected) || null;
        },
        select(id) {
            const p = this.packages.find((x) => x.id === id);
            if (!p || p.custom) return;
            this.selected = id;
        },
        selectedIsPaid() {
            const p = this.pkg();
            return !!p && !p.free && !p.custom;
        },
        canSubmit() {
            if (!this.selected) return false;
            if (!this.hasStore && !this.storeName.trim()) return false;
            return true;
        }
    };
}
</script>
@endpush
