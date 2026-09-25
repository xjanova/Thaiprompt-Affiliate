@extends('layouts.admin-v4')

@section('title', $product->name)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:7px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 16%, transparent);';

    // อัตรา GP ที่ใช้คิดเงินจริง (จาก PricingEngine — แหล่งเดียวกับตอนแบ่งเงิน)
    $gpInfo = null;
    try {
        $gpInfo = app(\App\Services\Pricing\PricingEngine::class)->gpRateInfoForProduct($product);
    } catch (\Throwable $e) {
        $gpInfo = null;
    }
    $gallery = collect([$product->primary_image_url])
        ->merge($product->images->map(fn ($img) => $img->url))
        ->filter()->unique()->values();
    $store = $product->store;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · สินค้า</div>
            <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">{{ $product->name }}</h1>
            <div style="display:flex; gap:7px; flex-wrap:wrap; margin-top:8px;">
                @if($product->is_blocked)
                    <span class="tp-pill" style="{{ $pill($c['bad']) }}">🚫 ถูกบล็อก</span>
                @elseif($product->is_active)
                    <span class="tp-pill" style="{{ $pill($c['ok']) }}">เปิดขาย</span>
                @else
                    <span class="tp-pill" style="{{ $pill($c['mute']) }}">ปิดขาย</span>
                @endif
                @if($product->is_featured)<span class="tp-pill tp-pill-gold">⭐ สินค้าแนะนำ</span>@endif
                @if($product->is_hidden)<span class="tp-pill tp-pill-soft">ซ่อนจากหน้าร้าน</span>@endif
                <span class="tp-pill tp-pill-soft tp-num">SKU: {{ $product->sku ?: '-' }}</span>
            </div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-pen"></i> แก้ไข / บล็อก</a>
            <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        @foreach([
            ['ขายแล้ว', number_format((int) ($stats['total_sales'] ?? 0)).' ชิ้น', 'fa-cart-shopping', null],
            ['ยอดขายรวม', '฿'.number_format((float) ($stats['total_revenue'] ?? 0), 2), 'fa-sack-dollar', $c['ok']],
            ['รีวิว', number_format((int) ($stats['total_reviews'] ?? 0)), 'fa-comments', $c['info']],
            ['คะแนนเฉลี่ย', number_format((float) ($stats['average_rating'] ?? 0), 1).' ★', 'fa-star', $c['warn']],
        ] as [$label, $value, $icon, $color])
            <div class="tp-card" style="padding:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:40px; height:40px; font-size:16px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; line-height:1.1;">{{ $value }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:16px; align-items:start;">
        {{-- รูปสินค้า --}}
        <div class="tp-card" x-data="{ active: 0 }">
            @if($gallery->isNotEmpty())
                <div class="tp-well" style="border-radius:18px; overflow:hidden; aspect-ratio:1/1;">
                    @foreach($gallery as $i => $src)
                        <img x-show="active === {{ $i }}" @if($i > 0) x-cloak @endif src="{{ $src }}" alt="{{ $product->name }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                    @endforeach
                </div>
                @if($gallery->count() > 1)
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                        @foreach($gallery as $i => $src)
                            <button type="button" @click="active = {{ $i }}" style="border:0; padding:0; cursor:pointer; border-radius:11px; overflow:hidden; width:54px; height:54px;"
                                    :style="{ boxShadow: active === {{ $i }} ? '0 0 0 2px var(--accent1)' : 'var(--raise)' }">
                                <img src="{{ $src }}" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="tp-well" style="aspect-ratio:1/1; border-radius:18px; display:grid; place-items:center; color:var(--ink2);">
                    <div style="text-align:center;"><i class="fas fa-image" style="font-size:36px; opacity:.5;"></i><div style="font-size:12.5px; margin-top:6px;">ยังไม่มีรูปสินค้า</div></div>
                </div>
            @endif
        </div>

        {{-- ข้อมูลสินค้า --}}
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-tag" style="color:var(--accent1);"></i> ราคาและสต็อก</div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">ราคาขาย</span><span class="tp-num" style="font-weight:800; font-size:16px; color:var(--deep1);">฿{{ number_format((float) $product->price, 2) }}</span></div>
                @if((float) $product->compare_at_price > 0)
                    <div style="{{ $row }}"><span style="color:var(--ink2);">ราคาก่อนลด</span><span class="tp-num" style="text-decoration:line-through; color:var(--ink2);">฿{{ number_format((float) $product->compare_at_price, 2) }}</span></div>
                @endif
                @if((float) $product->cost_price > 0)
                    <div style="{{ $row }}"><span style="color:var(--ink2);">ต้นทุน</span><span class="tp-num">฿{{ number_format((float) $product->cost_price, 2) }}</span></div>
                @endif
                <div style="{{ $row }}"><span style="color:var(--ink2);">สต็อก</span>
                    <span class="tp-num" style="font-weight:700;">{{ $product->track_inventory ? number_format((int) $product->stock_quantity).' ชิ้น' : 'ไม่นับสต็อก' }}</span>
                </div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">หมวดหมู่</span><span>{{ $product->category?->name ?? 'ไม่ระบุ' }}</span></div>
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-percent" style="color:var(--accent1);"></i> ค่า GP ที่ใช้คิดเงินจริง</div>
                @if($gpInfo)
                    <div style="display:flex; align-items:baseline; gap:10px; margin-top:6px;">
                        <span class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">{{ rtrim(rtrim(number_format((float) $gpInfo['rate'], 2), '0'), '.') }}%</span>
                        <span style="font-size:12.5px; color:var(--ink2);">{{ $gpInfo['label_th'] }}</span>
                    </div>
                    @if(!empty($gpInfo['clamped']))<div style="font-size:12px; color:{{ $c['warn'] }};">ปรับขึ้นเป็นอัตราขั้นต่ำของแพลตฟอร์ม</div>@endif
                @else
                    <div style="font-size:13px; color:var(--ink2);">คำนวณอัตรา GP ไม่ได้ในขณะนี้</div>
                @endif
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">ค่า "คอมมิชชั่น" ที่ผู้ขายกรอกเองไม่ถูกใช้คิดเงิน — แก้อัตราเฉพาะสินค้าได้ที่หน้าแก้ไข</div>
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-store" style="color:var(--accent1);"></i> ร้านและผู้ขาย</div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">ร้าน</span>
                    @if($store)
                        <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" style="color:var(--deep1);">{{ $store->store_name }}</a>
                    @else
                        <span>-</span>
                    @endif
                </div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">ผู้ขาย</span><span>{{ $product->seller?->name ?? '-' }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">สร้างเมื่อ</span><span class="tp-num">{{ $product->created_at?->format('d/m/Y H:i') }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">แก้ไขล่าสุด</span><span class="tp-num">{{ $product->updated_at?->diffForHumans() }}</span></div>
            </div>
        </div>
    </div>

    @if($product->is_blocked)
        <div class="tp-card" style="border-left:4px solid {{ $c['bad'] }};">
            <div class="tp-section-h" style="color:{{ $c['bad'] }};">🚫 สินค้านี้ถูกบล็อก</div>
            <div style="font-size:13px; margin-top:6px;">เหตุผล: {{ $product->block_reason ?: '-' }}</div>
            @if($product->blocked_at)<div style="font-size:12px; color:var(--ink2);">เมื่อ {{ $product->blocked_at->format('d/m/Y H:i') }}</div>@endif
        </div>
    @endif

    @if($product->short_description || $product->description)
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-align-left" style="color:var(--accent1);"></i> รายละเอียดสินค้า</div>
            @if($product->short_description)<div style="font-weight:600; font-size:13.5px; margin-bottom:8px;">{{ $product->short_description }}</div>@endif
            <div style="font-size:13px; line-height:1.75; white-space:pre-line; overflow-wrap:anywhere;">{{ $product->description }}</div>
        </div>
    @endif

    {{-- ===== รีวิว ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-star" style="color:var(--accent1);"></i> รีวิวล่าสุด</div>
        @forelse($product->reviews->sortByDesc('created_at')->take(10) as $review)
            <div style="padding:10px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 18%, transparent);">
                <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; font-size:13px;">
                    <span><strong>{{ $review->user?->name ?? 'ผู้ใช้' }}</strong> <span style="color:{{ $c['warn'] }};">{{ str_repeat('★', (int) $review->rating) }}</span><span style="color:var(--ink2);">{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</span></span>
                    <span class="tp-num" style="font-size:12px; color:var(--ink2);">{{ $review->created_at?->format('d/m/Y') }}</span>
                </div>
                @if($review->comment)<div style="font-size:13px; margin-top:4px; overflow-wrap:anywhere;">{{ $review->comment }}</div>@endif
            </div>
        @empty
            <div style="font-size:13px; color:var(--ink2);">ยังไม่มีรีวิว</div>
        @endforelse
        @if($product->reviews->count() > 10)
            <a href="{{ route('admin.ecommerce.reviews.index', ['search' => $product->name]) }}" style="display:inline-block; margin-top:8px; font-size:12.5px; color:var(--deep1);">ดูรีวิวทั้งหมด →</a>
        @endif
    </div>

    {{-- ===== ลบสินค้า ===== --}}
    <div style="display:flex; justify-content:flex-end;">
        <form method="POST" action="{{ route('admin.ecommerce.products.delete', $product) }}"
              @submit="if (!confirm(@js('ลบสินค้า "'.$product->name.'" ?'))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};"><i class="fas fa-trash"></i> ลบสินค้านี้</button>
        </form>
    </div>
</div>
@endsection
