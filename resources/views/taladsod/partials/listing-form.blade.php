{{--
 | ฟอร์มสินค้าตลาดสด (ใช้ร่วมกันระหว่างลงขายใหม่และแก้ไข) — ธีม V4
 | ใช้: @include('taladsod.partials.listing-form', ['mode' => 'create'|'edit', 'listing' => ?FreshMarketListing, 'categories', 'maxCashbackPercent', 'gpRate', 'gpFree', 'optionGroups' => array])
 |
 | ฟิลด์ (ตรงกับ HomeController@storeListing / @updateListing):
 |   title*, description, category_id*, price*, compare_at_price, unit*, track_stock (0|1), quantity_available (บังคับเมื่อตัดสต็อก),
 |   is_organic (0|1), freshness_level, cashback_percentage (≤ max), images[] (≤ 5 รูป)
 |   แก้ไขเพิ่ม: is_available (0|1), remove_images[], main_image, option_groups_present=1
 |   กลุ่มตัวเลือก: option_groups[k][id?|name|selection_type|is_required|min_select|max_select|options[j][id?|name|price_delta|is_available|image]]
 |   (ตัวเลือกเดิมที่ไม่ได้ส่งรูปใหม่ = ใช้รูปเดิม)
 --}}
@php
    $ui = \App\Support\TaladsodWebUi::class;
    $isEdit = ($mode ?? 'create') === 'edit' && isset($listing);
    $lf = $isEdit ? $listing : null;

    // กลุ่มตัวเลือกเริ่มต้นของตัวแก้ไข: ค่าที่ส่งไม่ผ่าน (old) → ค่าเดิมของสินค้า → ว่าง
    $oldGroups = old('option_groups');
    $editorGroups = [];
    if (is_array($oldGroups)) {
        foreach (array_values($oldGroups) as $g) {
            if (! is_array($g)) {
                continue;
            }
            $editorGroups[] = [
                'id' => isset($g['id']) && is_numeric($g['id']) ? (int) $g['id'] : null,
                'name' => (string) ($g['name'] ?? ''),
                'selection_type' => ($g['selection_type'] ?? 'single') === 'multi' ? 'multi' : 'single',
                'is_required' => filter_var($g['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'min_select' => isset($g['min_select']) && $g['min_select'] !== '' ? (int) $g['min_select'] : null,
                'max_select' => isset($g['max_select']) && $g['max_select'] !== '' ? (int) $g['max_select'] : null,
                'options' => collect(is_array($g['options'] ?? null) ? array_values($g['options']) : [])->map(fn ($o) => [
                    'id' => isset($o['id']) && is_numeric($o['id']) ? (int) $o['id'] : null,
                    'name' => (string) ($o['name'] ?? ''),
                    'price_delta' => (float) ($o['price_delta'] ?? 0),
                    'is_available' => filter_var($o['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'image_url' => null,
                ])->all(),
            ];
        }
    } else {
        foreach ($optionGroups ?? [] as $g) {
            $editorGroups[] = [
                'id' => (int) $g['id'],
                'name' => (string) $g['name'],
                'selection_type' => $g['selection_type'] === 'multi' ? 'multi' : 'single',
                'is_required' => (bool) $g['is_required'],
                'min_select' => $g['selection_type'] === 'multi' && (int) $g['min_select'] > 0 ? (int) $g['min_select'] : null,
                'max_select' => $g['selection_type'] === 'multi' ? ($g['max_select'] ?? null) : null,
                'options' => collect($g['options'] ?? [])->map(fn ($o) => [
                    'id' => (int) $o['id'],
                    'name' => (string) $o['name'],
                    'price_delta' => (float) $o['price_delta'],
                    'is_available' => (bool) $o['is_available'],
                    'image_url' => $o['image_url'] ?? null,
                ])->all(),
            ];
        }
    }

    $currentImages = $isEdit
        ? collect(is_array($lf->images) ? $lf->images : [])->prepend($lf->main_image_url)->filter(fn ($u) => is_string($u) && $u !== '')->unique()->values()->all()
        : [];

    $formCfg = [
        'price' => (float) old('price', $lf->price ?? 0) ?: null,
        'gpRate' => (float) $gpRate,
        'trackStock' => filter_var(old('track_stock', $lf ? ($lf->tracksStock() ? '1' : '0') : '0'), FILTER_VALIDATE_BOOLEAN),
        'groups' => $editorGroups,
        'currentImages' => $currentImages,
        'mainImage' => old('main_image', $lf->main_image_url ?? ($currentImages[0] ?? null)),
        'maxImages' => 5,
    ];
    $units = ['จาน', 'กล่อง', 'ถุง', 'ชุด', 'แก้ว', 'ชิ้น', 'กก.', 'ขีด', 'กำ', 'ห่อ', 'ลูก', 'แพ็ค'];
@endphp

<div class="ts-stack" style="gap:16px;" x-data="tsListingForm({{ \Illuminate\Support\Js::from($formCfg) }})">
    @if($errors->any())
        <div class="sf-note sf-note-err" role="alert">
            <b><i class="fas fa-circle-exclamation" aria-hidden="true"></i> บันทึกไม่สำเร็จ กรุณาตรวจสอบ</b>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ════════ ข้อมูลสินค้า ════════ --}}
    <section class="tp-card ts-stack" aria-labelledby="lf-info-h">
        <h2 id="lf-info-h" class="ts-h2"><i class="fas fa-bowl-food" style="color:var(--accent2);" aria-hidden="true"></i> ข้อมูลสินค้า</h2>
        <div>
            <label class="ts-label" for="lf-title">ชื่อสินค้า/เมนู <span class="req">*</span></label>
            <input id="lf-title" type="text" name="title" class="tp-input" required maxlength="200" value="{{ old('title', $lf->title ?? '') }}" placeholder="เช่น ผัดกะเพราราดข้าว, ผักบุ้งจีนปลอดสาร">
            @error('title')<p class="ts-err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ts-label" for="lf-desc">รายละเอียด</label>
            <textarea id="lf-desc" name="description" class="tp-input" rows="3" maxlength="2000" placeholder="วัตถุดิบ ปริมาณ จุดเด่น เช่น ผัดไฟแรง หอมใบกะเพรา">{{ old('description', $lf->description ?? '') }}</textarea>
        </div>
        <div class="ts-grid" style="--ts-min:200px;">
            <div>
                <label class="ts-label" for="lf-cat">หมวดหมู่ <span class="req">*</span></label>
                <select id="lf-cat" name="category_id" class="tp-input" required>
                    <option value="">เลือกหมวดหมู่</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) old('category_id', $lf->category_id ?? '') === (string) $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="ts-err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="ts-label" for="lf-fresh">ความสด</label>
                <select id="lf-fresh" name="freshness_level" class="tp-input">
                    <option value="">ไม่ระบุ</option>
                    @foreach(['สด', 'สดมาก', 'ผลิตวันนี้'] as $level)
                        <option value="{{ $level }}" @selected(old('freshness_level', $lf->freshness_level ?? '') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label class="ts-switch">
            <input type="hidden" name="is_organic" value="0">
            <input type="checkbox" name="is_organic" value="1" @checked(old('is_organic', $lf->is_organic ?? false))>
            <span class="track"></span>
            <span style="font-size:13.5px; font-weight:700;"><i class="fas fa-leaf" style="color:var(--ts-ok);" aria-hidden="true"></i> สินค้าอินทรีย์/ปลอดสาร</span>
        </label>
    </section>

    {{-- ════════ ราคา + สต็อก ════════ --}}
    <section class="tp-card ts-stack" aria-labelledby="lf-price-h">
        <h2 id="lf-price-h" class="ts-h2"><i class="fas fa-tag" style="color:var(--accent2);" aria-hidden="true"></i> ราคาและจำนวน</h2>
        <div class="ts-grid" style="--ts-min:170px;">
            <div>
                <label class="ts-label" for="lf-price">ราคาขาย (บาท) <span class="req">*</span></label>
                <input id="lf-price" type="number" name="price" class="tp-input" required min="1" max="1000000" step="0.01" inputmode="decimal" x-model.number="price" value="{{ old('price', $lf->price ?? '') }}">
                @error('price')<p class="ts-err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="ts-label" for="lf-compare">ราคาก่อนลด <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></label>
                <input id="lf-compare" type="number" name="compare_at_price" class="tp-input" min="1" step="0.01" inputmode="decimal" value="{{ old('compare_at_price', $lf->compare_at_price ?? '') }}">
                @error('compare_at_price')<p class="ts-err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="ts-label" for="lf-unit">หน่วยขาย <span class="req">*</span></label>
                <input id="lf-unit" type="text" name="unit" class="tp-input" required maxlength="50" list="lf-units" value="{{ old('unit', $lf->unit ?? 'จาน') }}">
                <datalist id="lf-units">
                    @foreach($units as $u)
                        <option value="{{ $u }}">
                    @endforeach
                </datalist>
                @error('unit')<p class="ts-err">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ราคา − GP = รับจริง --}}
        <div class="sf-note {{ $gpFree ? 'sf-note-ok' : 'sf-note-info' }}" x-show="price > 0">
            <i class="fas fa-calculator" aria-hidden="true"></i>
            ราคา ฿<b x-text="money(price)"></b> − GP {{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% (฿<span x-text="money(gpAmount)"></span>)
            = <b>ร้านรับจริง ฿<span x-text="money(net)"></span></b> ต่อ{{ $lf->unit ?? 'ชิ้น' }}
            @if($gpFree)
                <span class="ts-pill solid ts-tone-ok" style="margin-left:6px;"><i class="fas fa-gift" aria-hidden="true"></i> ฟรี GP ช่วงเปิดตัว</span>
            @endif
            <div class="ts-small ts-muted" style="margin-top:4px;">ราคาตัวเลือก (เช่น +฿20 กุ้ง) คิด GP อัตราเดียวกัน · ค่าส่งลูกค้าจ่ายให้ไรเดอร์ ไม่หักจากร้าน</div>
        </div>

        <div class="ts-grid" style="--ts-min:220px; gap:10px;">
            <button type="button" class="ts-choice" :class="!trackStock ? 'is-on' : ''" x-on:click="trackStock = false" :aria-pressed="!trackStock ? 'true' : 'false'">
                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                <span class="name">ทำตามสั่ง / ไม่จำกัดจำนวน<span class="ts-muted ts-small" style="display:block; font-weight:600;">เหมาะกับอาหารปรุงสด</span></span>
            </button>
            <button type="button" class="ts-choice" :class="trackStock ? 'is-on' : ''" x-on:click="trackStock = true" :aria-pressed="trackStock ? 'true' : 'false'">
                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                <span class="name">นับสต็อก<span class="ts-muted ts-small" style="display:block; font-weight:600;">ของหมดแล้วปิดขายอัตโนมัติ</span></span>
            </button>
        </div>
        <input type="hidden" name="track_stock" :value="trackStock ? 1 : 0" value="{{ $formCfg['trackStock'] ? 1 : 0 }}">
        <div x-show="trackStock" @if(! $formCfg['trackStock']) x-cloak @endif>
            <label class="ts-label" for="lf-qty">จำนวนที่มีขาย <span class="req">*</span></label>
            <div class="ts-input-group">
                <input id="lf-qty" type="number" name="quantity_available" class="tp-input" min="{{ $isEdit ? 0 : 1 }}" max="100000" inputmode="numeric" :required="trackStock" :disabled="!trackStock"
                       value="{{ old('quantity_available', $lf ? (int) $lf->quantity_available : '') }}">
                <span class="suffix">{{ $lf->unit ?? 'ชิ้น' }}</span>
            </div>
            @error('quantity_available')<p class="ts-err">{{ $message }}</p>@enderror
        </div>

        @if($isEdit)
            <label class="ts-switch">
                <input type="hidden" name="is_available" value="0">
                <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $lf->is_available)) @disabled($lf->status === 'suspended')>
                <span class="track"></span>
                <span style="font-size:13.5px; font-weight:700;">เปิดขายสินค้านี้</span>
            </label>
            @if($lf->status === 'suspended')
                <p class="ts-err" style="margin:0;">สินค้าถูกระงับโดยแอดมิน — เปิดขายเองไม่ได้ กรุณาติดต่อทีมงาน</p>
            @endif
        @endif

        @if((float) $maxCashbackPercent > 0)
            <div>
                <label class="ts-label" for="lf-cashback">เงินคืนให้ลูกค้า (%) <span class="ts-muted" style="font-weight:600;">ไม่เกิน {{ rtrim(rtrim(number_format((float) $maxCashbackPercent, 2), '0'), '.') }}%</span></label>
                <input id="lf-cashback" type="number" name="cashback_percentage" class="tp-input" min="0" max="{{ $maxCashbackPercent }}" step="0.5" value="{{ old('cashback_percentage', $lf->cashback_percentage ?? 0) }}">
                <p class="ts-help">เงินคืนจ่ายจากค่า GP ของแพลตฟอร์ม ไม่หักจากรายรับร้าน</p>
                @error('cashback_percentage')<p class="ts-err">{{ $message }}</p>@enderror
            </div>
        @endif
    </section>

    {{-- ════════ รูปสินค้า ════════ --}}
    <section class="tp-card ts-stack" aria-labelledby="lf-img-h">
        <h2 id="lf-img-h" class="ts-h2"><i class="fas fa-camera" style="color:var(--accent2);" aria-hidden="true"></i> รูปสินค้า <span class="ts-muted ts-small" style="font-weight:600;">(สูงสุด 5 รูป · รูปละไม่เกิน 5MB)</span></h2>
        @if($isEdit && count($currentImages) > 0)
            <div style="display:grid; gap:10px; grid-template-columns:repeat(auto-fill, minmax(118px, 1fr));">
                <template x-for="img in currentImages" :key="img">
                    <div class="tp-inset" style="border-radius:16px; padding:8px; position:relative;" :style="{ opacity: removed.includes(img) ? 0.35 : 1 }">
                        <img :src="img" alt="" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:12px;">
                        <div class="ts-row" style="gap:6px; margin-top:6px; justify-content:space-between;">
                            <button type="button" class="tp-btn tp-btn-sm" :class="mainImage === img ? 'tp-btn-primary' : ''" x-on:click="mainImage = img" :disabled="removed.includes(img)" style="padding:0 8px; font-size:11px;">
                                <i class="fas fa-star" aria-hidden="true"></i> <span x-text="mainImage === img ? 'รูปหลัก' : 'ตั้งเป็นหลัก'"></span>
                            </button>
                            <button type="button" class="ts-icon-btn" style="width:34px; height:34px; color:var(--ts-bad);" x-on:click="toggleRemove(img)" :aria-label="removed.includes(img) ? 'เก็บรูปนี้ไว้' : 'ลบรูปนี้'">
                                <i class="fas" :class="removed.includes(img) ? 'fa-rotate-left' : 'fa-trash-can'" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <template x-for="img in removed" :key="'rm' + img"><input type="hidden" name="remove_images[]" :value="img"></template>
            <input type="hidden" name="main_image" :value="mainImage || ''">
        @endif
        <label class="tp-inset" style="border-radius:18px; padding:18px; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; text-align:center;">
            <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:20px;"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i></span>
            <b style="font-size:14px;">{{ $isEdit ? 'เพิ่มรูปใหม่' : 'เลือกรูปสินค้า' }}</b>
            <span class="ts-muted ts-small">แตะเพื่อถ่ายรูปหรือเลือกจากเครื่อง (เหลือเพิ่มได้ <span x-text="slotsLeft"></span> รูป)</span>
            <input type="file" name="images[]" accept="image/*" multiple class="sr-only" style="position:absolute; width:1px; height:1px; opacity:0;" x-ref="images" x-on:change="previewNew($event)">
        </label>
        <div class="ts-row" style="gap:8px;" x-show="newPreviews.length > 0">
            <template x-for="(src, i) in newPreviews" :key="i">
                <img :src="src" alt="" style="width:72px; height:72px; object-fit:cover; border-radius:12px; box-shadow:var(--inset-sm);">
            </template>
        </div>
        <p class="ts-err" x-show="imageError" x-text="imageError" x-cloak></p>
        @error('images')<p class="ts-err">{{ $message }}</p>@enderror
        @error('images.*')<p class="ts-err">{{ $message }}</p>@enderror
    </section>

    {{-- ════════ ตัวเลือกสินค้า ════════ --}}
    <section class="tp-card ts-stack" aria-labelledby="lf-opt-h">
        <div class="ts-row" style="justify-content:space-between;">
            <h2 id="lf-opt-h" class="ts-h2"><i class="fas fa-sliders" style="color:var(--accent2);" aria-hidden="true"></i> ตัวเลือกสินค้า <span class="ts-muted ts-small" style="font-weight:600;">(ไม่บังคับ)</span></h2>
            <span class="ts-muted ts-small" x-text="groups.length + '/10 กลุ่ม'"></span>
        </div>
        <p class="ts-help" style="margin:0;">เช่น "เลือกเนื้อสัตว์" ให้ลูกค้าเลือก 1 อย่าง, "เพิ่มเติม" เลือกได้หลายอย่าง — ตั้งราคาเพิ่มต่อชิ้นได้ (ใส่ 0 = ไม่บวกเพิ่ม)</p>
        @if($isEdit)
            <input type="hidden" name="option_groups_present" value="1">
        @endif

        <div class="ts-row" style="gap:8px;">
            <span class="ts-muted ts-small">เพิ่มด่วน:</span>
            <button type="button" class="sf-chip" x-on:click="addTemplate('meat')" :disabled="groups.length >= 10"><i class="fas fa-drumstick-bite" aria-hidden="true"></i> เลือกเนื้อสัตว์</button>
            <button type="button" class="sf-chip" x-on:click="addTemplate('spicy')" :disabled="groups.length >= 10"><i class="fas fa-pepper-hot" aria-hidden="true"></i> ความเผ็ด</button>
            <button type="button" class="sf-chip" x-on:click="addTemplate('extra')" :disabled="groups.length >= 10"><i class="fas fa-egg" aria-hidden="true"></i> ท็อปปิ้ง</button>
            <button type="button" class="sf-chip" x-on:click="addTemplate('size')" :disabled="groups.length >= 10"><i class="fas fa-up-right-and-down-left-from-center" aria-hidden="true"></i> ขนาด</button>
            <button type="button" class="sf-chip" x-on:click="addGroup()" :disabled="groups.length >= 10"><i class="fas fa-plus" aria-hidden="true"></i> กลุ่มเปล่า</button>
        </div>
        @error('option_groups')<p class="ts-err">{{ $message }}</p>@enderror

        <template x-for="(g, gi) in groups" :key="g.key">
            <div class="tp-inset ts-stack" style="border-radius:18px; padding:14px; gap:12px;">
                <input type="hidden" :name="'option_groups[' + g.key + '][id]'" :value="g.id" :disabled="!g.id">
                <div class="ts-row" style="gap:8px; flex-wrap:nowrap;">
                    <input type="text" class="tp-input" :name="'option_groups[' + g.key + '][name]'" x-model="g.name" maxlength="100" required placeholder="ชื่อกลุ่ม เช่น เลือกเนื้อสัตว์" style="flex:1; min-width:0;" aria-label="ชื่อกลุ่มตัวเลือก">
                    <button type="button" class="ts-icon-btn" x-on:click="move(gi, -1)" :disabled="gi === 0" aria-label="เลื่อนกลุ่มขึ้น"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
                    <button type="button" class="ts-icon-btn" x-on:click="move(gi, 1)" :disabled="gi === groups.length - 1" aria-label="เลื่อนกลุ่มลง"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
                    <button type="button" class="ts-icon-btn" style="color:var(--ts-bad);" x-on:click="removeGroup(gi)" :aria-label="g.confirmDelete ? 'แตะอีกครั้งเพื่อยืนยันลบกลุ่ม' : 'ลบกลุ่ม'">
                        <i class="fas" :class="g.confirmDelete ? 'fa-check' : 'fa-trash-can'" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="ts-err" style="margin:0;" x-show="g.confirmDelete">แตะปุ่มถังขยะอีกครั้งเพื่อลบกลุ่มนี้</p>

                <div class="ts-row" style="gap:8px;">
                    <div class="sf-tabs" style="flex:1 1 260px; padding:4px;">
                        <button type="button" class="sf-tab" :class="g.selection_type === 'single' ? 'is-on' : ''" x-on:click="g.selection_type = 'single'">เลือกได้ 1 อย่าง</button>
                        <button type="button" class="sf-tab" :class="g.selection_type === 'multi' ? 'is-on' : ''" x-on:click="g.selection_type = 'multi'">เลือกได้หลายอย่าง</button>
                    </div>
                    <input type="hidden" :name="'option_groups[' + g.key + '][selection_type]'" :value="g.selection_type">
                    <label class="ts-switch">
                        <input type="checkbox" x-model="g.is_required">
                        <span class="track"></span>
                        <span style="font-size:13px; font-weight:700;">บังคับเลือก</span>
                    </label>
                    <input type="hidden" :name="'option_groups[' + g.key + '][is_required]'" :value="g.is_required ? 1 : 0">
                </div>

                <div class="ts-row" style="gap:10px;" x-show="g.selection_type === 'multi'">
                    <label class="ts-row" style="gap:6px; font-size:12.5px; font-weight:700;">ขั้นต่ำ
                        <input type="number" class="tp-input" style="width:84px; min-height:40px;" min="0" max="30" :name="'option_groups[' + g.key + '][min_select]'" x-model="g.min_select" :disabled="g.selection_type !== 'multi'" placeholder="0">
                    </label>
                    <label class="ts-row" style="gap:6px; font-size:12.5px; font-weight:700;">สูงสุด
                        <input type="number" class="tp-input" style="width:84px; min-height:40px;" min="0" max="30" :name="'option_groups[' + g.key + '][max_select]'" x-model="g.max_select" :disabled="g.selection_type !== 'multi'" placeholder="ไม่จำกัด">
                    </label>
                </div>
                <span class="ts-pill ts-tone-info" style="align-self:flex-start;" x-text="'ลูกค้าเห็น: ' + ruleLabel(g)"></span>

                <div class="ts-stack" style="gap:8px;">
                    <template x-for="(o, oi) in g.options" :key="o.key">
                        <div class="ts-row" style="gap:8px; align-items:center; padding:8px; border-radius:14px; background:var(--card-bg);" :style="{ opacity: o.is_available ? 1 : 0.6 }">
                            <input type="hidden" :name="'option_groups[' + g.key + '][options][' + o.key + '][id]'" :value="o.id" :disabled="!o.id">
                            <label class="sf-thumb" style="width:48px; height:48px; cursor:pointer; flex:none; position:relative;" :title="o.previewUrl || o.image_url ? 'เปลี่ยนรูปตัวเลือก' : 'เพิ่มรูปตัวเลือก'">
                                <template x-if="o.previewUrl || o.image_url"><img :src="o.previewUrl || o.image_url" alt=""></template>
                                <template x-if="!o.previewUrl && !o.image_url"><i class="fas fa-camera ts-muted" aria-hidden="true"></i></template>
                                <input type="file" accept="image/*" :name="'option_groups[' + g.key + '][options][' + o.key + '][image]'" style="position:absolute; width:1px; height:1px; opacity:0;" x-on:change="previewOption(o, $event)" aria-label="รูปตัวเลือก">
                            </label>
                            <input type="text" class="tp-input" style="flex:1 1 150px; min-width:0;" :name="'option_groups[' + g.key + '][options][' + o.key + '][name]'" x-model="o.name" maxlength="100" required placeholder="ชื่อตัวเลือก" aria-label="ชื่อตัวเลือก">
                            <div class="ts-row" style="gap:8px; flex:none; flex-wrap:nowrap;">
                                <div class="ts-input-group" style="width:108px;">
                                    <span class="suffix">+฿</span>
                                    <input type="number" class="tp-input" style="padding:11px 8px;" min="0" max="100000" step="0.5" :name="'option_groups[' + g.key + '][options][' + o.key + '][price_delta]'" x-model="o.price_delta" aria-label="ราคาเพิ่ม">
                                </div>
                                <button type="button" class="ts-icon-btn" :style="{ color: o.is_available ? 'var(--ts-ok)' : 'var(--ink2)' }" x-on:click="o.is_available = !o.is_available" :aria-label="o.is_available ? 'มีขาย (แตะเพื่อตั้งเป็นหมด)' : 'หมดชั่วคราว (แตะเพื่อเปิดขาย)'" :title="o.is_available ? 'มีขาย' : 'หมดชั่วคราว'">
                                    <i class="fas" :class="o.is_available ? 'fa-eye' : 'fa-eye-slash'" aria-hidden="true"></i>
                                </button>
                                <input type="hidden" :name="'option_groups[' + g.key + '][options][' + o.key + '][is_available]'" :value="o.is_available ? 1 : 0">
                                <button type="button" class="ts-icon-btn" style="color:var(--ts-bad);" x-on:click="removeOption(g, oi)" :disabled="g.options.length <= 1" aria-label="ลบตัวเลือก"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="tp-btn tp-btn-sm" style="align-self:flex-start;" x-on:click="addOption(g)" :disabled="g.options.length >= 30"><i class="fas fa-plus" aria-hidden="true"></i> เพิ่มตัวเลือก</button>
                </div>
            </div>
        </template>

        <div class="ts-empty" style="padding:14px;" x-show="groups.length === 0">
            <span class="ts-muted ts-small">ยังไม่มีตัวเลือก — สินค้าขายราคาเดียว</span>
        </div>
    </section>
</div>

@push('scripts')
<script>
    /**
     * ฟอร์มสินค้า: ราคา − GP = รับจริง, สต็อก, รูปสินค้า, ตัวแก้ไขกลุ่มตัวเลือก (เพิ่ม/ลบ/เรียง/รูป)
     * ชื่อช่องใช้ key คงที่ของแต่ละแถว → เซิร์ฟเวอร์จับคู่ไฟล์รูปกับตัวเลือกได้ถูกแถวแม้ลบแถวกลางไปแล้ว
     */
    window.tsListingForm = function (cfg) {
        let seq = 0;
        const nextKey = () => 'k' + (++seq);
        const TEMPLATES = {
            meat: { name: 'เลือกเนื้อสัตว์', selection_type: 'single', is_required: true, options: [['หมูสับ', 0], ['ไก่', 0], ['หมึก', 10], ['กุ้ง', 20]] },
            spicy: { name: 'ระดับความเผ็ด', selection_type: 'single', is_required: false, options: [['ไม่เผ็ด', 0], ['เผ็ดน้อย', 0], ['เผ็ดกลาง', 0], ['เผ็ดมาก', 0]] },
            extra: { name: 'เพิ่มเติม', selection_type: 'multi', is_required: false, options: [['ไข่ดาว', 10], ['ไข่เจียว', 10]] },
            size: { name: 'ขนาด', selection_type: 'single', is_required: true, options: [['ธรรมดา', 0], ['พิเศษ', 10]] }
        };
        const makeOption = (o) => ({
            key: nextKey(), id: o.id || null, name: o.name || '', price_delta: o.price_delta ?? 0,
            is_available: o.is_available !== false, image_url: o.image_url || null, previewUrl: null
        });
        const makeGroup = (g) => ({
            key: nextKey(), id: g.id || null, name: g.name || '', selection_type: g.selection_type === 'multi' ? 'multi' : 'single',
            is_required: !!g.is_required, min_select: g.min_select ?? '', max_select: g.max_select ?? '', confirmDelete: false,
            options: (g.options && g.options.length ? g.options : [{}]).map(makeOption)
        });

        return {
            price: cfg.price, gpRate: cfg.gpRate, trackStock: !!cfg.trackStock,
            groups: (cfg.groups || []).map(makeGroup),
            currentImages: cfg.currentImages || [], removed: [], mainImage: cfg.mainImage || null,
            newPreviews: [], imageError: '',

            money(n) { return window.ts.money(n || 0); },
            get gpAmount() { return Math.round((Number(this.price) || 0) * this.gpRate) / 100; },
            get net() { return Math.round(((Number(this.price) || 0) - this.gpAmount) * 100) / 100; },
            get slotsLeft() { return Math.max(0, cfg.maxImages - (this.currentImages.length - this.removed.length)); },

            toggleRemove(img) {
                if (this.removed.includes(img)) {
                    this.removed = this.removed.filter((x) => x !== img);
                } else {
                    this.removed.push(img);
                    if (this.mainImage === img) { this.mainImage = this.currentImages.find((x) => !this.removed.includes(x)) || null; }
                }
            },

            previewNew(e) {
                const files = Array.from(e.target.files || []);
                this.imageError = '';
                if (files.length > this.slotsLeft) {
                    this.imageError = 'เพิ่มได้อีกไม่เกิน ' + this.slotsLeft + ' รูป — เลือกใหม่อีกครั้ง';
                    e.target.value = '';
                    this.newPreviews = [];
                    return;
                }
                const big = files.find((f) => f.size > 5 * 1024 * 1024);
                if (big) {
                    this.imageError = 'รูป "' + big.name + '" ใหญ่เกิน 5MB';
                    e.target.value = '';
                    this.newPreviews = [];
                    return;
                }
                this.newPreviews = files.map((f) => URL.createObjectURL(f));
            },

            previewOption(o, e) {
                const f = e.target.files && e.target.files[0];
                if (!f) { return; }
                if (f.size > 5 * 1024 * 1024) {
                    window.ts.notify('รูปตัวเลือกต้องไม่เกิน 5MB', 'error');
                    e.target.value = '';
                    return;
                }
                o.previewUrl = URL.createObjectURL(f);
            },

            ruleLabel(g) {
                const min = g.selection_type === 'multi' ? Math.max(parseInt(g.min_select) || 0, g.is_required ? 1 : 0) : (g.is_required ? 1 : 0);
                const max = g.selection_type === 'multi' ? (parseInt(g.max_select) > 0 ? parseInt(g.max_select) : null) : 1;
                if (g.selection_type !== 'multi') { return min > 0 ? 'เลือก 1 อย่าง (บังคับ)' : 'เลือกได้ 1 อย่าง (ไม่บังคับ)'; }
                if (min > 0 && max !== null) { return min === max ? 'เลือก ' + min + ' อย่าง (บังคับ)' : 'เลือก ' + min + '-' + max + ' อย่าง (บังคับ)'; }
                if (min > 0) { return 'เลือกอย่างน้อย ' + min + ' อย่าง (บังคับ)'; }
                return max !== null ? 'เลือกได้สูงสุด ' + max + ' อย่าง (ไม่บังคับ)' : 'เลือกได้หลายอย่าง (ไม่บังคับ)';
            },

            addGroup() {
                if (this.groups.length >= 10) { return; }
                this.groups.push(makeGroup({}));
            },

            addTemplate(key) {
                const t = TEMPLATES[key];
                if (!t || this.groups.length >= 10) { return; }
                this.groups.push(makeGroup({
                    name: t.name, selection_type: t.selection_type, is_required: t.is_required,
                    options: t.options.map(([name, price]) => ({ name: name, price_delta: price, is_available: true }))
                }));
            },

            removeGroup(i) {
                const g = this.groups[i];
                if (!g.confirmDelete) {
                    g.confirmDelete = true;
                    setTimeout(() => { g.confirmDelete = false; }, 4000);
                    return;
                }
                this.groups.splice(i, 1);
            },

            move(i, d) {
                const j = i + d;
                if (j < 0 || j >= this.groups.length) { return; }
                const list = this.groups.slice();
                [list[i], list[j]] = [list[j], list[i]];
                this.groups = list;
            },

            addOption(g) {
                if (g.options.length >= 30) { return; }
                g.options.push(makeOption({}));
            },

            removeOption(g, i) {
                if (g.options.length <= 1) { return; }
                g.options.splice(i, 1);
            }
        };
    };
</script>
@endpush
