{{--
 | ฟอร์มสินค้าตลาดสด (ใช้ร่วมกันระหว่างลงขายใหม่และแก้ไข) — ธีมโนวา (layouts.user-nova)
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

<div class="nvm-stack" x-data="tsListingForm({{ \Illuminate\Support\Js::from($formCfg) }})">
    @if($errors->any())
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b>บันทึกไม่สำเร็จ กรุณาตรวจสอบ</b>
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        </section>
    @endif

    {{-- ════════ ข้อมูลสินค้า ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="lf-info-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Product</small><h2 id="lf-info-h">ข้อมูลสินค้า</h2></div></div>
        <div class="nvm-form">
            <div class="nvm-field">
                <label class="nvm-label" for="lf-title">ชื่อสินค้า/เมนู <em>*</em></label>
                <input id="lf-title" type="text" name="title" class="nvm-input @error('title') is-bad @enderror" required maxlength="200" value="{{ old('title', $lf->title ?? '') }}" placeholder="เช่น ผัดกะเพราราดข้าว, ผักบุ้งจีนปลอดสาร">
                @error('title')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="lf-desc">รายละเอียด</label>
                <textarea id="lf-desc" name="description" class="nvm-input nvm-textarea" rows="3" maxlength="2000" placeholder="วัตถุดิบ ปริมาณ จุดเด่น เช่น ผัดไฟแรง หอมใบกะเพรา">{{ old('description', $lf->description ?? '') }}</textarea>
            </div>
            <div class="nvm-td-cols">
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-cat">หมวดหมู่ <em>*</em></label>
                    <select id="lf-cat" name="category_id" class="nvm-input @error('category_id') is-bad @enderror" required>
                        <option value="">เลือกหมวดหมู่</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) old('category_id', $lf->category_id ?? '') === (string) $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-fresh">ความสด</label>
                    <select id="lf-fresh" name="freshness_level" class="nvm-input">
                        <option value="">ไม่ระบุ</option>
                        @foreach(['สด', 'สดมาก', 'ผลิตวันนี้'] as $level)
                            <option value="{{ $level }}" @selected(old('freshness_level', $lf->freshness_level ?? '') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="nvm-td-switch">
                <input type="hidden" name="is_organic" value="0">
                <span class="nvm-sw"><input type="checkbox" name="is_organic" value="1" @checked(old('is_organic', $lf->is_organic ?? false))><i></i></span>
                สินค้าอินทรีย์/ปลอดสาร
            </label>
        </div>
    </section>

    {{-- ════════ ราคา + สต็อก ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="lf-price-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Price & stock</small><h2 id="lf-price-h">ราคาและจำนวน</h2></div></div>
        <div class="nvm-form">
            <div class="nvm-td-cols">
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-price">ราคาขาย (บาท) <em>*</em></label>
                    <input id="lf-price" type="number" name="price" class="nvm-input @error('price') is-bad @enderror" required min="1" max="1000000" step="0.01" inputmode="decimal" x-model.number="price" value="{{ old('price', $lf->price ?? '') }}">
                    @error('price')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-compare">ราคาก่อนลด (ไม่บังคับ)</label>
                    <input id="lf-compare" type="number" name="compare_at_price" class="nvm-input @error('compare_at_price') is-bad @enderror" min="1" step="0.01" inputmode="decimal" value="{{ old('compare_at_price', $lf->compare_at_price ?? '') }}">
                    @error('compare_at_price')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-unit">หน่วยขาย <em>*</em></label>
                    <input id="lf-unit" type="text" name="unit" class="nvm-input @error('unit') is-bad @enderror" required maxlength="50" list="lf-units" value="{{ old('unit', $lf->unit ?? 'จาน') }}">
                    <datalist id="lf-units">
                        @foreach($units as $u)
                            <option value="{{ $u }}">
                        @endforeach
                    </datalist>
                    @error('unit')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- ราคา − GP = รับจริง --}}
            <div class="nvm-td-calc" x-show="price > 0" @if(! $formCfg['price']) x-cloak @endif>
                ราคา ฿<b x-text="money(price)"></b> − GP {{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% (฿<span x-text="money(gpAmount)"></span>)
                = <b>ร้านรับจริง ฿<span x-text="money(net)"></span></b> ต่อ{{ $lf->unit ?? 'ชิ้น' }}
                @if($gpFree)
                    <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>ฟรี GP ช่วงเปิดตัว</span>
                @endif
                <small>ราคาตัวเลือก (เช่น +฿20 กุ้ง) คิด GP อัตราเดียวกัน · ค่าส่งลูกค้าจ่ายให้ไรเดอร์ ไม่หักจากร้าน</small>
            </div>

            <div class="nvm-opts nvm-opts--2" role="radiogroup" aria-label="วิธีนับจำนวนสินค้า">
                <label class="nvm-opt">
                    <input type="radio" value="0" :checked="!trackStock" x-on:change="trackStock = false" @checked(! $formCfg['trackStock'])>
                    <span><b>ทำตามสั่ง / ไม่จำกัดจำนวน</b><small>เหมาะกับอาหารปรุงสด</small></span>
                </label>
                <label class="nvm-opt">
                    <input type="radio" value="1" :checked="trackStock" x-on:change="trackStock = true" @checked($formCfg['trackStock'])>
                    <span><b>นับสต็อก</b><small>ของหมดแล้วปิดขายอัตโนมัติ</small></span>
                </label>
            </div>
            <input type="hidden" name="track_stock" :value="trackStock ? 1 : 0" value="{{ $formCfg['trackStock'] ? 1 : 0 }}">
            <div class="nvm-field" x-show="trackStock" @if(! $formCfg['trackStock']) x-cloak @endif>
                <label class="nvm-label" for="lf-qty">จำนวนที่มีขาย <em>*</em></label>
                <div class="nvm-td-affix">
                    <input id="lf-qty" type="number" name="quantity_available" class="nvm-input @error('quantity_available') is-bad @enderror" min="{{ $isEdit ? 0 : 1 }}" max="100000" inputmode="numeric" :required="trackStock" :disabled="!trackStock"
                           value="{{ old('quantity_available', $lf ? (int) $lf->quantity_available : '') }}">
                    <span>{{ $lf->unit ?? 'ชิ้น' }}</span>
                </div>
                @error('quantity_available')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            @if($isEdit)
                <label class="nvm-td-switch {{ $lf->status === 'suspended' ? 'is-dim' : '' }}">
                    <input type="hidden" name="is_available" value="0">
                    <span class="nvm-sw"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $lf->is_available)) @disabled($lf->status === 'suspended')><i></i></span>
                    เปิดขายสินค้านี้
                </label>
                @if($lf->status === 'suspended')
                    <span class="nvm-err">สินค้าถูกระงับโดยแอดมิน — เปิดขายเองไม่ได้ กรุณาติดต่อทีมงาน</span>
                @endif
            @endif

            @if((float) $maxCashbackPercent > 0)
                <div class="nvm-field">
                    <label class="nvm-label" for="lf-cashback">เงินคืนให้ลูกค้า (%) · ไม่เกิน {{ rtrim(rtrim(number_format((float) $maxCashbackPercent, 2), '0'), '.') }}%</label>
                    <input id="lf-cashback" type="number" name="cashback_percentage" class="nvm-input @error('cashback_percentage') is-bad @enderror" min="0" max="{{ $maxCashbackPercent }}" step="0.5" value="{{ old('cashback_percentage', $lf->cashback_percentage ?? 0) }}">
                    <span class="nvm-help">เงินคืนจ่ายจากค่า GP ของแพลตฟอร์ม ไม่หักจากรายรับร้าน</span>
                    @error('cashback_percentage')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            @endif
        </div>
    </section>

    {{-- ════════ รูปสินค้า ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="lf-img-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Photos · สูงสุด 5 รูป รูปละไม่เกิน 5MB</small><h2 id="lf-img-h">รูปสินค้า</h2></div></div>
        <div class="nvm-form">
            @if($isEdit && count($currentImages) > 0)
                <div class="nvm-td-imgs">
                    <template x-for="img in currentImages" :key="img">
                        <div class="nvm-td-img" :class="{ 'is-off': removed.includes(img), 'is-main': mainImage === img && ! removed.includes(img) }">
                            <img :src="img" alt="">
                            <div>
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-on:click="mainImage = img" :disabled="removed.includes(img)">
                                    <span x-text="mainImage === img ? 'รูปหลัก' : 'ตั้งเป็นหลัก'"></span>
                                </button>
                                <button type="button" class="nvm-td-ib is-bad" x-on:click="toggleRemove(img)" :aria-label="removed.includes(img) ? 'เก็บรูปนี้ไว้' : 'ลบรูปนี้'">
                                    <svg class="nvm-ico"><use :href="removed.includes(img) ? '#nvm-i-swap' : '#nvm-i-x'"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <template x-for="img in removed" :key="'rm' + img"><input type="hidden" name="remove_images[]" :value="img"></template>
                <input type="hidden" name="main_image" :value="mainImage || ''">
            @endif
            <label class="nvm-drop-zone">
                <img src="{{ asset('images/nova/deco/bag.webp') }}" alt="" width="72" height="72" loading="lazy">
                <b>{{ $isEdit ? 'เพิ่มรูปใหม่' : 'เลือกรูปสินค้า' }}</b>
                <small>แตะเพื่อถ่ายรูปหรือเลือกจากเครื่อง (เหลือเพิ่มได้ <span x-text="slotsLeft"></span> รูป)</small>
                <input type="file" name="images[]" accept="image/*" multiple class="nvm-sr" x-ref="images" x-on:change="previewNew($event)">
            </label>
            <div class="nvm-td-new" x-show="newPreviews.length > 0" x-cloak>
                <template x-for="(src, i) in newPreviews" :key="i">
                    <img :src="src" alt="">
                </template>
            </div>
            <span class="nvm-err" x-show="imageError" x-text="imageError" x-cloak></span>
            @error('images')<span class="nvm-err">{{ $message }}</span>@enderror
            @error('images.*')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
    </section>

    {{-- ════════ ตัวเลือกสินค้า ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="lf-opt-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Options · ไม่บังคับ</small><h2 id="lf-opt-h">ตัวเลือกสินค้า</h2></div>
            <span class="nvm-chip" x-text="groups.length + '/10 กลุ่ม'"></span>
        </div>
        <div class="nvm-form">
            <p class="nvm-desc">เช่น "เลือกเนื้อสัตว์" ให้ลูกค้าเลือก 1 อย่าง, "เพิ่มเติม" เลือกได้หลายอย่าง — ตั้งราคาเพิ่มต่อชิ้นได้ (ใส่ 0 = ไม่บวกเพิ่ม)</p>
            @if($isEdit)
                <input type="hidden" name="option_groups_present" value="1">
            @endif

            <div class="nvm-picks" aria-label="เพิ่มกลุ่มตัวเลือกด่วน">
                <button type="button" x-on:click="addTemplate('meat')" :disabled="groups.length >= 10">+ เลือกเนื้อสัตว์</button>
                <button type="button" x-on:click="addTemplate('spicy')" :disabled="groups.length >= 10">+ ความเผ็ด</button>
                <button type="button" x-on:click="addTemplate('extra')" :disabled="groups.length >= 10">+ ท็อปปิ้ง</button>
                <button type="button" x-on:click="addTemplate('size')" :disabled="groups.length >= 10">+ ขนาด</button>
                <button type="button" x-on:click="addGroup()" :disabled="groups.length >= 10">+ กลุ่มเปล่า</button>
            </div>
            @error('option_groups')<span class="nvm-err">{{ $message }}</span>@enderror

            <template x-for="(g, gi) in groups" :key="g.key">
                <div class="nvm-td-group">
                    <input type="hidden" :name="'option_groups[' + g.key + '][id]'" :value="g.id" :disabled="!g.id">
                    <div class="nvm-td-group__head">
                        <input type="text" class="nvm-input" :name="'option_groups[' + g.key + '][name]'" x-model="g.name" maxlength="100" required placeholder="ชื่อกลุ่ม เช่น เลือกเนื้อสัตว์" aria-label="ชื่อกลุ่มตัวเลือก">
                        <button type="button" class="nvm-td-ib" x-on:click="move(gi, -1)" :disabled="gi === 0" aria-label="เลื่อนกลุ่มขึ้น"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg></button>
                        <button type="button" class="nvm-td-ib" x-on:click="move(gi, 1)" :disabled="gi === groups.length - 1" aria-label="เลื่อนกลุ่มลง"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg></button>
                        <button type="button" class="nvm-td-ib is-bad" x-on:click="removeGroup(gi)" :aria-label="g.confirmDelete ? 'แตะอีกครั้งเพื่อยืนยันลบกลุ่ม' : 'ลบกลุ่ม'">
                            <svg class="nvm-ico"><use :href="g.confirmDelete ? '#nvm-i-check' : '#nvm-i-x'"/></svg>
                        </button>
                    </div>
                    <span class="nvm-err" x-show="g.confirmDelete">แตะปุ่มลบอีกครั้งเพื่อลบกลุ่มนี้</span>

                    <div class="nvm-td-group__rule">
                        <div class="nvm-seg" role="group" aria-label="จำนวนที่เลือกได้">
                            <button type="button" :class="g.selection_type === 'single' ? 'is-on' : ''" :aria-pressed="g.selection_type === 'single'" x-on:click="g.selection_type = 'single'">เลือกได้ 1 อย่าง</button>
                            <button type="button" :class="g.selection_type === 'multi' ? 'is-on' : ''" :aria-pressed="g.selection_type === 'multi'" x-on:click="g.selection_type = 'multi'">เลือกได้หลายอย่าง</button>
                        </div>
                        <input type="hidden" :name="'option_groups[' + g.key + '][selection_type]'" :value="g.selection_type">
                        <label class="nvm-td-switch">
                            <span class="nvm-sw"><input type="checkbox" x-model="g.is_required"><i></i></span>
                            บังคับเลือก
                        </label>
                        <input type="hidden" :name="'option_groups[' + g.key + '][is_required]'" :value="g.is_required ? 1 : 0">
                    </div>

                    <div class="nvm-td-group__rule" x-show="g.selection_type === 'multi'">
                        <label>ขั้นต่ำ
                            <input type="number" class="nvm-input" min="0" max="30" :name="'option_groups[' + g.key + '][min_select]'" x-model="g.min_select" :disabled="g.selection_type !== 'multi'" placeholder="0">
                        </label>
                        <label>สูงสุด
                            <input type="number" class="nvm-input" min="0" max="30" :name="'option_groups[' + g.key + '][max_select]'" x-model="g.max_select" :disabled="g.selection_type !== 'multi'" placeholder="ไม่จำกัด">
                        </label>
                    </div>
                    <div><span class="nvm-chip nvm-chip--info" x-text="'ลูกค้าเห็น: ' + ruleLabel(g)"></span></div>

                    <template x-for="(o, oi) in g.options" :key="o.key">
                        <div class="nvm-td-optrow" :class="o.is_available ? '' : 'is-off'">
                            <input type="hidden" :name="'option_groups[' + g.key + '][options][' + o.key + '][id]'" :value="o.id" :disabled="!o.id">
                            <label class="nvm-td-thumb nvm-td-thumb--sm" :title="o.previewUrl || o.image_url ? 'เปลี่ยนรูปตัวเลือก' : 'เพิ่มรูปตัวเลือก'">
                                <template x-if="o.previewUrl || o.image_url"><img :src="o.previewUrl || o.image_url" alt=""></template>
                                <template x-if="!o.previewUrl && !o.image_url"><svg class="nvm-ico"><use href="#nvm-i-camera"/></svg></template>
                                <input type="file" accept="image/*" class="nvm-sr" :name="'option_groups[' + g.key + '][options][' + o.key + '][image]'" x-on:change="previewOption(o, $event)" aria-label="รูปตัวเลือก">
                            </label>
                            <input type="text" class="nvm-input" :name="'option_groups[' + g.key + '][options][' + o.key + '][name]'" x-model="o.name" maxlength="100" required placeholder="ชื่อตัวเลือก" aria-label="ชื่อตัวเลือก">
                            <div class="nvm-td-optrow__r">
                                <div class="nvm-td-affix nvm-td-affix--pre">
                                    <span>+฿</span>
                                    <input type="number" class="nvm-input" min="0" max="100000" step="0.5" :name="'option_groups[' + g.key + '][options][' + o.key + '][price_delta]'" x-model="o.price_delta" aria-label="ราคาเพิ่ม">
                                </div>
                                <button type="button" class="nvm-td-avail" :class="o.is_available ? '' : 'is-off'" x-on:click="o.is_available = !o.is_available" :aria-label="o.is_available ? 'มีขาย (แตะเพื่อตั้งเป็นหมด)' : 'หมดชั่วคราว (แตะเพื่อเปิดขาย)'" x-text="o.is_available ? 'มีขาย' : 'หมด'"></button>
                                <input type="hidden" :name="'option_groups[' + g.key + '][options][' + o.key + '][is_available]'" :value="o.is_available ? 1 : 0">
                                <button type="button" class="nvm-td-ib is-bad" x-on:click="removeOption(g, oi)" :disabled="g.options.length <= 1" aria-label="ลบตัวเลือก"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></button>
                            </div>
                        </div>
                    </template>
                    <div><button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-on:click="addOption(g)" :disabled="g.options.length >= 30"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มตัวเลือก</button></div>
                </div>
            </template>

            <p class="nvm-help" x-show="groups.length === 0">ยังไม่มีตัวเลือก — สินค้าขายราคาเดียว</p>
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
