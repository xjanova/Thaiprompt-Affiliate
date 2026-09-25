@extends('layouts.admin-v4')

@section('title', 'สินค้าที่ถูกบล็อก')

@php
    $bad = 'var(--tp-bad,#d9534f)';
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · สินค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สินค้าที่ถูกบล็อก 🚫</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สินค้าที่ถูกระงับการขาย — ปลดบล็อกแล้วร้านต้องเปิดขายเอง</div>
        </div>
        <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> สินค้าทั้งหมด</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; font-size:17px; background:{{ $bad }};"><i class="fas fa-ban"></i></div>
                <div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1;">{{ number_format($products->total()) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">สินค้าถูกบล็อก</div>
                </div>
            </div>
        </div>
    </div>

    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.ecommerce.products.blocked') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ชื่อ SKU หรือเหตุผล">
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
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.ecommerce.products.blocked') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:860px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">สินค้า</th>
                        <th style="{{ $th }}">ผู้ขาย</th>
                        <th style="{{ $th }}">เหตุผลที่บล็อก</th>
                        <th style="{{ $th }}">บล็อกโดย</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" loading="lazy" style="width:44px; height:44px; border-radius:11px; object-fit:cover; opacity:.6; flex:none;">
                                    @else
                                        <span class="tp-well" style="width:44px; height:44px; border-radius:11px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <div style="font-weight:700; text-decoration:line-through; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</div>
                                        <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $product->sku ?: '-' }} · <span class="tp-num">฿{{ number_format((float) $product->price, 2) }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }}">{{ $product->seller?->name ?? '-' }}</td>
                            <td style="{{ $td }} max-width:280px; color:{{ $bad }}; overflow-wrap:anywhere;">{{ $product->block_reason ?: 'ไม่ระบุเหตุผล' }}</td>
                            <td style="{{ $td }} white-space:nowrap;">
                                <div>{{ $product->blockedByUser?->name ?? '-' }}</div>
                                <div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ $product->blocked_at ? $product->blocked_at->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="{{ route('admin.ecommerce.products.show', $product) }}" class="tp-btn tp-btn-sm"><i class="fas fa-eye"></i> ดู</a>
                                    <form method="POST" action="{{ route('admin.ecommerce.products.unblock', $product) }}"
                                          @submit="if (!confirm(@js('ปลดบล็อก "'.$product->name.'" ? ร้านค้าจะได้รับแจ้งเตือน'))) $event.preventDefault()">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-lock-open"></i> ปลดบล็อก</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-circle-check" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ไม่มีสินค้าที่ถูกบล็อก
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
