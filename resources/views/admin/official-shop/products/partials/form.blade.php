{{--
 | ฟอร์มสินค้าร้านทางการ (ใช้ร่วม create/edit) — ธีม V4
 | ตัวแปร: $product (Product|null), $categories, $action, $method ('POST'|'PUT')
 | ฟิลด์ตรงกับ OfficialShopAdminController@store/update · checkbox ส่ง hidden 0 เพราะ controller ใช้ $request->boolean(x, ค่าเริ่มต้น true)
 --}}
@php
    $p = $product ?? null;
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $hint = 'font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.5;';
    $check = 'display:flex; align-items:flex-start; gap:10px; font-size:13px; cursor:pointer; padding:11px 13px; border-radius:13px;';
    $bad = 'var(--tp-bad,#d9534f)';
    $flags = [
        ['is_active', 'เปิดขาย', 'แสดงในหน้า Official Shop', (bool) old('is_active', $p?->is_active ?? true)],
        ['is_featured', 'สินค้าแนะนำ', 'ขึ้นโซนแนะนำของร้านทางการ', (bool) old('is_featured', $p?->is_featured ?? false)],
        ['track_inventory', 'นับสต็อก', 'ปิดถ้าเป็นสินค้าที่ไม่จำกัดจำนวน', (bool) old('track_inventory', $p?->track_inventory ?? true)],
    ];
@endphp

@if($errors->any())
    <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $bad }};">
        <div style="font-weight:700; color:{{ $bad }}; margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
        <ul style="margin:0; padding-left:18px; font-size:13px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;"
      x-data="{ busy: false, coins: @js((bool) old('allow_coin_purchase', $p?->allow_coin_purchase ?? false)), mainPreview: null, gallery: [], removed: [],
                pickMain(e) { const f = e.target.files[0]; if (f && f.size > 5 * 1024 * 1024) { alert('ขนาดไฟล์ต้องไม่เกิน 5MB'); e.target.value = ''; return; } this.mainPreview = f ? URL.createObjectURL(f) : null; },
                pickGallery(e) { this.gallery = Array.from(e.target.files).slice(0, 10).map(f => URL.createObjectURL(f)); },
                toggleRemove(id) { this.removed.includes(id) ? this.removed = this.removed.filter(x => x !== id) : this.removed.push(id); } }"
      @submit="busy = true">
    @csrf
    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    <div style="flex:2 1 440px; min-width:0; display:flex; flex-direction:column; gap:16px;">
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> ข้อมูลสินค้า</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:14px;">
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">ชื่อสินค้า <span style="color:{{ $bad }};">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $p?->name) }}" required maxlength="255" class="tp-input">
                </div>
                <div>
                    <label style="{{ $lbl }}">หมวดหมู่ <span style="color:{{ $bad }};">*</span></label>
                    <select name="category_id" required class="tp-input">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $p?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="{{ $lbl }}">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $p?->sku) }}" maxlength="100" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">แบรนด์</label>
                    <input type="text" name="brand" value="{{ old('brand', $p?->brand) }}" maxlength="100" class="tp-input">
                </div>
                <div>
                    <label style="{{ $lbl }}">น้ำหนัก (กรัม)</label>
                    <input type="number" name="weight" step="0.01" min="0" value="{{ old('weight', $p?->weight) }}" class="tp-input tp-num">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">คำอธิบายย่อ</label>
                    <textarea name="short_description" rows="2" maxlength="500" class="tp-input">{{ old('short_description', $p?->short_description) }}</textarea>
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">รายละเอียดสินค้า</label>
                    <textarea name="description" rows="6" class="tp-input">{{ old('description', $p?->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-tag" style="color:var(--accent1);"></i> ราคาและสต็อก</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,150px),1fr)); gap:14px;">
                <div>
                    <label style="{{ $lbl }}">ราคาขาย (฿) <span style="color:{{ $bad }};">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" required value="{{ old('price', $p?->price) }}" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">ราคาก่อนลด (฿)</label>
                    <input type="number" name="compare_at_price" step="0.01" min="0" value="{{ old('compare_at_price', $p?->compare_at_price) }}" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">ต้นทุน (฿)</label>
                    <input type="number" name="cost_price" step="0.01" min="0" value="{{ old('cost_price', $p?->cost_price) }}" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">จำนวนสต็อก</label>
                    <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $p?->stock_quantity ?? 0) }}" class="tp-input tp-num">
                </div>
                <div>
                    <label style="{{ $lbl }}">แจ้งเตือนเมื่อเหลือ</label>
                    <input type="number" name="low_stock_threshold" min="0" value="{{ old('low_stock_threshold', $p?->low_stock_threshold ?? 5) }}" class="tp-input tp-num">
                </div>
            </div>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-images" style="color:var(--accent1);"></i> รูปภาพ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr)); gap:16px;">
                <div>
                    <label style="{{ $lbl }}">รูปหลัก (ไม่เกิน 5MB)</label>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div class="tp-well" style="width:88px; height:88px; border-radius:14px; overflow:hidden; flex:none; display:grid; place-items:center; color:var(--ink2);">
                            <template x-if="mainPreview"><img :src="mainPreview" alt="" style="width:100%; height:100%; object-fit:cover;"></template>
                            <template x-if="!mainPreview">
                                @if($p?->primary_image_url)
                                    <img src="{{ $p->primary_image_url }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                                @else
                                    <i class="fas fa-image"></i>
                                @endif
                            </template>
                        </div>
                        <input type="file" name="main_image" accept="image/*" class="tp-input" style="padding:9px 12px;" @change="pickMain($event)">
                    </div>
                </div>
                <div>
                    <label style="{{ $lbl }}">รูปเพิ่มเติม (สูงสุด 10 รูป)</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="tp-input" style="padding:9px 12px;" @change="pickGallery($event)">
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                        <template x-for="(src, i) in gallery" :key="i"><img :src="src" alt="" style="width:52px; height:52px; object-fit:cover; border-radius:10px; box-shadow:var(--raise);"></template>
                    </div>
                </div>
            </div>
            @if($p && $p->images->isNotEmpty())
                <div style="margin-top:14px;">
                    <div style="{{ $lbl }}">รูปเพิ่มเติมปัจจุบัน — แตะเพื่อเลือกลบ</div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        @foreach($p->images as $image)
                            <button type="button" @click="toggleRemove({{ $image->id }})"
                                    style="position:relative; width:72px; height:72px; border:0; padding:0; border-radius:12px; overflow:hidden; cursor:pointer;"
                                    :style="{ opacity: removed.includes({{ $image->id }}) ? '.35' : '1', boxShadow: removed.includes({{ $image->id }}) ? '0 0 0 2px var(--tp-bad,#d9534f)' : 'var(--raise)' }">
                                <img src="{{ $image->url }}" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                            </button>
                        @endforeach
                    </div>
                    <template x-for="id in removed" :key="id"><input type="hidden" name="delete_images[]" :value="id"></template>
                    <div x-show="removed.length > 0" x-cloak style="font-size:12px; color:{{ $bad }}; margin-top:6px;"><span x-text="removed.length"></span> รูปจะถูกลบเมื่อกดบันทึก</div>
                </div>
            @endif
        </div>
    </div>

    <div style="flex:1 1 280px; min-width:0; display:flex; flex-direction:column; gap:16px;">
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-toggle-on" style="color:var(--accent1);"></i> การแสดงผล</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($flags as [$name, $label, $desc, $value])
                    <input type="hidden" name="{{ $name }}" value="0">
                    <label class="tp-well" style="{{ $check }}">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked($value) style="accent-color:var(--accent1); width:18px; height:18px; margin-top:2px; flex:none;">
                        <span><span style="font-weight:700;">{{ $label }}</span><br><span style="font-size:11.5px; color:var(--ink2);">{{ $desc }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-people-arrows" style="color:var(--accent1);"></i> ค่าแนะนำและเงินคืน</div>
            <div class="tp-well" style="padding:10px 12px; font-size:12px; color:var(--ink2); margin-bottom:12px;">สินค้าร้านทางการไม่มีค่า GP — รายได้สุทธิเข้ากระเป๋าร้านทางการ</div>
            <label style="{{ $lbl }}">PV ต่อชิ้น</label>
            <input type="number" name="pv_value" step="0.01" min="0" value="{{ old('pv_value', $p?->pv_value ?? 0) }}" class="tp-input tp-num">
            <div style="{{ $hint }}">ใช้คิดค่าแนะนำเมื่อเปิดระบบค่าแนะนำ</div>
            <label style="{{ $lbl }} margin-top:12px;">เงินคืนลูกค้า (%)</label>
            <input type="number" name="cashback_percentage" step="0.01" min="0" max="100" value="{{ old('cashback_percentage', $p?->cashback_percentage ?? 0) }}" class="tp-input tp-num">
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-coins" style="color:var(--accent1);"></i> ซื้อด้วยเหรียญ</div>
            <input type="hidden" name="allow_coin_purchase" value="0">
            <label class="tp-well" style="{{ $check }}">
                <input type="checkbox" name="allow_coin_purchase" value="1" x-model="coins" style="accent-color:var(--accent1); width:18px; height:18px; margin-top:2px; flex:none;">
                <span><span style="font-weight:700;">อนุญาตให้ซื้อด้วยเหรียญ</span><br><span style="font-size:11.5px; color:var(--ink2);">ลูกค้าจ่ายเป็นเหรียญแทนเงินได้</span></span>
            </label>
            <div x-show="coins" x-cloak style="margin-top:12px;">
                <label style="{{ $lbl }}">ราคาเป็นเหรียญ</label>
                <input type="number" name="price_coins" step="1" min="0" value="{{ old('price_coins', $p?->price_coins) }}" class="tp-input tp-num" :disabled="!coins">
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn" style="flex:1;">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;" :disabled="busy">
                <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> บันทึก
            </button>
        </div>
    </div>
</form>
