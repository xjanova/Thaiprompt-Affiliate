@extends('layouts.admin-v4')

@section('title', 'ตั้งค่าหน้าร้าน (Storefront)')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $check = 'display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer; padding:11px 13px; border-radius:12px;';

    // สีหน้าร้าน (ข้อมูลที่ร้านค้าหน้าบ้านใช้ ไม่ใช่สีของหลังบ้าน V4)
    $colors = [
        'primary' => (string) setting('storefront_primary_color', '#F97316'),
        'secondary' => (string) setting('storefront_secondary_color', '#EF4444'),
        'accent' => (string) setting('storefront_accent_color', '#EC4899'),
    ];
    $perRow = (string) setting('storefront_products_per_row', '6');
    $toggles = [
        ['show_flash_deals', 'storefront_show_flash_deals', '1', 'แสดง Flash Deals', 'โซนสินค้าลดราคาจำกัดเวลา'],
        ['show_featured_stores', 'storefront_show_featured_stores', '1', 'แสดงร้านค้าแนะนำ', 'ร้านที่เลือกในหน้า "ร้านแนะนำ"'],
        ['show_categories', 'storefront_show_categories', '1', 'แสดงหมวดหมู่', 'แถบหมวดหมู่หลักบนหน้าแรก'],
        ['show_pv_on_products', 'storefront_show_pv', '1', 'แสดง PV บนสินค้า', 'สำหรับสมาชิกที่ใช้ระบบค่าแนะนำ'],
        ['show_commission_on_products', 'storefront_show_commission', '0', 'แสดงค่าคอมมิชชั่นบนสินค้า', 'แนะนำให้ปิดสำหรับลูกค้าทั่วไป'],
    ];
    $activeTab = in_array(request('tab'), ['theme', 'banners', 'categories', 'layout'], true) ? request('tab') : 'theme';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="{ tab: @js($activeTab) }">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · หน้าร้าน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ตั้งค่าหน้าร้าน 🏬</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สี แบนเนอร์ หมวดหมู่ และการจัดวางของหน้าตลาดรวมร้านค้า</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.featured-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-award"></i> ร้านแนะนำ</a>
            <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-store"></i> ร้านค้า</a>
            <a href="{{ route('storefront.index') }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-up-right-from-square"></i> ดูหน้าร้าน</a>
        </div>
    </div>

    {{-- ===== แท็บ ===== --}}
    <div class="tp-card tp-inset-sm" style="padding:6px; display:flex; gap:6px; flex-wrap:wrap;">
        @foreach(['theme' => ['fa-palette', 'สีธีม'], 'banners' => ['fa-images', 'แบนเนอร์ ('.$banners->count().')'], 'categories' => ['fa-tags', 'หมวดหมู่'], 'layout' => ['fa-table-cells', 'การจัดวาง']] as $key => [$icon, $label])
            <button type="button" class="tp-seg" style="min-width:120px;" @click="tab = @js($key)" :style="{ background: tab === @js($key) ? 'var(--card-bg)' : '', boxShadow: tab === @js($key) ? 'var(--raise)' : '' }">
                <i class="fas {{ $icon }}"></i> {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ===== สีธีม ===== --}}
    <div x-show="tab === 'theme'" class="tp-card"
         x-data="{ primary: @js($colors['primary']), secondary: @js($colors['secondary']), accent: @js($colors['accent']) }">
        <div class="tp-section-h" style="margin-bottom:4px;">สีของหน้าร้าน</div>
        <div style="font-size:12px; color:var(--ink2); margin-bottom:14px;">ใช้กับปุ่ม ป้าย และไล่เฉดบนหน้าตลาดรวม (ไม่กระทบธีมหลังบ้าน)</div>
        <form method="POST" action="{{ route('admin.storefront.update-theme') }}" style="display:flex; flex-direction:column; gap:16px;" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                @foreach(['primary' => 'สีหลัก (Primary)', 'secondary' => 'สีรอง (Secondary)', 'accent' => 'สีเน้น (Accent)'] as $key => $label)
                    <div>
                        <label style="{{ $lbl }}">{{ $label }}</label>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <input type="color" name="{{ $key }}_color" x-model="{{ $key }}" style="width:48px; height:44px; border:0; padding:0; background:transparent; cursor:pointer; flex:none;">
                            <input type="text" name="{{ $key }}_color_hex" x-model="{{ $key }}" maxlength="20" class="tp-input tp-num" pattern="^#?[0-9A-Fa-f]{3,8}$">
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="border-radius:18px; padding:22px; color:var(--tp-on-accent,#fff);" :style="{ background: 'linear-gradient(120deg,' + primary + ',' + secondary + ',' + accent + ')' }">
                <div style="font-weight:800; font-size:17px;">ตัวอย่างไล่เฉดหน้าร้าน</div>
                <div style="font-size:13px; opacity:.85; margin-top:3px;">สีจริงที่ลูกค้าจะเห็นบนหน้าตลาดรวม</div>
                <span style="display:inline-block; margin-top:12px; padding:8px 16px; border-radius:11px; font-weight:700; font-size:13px; background:var(--tp-on-accent,#fff);" :style="{ color: primary }">ปุ่มตัวอย่าง</span>
            </div>
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy"><i class="fas fa-floppy-disk"></i> บันทึกสีธีม</button>
            </div>
        </form>
    </div>

    {{-- ===== แบนเนอร์ ===== --}}
    <div x-show="tab === 'banners'" x-cloak class="tp-card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
            <div class="tp-section-h">แบนเนอร์หน้าแรก</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="{{ route('admin.storefront.banners.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-sort"></i> จัดการ/เรียงลำดับ</a>
                <a href="{{ route('admin.storefront.banners.create') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-plus"></i> เพิ่มแบนเนอร์</a>
            </div>
        </div>
        @if($banners->count() > 0)
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,240px),1fr)); gap:14px;">
                @foreach($banners as $banner)
                    <div class="tp-well" style="padding:0; overflow:hidden; border-radius:16px;">
                        <div style="position:relative; aspect-ratio:16/7; background:linear-gradient(120deg,var(--accent1),var(--accent2));">
                            @if($banner->image_url)
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                <div style="position:absolute; inset:0; display:grid; place-items:center; color:var(--tp-on-accent,#fff); font-weight:800; padding:10px; text-align:center;">{{ $banner->title ?: 'ไม่มีรูป' }}</div>
                            @endif
                            <span class="tp-pill" style="position:absolute; top:8px; left:8px; {{ $pill($banner->is_active ? $c['ok'] : $c['mute']) }} background:var(--card-bg);">{{ $banner->is_active ? 'แสดงอยู่' : 'ปิด' }}</span>
                        </div>
                        <div style="padding:10px 12px; display:flex; justify-content:space-between; gap:8px; align-items:center;">
                            <div style="min-width:0;">
                                <div style="font-weight:700; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $banner->title ?: 'ไม่มีหัวข้อ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">ลำดับ {{ $banner->sort_order }}</div>
                            </div>
                            <a href="{{ route('admin.storefront.banners.edit', $banner) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="แก้ไข"><i class="fas fa-pen"></i></a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center; color:var(--ink2); padding:36px 0; font-size:13px;">
                <i class="fas fa-image" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                ยังไม่มีแบนเนอร์ — <a href="{{ route('admin.storefront.banners.create') }}" style="color:var(--deep1);">เพิ่มแบนเนอร์แรก</a>
            </div>
        @endif
    </div>

    {{-- ===== หมวดหมู่ ===== --}}
    <div x-show="tab === 'categories'" x-cloak class="tp-card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
            <div>
                <div class="tp-section-h">หมวดหมู่หลักที่แสดงบนหน้าร้าน</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">แสดงเฉพาะหมวดที่เปิดใช้งานและไม่มีหมวดแม่</div>
            </div>
            <a href="{{ route('admin.ecommerce.categories.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-tags"></i> จัดการหมวดหมู่</a>
        </div>
        @if($categories->count() > 0)
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,180px),1fr)); gap:12px;">
                @foreach($categories as $category)
                    <a href="{{ route('admin.ecommerce.products.index', ['category' => $category->id]) }}" class="tp-well" style="padding:12px; border-radius:14px; display:flex; gap:10px; align-items:center; text-decoration:none; color:var(--ink);">
                        @if($category->image_url_full)
                            <img src="{{ $category->image_url_full }}" alt="" loading="lazy" style="width:38px; height:38px; border-radius:10px; object-fit:cover; flex:none;">
                        @else
                            <span class="tp-tile" style="width:38px; height:38px; font-size:15px;"><i class="{{ $category->icon ?: 'fas fa-tag' }}"></i></span>
                        @endif
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $category->name }}</div>
                            <div style="font-size:11.5px; color:var(--ink2);">{{ number_format((int) ($category->products_count ?? $category->products()->count())) }} สินค้า</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div style="text-align:center; color:var(--ink2); padding:30px 0; font-size:13px;">ยังไม่มีหมวดหมู่หลักที่เปิดใช้งาน</div>
        @endif
    </div>

    {{-- ===== การจัดวาง ===== --}}
    <div x-show="tab === 'layout'" x-cloak class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;">การจัดวางหน้าตลาดรวม</div>
        <form method="POST" action="{{ route('admin.storefront.update-layout') }}" style="display:flex; flex-direction:column; gap:14px;" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')
            <div style="max-width:320px;">
                <label style="{{ $lbl }}">จำนวนสินค้าต่อแถว (จอใหญ่)</label>
                <select name="products_per_row" class="tp-input">
                    <option value="4" @selected($perRow === '4')>4 สินค้า</option>
                    <option value="5" @selected($perRow === '5')>5 สินค้า</option>
                    <option value="6" @selected($perRow === '6')>6 สินค้า (แนะนำ)</option>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr)); gap:10px;">
                @foreach($toggles as [$name, $key, $default, $label, $desc])
                    <label class="tp-well" style="{{ $check }}">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) setting($key, $default)) style="accent-color:var(--accent1); width:18px; height:18px; flex:none;">
                        <span><span style="font-weight:600;">{{ $label }}</span><br><span style="font-size:11.5px; color:var(--ink2);">{{ $desc }}</span></span>
                    </label>
                @endforeach
            </div>
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy"><i class="fas fa-floppy-disk"></i> บันทึกการจัดวาง</button>
            </div>
        </form>
    </div>
</div>
@endsection
