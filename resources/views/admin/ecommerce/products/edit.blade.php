@extends('layouts.admin-v4')

@section('title', 'แก้ไขสินค้า - ' . $product->name)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
    ];
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $hint = 'font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.5;';
    $check = 'display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer; padding:9px 12px; border-radius:12px;';

    // อัตรา GP ปัจจุบันที่ระบบใช้คิดเงิน (ก่อนแก้)
    $gpInfo = null;
    try {
        $gpInfo = app(\App\Services\Pricing\PricingEngine::class)->gpRateInfoForProduct($product);
    } catch (\Throwable $e) {
        $gpInfo = null;
    }
    $adminGp = old('admin_gp_rate', $product->admin_gp_rate !== null ? rtrim(rtrim(number_format((float) $product->admin_gp_rate, 2, '.', ''), '0'), '.') : '');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · แก้ไขสินค้า</div>
            <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">{{ $product->name }}</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ร้าน: {{ $product->store?->store_name ?? '-' }} · ผู้ขาย: {{ $product->seller?->name ?? '-' }}</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.products.show', $product) }}" class="tp-btn tp-btn-sm"><i class="fas fa-eye"></i> ดูสินค้า</a>
            <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
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

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">

        {{-- ===== ฟอร์มหลัก ===== --}}
        <form id="product-edit-form" method="POST" action="{{ route('admin.ecommerce.products.update', $product) }}" enctype="multipart/form-data"
              style="flex:2 1 460px; min-width:0; display:flex; flex-direction:column; gap:16px;"
              x-data="{ busy: false, shipping: @js(old('shipping_method', $product->shipping_method ?? 'store_default')),
                        mainPreview: null, gallery: [], deleted: [],
                        pickMain(e) { const f = e.target.files[0]; if (f && f.size > 5 * 1024 * 1024) { alert('ขนาดไฟล์ต้องไม่เกิน 5MB'); e.target.value = ''; return; } this.mainPreview = f ? URL.createObjectURL(f) : null; },
                        pickGallery(e) { this.gallery = Array.from(e.target.files).slice(0, 10).map(f => URL.createObjectURL(f)); },
                        toggleDelete(id) { this.deleted.includes(id) ? this.deleted = this.deleted.filter(x => x !== id) : this.deleted.push(id); } }"
              @submit="busy = true">
            @csrf
            @method('PUT')

            {{-- ข้อมูลพื้นฐาน --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> ข้อมูลพื้นฐาน</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                    <div style="grid-column:1 / -1;">
                        <label style="{{ $lbl }}">ชื่อสินค้า <span style="color:{{ $c['bad'] }};">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="255" class="tp-input">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="100" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">แบรนด์</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" maxlength="100" class="tp-input">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">หมวดหมู่ <span style="color:{{ $c['bad'] }};">*</span></label>
                        <select name="category_id" required class="tp-input">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                            @if($product->category && ! $categories->contains('id', $product->category_id))
                                <option value="{{ $product->category_id }}" selected>{{ $product->category->name }} (ปิดใช้งาน)</option>
                            @endif
                        </select>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label style="{{ $lbl }}">คำอธิบายย่อ</label>
                        <textarea name="short_description" rows="2" maxlength="500" class="tp-input">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label style="{{ $lbl }}">รายละเอียดสินค้า</label>
                        <textarea name="description" rows="6" class="tp-input">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ราคาและสต็อก --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-tag" style="color:var(--accent1);"></i> ราคาและสต็อก</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">ราคาขาย (฿) <span style="color:{{ $c['bad'] }};">*</span></label>
                        <input type="number" name="price" step="0.01" min="0" required value="{{ old('price', $product->price) }}" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ราคาก่อนลด (฿)</label>
                        <input type="number" name="compare_at_price" step="0.01" min="0" value="{{ old('compare_at_price', $product->compare_at_price) }}" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ต้นทุน (฿)</label>
                        <input type="number" name="cost_price" step="0.01" min="0" value="{{ old('cost_price', $product->cost_price) }}" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">จำนวนสต็อก <span style="color:{{ $c['bad'] }};">*</span></label>
                        <input type="number" name="stock_quantity" min="0" step="1" required value="{{ old('stock_quantity', $product->stock_quantity) }}" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">แจ้งเตือนเมื่อเหลือ <span style="color:{{ $c['bad'] }};">*</span></label>
                        <input type="number" name="low_stock_threshold" min="0" step="1" required value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 10) }}" class="tp-input tp-num" placeholder="10">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">น้ำหนัก (กรัม)</label>
                        <input type="number" name="weight" step="0.01" min="0" value="{{ old('weight', $product->weight) }}" class="tp-input tp-num">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ขนาด (กxยxส)</label>
                        <input type="text" name="dimensions" maxlength="100" value="{{ old('dimensions', $product->dimensions) }}" class="tp-input" placeholder="เช่น 10x20x5 ซม.">
                    </div>
                </div>
            </div>

            {{-- การจัดส่ง --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-truck" style="color:var(--accent1);"></i> การจัดส่ง</div>
                <select name="shipping_method" x-model="shipping" class="tp-input">
                    <option value="store_default">ใช้ค่าตั้งต้นของร้าน</option>
                    <option value="free">ส่งฟรี</option>
                    <option value="flat_rate">ค่าส่งคงที่</option>
                    <option value="weight_based">คิดตามน้ำหนัก</option>
                </select>
                <div x-show="shipping === 'flat_rate'" x-cloak class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:12px;">
                    <div>
                        <label style="{{ $lbl }}">ค่าส่ง (฿)</label>
                        <input type="number" name="shipping_fee" step="0.01" min="0" value="{{ old('shipping_fee', $product->shipping_fee) }}" class="tp-input tp-num" :disabled="shipping !== 'flat_rate'">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ส่งฟรีเมื่อซื้อครบ (฿)</label>
                        <input type="number" name="free_shipping_min_amount" step="0.01" min="0" value="{{ old('free_shipping_min_amount', $product->free_shipping_min_amount) }}" class="tp-input tp-num" placeholder="0 = ไม่มี" :disabled="shipping !== 'flat_rate'">
                    </div>
                </div>
                <div x-show="shipping === 'weight_based'" x-cloak class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:12px;">
                    <div>
                        <label style="{{ $lbl }}">น้ำหนักพัสดุ (กก.)</label>
                        <input type="number" name="shipping_weight_kg" step="0.001" min="0" value="{{ old('shipping_weight_kg', $product->shipping_weight_kg) }}" class="tp-input tp-num" :disabled="shipping !== 'weight_based'">
                    </div>
                    <div>
                        <label style="{{ $lbl }}">ส่งฟรีเมื่อซื้อครบ (฿)</label>
                        <input type="number" name="free_shipping_min_amount_weight" step="0.01" min="0" value="{{ old('free_shipping_min_amount_weight', $product->free_shipping_min_amount) }}" class="tp-input tp-num" placeholder="0 = ไม่มี" :disabled="shipping !== 'weight_based'">
                    </div>
                </div>
            </div>

            {{-- รูปภาพ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-images" style="color:var(--accent1);"></i> รูปภาพ</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                    <div>
                        <label style="{{ $lbl }}">รูปหลัก (ไม่เกิน 5MB)</label>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <div class="tp-well" style="width:88px; height:88px; border-radius:14px; overflow:hidden; flex:none; display:grid; place-items:center; color:var(--ink2);">
                                <template x-if="mainPreview"><img :src="mainPreview" alt="" style="width:100%; height:100%; object-fit:cover;"></template>
                                <template x-if="!mainPreview">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                                    @else
                                        <i class="fas fa-image"></i>
                                    @endif
                                </template>
                            </div>
                            <input type="file" name="main_image" accept="image/*" class="tp-input" style="padding:9px 12px;" @change="pickMain($event)">
                        </div>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">เพิ่มรูปเพิ่มเติม</label>
                        <input type="file" name="images[]" accept="image/*" multiple class="tp-input" style="padding:9px 12px;" @change="pickGallery($event)">
                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                            <template x-for="(src, i) in gallery" :key="i"><img :src="src" alt="" style="width:52px; height:52px; object-fit:cover; border-radius:10px; box-shadow:0 0 0 2px var(--tp-ok,#5aa07e);"></template>
                        </div>
                    </div>
                </div>
                @if($product->images->isNotEmpty())
                    <div style="margin-top:14px;">
                        <div style="{{ $lbl }}">รูปเพิ่มเติมปัจจุบัน — แตะเพื่อเลือกลบ</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            @foreach($product->images as $image)
                                <button type="button" @click="toggleDelete({{ $image->id }})"
                                        style="position:relative; width:72px; height:72px; border:0; padding:0; border-radius:12px; overflow:hidden; cursor:pointer;"
                                        :style="{ opacity: deleted.includes({{ $image->id }}) ? '.35' : '1', boxShadow: deleted.includes({{ $image->id }}) ? '0 0 0 2px var(--tp-bad,#d9534f)' : 'var(--raise)' }">
                                    <img src="{{ $image->url }}" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                                    <span x-show="deleted.includes({{ $image->id }})" x-cloak class="grid" style="position:absolute; inset:0; place-items:center; color:var(--tp-bad,#d9534f); font-size:22px;"><i class="fas fa-trash"></i></span>
                                </button>
                            @endforeach
                        </div>
                        <template x-for="id in deleted" :key="id"><input type="hidden" name="deleted_images[]" :value="id"></template>
                        <div x-show="deleted.length > 0" x-cloak style="font-size:12px; color:{{ $c['bad'] }}; margin-top:6px;"><span x-text="deleted.length"></span> รูปจะถูกลบเมื่อกดบันทึก</div>
                    </div>
                @endif
            </div>

            {{-- GP / PV / เงินคืน --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-percent" style="color:var(--accent1);"></i> ค่า GP และค่าแนะนำ</div>
                @if($gpInfo)
                    <div class="tp-well" style="padding:12px 14px; margin-bottom:14px; font-size:13px;">
                        ตอนนี้ระบบคิด GP สินค้านี้ <strong class="tp-num" style="color:var(--deep1);">{{ rtrim(rtrim(number_format((float) $gpInfo['rate'], 2), '0'), '.') }}%</strong>
                        <span style="color:var(--ink2);">— {{ $gpInfo['label_th'] }}</span>
                    </div>
                @endif
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">อัตรา GP เฉพาะสินค้านี้ (%)</label>
                        <input type="number" name="admin_gp_rate" step="0.01" min="0" max="100" value="{{ $adminGp }}" class="tp-input tp-num" placeholder="ว่าง = ใช้อัตราตามแพ็กเกจร้าน">
                        <div style="{{ $hint }}">ตั้งค่านี้แล้วจะทับอัตราแพ็กเกจและโปรฯ GP ฟรี · เว้นว่างเพื่อกลับไปใช้อัตราปกติ</div>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">PV ต่อชิ้น</label>
                        <input type="number" name="pv_value" step="0.01" min="0" value="{{ old('pv_value', $product->pv_value ?? 0) }}" class="tp-input tp-num">
                        <div style="{{ $hint }}">ใช้คิดค่าแนะนำ (เมื่อเปิดระบบค่าแนะนำ)</div>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">เงินคืนลูกค้า (%)</label>
                        <input type="number" name="cashback_percentage" step="0.01" min="0" max="100" value="{{ old('cashback_percentage', $product->cashback_percentage ?? 0) }}" class="tp-input tp-num">
                    </div>
                </div>
            </div>

            {{-- การแสดงผล --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-toggle-on" style="color:var(--accent1);"></i> การแสดงผล</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:8px;">
                    <label class="tp-well" style="{{ $check }}"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) style="accent-color:var(--accent1); width:17px; height:17px;"> เปิดขาย</label>
                    <label class="tp-well" style="{{ $check }}"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) style="accent-color:var(--accent1); width:17px; height:17px;"> ⭐ สินค้าแนะนำ</label>
                    <label class="tp-well" style="{{ $check }}"><input type="checkbox" name="is_hidden" value="1" @checked(old('is_hidden', $product->is_hidden ?? false)) style="accent-color:var(--accent1); width:17px; height:17px;"> ซ่อนจากหน้าร้าน</label>
                    <label class="tp-well" style="{{ $check }}"><input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', $product->track_inventory)) style="accent-color:var(--accent1); width:17px; height:17px;"> นับสต็อก</label>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="busy ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง'"></span>
                </button>
            </div>
        </form>

        {{-- ===== แถบข้าง: บล็อก/ปลดบล็อก (ฟอร์มแยก ไม่ซ้อนในฟอร์มหลัก) ===== --}}
        <div style="flex:1 1 260px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card" x-data="{ open: false, busy: false }">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-shield-halved" style="color:var(--accent1);"></i> การบล็อกสินค้า</div>
                @if($product->is_blocked)
                    <div style="font-size:13px; margin-bottom:10px;">
                        <span class="tp-pill" style="background:color-mix(in srgb, {{ $c['bad'] }} 16%, transparent); color:{{ $c['bad'] }};">🚫 ถูกบล็อกอยู่</span>
                        <div style="margin-top:8px;">เหตุผล: {{ $product->block_reason ?: '-' }}</div>
                        @if($product->blocked_at)<div style="font-size:12px; color:var(--ink2);">เมื่อ {{ $product->blocked_at->format('d/m/Y H:i') }}{{ $product->blockedByUser ? ' โดย '.$product->blockedByUser->name : '' }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.ecommerce.products.unblock', $product) }}"
                          @submit="if (!confirm('ปลดบล็อกสินค้านี้? ร้านค้าจะได้รับแจ้งเตือน และต้องเปิดขายเอง')) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-primary" style="width:100%;" :disabled="busy"><i class="fas fa-lock-open"></i> ปลดบล็อกสินค้า</button>
                    </form>
                @else
                    <div style="font-size:12.5px; color:var(--ink2); margin-bottom:10px;">บล็อกแล้วสินค้าจะถูกปิดขายทันที และร้านค้าได้รับแจ้งเตือนพร้อมเหตุผล</div>
                    <button type="button" class="tp-btn" style="width:100%; color:{{ $c['bad'] }};" @click="open = !open"><i class="fas fa-ban"></i> บล็อกสินค้านี้</button>
                    <form x-show="open" x-cloak method="POST" action="{{ route('admin.ecommerce.products.block', $product) }}" style="margin-top:12px;"
                          @submit="if (!confirm('ยืนยันบล็อกสินค้านี้?')) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <label style="{{ $lbl }}">เหตุผล (ร้านค้าจะเห็น) <span style="color:{{ $c['bad'] }};">*</span></label>
                        <textarea name="block_reason" rows="3" maxlength="1000" required class="tp-input" placeholder="เช่น ละเมิดลิขสิทธิ์ รูปไม่เหมาะสม ข้อมูลไม่ถูกต้อง"></textarea>
                        <button type="submit" class="tp-btn" style="width:100%; margin-top:10px; background:{{ $c['bad'] }}; color:var(--tp-on-accent,#fff);" :disabled="busy">ยืนยันการบล็อก</button>
                    </form>
                @endif
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> ข้อมูลระบบ</div>
                <div style="font-size:12.5px; color:var(--ink2); line-height:1.8;">
                    รหัสสินค้า: <span class="tp-num">#{{ $product->id }}</span><br>
                    สร้างเมื่อ: <span class="tp-num">{{ $product->created_at?->format('d/m/Y H:i') }}</span><br>
                    แก้ไขล่าสุด: <span class="tp-num">{{ $product->updated_at?->format('d/m/Y H:i') }}</span><br>
                    ขายแล้ว: <span class="tp-num">{{ number_format((int) ($product->sales_count ?? 0)) }}</span> ชิ้น
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
