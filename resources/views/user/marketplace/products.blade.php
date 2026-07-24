@extends('layouts.user-v4')

@section('title', 'Marketplace Affiliate - สินค้าแนะนำสำหรับแอฟฟิลิเอท')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:32px 24px; text-align:center; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="font-size:56px; margin-bottom:10px;">🛍️</div>
        <h1 style="font-size:clamp(26px,5.5vw,40px); font-weight:800; margin:0; color:var(--deep1);">Marketplace Affiliate</h1>
        <p style="font-size:17px; color:var(--ink); font-weight:700; margin:10px 0 4px;">แชร์สินค้า รับค่าคอมมิชชั่นสูง</p>
        <p style="font-size:14px; color:var(--ink2); margin:0; max-width:640px; margin-inline:auto;">เลือกสินค้าคุณภาพ แชร์ให้เพื่อน รับค่าคอมมิชชั่นทันที พร้อมโอกาสสร้างรายได้ไม่จำกัด</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px; max-width:760px; margin:26px auto 0;">
            @php
                $mpStats = [
                    ['📦', $products->total(), 'สินค้าทั้งหมด'],
                    ['💰', number_format($products->max('commission_rate') ?? 0) . '%', 'คอมมิชชั่นสูงสุด'],
                    ['🎯', $categories->count(), 'หมวดหมู่'],
                    ['🚀', '∞', 'รายได้ไม่จำกัด'],
                ];
            @endphp
            @foreach($mpStats as [$icon, $val, $label])
                <div class="tp-card" style="padding:16px; box-shadow:var(--inset-sm);">
                    <div style="font-size:26px; margin-bottom:6px;">{{ $icon }}</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">{{ $val }}</div>
                    <div style="font-size:12px; color:var(--ink2);">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <form method="GET" action="{{ route('user.marketplace.products') }}">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">🔍 ค้นหา</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อสินค้า..." style="width:100%; padding:10px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">📁 หมวดหมู่</label>
                    <select name="category" style="width:100%; padding:10px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">💵 ราคา (฿)</label>
                    <div style="display:flex; gap:8px;">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="ต่ำสุด" style="width:50%; padding:10px 11px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="สูงสุด" style="width:50%; padding:10px 11px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">🔄 เรียงตาม</label>
                    <select name="sort_by" style="width:100%; padding:10px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                        <option value="newest" {{ request('sort_by') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                        <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ขายดี</option>
                        <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                        <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                        <option value="commission_high" {{ request('sort_by') == 'commission_high' ? 'selected' : '' }}>คอมมิชชั่นสูงสุด</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:12px; margin-top:16px;">
                <button type="submit" class="tp-btn" style="padding:10px 22px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer;">🔍 ค้นหา</button>
                <a href="{{ route('user.marketplace.products') }}" class="tp-btn" style="padding:10px 22px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; text-decoration:none;">🔄 รีเซ็ต</a>
            </div>
        </form>
    </div>

    {{-- ── Products Grid ─────────────────────────────────────── --}}
    @if($products->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:18px;">
            @foreach($products as $product)
                <div class="tp-card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <div style="position:relative; height:180px; overflow:hidden; background:var(--surf);">
                        @if($product->primary_image)
                            <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:52px;">📦</div>
                        @endif
                        @if($product->commission_rate > 0)
                            <div class="tp-pill" style="position:absolute; top:12px; right:12px; background:#5aa07e; color:#fff; font-size:12px; font-weight:800; box-shadow:var(--raise);">💰 {{ number_format($product->commission_rate) }}%</div>
                        @endif
                        @if($product->is_featured)
                            <div class="tp-pill" style="position:absolute; top:12px; left:12px; background:var(--accent1); color:#fff; font-size:12px; font-weight:800; box-shadow:var(--raise);">⭐ แนะนำ</div>
                        @endif
                    </div>
                    <div style="padding:18px; display:flex; flex-direction:column; flex:1;">
                        @if($product->category)<div style="font-size:11.5px; color:var(--ink2); margin-bottom:6px;">{{ $product->category->name }}</div>@endif
                        <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0 0 8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $product->name }}</h3>
                        @if($product->short_description)<p style="font-size:12.5px; color:var(--ink2); margin:0 0 12px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $product->short_description }}</p>@endif
                        <div style="display:flex; align-items:baseline; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
                            <span class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">฿{{ number_format($product->price, 2) }}</span>
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                <span style="font-size:13px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format($product->compare_at_price, 2) }}</span>
                                <span class="tp-pill" style="background:color-mix(in srgb, #d9534f 18%, transparent); color:#d9534f; font-size:11px; font-weight:700;">-{{ $product->discount_percentage }}%</span>
                            @endif
                        </div>
                        @if($product->commission_rate > 0)
                            <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); margin-bottom:14px; background:color-mix(in srgb, #5aa07e 8%, transparent);">
                                <div style="font-size:11.5px; color:#5aa07e; margin-bottom:2px;">คอมมิชชั่นที่คุณจะได้รับ:</div>
                                <div class="tp-num" style="font-size:17px; font-weight:800; color:#5aa07e;">฿{{ number_format($product->calculateCommission($product->price), 2) }}</div>
                            </div>
                        @endif
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:11.5px; color:var(--ink2); margin-bottom:14px;">
                            <span>👁️ {{ number_format($product->view_count) }}</span>
                            <span>🛒 {{ number_format($product->sales_count) }} ขาย</span>
                            @if($product->rating_count > 0)<span>⭐ {{ number_format($product->rating_average, 1) }}</span>@endif
                        </div>
                        <a href="{{ route('shop.show', $product->slug ?: $product->id) }}" target="_blank" class="tp-btn" style="margin-top:auto; display:block; width:100%; padding:11px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; text-align:center; text-decoration:none; box-shadow:var(--raise);">🚀 ดูสินค้าและแชร์</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div style="display:flex; justify-content:center;">{{ $products->links() }}</div>
    @else
        <div class="tp-card" style="padding:60px 24px; text-align:center;">
            <div style="font-size:64px; margin-bottom:16px;">🔍</div>
            <h3 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 8px;">ไม่พบสินค้า</h3>
            <p style="color:var(--ink2); font-size:14px; margin:0 0 22px;">ลองปรับเกณฑ์การค้นหาใหม่อีกครั้ง</p>
            <a href="{{ route('user.marketplace.products') }}" class="tp-btn" style="display:inline-block; padding:12px 24px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">🔄 ดูสินค้าทั้งหมด</a>
        </div>
    @endif

    {{-- ── วิธีสร้างรายได้ ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:28px 24px;">
        <h2 style="font-size:22px; font-weight:800; text-align:center; color:var(--deep1); margin:0 0 24px;">💡 วิธีสร้างรายได้จาก Marketplace Affiliate</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:18px;">
            @foreach([['1️⃣','เลือกสินค้า','เลือกสินค้าที่คุณสนใจจากรายการด้านบน'],['2️⃣','แชร์ลิงก์','แชร์ลิงก์สินค้าให้เพื่อนและครอบครัว'],['3️⃣','มีคนซื้อ','เมื่อมีคนซื้อผ่านลิงก์ของคุณ'],['4️⃣','รับค่าคอมฯ','คุณจะได้รับค่าคอมมิชชั่นทันที']] as [$num, $title, $desc])
                <div style="text-align:center;">
                    <div style="font-size:42px; margin-bottom:12px;">{{ $num }}</div>
                    <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 6px;">{{ $title }}</h3>
                    <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
        <div class="tp-card" style="margin-top:24px; padding:22px; box-shadow:var(--inset-sm); text-align:center; background:color-mix(in srgb, var(--accent1) 8%, transparent);">
            <div style="font-size:36px; margin-bottom:10px;">💰</div>
            <h3 style="font-size:18px; font-weight:800; color:var(--deep1); margin:0 0 8px;">ตัวอย่างรายได้</h3>
            <p style="color:var(--ink2); font-size:14px; margin:0;">แชร์สินค้าราคา <strong style="color:var(--deep1);">1,000฿</strong> คอมมิชชั่น <strong style="color:var(--deep1);">10%</strong> มีคนซื้อ <strong style="color:var(--deep1);">100</strong> คน</p>
            <p class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e; margin:12px 0 0;">= รายได้ 10,000฿</p>
        </div>
    </div>
</div>
@endsection
