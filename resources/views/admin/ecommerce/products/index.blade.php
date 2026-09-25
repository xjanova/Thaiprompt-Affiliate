@extends('layouts.admin-v4')

@section('title', 'สินค้าทั้งหมด')

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
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    // เปิดฟอร์มเพิ่มสินค้าอัตโนมัติเมื่อบันทึกไม่ผ่าน validation
    $openCreate = $errors->any() && old('name') !== null;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="{ createOpen: @js($openCreate) }" @keydown.escape.window="createOpen = false">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · สินค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สินค้าทั้งหมด 📦</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สินค้าของทุกร้านในระบบ — แก้ไข บล็อก หรือกำหนดอัตรา GP รายสินค้าได้จากหน้าแก้ไข</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.products.blocked') }}" class="tp-btn tp-btn-sm"><i class="fas fa-ban"></i> สินค้าที่ถูกบล็อก</a>
            <a href="{{ route('admin.ecommerce.lazada-import.form') }}" class="tp-btn tp-btn-sm"><i class="fas fa-cloud-arrow-down"></i> นำเข้าจาก Lazada</a>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="createOpen = true"><i class="fas fa-plus"></i> เพิ่มสินค้า</button>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <div style="font-weight:700; color:{{ $c['bad'] }}; margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.ecommerce.products.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; align-items:end;">
            <div style="grid-column:1 / -1;">
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ชื่อสินค้า รหัส SKU หรือคำอธิบาย">
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
            <div>
                <label style="{{ $lbl }}">เรียงตาม</label>
                <select name="sort_by" class="tp-input">
                    <option value="created_at" @selected(request('sort_by', 'created_at') === 'created_at')>ล่าสุด</option>
                    <option value="name" @selected(request('sort_by') === 'name')>ชื่อ</option>
                    <option value="price" @selected(request('sort_by') === 'price')>ราคา</option>
                    <option value="stock_quantity" @selected(request('sort_by') === 'stock_quantity')>สต็อก</option>
                    <option value="sales_count" @selected(request('sort_by') === 'sales_count')>ยอดขาย</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @if(request('store_id'))<input type="hidden" name="store_id" value="{{ request('store_id') }}">@endif
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ===== ตารางสินค้า ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:var(--inset-sm);">
            <div class="tp-section-h">รายการสินค้า</div>
            <div style="font-size:12px; color:var(--ink2);">พบ <span class="tp-num">{{ number_format($products->total()) }}</span> รายการ</div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:900px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">สินค้า</th>
                        <th style="{{ $th }}">ร้าน / ผู้ขาย</th>
                        <th style="{{ $th }}">หมวดหมู่</th>
                        <th style="{{ $th }} text-align:right;">ราคา</th>
                        <th style="{{ $th }} text-align:right;">สต็อก</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" loading="lazy" style="width:46px; height:46px; border-radius:12px; object-fit:cover; box-shadow:var(--inset-sm); flex:none;">
                                    @else
                                        <span class="tp-well" style="width:46px; height:46px; border-radius:12px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.ecommerce.products.show', $product) }}" style="font-weight:700; color:var(--ink); text-decoration:none; display:block; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $product->sku ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }}">
                                @if($product->store)
                                    <a href="{{ route('admin.storefront.vendor-stores.show', $product->store) }}" style="color:var(--ink); text-decoration:none; display:block; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->store->store_name }}</a>
                                @endif
                                <div style="font-size:11.5px; color:var(--ink2); max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->seller?->name ?? '-' }}</div>
                            </td>
                            <td style="{{ $td }} color:var(--ink2);">{{ $product->category?->name ?? 'ไม่ระบุ' }}</td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div class="tp-num" style="font-weight:700;">฿{{ number_format((float) $product->price, 2) }}</div>
                                @if((float) $product->compare_at_price > (float) $product->price)
                                    <div class="tp-num" style="font-size:11.5px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format((float) $product->compare_at_price, 2) }}</div>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                @if(! $product->track_inventory)
                                    <span style="color:var(--ink2); font-size:12px;">ไม่นับสต็อก</span>
                                @else
                                    @php $stockColor = (int) $product->stock_quantity <= 0 ? $c['bad'] : ((int) $product->stock_quantity <= (int) ($product->low_stock_threshold ?? 10) ? $c['warn'] : $c['ok']); @endphp
                                    <span class="tp-num" style="font-weight:800; color:{{ $stockColor }};">{{ number_format((int) $product->stock_quantity) }}</span>
                                @endif
                            </td>
                            <td style="{{ $td }}">
                                <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                    @if($product->is_blocked)
                                        <span class="tp-pill" style="{{ $pill($c['bad']) }}">🚫 ถูกบล็อก</span>
                                    @elseif($product->is_active)
                                        <span class="tp-pill" style="{{ $pill($c['ok']) }}">เปิดขาย</span>
                                    @else
                                        <span class="tp-pill" style="{{ $pill($c['mute']) }}">ปิดขาย</span>
                                    @endif
                                    @if($product->is_featured)<span class="tp-pill tp-pill-gold">⭐ แนะนำ</span>@endif
                                    @if($product->is_hidden)<span class="tp-pill tp-pill-soft">ซ่อน</span>@endif
                                    @if($product->admin_gp_rate !== null)<span class="tp-pill" style="{{ $pill($c['info']) }}" title="อัตรา GP ที่แอดมินกำหนด">GP {{ rtrim(rtrim(number_format((float) $product->admin_gp_rate, 2), '0'), '.') }}%</span>@endif
                                </div>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="{{ route('admin.ecommerce.products.show', $product) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="แก้ไข"><i class="fas fa-pen"></i></a>
                                    <form method="POST" action="{{ route('admin.ecommerce.products.delete', $product) }}"
                                          @submit="if (!confirm(@js('ลบสินค้า "'.$product->name.'" ? การลบซ่อนสินค้าออกจากร้าน'))) $event.preventDefault()">
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
                                ไม่พบสินค้าตามเงื่อนไขนี้
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

    {{-- ===== ฟอร์มเพิ่มสินค้า (โมดัล) ===== --}}
    <div x-show="createOpen" x-cloak x-transition.opacity class="flex"
         style="position:fixed; inset:0; z-index:80; background:rgba(0,0,0,.45); align-items:flex-start; justify-content:center; padding:24px 12px; overflow-y:auto;"
         @click.self="createOpen = false">
        <div class="tp-card" style="width:100%; max-width:760px; padding:0; overflow:hidden;"
             x-data="{ busy: false, shipping: @js(old('shipping_method', 'store_default')), mainPreview: null, gallery: [],
                       pickMain(e) { const f = e.target.files[0]; this.mainPreview = f ? URL.createObjectURL(f) : null; },
                       pickGallery(e) { this.gallery = Array.from(e.target.files).slice(0, 10).map(f => URL.createObjectURL(f)); } }">
            <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:var(--tp-on-accent,#fff);">
                <div>
                    <div style="font-weight:800; font-size:17px;">เพิ่มสินค้าใหม่</div>
                    <div style="font-size:12px; opacity:.9;">สินค้าที่แอดมินเพิ่มจากหน้านี้จะอยู่ในบัญชีของคุณ — สินค้าของแพลตฟอร์มควรเพิ่มที่ Official Shop</div>
                </div>
                <button type="button" class="tp-icon-btn" style="width:36px; height:36px;" @click="createOpen = false" aria-label="ปิด"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.ecommerce.products.store') }}" enctype="multipart/form-data" style="padding:20px; display:flex; flex-direction:column; gap:14px;" @submit="busy = true">
                @csrf
                <div>
                    <label style="{{ $lbl }}">ชื่อสินค้า <span style="color:{{ $c['bad'] }};">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="tp-input" placeholder="กรอกชื่อสินค้า">
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">หมวดหมู่ <span style="color:{{ $c['bad'] }};">*</span></label>
                        <select name="category_id" required class="tp-input">
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku') }}" maxlength="100" class="tp-input tp-num" placeholder="รหัสสินค้า (ถ้ามี)">
                    </div>
                </div>
                <div>
                    <label style="{{ $lbl }}">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="tp-input" placeholder="รายละเอียดสินค้า">{{ old('description') }}</textarea>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">ราคาขาย (฿) <span style="color:{{ $c['bad'] }};">*</span></label>
                        <input type="number" name="price" step="0.01" min="0" required value="{{ old('price') }}" class="tp-input tp-num" placeholder="0.00">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ราคาก่อนลด (฿)</label>
                        <input type="number" name="compare_at_price" step="0.01" min="0" value="{{ old('compare_at_price') }}" class="tp-input tp-num" placeholder="0.00">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">จำนวนสต็อก</label>
                        <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', 0) }}" class="tp-input tp-num">
                    </div>
                </div>

                <div class="tp-well" style="padding:14px;">
                    <label style="{{ $lbl }}">🚚 การจัดส่ง</label>
                    <select name="shipping_method" x-model="shipping" class="tp-input">
                        <option value="store_default">ใช้ค่าตั้งต้นของร้าน</option>
                        <option value="free">ส่งฟรี</option>
                        <option value="flat_rate">ค่าส่งคงที่</option>
                        <option value="weight_based">คิดตามน้ำหนัก</option>
                    </select>
                    <div x-show="shipping === 'flat_rate'" x-cloak class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:12px;">
                        <div>
                            <label style="{{ $lbl }}">ค่าส่ง (฿)</label>
                            <input type="number" name="shipping_fee" step="0.01" min="0" value="{{ old('shipping_fee') }}" class="tp-input tp-num" placeholder="0.00" :disabled="shipping !== 'flat_rate'">
                        </div>
                        <div>
                            <label style="{{ $lbl }}">ส่งฟรีเมื่อซื้อครบ (฿)</label>
                            <input type="number" name="free_shipping_min_amount" step="0.01" min="0" value="{{ old('free_shipping_min_amount') }}" class="tp-input tp-num" placeholder="0 = ไม่มี" :disabled="shipping !== 'flat_rate'">
                        </div>
                    </div>
                    <div x-show="shipping === 'weight_based'" x-cloak class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:12px;">
                        <div>
                            <label style="{{ $lbl }}">น้ำหนัก (กก.)</label>
                            <input type="number" name="shipping_weight_kg" step="0.001" min="0" value="{{ old('shipping_weight_kg') }}" class="tp-input tp-num" placeholder="0.000" :disabled="shipping !== 'weight_based'">
                        </div>
                        <div>
                            <label style="{{ $lbl }}">ส่งฟรีเมื่อซื้อครบ (฿)</label>
                            <input type="number" name="free_shipping_min_amount_weight" step="0.01" min="0" value="{{ old('free_shipping_min_amount_weight') }}" class="tp-input tp-num" placeholder="0 = ไม่มี" :disabled="shipping !== 'weight_based'">
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">รูปหลัก (ไม่เกิน 5MB)</label>
                        <input type="file" name="main_image" accept="image/*" class="tp-input" style="padding:9px 12px;" @change="pickMain($event)">
                        <template x-if="mainPreview"><img :src="mainPreview" alt="" style="margin-top:8px; width:96px; height:96px; object-fit:cover; border-radius:14px; box-shadow:var(--raise);"></template>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">รูปเพิ่มเติม (สูงสุด 10 รูป)</label>
                        <input type="file" name="images[]" accept="image/*" multiple class="tp-input" style="padding:9px 12px;" @change="pickGallery($event)">
                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                            <template x-for="(src, i) in gallery" :key="i"><img :src="src" alt="" style="width:52px; height:52px; object-fit:cover; border-radius:10px; box-shadow:var(--raise);"></template>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:16px; flex-wrap:wrap; font-size:13px;">
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer;"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) style="accent-color:var(--accent1); width:17px; height:17px;"> เปิดขายทันที</label>
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer;"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) style="accent-color:var(--accent1); width:17px; height:17px;"> สินค้าแนะนำ</label>
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer;"><input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory')) style="accent-color:var(--accent1); width:17px; height:17px;"> นับสต็อก</label>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="tp-btn" @click="createOpen = false">ยกเลิก</button>
                    <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy">
                        <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="busy ? 'กำลังบันทึก...' : 'บันทึกสินค้า'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
