{{--
 | ฟอร์มสินค้า (ใช้ร่วมกันทั้งหน้าเพิ่มและแก้ไข) — ธีม V4
 | ตัวแปร: $product (null = สร้างใหม่), $categories, $gpInfo, $gpPromoActive, $vatRegistered, $mlmEnabled, $pvValue
 |
 | ช่องที่ส่งไปคอนโทรลเลอร์ (ชื่อเดิมทั้งหมด): name, short_description, description, price, compare_at_price,
 | cost_price, pv_value (เฉพาะเมื่อเปิดระบบค่าแนะนำ), customer_cashback, cashback_percentage (ซ่อน — คงค่าเดิม),
 | sku, stock_quantity, track_inventory, shipping_method, shipping_fee, free_shipping_min_amount,
 | shipping_weight_kg, free_shipping_min_amount_weight, main_image, images[], deleted_images (แก้ไข),
 | category_id, brand, weight, dimensions
 |
 | ตัวคำนวณรายได้ด้านขวาเรียก POST seller.pricing.quote (PricingEngine ชุดเดียวกับตอนแบ่งเงินจริง)
 --}}
@php
    $isEdit = $product !== null;
    $mainImageUrl = $isEdit ? \App\Services\Shop\ShopPresenter::imageUrl($product->main_image_url) : null;
    $galleryImages = $isEdit
        ? $product->images->map(fn ($img) => [
            'id' => (int) $img->id,
            'url' => \App\Services\Shop\ShopPresenter::imageUrl($img->image_url),
        ])->filter(fn ($img) => ! empty($img['url']))->values()
        : collect();
    $ui = \App\Support\Seller\SellerUi::class;

    $formConfig = [
        'quoteUrl' => route('seller.pricing.quote'),
        'productId' => $isEdit ? (int) $product->id : null,
        'price' => (float) old('price', $isEdit ? $product->price : ''),
        'cost' => old('cost_price', $isEdit ? $product->cost_price : '') !== null ? (string) old('cost_price', $isEdit ? $product->cost_price : '') : '',
        'pv' => (float) old('pv_value', $pvValue ?? 0),
        'mlmEnabled' => (bool) ($mlmEnabled ?? false),
        'sku' => (string) old('sku', $isEdit ? $product->sku : ''),
        'shippingMethod' => (string) old('shipping_method', $isEdit ? ($product->shipping_method ?? 'store_default') : 'store_default'),
        'mainImage' => $mainImageUrl,
        'gallery' => $galleryImages,
    ];
@endphp

<form action="{{ $isEdit ? route('seller.products.update', $product) : route('seller.products.store') }}"
      method="POST" enctype="multipart/form-data"
      x-data="productFormV4(@js($formConfig))" @submit="submitting = true">
    @csrf
    @if($isEdit)
        @method('PUT')
        <input type="hidden" name="deleted_images" :value="deletedIds.join(',')">
    @endif

    {{-- เงินคืนลูกค้าตั้งโดยแพลตฟอร์ม (CashbackSetting) — ช่องเดิมของร้านไม่มีผลกับเงินจริง จึงซ่อนไว้และคงค่าเดิม --}}
    <input type="hidden" name="customer_cashback" value="{{ old('customer_cashback', $isEdit ? ($product->customer_cashback ?? 0) : 0) }}">
    <input type="hidden" name="cashback_percentage" value="{{ old('cashback_percentage', $isEdit ? ($product->cashback_percentage ?? 0) : 0) }}">

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ── คอลัมน์ฟอร์ม ─────────────────────────────────── --}}
        <div style="flex:2 1 480px; min-width:0; display:flex; flex-direction:column; gap:18px;">

            {{-- 1) ข้อมูลพื้นฐาน --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📝 ข้อมูลพื้นฐาน</div>
                <div class="sv4-sub">ชื่อและรายละเอียดที่ลูกค้าจะเห็น</div>
                <div style="display:flex; flex-direction:column; gap:14px; margin-top:14px;">
                    <div>
                        <label for="name" class="sv4-label">ชื่อสินค้า <span class="req">*</span></label>
                        <input type="text" name="name" id="name" required maxlength="255" class="tp-input"
                               value="{{ old('name', $isEdit ? $product->name : '') }}" placeholder="เช่น ผ้าไหมไทยทอมือ ลายดอกพิกุล 2 เมตร">
                        <div class="sv4-hint">ใส่ยี่ห้อ รุ่น ขนาด สี ให้ครบ ลูกค้าค้นหาเจอง่ายขึ้น</div>
                        @error('name')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="short_description" class="sv4-label">คำอธิบายสั้น</label>
                        <textarea name="short_description" id="short_description" rows="2" maxlength="500" class="tp-input"
                                  placeholder="สรุปจุดเด่นของสินค้าในประโยคเดียว">{{ old('short_description', $isEdit ? $product->short_description : '') }}</textarea>
                        <div class="sv4-hint">แสดงในหน้ารายการสินค้า (แนะนำไม่เกิน 150 ตัวอักษร)</div>
                        @error('short_description')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="description" class="sv4-label">รายละเอียดสินค้า</label>
                        <textarea name="description" id="description" rows="6" class="tp-input"
                                  placeholder="คุณสมบัติ วัสดุ วิธีใช้ สิ่งที่ได้รับในกล่อง ฯลฯ">{{ old('description', $isEdit ? $product->description : '') }}</textarea>
                        @error('description')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- 2) ราคาและต้นทุน --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-row" style="flex-wrap:wrap;">
                    <div>
                        <div class="sv4-h2">💰 ราคาและต้นทุน</div>
                        <div class="sv4-sub">ราคาที่ลูกค้าเห็นรวม VAT แล้ว — ดูรายได้สุทธิแบบสดที่แผงด้านขวา</div>
                    </div>
                    <a href="{{ route('seller.pricing.planner', $isEdit ? ['product_id' => $product->id] : []) }}" class="tp-btn tp-btn-sm">💡 วางแผนราคา & กลยุทธ์</a>
                </div>
                <div class="sv4-grid" style="margin-top:14px;">
                    <div>
                        <label for="price" class="sv4-label">ราคาขาย (บาท) <span class="req">*</span></label>
                        <input type="number" name="price" id="price" required min="0" step="0.01" inputmode="decimal" class="tp-input tp-num"
                               x-model="price" value="{{ old('price', $isEdit ? $product->price : '') }}" placeholder="0.00">
                        @error('price')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="compare_at_price" class="sv4-label">ราคาก่อนลด (บาท)</label>
                        <input type="number" name="compare_at_price" id="compare_at_price" min="0" step="0.01" inputmode="decimal" class="tp-input tp-num"
                               value="{{ old('compare_at_price', $isEdit ? $product->compare_at_price : '') }}" placeholder="ไม่บังคับ">
                        <div class="sv4-hint">ต้องเป็นราคาที่เคยขายจริง (ห้ามตั้งสูงเกินจริงให้ดูลดเยอะ)</div>
                        @error('compare_at_price')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="cost_price" class="sv4-label">ต้นทุนต่อชิ้น (บาท)</label>
                        <input type="number" name="cost_price" id="cost_price" min="0" step="0.01" inputmode="decimal" class="tp-input tp-num"
                               x-model="cost" value="{{ old('cost_price', $isEdit ? $product->cost_price : '') }}" placeholder="ใช้คำนวณกำไร">
                        <div class="sv4-hint">ลูกค้าไม่เห็นตัวเลขนี้</div>
                        @error('cost_price')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- GP / VAT อ่านอย่างเดียว (แพลตฟอร์มกำหนด) --}}
                <div class="sv4-grid" style="margin-top:14px;">
                    <div class="sv4-well">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:12px; font-weight:700; color:var(--ink2);">🔒 ค่า GP แพลตฟอร์ม</span>
                            @if($gpPromoActive ?? false)
                                <span class="sv4-pill" style="{{ $ui::pill($ui::OK) }}">🎉 ฟรี GP ช่วงเปิดตัว</span>
                            @endif
                        </div>
                        <div class="tp-num" style="font-size:22px; font-weight:800; margin-top:4px; color:{{ ($gpInfo['rate'] ?? 0) > 0 ? 'var(--ink)' : $ui::OK }};">
                            {{ isset($gpInfo['rate']) ? rtrim(rtrim(number_format((float) $gpInfo['rate'], 2), '0'), '.') . '%' : '—' }}
                        </div>
                        <div class="sv4-hint" style="margin-top:2px;">{{ $gpInfo['label_th'] ?? '' }} · ผู้ขายเปลี่ยนเองไม่ได้</div>
                    </div>
                    <div class="sv4-well">
                        <span style="font-size:12px; font-weight:700; color:var(--ink2);">🧾 ภาษีมูลค่าเพิ่ม (VAT)</span>
                        <div style="font-size:15px; font-weight:800; margin-top:6px;">{{ ($vatRegistered ?? false) ? 'ร้านจดทะเบียน VAT' : 'ร้านไม่ได้จด VAT' }}</div>
                        <div class="sv4-hint" style="margin-top:2px;">
                            {{ ($vatRegistered ?? false) ? 'ระบบถอด VAT 7/107 ออกจากยอดขายก่อนโอนให้ร้าน' : 'ไม่หัก VAT จากยอดขาย' }}
                            · <a href="{{ route('seller.store.settings') }}#tax" class="sv4-link">ตั้งค่า</a>
                        </div>
                    </div>
                    @if($mlmEnabled ?? false)
                        {{-- ค่าแนะนำ (PV) — ฟีเจอร์เฉพาะเว็บ แสดงเมื่อแพลตฟอร์มเปิดระบบแนะนำเท่านั้น --}}
                        <div>
                            <label for="pv_value" class="sv4-label">ค่าแนะนำ (PV ต่อชิ้น)</label>
                            <input type="number" name="pv_value" id="pv_value" min="0" step="0.01" inputmode="decimal" class="tp-input tp-num"
                                   x-model="pv" value="{{ old('pv_value', $pvValue ?? 0) }}" placeholder="0">
                            <div class="sv4-hint">คะแนนสำหรับจ่ายค่าแนะนำให้ผู้ที่ช่วยแนะนำสินค้า 0 = ไม่หักค่าแนะนำ</div>
                            @error('pv_value')<div class="sv4-err">{{ $message }}</div>@enderror
                        </div>
                    @endif
                </div>
            </div>

            {{-- 3) สต็อก --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📊 สต็อกสินค้า</div>
                <div class="sv4-grid" style="margin-top:14px;">
                    <div>
                        <label for="sku" class="sv4-label">SKU (รหัสสินค้า)</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="sku" id="sku" maxlength="100" class="tp-input tp-num" x-model="sku"
                                   value="{{ old('sku', $isEdit ? $product->sku : '') }}" placeholder="ว่าง = สร้างให้อัตโนมัติ" autocomplete="off">
                            <button type="button" class="tp-btn" @click="generateSku()" title="สร้าง SKU อัตโนมัติ">🎲</button>
                        </div>
                        @error('sku')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="stock_quantity" class="sv4-label">จำนวนในสต็อก <span class="req">*</span></label>
                        <input type="number" name="stock_quantity" id="stock_quantity" required min="0" step="1" inputmode="numeric" class="tp-input tp-num"
                               value="{{ old('stock_quantity', $isEdit ? $product->stock_quantity : 0) }}">
                        @error('stock_quantity')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
                <label class="sv4-well" style="display:flex; align-items:center; gap:12px; margin-top:14px; cursor:pointer;">
                    <input type="hidden" name="track_inventory" value="0">
                    <span class="sv4-switch">
                        <input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', $isEdit ? $product->track_inventory : true))>
                        <span></span>
                    </span>
                    <span style="min-width:0;">
                        <span style="font-weight:800; font-size:13px;">ติดตามสต็อก</span>
                        <span class="sv4-hint" style="display:block; margin-top:1px;">ระบบตัดสต็อกเมื่อขาย และหยุดรับออเดอร์เมื่อสินค้าหมด</span>
                    </span>
                </label>
            </div>

            {{-- 4) การจัดส่ง --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">🚚 การจัดส่ง</div>
                <div class="sv4-sub">ตั้งค่าส่งเฉพาะสินค้าชิ้นนี้ (ส่งด้วยไรเดอร์ตั้งที่หน้าตั้งค่าร้าน)</div>
                <div style="margin-top:14px;">
                    <label for="shipping_method" class="sv4-label">วิธีคิดค่าจัดส่ง</label>
                    <select name="shipping_method" id="shipping_method" class="tp-input" x-model="shippingMethod">
                        <option value="store_default">ใช้ค่าเริ่มต้นของร้าน</option>
                        <option value="free">ส่งฟรี</option>
                        <option value="flat_rate">ค่าส่งเหมาต่อชิ้น</option>
                        <option value="weight_based">คิดตามน้ำหนัก</option>
                    </select>
                    @error('shipping_method')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>

                <div class="sv4-grid" style="margin-top:14px;" x-show="shippingMethod === 'flat_rate'" x-cloak>
                    <div>
                        <label for="shipping_fee" class="sv4-label">ค่าส่งต่อชิ้น (บาท)</label>
                        <input type="number" name="shipping_fee" id="shipping_fee" min="0" max="{{ \App\Http\Controllers\Seller\ProductController::MAX_SHIPPING_FEE }}" step="0.01"
                               class="tp-input tp-num" :disabled="shippingMethod !== 'flat_rate'"
                               value="{{ old('shipping_fee', $isEdit ? $product->shipping_fee : 50) }}">
                        <div class="sv4-hint">สูงสุด ฿{{ number_format(\App\Http\Controllers\Seller\ProductController::MAX_SHIPPING_FEE) }} ต่อชิ้น</div>
                        @error('shipping_fee')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="free_shipping_min_amount" class="sv4-label">ส่งฟรีเมื่อซื้อครบ (บาท)</label>
                        <input type="number" name="free_shipping_min_amount" id="free_shipping_min_amount" min="0" step="0.01"
                               class="tp-input tp-num" :disabled="shippingMethod !== 'flat_rate'"
                               value="{{ old('free_shipping_min_amount', $isEdit ? $product->free_shipping_min_amount : '') }}" placeholder="ว่าง = ไม่มี">
                    </div>
                </div>

                <div class="sv4-grid" style="margin-top:14px;" x-show="shippingMethod === 'weight_based'" x-cloak>
                    <div>
                        <label for="shipping_weight_kg" class="sv4-label">น้ำหนักจัดส่ง (กก.)</label>
                        <input type="number" name="shipping_weight_kg" id="shipping_weight_kg" min="0" step="0.001"
                               class="tp-input tp-num" :disabled="shippingMethod !== 'weight_based'"
                               value="{{ old('shipping_weight_kg', $isEdit ? $product->shipping_weight_kg : '') }}" placeholder="เช่น 0.5">
                        <div class="sv4-hint">น้ำหนักรวมบรรจุภัณฑ์</div>
                        @error('shipping_weight_kg')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="free_shipping_min_amount_weight" class="sv4-label">ส่งฟรีเมื่อซื้อครบ (บาท)</label>
                        <input type="number" name="free_shipping_min_amount_weight" id="free_shipping_min_amount_weight" min="0" step="0.01"
                               class="tp-input tp-num" :disabled="shippingMethod !== 'weight_based'"
                               value="{{ old('free_shipping_min_amount_weight', $isEdit ? $product->free_shipping_min_amount : '') }}" placeholder="ว่าง = ไม่มี">
                    </div>
                </div>

                <div class="sv4-note" style="margin-top:14px; --c:var(--accent1);">
                    <span x-show="shippingMethod === 'store_default'">ใช้ค่าส่งและเกณฑ์ส่งฟรีที่ตั้งไว้ในหน้าตั้งค่าร้าน</span>
                    <span x-show="shippingMethod === 'free'" x-cloak>ลูกค้าไม่ต้องจ่ายค่าส่งสำหรับสินค้าชิ้นนี้ (ร้านรับภาระค่าส่งเอง)</span>
                    <span x-show="shippingMethod === 'flat_rate'" x-cloak>คิดค่าส่งคงที่ต่อชิ้น ไม่ขึ้นกับน้ำหนัก</span>
                    <span x-show="shippingMethod === 'weight_based'" x-cloak>ระบบคำนวณค่าส่งจากน้ำหนักตามอัตรามาตรฐานของระบบ</span>
                </div>
            </div>

            {{-- 5) รูปภาพ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">🖼️ รูปภาพสินค้า</div>
                <div class="sv4-sub">JPG, PNG, WebP ไม่เกิน 5MB ต่อรูป · แนะนำ 1200×1200 พิกเซล</div>

                <div style="display:flex; flex-wrap:wrap; gap:18px; margin-top:14px;">
                    <div style="flex:0 0 auto;">
                        <label for="main_image" class="sv4-label">รูปหลัก @unless($isEdit)<span class="req">*</span>@endunless</label>
                        <label for="main_image" style="display:grid; place-items:center; width:170px; height:170px; border-radius:18px; box-shadow:var(--inset); cursor:pointer; overflow:hidden; background:var(--surf);">
                            <img x-show="mainPreview" :src="mainPreview" alt="ตัวอย่างรูปหลัก" style="width:100%; height:100%; object-fit:cover;">
                            <span x-show="!mainPreview" style="text-align:center; color:var(--ink2); font-size:12px; padding:10px;">
                                <span style="font-size:30px; display:block;">📷</span>แตะเพื่อเลือกรูป
                            </span>
                        </label>
                        <input type="file" name="main_image" id="main_image" accept="image/jpeg,image/png,image/webp,image/gif"
                               @unless($isEdit) required @endunless @change="onMainChange($event)"
                               style="margin-top:8px; font-size:12px; max-width:170px; color:var(--ink2);">
                        @error('main_image')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>

                    <div style="flex:1 1 260px; min-width:0;">
                        <label for="images" class="sv4-label">รูปเพิ่มเติม (สูงสุด 10 รูป)</label>
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(84px, 1fr)); gap:10px;">
                            <template x-for="img in gallery" :key="'e' + img.id">
                                <div style="position:relative; aspect-ratio:1; border-radius:14px; overflow:hidden; box-shadow:var(--inset-sm);"
                                     :style="img.removed ? 'opacity:.35; filter:grayscale(1);' : ''">
                                    <img :src="img.url" alt="รูปสินค้าเพิ่มเติม" style="width:100%; height:100%; object-fit:cover;">
                                    <button type="button" class="tp-icon-btn" @click="toggleRemove(img)"
                                            :title="img.removed ? 'เก็บรูปนี้ไว้' : 'ลบรูปนี้'"
                                            style="position:absolute; top:5px; right:5px; width:28px; height:28px; border-radius:9px; font-size:12px;"
                                            x-text="img.removed ? '↺' : '✕'"></button>
                                </div>
                            </template>
                            <template x-for="(src, i) in newPreviews" :key="'n' + i">
                                <div style="position:relative; aspect-ratio:1; border-radius:14px; overflow:hidden; box-shadow:0 0 0 2px var(--accent1);">
                                    <img :src="src" alt="รูปใหม่" style="width:100%; height:100%; object-fit:cover;">
                                    <span class="sv4-pill tp-pill-gold" style="position:absolute; left:5px; top:5px; font-size:10px; color:var(--tp-on-accent, #fff);">ใหม่</span>
                                </div>
                            </template>
                            <label for="images" style="aspect-ratio:1; border-radius:14px; box-shadow:var(--inset); display:grid; place-items:center; cursor:pointer; color:var(--ink2); font-size:12px; text-align:center;">
                                <span><span style="font-size:22px; display:block;">➕</span>เพิ่มรูป</span>
                            </label>
                        </div>
                        <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/webp,image/gif"
                               x-ref="galleryInput" @change="onGalleryChange($event)" style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;">
                        <div class="sv4-hint" x-show="newPreviews.length > 0">
                            เลือกรูปใหม่ <span x-text="newPreviews.length"></span> รูป · <button type="button" class="sv4-link" style="background:none; border:0; padding:0; cursor:pointer; font:inherit;" @click="clearGallery()">ล้างรูปที่เลือก</button>
                        </div>
                        <div class="sv4-hint" x-show="deletedIds.length > 0" style="color:{{ $ui::BAD }};">จะลบรูปเดิม <span x-text="deletedIds.length"></span> รูปเมื่อกดบันทึก</div>
                        <div class="sv4-err" x-show="galleryError" x-text="galleryError"></div>
                        @error('images.*')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- 6) หมวดหมู่และรายละเอียด --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">🏷️ หมวดหมู่และรายละเอียด</div>
                <div class="sv4-grid" style="margin-top:14px;">
                    <div>
                        <label for="category_id" class="sv4-label">หมวดหมู่ <span class="req">*</span></label>
                        <select name="category_id" id="category_id" required class="tp-input">
                            <option value="">— เลือกหมวดหมู่ —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $isEdit ? $product->category_id : '') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="brand" class="sv4-label">แบรนด์</label>
                        <input type="text" name="brand" id="brand" maxlength="100" class="tp-input"
                               value="{{ old('brand', $isEdit ? $product->brand : '') }}" placeholder="ชื่อแบรนด์หรือผู้ผลิต">
                        @error('brand')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="weight" class="sv4-label">น้ำหนัก (กรัม)</label>
                        <input type="number" name="weight" id="weight" min="0" step="0.01" class="tp-input tp-num"
                               value="{{ old('weight', $isEdit ? $product->weight : '') }}" placeholder="0">
                        @error('weight')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="dimensions" class="sv4-label">ขนาด</label>
                        <input type="text" name="dimensions" id="dimensions" maxlength="100" class="tp-input"
                               value="{{ old('dimensions', $isEdit ? $product->dimensions : '') }}" placeholder="เช่น 10 × 20 × 5 ซม.">
                        @error('dimensions')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; padding-bottom:8px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="height:48px; padding:0 26px; font-size:14px;" :disabled="submitting">
                    <span x-show="!submitting">💾 {{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกสินค้า' }}</span>
                    <span x-show="submitting" x-cloak>กำลังบันทึก…</span>
                </button>
                <a href="{{ route('seller.products.index') }}" class="tp-btn" style="height:48px; padding:0 22px;">ยกเลิก</a>
            </div>
        </div>

        {{-- ── แผงรายได้สด (PricingEngine) ──────────────────── --}}
        <aside style="flex:1 1 300px; min-width:0; position:sticky; top:84px;">
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-row">
                    <div class="sv4-h2">🧮 คุณจะได้รับ</div>
                    <span class="sv4-pill tp-pill-soft" x-show="loading" x-cloak>กำลังคำนวณ…</span>
                </div>
                <div class="sv4-sub">คำนวณด้วยสูตรเดียวกับตอนแบ่งเงินจริง ต่อ 1 ชิ้น</div>

                <template x-if="!quote && !error">
                    <div class="sv4-well" style="margin-top:14px; font-size:12.5px; color:var(--ink2); text-align:center;">กรอกราคาขายเพื่อดูรายได้สุทธิ</div>
                </template>
                <div class="sv4-err" x-show="error" x-text="error" style="margin-top:12px;"></div>

                <template x-if="quote">
                    <div style="margin-top:14px;">
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <template x-for="line in quote.lines" :key="line.key">
                                <div class="sv4-kv" :style="line.kind === 'result' ? 'font-size:14px; border-top:1px dashed color-mix(in srgb, var(--ink2) 30%, transparent); padding-top:8px; margin-top:4px;' : ''">
                                    <span x-text="line.label_th" :style="line.kind === 'result' ? 'color:var(--ink); font-weight:800;' : ''"></span>
                                    <span class="tp-num" :style="lineStyle(line)" x-text="money(line.amount, line.kind === 'deduction')"></span>
                                </div>
                            </template>
                        </div>
                        <div class="sv4-well" style="margin-top:12px; text-align:center;">
                            <div style="font-size:11.5px; color:var(--ink2);">ร้านได้รับสุทธิต่อชิ้น</div>
                            <div class="tp-num" style="font-size:28px; font-weight:800;" :style="'color:' + (quote.seller_net < 0 ? '{{ $ui::BAD }}' : '{{ $ui::OK }}')" x-text="money(quote.seller_net)"></div>
                            <template x-if="quote.margin_percent !== null">
                                <div style="font-size:12px; margin-top:2px;">
                                    กำไร <b class="tp-num" x-text="money(quote.profit)"></b>
                                    (<span class="tp-num" x-text="Number(quote.margin_percent).toFixed(1) + '%'"></span> ของราคาขาย)
                                </div>
                            </template>
                            <template x-if="quote.margin_percent === null">
                                <div class="sv4-hint">กรอกต้นทุนเพื่อดูกำไร</div>
                            </template>
                        </div>
                        <template x-if="quote.gp_promo_active">
                            <div class="sv4-note" style="margin-top:10px; --c:{{ $ui::OK }};">🎉 ช่วงเปิดตัวไม่เก็บค่า GP — ร้านได้เต็มยอดขาย</div>
                        </template>
                        <template x-for="w in quote.warnings" :key="w.code">
                            <div class="sv4-note" style="margin-top:10px; --c:{{ $ui::BAD }};" x-text="'⚠️ ' + w.message"></div>
                        </template>
                    </div>
                </template>
            </div>
        </aside>
    </div>
</form>

@push('scripts')
<script>
/**
 * ฟอร์มสินค้า V4: คำนวณรายได้สดผ่าน API ของระบบ (debounce ~300ms), พรีวิวรูป, สร้าง SKU, เลือกรูปเดิมที่จะลบ
 */
function productFormV4(cfg) {
    return {
        price: cfg.price || '',
        cost: cfg.cost || '',
        pv: cfg.pv || 0,
        sku: cfg.sku || '',
        shippingMethod: cfg.shippingMethod || 'store_default',
        mainPreview: cfg.mainImage || null,
        gallery: (cfg.gallery || []).map((g) => ({ id: g.id, url: g.url, removed: false })),
        newPreviews: [],
        galleryError: '',
        quote: null,
        loading: false,
        error: '',
        submitting: false,
        _timer: null,
        _seq: 0,

        get deletedIds() {
            return this.gallery.filter((g) => g.removed).map((g) => g.id);
        },

        init() {
            this.$watch('price', () => this.scheduleQuote());
            this.$watch('cost', () => this.scheduleQuote());
            this.$watch('pv', () => this.scheduleQuote());
            this.scheduleQuote();
        },

        scheduleQuote() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.fetchQuote(), 300);
        },

        async fetchQuote() {
            const price = parseFloat(this.price);
            if (!isFinite(price) || price < 0) { this.quote = null; this.error = ''; return; }
            const seq = ++this._seq;
            this.loading = true;
            this.error = '';
            const body = { price: price, quantity: 1 };
            const cost = parseFloat(this.cost);
            if (isFinite(cost) && cost > 0) body.cost = cost;
            if (cfg.productId) body.product_id = cfg.productId;
            if (cfg.mlmEnabled) body.pv = parseFloat(this.pv) || 0;
            try {
                const token = document.querySelector('meta[name="csrf-token"]');
                const res = await fetch(cfg.quoteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                    },
                    body: JSON.stringify(body)
                });
                const json = await res.json().catch(() => null);
                if (seq !== this._seq) return;
                if (!res.ok || !json || !json.success) {
                    this.quote = null;
                    this.error = (json && json.message) ? json.message : 'คำนวณไม่สำเร็จ กรุณาลองใหม่';
                } else {
                    this.quote = json.data;
                }
            } catch (e) {
                if (seq === this._seq) { this.quote = null; this.error = 'เชื่อมต่อไม่ได้ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'; }
            } finally {
                if (seq === this._seq) this.loading = false;
            }
        },

        money(amount, asDeduction) {
            const n = Number(amount) || 0;
            const text = '฿' + Math.abs(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return (n < 0 || asDeduction && n !== 0) ? '−' + text : text;
        },

        lineStyle(line) {
            if (line.kind === 'deduction') return 'color:var(--tp-bad, #d9534f);';
            if (line.kind === 'income') return 'color:var(--tp-ok, #5aa07e);';
            if (line.kind === 'result') return 'font-weight:800; color:' + (line.amount < 0 ? 'var(--tp-bad, #d9534f)' : 'var(--ink)') + ';';
            return 'color:var(--ink2); font-weight:500;';
        },

        generateSku() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let out = '';
            const rnd = (window.crypto && window.crypto.getRandomValues) ? window.crypto.getRandomValues(new Uint32Array(8)) : null;
            for (let i = 0; i < 8; i++) {
                const r = rnd ? rnd[i] : Math.floor(Math.random() * 4294967295);
                out += chars.charAt(r % chars.length);
            }
            this.sku = 'PRD-' + out;
        },

        onMainChange(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('รูปหลักต้องมีขนาดไม่เกิน 5MB');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => { this.mainPreview = ev.target.result; };
            reader.readAsDataURL(file);
        },

        onGalleryChange(e) {
            this.galleryError = '';
            this.newPreviews.forEach((u) => URL.revokeObjectURL(u));
            this.newPreviews = [];
            const files = Array.from(e.target.files || []);
            const kept = this.gallery.filter((g) => !g.removed).length;
            if (files.length + kept > 10) {
                this.galleryError = 'รวมรูปเพิ่มเติมได้ไม่เกิน 10 รูป กรุณาเลือกใหม่';
                e.target.value = '';
                return;
            }
            if (files.some((f) => f.size > 5 * 1024 * 1024)) {
                this.galleryError = 'มีรูปที่ใหญ่เกิน 5MB กรุณาเลือกใหม่';
                e.target.value = '';
                return;
            }
            this.newPreviews = files.map((f) => URL.createObjectURL(f));
        },

        clearGallery() {
            this.newPreviews.forEach((u) => URL.revokeObjectURL(u));
            this.newPreviews = [];
            this.galleryError = '';
            if (this.$refs.galleryInput) this.$refs.galleryInput.value = '';
        },

        toggleRemove(img) {
            img.removed = !img.removed;
        }
    };
}
</script>
@endpush
