@extends('layouts.seller-v4')

@section('title', 'การตลาด')

@php
    $typeLabels = [
        \App\Models\OfficialShopProduct::TYPE_AI_FEATURED => ['AI แนะนำ', 'gold'],
        \App\Models\OfficialShopProduct::TYPE_NEW_PRODUCT => ['สินค้าใหม่', 'info'],
        \App\Models\OfficialShopProduct::TYPE_BEST_SELLER => ['ขายดี', 'ok'],
    ];
    $canPromote = (bool) ($promoCheck['can_promote'] ?? false);
    $availableAt = $promoCheck['available_at'] ?? null;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ศูนย์การตลาด" icon="📢" crumb="ร้านค้า · การตลาด"
                         :subtitle="'เครื่องมือเพิ่มยอดขายของ '.($store->store_name ?? 'ร้านคุณ').' — คูปอง โปรโมทสินค้าใหม่ และพื้นที่ Official Shop'">
        <a href="{{ route('seller.coupons.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">🎟️ สร้างคูปอง</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
        <x-seller-kit.stat label="สินค้าใน Official Shop" :value="number_format($stats['in_official_shop'])" icon="🏅" tone="gold" />
        <x-seller-kit.stat label="AI แนะนำ" :value="number_format($stats['ai_featured'])" icon="🤖" tone="violet" />
        <x-seller-kit.stat label="สินค้าใหม่" :value="number_format($stats['as_new_product'])" icon="🆕" tone="info" />
        <x-seller-kit.stat label="ขายดี" :value="number_format($stats['as_best_seller'])" icon="🔥" tone="ok" />
        <x-seller-kit.stat label="การแจ้งเตือนค้าง" :value="number_format($stats['pending_warnings'])" icon="⚠️" :tone="$stats['pending_warnings'] > 0 ? 'bad' : 'muted'" />
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- โปรโมทสินค้าใหม่ --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:12px; background:linear-gradient(140deg, color-mix(in srgb, var(--accent1) 18%, var(--card-bg)), var(--card-bg) 70%);">
            <div class="tp-section-h">🚀 โปรโมทสินค้าใหม่ (ฟรี)</div>
            @if($activePromotion)
                <div style="display:flex; gap:12px; align-items:center;">
                    <span class="tp-tile" style="width:48px; height:48px; font-size:22px;" aria-hidden="true">📦</span>
                    <div style="min-width:0;">
                        <div style="font-weight:800; overflow-wrap:anywhere;">{{ $activePromotion->product->name ?? 'สินค้า' }}</div>
                        <div style="font-size:12px; color:var(--ink2);">กำลังโปรโมท · สิ้นสุด {{ $activePromotion->time_remaining ?? '-' }}</div>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px;"><div style="font-size:11px; color:var(--ink2);">ยอดเข้าชมช่วงโปร</div><div class="tp-num" style="font-weight:800;">{{ number_format((int) $activePromotion->views_during_promo) }}</div></div>
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px;"><div style="font-size:11px; color:var(--ink2);">ขายได้ช่วงโปร</div><div class="tp-num" style="font-weight:800;">{{ number_format((int) $activePromotion->sales_during_promo) }}</div></div>
                </div>
            @elseif($canPromote)
                <div style="font-size:13px; line-height:1.65;">คุณใช้สิทธิ์ดันสินค้าใหม่ขึ้นหน้า Official Shop ได้ {{ \App\Models\NewProductPromotion::PROMOTION_DAYS }} วัน โดยไม่มีค่าใช้จ่าย</div>
                <a href="{{ route('seller.marketing.select-product') }}" class="tp-btn tp-btn-primary">เลือกสินค้าที่จะโปรโมท →</a>
            @else
                <div style="font-size:13px; line-height:1.65;">{{ $promoCheck['reason'] ?? 'ยังใช้สิทธิ์โปรโมทไม่ได้ตอนนี้' }}</div>
                @if($availableAt)
                    <x-seller-kit.pill tone="info">ใช้สิทธิ์ได้อีกครั้ง {{ \Illuminate\Support\Carbon::parse($availableAt)->diffForHumans() }}</x-seller-kit.pill>
                @endif
            @endif
            <a href="{{ route('seller.marketing.promotion-history') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ดูประวัติการโปรโมท →</a>
        </div>

        {{-- การแจ้งเตือน --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="tp-section-h">⚠️ สินค้าที่ต้องปรับปรุง</div>
                <a href="{{ route('seller.marketing.warnings') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ทั้งหมด →</a>
            </div>
            @forelse($pendingWarnings->take(4) as $warning)
                <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
                    <div style="font-weight:700; font-size:13px;">{{ $warning->product->name ?? 'สินค้า' }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:2px;">คะแนน {{ number_format((float) $warning->current_score, 1) }} / ต้องการ {{ number_format((float) $warning->required_score, 1) }} · เหลือเวลา {{ $warning->time_remaining }}</div>
                </div>
            @empty
                <x-seller-kit.empty icon="✅" title="ไม่มีการแจ้งเตือน" text="สินค้าใน Official Shop ของคุณผ่านเกณฑ์ทั้งหมด" />
            @endforelse
        </div>
    </div>

    {{-- สินค้าใน Official Shop --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 18px;">
            <div class="tp-section-h">🏅 สินค้าที่อยู่ใน Official Shop</div>
            <a href="{{ route('seller.marketing.official-products') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ดูทั้งหมด →</a>
        </div>
        @if($officialProducts->count() > 0)
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:12px; padding:0 18px 18px;">
                @foreach($officialProducts->take(6) as $op)
                    @php
                        [$tLabel, $tTone] = $typeLabels[$op->selection_type] ?? [$op->selection_type, 'muted'];
                    @endphp
                    <div class="tp-inset-sm" style="border-radius:14px; padding:12px; display:flex; flex-direction:column; gap:6px;">
                        <div style="font-weight:700; font-size:13px; overflow-wrap:anywhere;">{{ $op->product->name ?? 'สินค้า' }}</div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <x-seller-kit.pill :tone="$tTone">{{ $tLabel }}</x-seller-kit.pill>
                            @if($op->ai_score)<x-seller-kit.pill tone="muted">AI {{ number_format((float) $op->ai_score, 0) }}</x-seller-kit.pill>@endif
                        </div>
                        @if($op->expires_at)<div style="font-size:11px; color:var(--ink2);">หมดอายุ {{ $op->time_remaining }}</div>@endif
                    </div>
                @endforeach
            </div>
        @else
            <x-seller-kit.empty icon="🏅" title="ยังไม่มีสินค้าใน Official Shop" text="สินค้าที่รีวิวดี ขายดี หรือใช้สิทธิ์โปรโมทสินค้าใหม่ จะถูกคัดขึ้นหน้า Official Shop อัตโนมัติ" />
        @endif
    </div>

    {{-- ทางลัดเครื่องมือการตลาด --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
        @foreach([
            ['🎟️', 'คูปองร้าน', 'แจกส่วนลด ดึงลูกค้าใหม่ และปิดการขาย', route('seller.coupons.index')],
            ['⭐', 'คะแนนและรีวิวร้าน', 'ตอบรีวิวลูกค้าให้ร้านน่าเชื่อถือ', route('seller.store-rating.index')],
            ['🏆', 'รางวัลและร้าน Premium', 'สะสม Trophy ปลดล็อกสถานะร้านพรีเมียม', route('seller.achievements.index')],
        ] as [$qIcon, $qTitle, $qText, $qUrl])
            <a href="{{ $qUrl }}" class="tp-card tp-card-hover" style="text-decoration:none; color:var(--ink); display:flex; gap:12px; align-items:center;">
                <span class="tp-tile" style="width:46px; height:46px; font-size:21px;" aria-hidden="true">{{ $qIcon }}</span>
                <span>
                    <span style="display:block; font-weight:800;">{{ $qTitle }}</span>
                    <span style="display:block; font-size:12px; color:var(--ink2); margin-top:2px;">{{ $qText }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
