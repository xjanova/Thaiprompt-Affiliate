@extends('layouts.admin-v4')

@section('title', $product->name . ' · Official Shop')

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
    $gallery = collect([$product->primary_image_url])
        ->merge($product->images->map(fn ($img) => $img->url))
        ->filter()->unique()->values();
    $reviews = $product->approvedReviews->sortByDesc('created_at')->take(10);
@endphp

@section('content')
{{-- 👑 รายละเอียดสินค้าร้านทางการ (OfficialShopAdminController::show — เดิมไม่มีไฟล์ view → 500) --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · Official Shop · สินค้า</div>
            <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">{{ $product->name }}</h1>
            <div style="display:flex; gap:7px; flex-wrap:wrap; margin-top:8px;">
                <span class="tp-pill" style="{{ $pill($product->is_active ? $c['ok'] : $c['mute']) }}">{{ $product->is_active ? 'เปิดขาย' : 'ปิดขาย' }}</span>
                @if($product->is_featured)<span class="tp-pill tp-pill-gold">⭐ แนะนำ</span>@endif
                @if($product->allow_coin_purchase)<span class="tp-pill tp-pill-soft">🪙 ซื้อด้วยเหรียญได้</span>@endif
                <span class="tp-pill tp-pill-soft tp-num">SKU: {{ $product->sku ?: '-' }}</span>
            </div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            @if($product->slug)
                <a href="{{ route('official-shop.show', $product->slug) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-up-right-from-square"></i> ดูหน้าสินค้า</a>
            @endif
            <a href="{{ route('admin.official-shop.products.edit', $product) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-pen"></i> แก้ไข</a>
            <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @foreach([
            ['ราคา', '฿'.number_format((float) $product->price, 2), 'fa-tag', null],
            ['ขายแล้ว', number_format((int) $product->sales_count).' ชิ้น', 'fa-cart-shopping', $c['ok']],
            ['เข้าชม', number_format((int) $product->view_count), 'fa-eye', $c['info']],
            ['สต็อก', $product->track_inventory ? number_format((int) $product->stock_quantity) : 'ไม่นับ', 'fa-boxes-stacked', $c['warn']],
        ] as [$label, $value, $icon, $color])
            <div class="tp-card" style="padding:14px 16px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="tp-tile" style="width:38px; height:38px; font-size:15px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:19px; font-weight:800; line-height:1.1;">{{ $value }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:16px; align-items:start;">
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
                <div class="tp-well" style="aspect-ratio:1/1; border-radius:18px; display:grid; place-items:center; color:var(--ink2);"><i class="fas fa-image" style="font-size:36px; opacity:.5;"></i></div>
            @endif
        </div>

        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> รายละเอียด</div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">หมวดหมู่</span><span>{{ $product->category?->name ?? 'ไม่ระบุ' }}</span></div>
                @if($product->brand)<div style="{{ $row }}"><span style="color:var(--ink2);">แบรนด์</span><span>{{ $product->brand }}</span></div>@endif
                @if((float) $product->compare_at_price > 0)<div style="{{ $row }}"><span style="color:var(--ink2);">ราคาก่อนลด</span><span class="tp-num" style="text-decoration:line-through;">฿{{ number_format((float) $product->compare_at_price, 2) }}</span></div>@endif
                @if((float) $product->cost_price > 0)<div style="{{ $row }}"><span style="color:var(--ink2);">ต้นทุน</span><span class="tp-num">฿{{ number_format((float) $product->cost_price, 2) }}</span></div>@endif
                <div style="{{ $row }}"><span style="color:var(--ink2);">ค่า GP</span><span>0% (ร้านทางการ)</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">PV ต่อชิ้น</span><span class="tp-num">{{ rtrim(rtrim(number_format((float) $product->pv_value, 2), '0'), '.') ?: '0' }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">เงินคืนลูกค้า</span><span class="tp-num">{{ rtrim(rtrim(number_format((float) $product->cashback_percentage, 2), '0'), '.') ?: '0' }}%</span></div>
                @if($product->allow_coin_purchase)<div style="{{ $row }}"><span style="color:var(--ink2);">ราคาเป็นเหรียญ</span><span class="tp-num">{{ number_format((float) $product->price_coins) }}</span></div>@endif
                <div style="{{ $row }}"><span style="color:var(--ink2);">สร้างเมื่อ</span><span class="tp-num">{{ $product->created_at?->format('d/m/Y H:i') }}</span></div>
            </div>

            <div class="tp-card" style="display:flex; gap:10px; flex-wrap:wrap;">
                <form method="POST" action="{{ route('admin.official-shop.products.toggle-active', $product) }}">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm"><i class="fas {{ $product->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i> {{ $product->is_active ? 'ปิดการขาย' : 'เปิดการขาย' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.official-shop.products.toggle-featured', $product) }}">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm"><i class="{{ $product->is_featured ? 'far' : 'fas' }} fa-star"></i> {{ $product->is_featured ? 'เลิกแนะนำ' : 'ตั้งเป็นสินค้าแนะนำ' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.official-shop.products.destroy', $product) }}"
                      @submit="if (!confirm(@js('ลบสินค้า "'.$product->name.'" ?'))) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};"><i class="fas fa-trash"></i> ลบ</button>
                </form>
            </div>
        </div>
    </div>

    @if($product->short_description || $product->description)
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-align-left" style="color:var(--accent1);"></i> คำอธิบายสินค้า</div>
            @if($product->short_description)<div style="font-weight:600; font-size:13.5px; margin-bottom:8px;">{{ $product->short_description }}</div>@endif
            <div style="font-size:13px; line-height:1.75; white-space:pre-line; overflow-wrap:anywhere;">{{ $product->description }}</div>
        </div>
    @endif

    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-star" style="color:var(--accent1);"></i> รีวิวที่อนุมัติแล้ว</div>
        @forelse($reviews as $review)
            <div style="padding:10px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 18%, transparent); font-size:13px;">
                <strong>{{ $review->user?->name ?? 'ผู้ใช้' }}</strong>
                <span style="color:{{ $c['warn'] }};">{{ str_repeat('★', (int) $review->rating) }}</span>
                <span class="tp-num" style="font-size:12px; color:var(--ink2);">{{ $review->created_at?->format('d/m/Y') }}</span>
                @if($review->comment)<div style="margin-top:4px; overflow-wrap:anywhere;">{{ $review->comment }}</div>@endif
            </div>
        @empty
            <div style="font-size:13px; color:var(--ink2);">ยังไม่มีรีวิว</div>
        @endforelse
    </div>
</div>
@endsection
