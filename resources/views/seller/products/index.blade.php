@extends('layouts.seller-v4')

@section('title', 'จัดการสินค้า')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Services\Shop\ShopPresenter;
    use App\Support\Seller\SellerUi;

    $hasFilter = request()->anyFilled(['search', 'status', 'stock']);
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="จัดการสินค้า" subtitle="เพิ่ม แก้ไข เปิด/ปิดการขาย และดูสต็อกสินค้าของร้าน" icon="📦">
        <a href="{{ route('seller.pricing.planner') }}" class="tp-btn tp-btn-sm">💡 วางแผนราคา</a>
        <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-sm tp-btn-primary">➕ เพิ่มสินค้าใหม่</a>
    </x-seller-v4.header>

    <div class="sv4-stats">
        <x-seller-v4.stat label="สินค้าทั้งหมด" :value="number_format($stats['total'] ?? 0)" icon="📦" :href="route('seller.products.index')" />
        <x-seller-v4.stat label="เปิดขายอยู่" :value="number_format($stats['active'] ?? 0)" icon="✅" :color="SellerUi::OK"
                          :href="route('seller.products.index', ['status' => 'active'])" />
        <x-seller-v4.stat label="สต็อกใกล้หมด" :value="number_format($stats['low_stock'] ?? 0)" icon="⚠️" :color="SellerUi::WARN"
                          :href="route('seller.products.index', ['stock' => 'low_stock'])" />
        <x-seller-v4.stat label="สินค้าหมด" :value="number_format($stats['out_of_stock'] ?? 0)" icon="❌" :color="SellerUi::BAD"
                          :href="route('seller.products.index', ['stock' => 'out_of_stock'])" />
    </div>

    {{-- ตัวกรอง --}}
    <form method="GET" action="{{ route('seller.products.index') }}" class="tp-card" style="padding:16px 18px;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
            <div style="flex:1 1 240px; position:relative;">
                <input type="search" name="search" value="{{ request('search') }}" class="tp-input" placeholder="🔍 ค้นหาชื่อสินค้าหรือ SKU…" aria-label="ค้นหาสินค้า">
            </div>
            <select name="status" class="tp-input" style="flex:0 1 170px; width:auto;" aria-label="สถานะ">
                <option value="">ทุกสถานะ</option>
                <option value="active" @selected(request('status') === 'active')>เปิดขาย</option>
                <option value="inactive" @selected(request('status') === 'inactive')>ปิดขาย</option>
            </select>
            <select name="stock" class="tp-input" style="flex:0 1 170px; width:auto;" aria-label="สต็อก">
                <option value="">สต็อกทั้งหมด</option>
                <option value="in_stock" @selected(request('stock') === 'in_stock')>มีสินค้า</option>
                <option value="low_stock" @selected(request('stock') === 'low_stock')>สต็อกต่ำ</option>
                <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>สินค้าหมด</option>
            </select>
            <button type="submit" class="tp-btn tp-btn-primary">ค้นหา</button>
            @if($hasFilter)
                <a href="{{ route('seller.products.index') }}" class="tp-btn">ล้างตัวกรอง</a>
            @endif
        </div>
    </form>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($products->count() > 0)
            <div class="sv4-table-wrap">
                <table class="sv4-table">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th class="sv4-hide-sm">หมวดหมู่</th>
                            <th style="text-align:right;">ราคา</th>
                            <th style="text-align:right;">สต็อก</th>
                            <th class="sv4-hide-sm" style="text-align:right;">ขายแล้ว</th>
                            <th style="text-align:center;">เปิดขาย</th>
                            <th style="text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $img = ShopPresenter::imageUrl($product->main_image_url);
                                $lowStock = $product->stock_quantity <= ($product->low_stock_threshold ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:11px; min-width:200px;">
                                        @if($img)
                                            <img src="{{ $img }}" alt="รูปสินค้า {{ $product->name }}" class="sv4-thumb" loading="lazy">
                                        @else
                                            <span class="sv4-thumb">📦</span>
                                        @endif
                                        <div style="min-width:0;">
                                            <a href="{{ route('seller.products.edit', $product) }}" style="font-weight:800; color:var(--ink); text-decoration:none; overflow-wrap:anywhere;">{{ $product->name }}</a>
                                            <div style="font-size:11px; color:var(--ink2);">
                                                <span class="tp-num">{{ $product->sku }}</span>
                                                @if($product->brand) · {{ $product->brand }} @endif
                                            </div>
                                            @if($product->is_blocked)
                                                <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::BAD) }} margin-top:4px;">⛔ ถูกระงับโดยแอดมิน</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="sv4-hide-sm">{{ $product->category->name ?? '-' }}</td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap;">
                                    <div style="font-weight:800;">฿{{ number_format((float) $product->price, 2) }}</div>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <div style="font-size:11px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format((float) $product->compare_at_price, 2) }}</div>
                                    @endif
                                </td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap;">
                                    <span style="font-weight:800; color:{{ $product->stock_quantity <= 0 ? SellerUi::BAD : ($lowStock ? SellerUi::WARN : 'var(--ink)') }};">{{ number_format($product->stock_quantity) }}</span>
                                    @if($product->stock_quantity <= 0)
                                        <div style="font-size:10.5px; color:{{ SellerUi::BAD }};">หมด</div>
                                    @elseif($lowStock)
                                        <div style="font-size:10.5px; color:{{ SellerUi::WARN }};">ใกล้หมด</div>
                                    @endif
                                </td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right;">{{ number_format((int) $product->sales_count) }}</td>
                                <td style="text-align:center;">
                                    <form action="{{ route('seller.products.toggle-status', $product) }}" method="POST" x-data="{ busy: false }" @submit="busy = true">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm" :disabled="busy"
                                                aria-pressed="{{ $product->is_active ? 'true' : 'false' }}"
                                                title="{{ $product->is_active ? 'กำลังขาย — กดเพื่อปิดการขาย' : 'ปิดอยู่ — กดเพื่อเปิดขาย' }}"
                                                style="{{ SellerUi::pill($product->is_active ? SellerUi::OK : SellerUi::MUTED) }} white-space:nowrap;">
                                            {{ $product->is_active ? '● เปิดขาย' : '○ ปิดอยู่' }}
                                        </button>
                                    </form>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:6px; flex-wrap:nowrap;">
                                        <a href="{{ route('seller.products.edit', $product) }}" class="tp-btn tp-btn-sm" title="แก้ไข">✏️<span class="sv4-hide-sm"> แก้ไข</span></a>
                                        <a href="{{ route('seller.pricing.planner', ['product_id' => $product->id]) }}" class="tp-btn tp-btn-sm" title="วางแผนราคา">💡</a>
                                        <x-seller-v4.confirm-form :action="route('seller.products.destroy', $product)" method="DELETE"
                                                                  title="ลบสินค้านี้?"
                                                                  :message="'ลบ “' . $product->name . '” พร้อมรูปภาพทั้งหมด การลบย้อนกลับไม่ได้'"
                                                                  confirm-label="ลบสินค้า" danger>
                                            <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ SellerUi::BAD }};" title="ลบ">🗑️</button>
                                        </x-seller-v4.confirm-form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $products->appends(request()->query())->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="📦" :title="$hasFilter ? 'ไม่พบสินค้าที่ตรงกับตัวกรอง' : 'ยังไม่มีสินค้า'"
                               :text="$hasFilter ? 'ลองเปลี่ยนคำค้นหาหรือล้างตัวกรอง' : 'เพิ่มสินค้าชิ้นแรกของร้านได้เลย ใช้เวลาไม่ถึง 2 นาที'">
                @if($hasFilter)
                    <a href="{{ route('seller.products.index') }}" class="tp-btn tp-btn-sm">ล้างตัวกรอง</a>
                @else
                    <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-sm tp-btn-primary">➕ เพิ่มสินค้าแรก</a>
                @endif
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
