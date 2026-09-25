@extends('layouts.admin-v4')

@section('title', 'สินค้า Official Shop')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · Official Shop · สินค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สินค้าร้านทางการ 👑</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สินค้าที่แพลตฟอร์มขายเอง (ไม่มีค่า GP)</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.official-shop.dashboard') }}" class="tp-btn tp-btn-sm"><i class="fas fa-gauge"></i> แดชบอร์ด</a>
            <a href="{{ route('admin.official-shop.products.create') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้า</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @foreach([
            ['ทั้งหมด', $stats['total'] ?? 0, 'fa-box', null, []],
            ['เปิดขาย', $stats['active'] ?? 0, 'fa-circle-check', $c['ok'], ['status' => 'active']],
            ['แนะนำ', $stats['featured'] ?? 0, 'fa-star', $c['warn'], ['status' => 'featured']],
            ['สินค้าหมด', $stats['out_of_stock'] ?? 0, 'fa-circle-xmark', $c['bad'], ['stock_status' => 'out_of_stock']],
        ] as [$label, $value, $icon, $color, $query])
            <a href="{{ route('admin.official-shop.products.index', $query) }}" class="tp-card tp-card-hover" style="padding:14px 16px; text-decoration:none; color:inherit;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="tp-tile" style="width:38px; height:38px; font-size:15px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:21px; font-weight:800; line-height:1;">{{ number_format((int) $value) }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.official-shop.products.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; align-items:end;">
            <div style="grid-column:1 / -1;">
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ชื่อ SKU หรือคำอธิบาย">
            </div>
            <div>
                <label style="{{ $lbl }}">หมวดหมู่</label>
                <select name="category" class="tp-input">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">สถานะ</label>
                <select name="status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="active" @selected(request('status') === 'active')>เปิดขาย</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>ปิดขาย</option>
                    <option value="featured" @selected(request('status') === 'featured')>สินค้าแนะนำ</option>
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">สต็อก</label>
                <select name="stock_status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="in_stock" @selected(request('stock_status') === 'in_stock')>มีสินค้า</option>
                    <option value="low_stock" @selected(request('stock_status') === 'low_stock')>ใกล้หมด</option>
                    <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock')>หมด</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:900px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">สินค้า</th>
                        <th style="{{ $th }}">หมวดหมู่</th>
                        <th style="{{ $th }} text-align:right;">ราคา</th>
                        <th style="{{ $th }} text-align:right;">สต็อก</th>
                        <th style="{{ $th }} text-align:center;">เปิดขาย</th>
                        <th style="{{ $th }} text-align:center;">แนะนำ</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" loading="lazy" style="width:46px; height:46px; border-radius:12px; object-fit:cover; flex:none;">
                                    @else
                                        <span class="tp-well" style="width:46px; height:46px; border-radius:12px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.official-shop.products.show', $product) }}" style="font-weight:700; color:var(--ink); text-decoration:none; display:block; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $product->sku ?: '-' }}@if((float) $product->pv_value > 0) · PV <span class="tp-num">{{ rtrim(rtrim(number_format((float) $product->pv_value, 2), '0'), '.') }}</span>@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }} color:var(--ink2);">{{ $product->category?->name ?? 'ไม่ระบุ' }}</td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div class="tp-num" style="font-weight:700;">฿{{ number_format((float) $product->price, 2) }}</div>
                                @if((float) $product->compare_at_price > (float) $product->price)
                                    <div class="tp-num" style="font-size:11.5px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format((float) $product->compare_at_price, 2) }}</div>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:right;">
                                @if(! $product->track_inventory)
                                    <span style="font-size:12px; color:var(--ink2);">ไม่นับ</span>
                                @else
                                    <span class="tp-num" style="font-weight:800; color:{{ (int) $product->stock_quantity <= 0 ? $c['bad'] : ((int) $product->stock_quantity <= (int) ($product->low_stock_threshold ?? 5) ? $c['warn'] : $c['ok']) }};">{{ number_format((int) $product->stock_quantity) }}</span>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:center;">
                                <form method="POST" action="{{ route('admin.official-shop.products.toggle-active', $product) }}">
                                    @csrf
                                    <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; margin:auto; color:{{ $product->is_active ? $c['ok'] : $c['mute'] }};" title="{{ $product->is_active ? 'ปิดการขาย' : 'เปิดการขาย' }}">
                                        <i class="fas {{ $product->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="font-size:18px;"></i>
                                    </button>
                                </form>
                            </td>
                            <td style="{{ $td }} text-align:center;">
                                <form method="POST" action="{{ route('admin.official-shop.products.toggle-featured', $product) }}">
                                    @csrf
                                    <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; margin:auto; color:{{ $product->is_featured ? 'var(--accent1)' : $c['mute'] }};" title="{{ $product->is_featured ? 'เลิกแนะนำ' : 'ตั้งเป็นสินค้าแนะนำ' }}">
                                        <i class="{{ $product->is_featured ? 'fas' : 'far' }} fa-star"></i>
                                    </button>
                                </form>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="{{ route('admin.official-shop.products.edit', $product) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="แก้ไข"><i class="fas fa-pen"></i></a>
                                    @if($product->slug)
                                        <a href="{{ route('official-shop.show', $product->slug) }}" target="_blank" rel="noopener" class="tp-icon-btn" style="width:34px; height:34px;" title="ดูหน้าสินค้า"><i class="fas fa-up-right-from-square"></i></a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.official-shop.products.destroy', $product) }}"
                                          @submit="if (!confirm(@js('ลบสินค้า "'.$product->name.'" ?'))) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" style="width:34px; height:34px; color:{{ $c['bad'] }};" title="ลบ"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-box-open" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ยังไม่มีสินค้าร้านทางการ
                                <div style="margin-top:12px;"><a href="{{ route('admin.official-shop.products.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้าแรก</a></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($products->hasPages())
        <div>{{ $products->links() }}</div>
    @endif
</div>
@endsection
