@extends('layouts.admin-v4')

@section('title', 'Official Shop — ร้านทางการ')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $catMax = max(1, (int) collect($categoriesWithProducts ?? [])->max('products_count'));
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัว ===== --}}
    <div class="tp-card" style="padding:22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 22%, var(--card-bg)), var(--card-bg) 72%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:14px; align-items:flex-end;">
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · ร้านทางการ</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">Official Shop 👑</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สินค้าที่แพลตฟอร์มขายเอง — ไม่มีค่า GP รายได้เข้ากระเป๋าร้านทางการ</div>
            </div>
            <div style="display:flex; gap:9px; flex-wrap:wrap;">
                <a href="{{ route('official-shop.index') }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-up-right-from-square"></i> ดูหน้าร้าน</a>
                <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-boxes-stacked"></i> สินค้าทั้งหมด</a>
                <a href="{{ route('admin.official-shop.products.create') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้า</a>
            </div>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @foreach([
            ['สินค้าทั้งหมด', $stats['total_products'] ?? 0, 'fa-box', null, route('admin.official-shop.products.index')],
            ['เปิดขาย', $stats['active_products'] ?? 0, 'fa-circle-check', $c['ok'], route('admin.official-shop.products.index', ['status' => 'active'])],
            ['สินค้าแนะนำ', $stats['featured_products'] ?? 0, 'fa-star', $c['warn'], route('admin.official-shop.products.index', ['status' => 'featured'])],
            ['สินค้าหมด', $stats['out_of_stock'] ?? 0, 'fa-circle-xmark', $c['bad'], route('admin.official-shop.products.index', ['stock_status' => 'out_of_stock'])],
            ['ยอดเข้าชม', $stats['total_views'] ?? 0, 'fa-eye', $c['info'], null],
            ['ขายแล้ว (ชิ้น)', $stats['total_sales'] ?? 0, 'fa-cart-shopping', $c['violet'], null],
        ] as [$label, $value, $icon, $color, $url])
            @if($url)<a href="{{ $url }}" class="tp-card tp-card-hover" style="padding:14px 16px; text-decoration:none; color:inherit;">@else<div class="tp-card" style="padding:14px 16px;">@endif
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="tp-tile" style="width:38px; height:38px; font-size:15px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:21px; font-weight:800; line-height:1;">{{ number_format((int) $value) }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            @if($url)</a>@else</div>@endif
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:16px; align-items:start;">
        @foreach([
            ['สินค้าขายดี', 'fa-fire', $topProducts, route('admin.official-shop.products.index', ['sort_by' => 'sales_count', 'sort_order' => 'desc'])],
            ['เพิ่มล่าสุด', 'fa-clock', $recentProducts, route('admin.official-shop.products.index')],
        ] as [$title, $icon, $list, $moreUrl])
            <div class="tp-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div class="tp-section-h"><i class="fas {{ $icon }}" style="color:var(--accent1);"></i> {{ $title }}</div>
                    <a href="{{ $moreUrl }}" style="font-size:12.5px; color:var(--deep1);">ดูทั้งหมด →</a>
                </div>
                @forelse($list as $product)
                    <a href="{{ route('admin.official-shop.products.show', $product) }}" style="display:flex; gap:10px; align-items:center; padding:8px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 16%, transparent); text-decoration:none; color:var(--ink);">
                        @if($product->main_image_url)
                            <img src="{{ $product->main_image_url }}" alt="" loading="lazy" style="width:42px; height:42px; border-radius:11px; object-fit:cover; flex:none;">
                        @else
                            <span class="tp-well" style="width:42px; height:42px; border-radius:11px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                        @endif
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</div>
                            <div style="font-size:11.5px; color:var(--ink2);">{{ $product->category?->name ?? 'ไม่ระบุหมวด' }} · ขาย <span class="tp-num">{{ number_format((int) $product->sales_count) }}</span></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="tp-num" style="font-weight:700; font-size:13px;">฿{{ number_format((float) $product->price, 2) }}</div>
                            <span class="tp-pill" style="{{ $pill($product->is_active ? $c['ok'] : $c['mute']) }}">{{ $product->is_active ? 'เปิด' : 'ปิด' }}</span>
                        </div>
                    </a>
                @empty
                    <div style="font-size:13px; color:var(--ink2); padding:20px 0; text-align:center;">ยังไม่มีสินค้า</div>
                @endforelse
            </div>
        @endforeach
    </div>

    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-tags" style="color:var(--accent1);"></i> หมวดหมู่ที่มีสินค้าร้านทางการ</div>
        @forelse($categoriesWithProducts as $category)
            <a href="{{ route('admin.official-shop.products.index', ['category' => $category->id]) }}" style="display:block; margin-bottom:10px; text-decoration:none; color:var(--ink);">
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span>{{ $category->name }}</span><span class="tp-num" style="font-weight:700;">{{ number_format((int) $category->products_count) }}</span></div>
                <div class="tp-inset-sm" style="height:8px; border-radius:6px; margin-top:5px; overflow:hidden;">
                    <div style="height:100%; width:{{ round(((int) $category->products_count / $catMax) * 100) }}%; background:linear-gradient(90deg,var(--accent1),var(--accent2)); border-radius:6px;"></div>
                </div>
            </a>
        @empty
            <div style="font-size:13px; color:var(--ink2);">ยังไม่มีสินค้าที่เปิดขาย</div>
        @endforelse
    </div>
</div>
@endsection
