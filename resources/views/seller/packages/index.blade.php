@extends('layouts.seller-v4')

@section('title', 'แพ็กเกจร้านค้า')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    $subscriptionService = app(\App\Services\VendorSubscriptionService::class);
    $gpPromoActive = app(\App\Services\Pricing\PricingEngine::class)->gpPromoActive();
    $supportEmail = \App\Support\ContactInfo::supportEmail();
    $currentPackage = $store?->package;
    $subscriptionLabels = [
        'trial' => 'ทดลองใช้',
        'active' => 'ใช้งานอยู่',
        'expired' => 'หมดอายุ',
        'cancelled' => 'ยกเลิกแล้ว',
        'pending' => 'รอชำระเงิน',
    ];
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="แพ็กเกจร้านค้า" subtitle="เริ่มต้นฟรี หรืออัปเกรดเพื่อปลดล็อกความสามารถเพิ่ม" icon="💎" :back="route('seller.dashboard')" />

    @if($gpPromoActive)
        <div class="sv4-note" style="--c:{{ SellerUi::OK }}; font-size:13px;">
            🎉 <b>ช่วงเปิดตัว: ไม่เก็บค่า GP ทุกแพ็กเกจ</b> — อัตรา GP ด้านล่างจะเริ่มใช้หลังหมดโปรโมชัน
        </div>
    @endif

    @if($store && $currentPackage)
        <div class="tp-card" style="padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:21px;">🏷️</span>
                <div style="min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); font-weight:700;">แพ็กเกจปัจจุบัน</div>
                    <div style="font-size:18px; font-weight:800;">{{ $currentPackage->display_name }}</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">
                        @if($store->subscription_status === 'trial')
                            ทดลองใช้ฟรี · หมดอายุ {{ $store->trial_ends_at?->diffForHumans() ?? '-' }}
                        @elseif($store->subscription_status === 'active')
                            ใช้งานได้ถึง {{ $store->subscription_expires_at?->format('d/m/Y') ?? 'ไม่มีกำหนด' }}
                        @else
                            สถานะ: {{ $subscriptionLabels[$store->subscription_status] ?? $store->subscription_status }}
                        @endif
                    </div>
                </div>
            </div>
            @if($store->subscription_status === 'active' && ! $subscriptionService->isFree($currentPackage))
                <x-seller-v4.confirm-form :action="route('seller.packages.cancel')" title="ยกเลิกแพ็กเกจ?"
                                          message="ร้านจะหยุดต่ออายุแพ็กเกจนี้ และอาจใช้ความสามารถบางอย่างไม่ได้ จนกว่าจะสมัครใหม่"
                                          confirm-label="ยกเลิกแพ็กเกจ" danger>
                    <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ SellerUi::BAD }};">ยกเลิกแพ็กเกจ</button>
                </x-seller-v4.confirm-form>
            @endif
        </div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; padding-top:10px;">
        @foreach($packages as $package)
            @php
                $isCurrent = $store && (int) $store->package_id === (int) $package->id;
                $isCustom = $subscriptionService->isCustomPricing($package);
                $isFree = $subscriptionService->isFree($package);
                $gpRate = rtrim(rtrim(number_format((float) $package->commission_rate, 2), '0'), '.');
                $features = array_values(array_filter([
                    $package->max_products ? number_format($package->max_products) . ' สินค้า' : 'สินค้าไม่จำกัด',
                    $package->max_images_per_product ? $package->max_images_per_product . ' รูปต่อสินค้า' : null,
                    $package->max_storage_mb ? number_format($package->max_storage_mb / 1024, 1) . ' GB พื้นที่เก็บรูป' : null,
                    $package->max_monthly_orders ? number_format($package->max_monthly_orders) . ' ออเดอร์/เดือน' : 'ออเดอร์ไม่จำกัด',
                    $package->allow_custom_domain ? 'โดเมนของร้านเอง' : null,
                    $package->allow_advanced_analytics ? 'รายงานขั้นสูง' : null,
                    $package->allow_marketing_tools ? 'เครื่องมือการตลาด' : null,
                    $package->allow_ai_bot ? 'แชทบอท AI' : null,
                    $package->priority_support ? 'ทีมงานดูแลพิเศษ' : null,
                ]));
            @endphp
            <div class="tp-card" style="position:relative; padding:0; overflow:visible; display:flex; flex-direction:column; {{ $package->is_featured ? 'box-shadow:var(--card-shadow), 0 0 0 2px var(--accent1);' : '' }}">
                @if($package->is_featured || $package->badge)
                    <span class="sv4-pill tp-pill-gold" style="position:absolute; top:-11px; left:18px; color:var(--tp-on-accent, #fff);">{{ $package->badge ?: '⭐ แนะนำ' }}</span>
                @endif
                <div style="padding:22px 20px 16px; border-radius:var(--card-radius) var(--card-radius) 0 0; background:linear-gradient(135deg, color-mix(in srgb, var(--accent1) {{ $package->is_featured ? 26 : 14 }}%, transparent), transparent);">
                    <div style="font-size:18px; font-weight:800;">{{ $package->display_name }}</div>
                    @if($package->description)
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px; line-height:1.5;">{{ $package->description }}</div>
                    @endif
                    <div style="margin-top:12px;">
                        @if($isCustom)
                            <div style="font-size:22px; font-weight:800;">ราคาพิเศษ</div>
                            <div style="font-size:12px; color:var(--ink2);">ตามข้อตกลงกับทีมงาน</div>
                        @elseif($isFree)
                            <div class="tp-num" style="font-size:30px; font-weight:800; color:{{ SellerUi::OK }};">ฟรี</div>
                        @else
                            <div><span class="tp-num" style="font-size:30px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $package->price, 0) }}</span>
                                <span style="font-size:12px; color:var(--ink2);">/เดือน</span></div>
                            @if($package->yearly_price)
                                <div style="font-size:12px; color:var(--ink2); margin-top:2px;">
                                    หรือ <span class="tp-num" style="font-weight:700; color:var(--ink);">฿{{ number_format((float) $package->yearly_price, 0) }}</span>/ปี
                                    @if($package->yearly_savings_percentage)
                                        <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::OK) }}">ประหยัด {{ $package->yearly_savings_percentage }}%</span>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div style="padding:16px 20px; flex:1; display:flex; flex-direction:column; gap:12px;">
                    <div class="sv4-well" style="padding:10px 12px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <span style="font-size:12px; color:var(--ink2); font-weight:700;">ค่า GP ต่อยอดขาย</span>
                        <span>
                            @if($gpPromoActive)
                                <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::OK) }}">ฟรี (ปกติ {{ $gpRate }}%)</span>
                            @else
                                <span class="tp-num" style="font-weight:800;">{{ $gpRate }}%</span>
                            @endif
                        </span>
                    </div>
                    <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:7px; font-size:13px;">
                        @foreach($features as $feature)
                            <li style="display:flex; gap:8px; align-items:flex-start;"><span style="color:{{ SellerUi::OK }}; font-weight:800;">✓</span><span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>
                    @if($package->trial_days > 0 && ! $isCustom)
                        <div class="sv4-note" style="--c:{{ SellerUi::INFO }}; padding:9px 12px;">🎁 ทดลองใช้ฟรี {{ $package->trial_days }} วัน</div>
                    @endif

                    <div style="margin-top:auto; display:flex; flex-direction:column; gap:8px;">
                        @if($isCurrent)
                            <span class="tp-btn is-disabled sv4-btn-block" aria-disabled="true">✓ แพ็กเกจปัจจุบัน</span>
                        @elseif($isCustom)
                            <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('สนใจแพ็กเกจ ' . $package->display_name) }}" class="tp-btn sv4-btn-block">📞 ติดต่อทีมงาน</a>
                        @else
                            <form action="{{ route('seller.packages.subscribe', $package->id) }}" method="POST" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <input type="hidden" name="subscription_type" value="monthly">
                                <button type="submit" class="tp-btn sv4-btn-block {{ $package->is_featured || $isFree ? 'tp-btn-primary' : '' }}" :disabled="busy">
                                    {{ $isFree ? 'เริ่มใช้ฟรี' : 'สมัครรายเดือน' }}
                                </button>
                            </form>
                            @if($package->yearly_price && ! $isFree)
                                <form action="{{ route('seller.packages.subscribe', $package->id) }}" method="POST" x-data="{ busy: false }" @submit="busy = true">
                                    @csrf
                                    <input type="hidden" name="subscription_type" value="yearly">
                                    <button type="submit" class="tp-btn tp-btn-sm sv4-btn-block" :disabled="busy">
                                        สมัครรายปี{{ $package->yearly_savings_percentage ? ' (ประหยัด ' . $package->yearly_savings_percentage . '%)' : '' }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="tp-card" style="padding:16px 20px; text-align:center; font-size:12.5px; color:var(--ink2); line-height:1.7;">
        ชำระค่าแพ็กเกจผ่านกระเป๋าเงินของคุณ (เติมเงินด้วย PromptPay/โอนได้ที่ <a href="{{ route('user.wallet.topup') }}" class="sv4-link">เติมเงิน</a>)
        · มีคำถาม ติดต่อทีมงาน <a href="mailto:{{ $supportEmail }}" class="sv4-link">{{ $supportEmail }}</a>
    </div>
</div>
@endsection
