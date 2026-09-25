@extends('layouts.seller-v4')

@section('title', 'ขายหน้าร้าน (POS)')

@php
    // ── เตรียมข้อมูลสินค้าให้ JS แบบเบา ๆ (ส่งเฉพาะฟิลด์ที่หน้าขายใช้จริง) ──
    $posProducts = $products->map(fn ($p) => [
        'id' => (int) $p->id,
        'name' => (string) $p->name,
        'sku' => (string) ($p->sku ?? ''),
        'barcode' => (string) ($p->barcode ?? ''),
        'price' => round((float) $p->price, 2),
        'stock' => (float) ($p->stock_quantity ?? 0),
        'track' => (bool) $p->track_inventory,
        'category_id' => $p->category_id ? (int) $p->category_id : null,
        'image' => $p->main_image_url ?? null,
    ])->values();

    // วิธีชำระที่เปิดใช้ในตั้งค่า POS (กรองให้เหลือเฉพาะค่าที่ API รับ)
    $payOptions = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR พร้อมเพย์', 'bank_transfer' => '🏦 โอนเงิน', 'other' => '• อื่น ๆ'];
    $enabledPay = array_values(array_intersect(array_keys($payOptions), (array) ($settings->enabled_payment_methods ?: ['cash', 'card', 'qr'])));
    if (empty($enabledPay)) {
        $enabledPay = ['cash'];
    }

    $posConfig = [
        'deviceId' => (int) $device->id,
        'sessionId' => (int) $session->id,
        'taxEnabled' => (bool) $settings->tax_enabled,
        'taxRate' => (float) ($settings->tax_percentage ?? 0),
        'taxInclusive' => (bool) $settings->tax_inclusive,
        'allowDiscount' => $settings->allow_discounts !== false,
        'maxDiscountPct' => (float) ($settings->max_discount_percentage ?? 100),
        'preventNegative' => (bool) $settings->prevent_negative_stock,
        'showImages' => $settings->show_product_images !== false,
        'showStock' => $settings->show_stock_levels !== false,
        'autoPrint' => (bool) $settings->auto_print_receipt,
        'payMethods' => $enabledPay,
        'payLabels' => $payOptions,
        'endpoint' => route('seller.pos.create-transaction'),
    ];
@endphp

@section('content')
<div x-data="posTerminal(@js($posProducts), @js($posConfig))" style="display:flex; flex-direction:column; gap:16px;">

    <x-seller-kit.header title="ขายหน้าร้าน" icon="🛒" crumb="ร้านค้า · POS"
                         :subtitle="($store->store_name ?? 'ร้านของฉัน').' · '.$device->device_name">
        <span class="tp-pill tp-pill-soft">🔐 เซสชัน #{{ $session->id }}</span>
        <a href="{{ route('seller.pos.transactions') }}" class="tp-btn tp-btn-sm">🧾 รายการขาย</a>
        <a href="{{ route('seller.pos.index') }}" class="tp-btn tp-btn-sm">← ภาพรวม POS</a>
    </x-seller-kit.header>

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">

        {{-- ─────────── ฝั่งสินค้า ─────────── --}}
        <div class="tp-card" style="padding:16px; min-width:0; flex:2 1 460px;">
            <div style="position:relative;">
                <span aria-hidden="true" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ink2);"><i class="fas fa-magnifying-glass"></i></span>
                <input type="search" x-ref="search" x-model="search" @keydown.enter.prevent="scanEnter()"
                       class="tp-input" style="padding-left:40px;" autocomplete="off"
                       placeholder="ค้นหาชื่อ / SKU หรือสแกนบาร์โค้ดแล้วกด Enter" aria-label="ค้นหาสินค้า">
            </div>

            {{-- หมวดหมู่ --}}
            <div style="display:flex; gap:6px; overflow-x:auto; padding:12px 2px 4px; scrollbar-width:none;">
                <button type="button" class="tp-btn tp-btn-sm" @click="category = null"
                        :class="category === null ? 'tp-btn-primary' : ''">ทั้งหมด</button>
                @foreach($categories as $category)
                    <button type="button" class="tp-btn tp-btn-sm" style="white-space:nowrap;"
                            @click="category = {{ (int) $category->id }}"
                            :class="category === {{ (int) $category->id }} ? 'tp-btn-primary' : ''">{{ $category->name }}</button>
                @endforeach
            </div>

            {{-- กริดสินค้า --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(138px,1fr)); gap:12px; margin-top:10px; max-height:68vh; overflow-y:auto; padding:4px;">
                <template x-for="p in filtered" :key="p.id">
                    <button type="button" @click="add(p)" class="tp-card tp-card-hover"
                            :disabled="isSoldOut(p)"
                            :style="'padding:10px; text-align:left; cursor:pointer; border:0; box-shadow:var(--card-shadow-sm); font-family:inherit; color:var(--ink);' + (isSoldOut(p) ? ' opacity:.45; cursor:not-allowed;' : '')">
                        <template x-if="cfg.showImages">
                            <div class="tp-inset-sm" style="aspect-ratio:1/1; border-radius:14px; overflow:hidden; display:grid; place-items:center; margin-bottom:8px; font-size:26px;">
                                <template x-if="p.image"><img :src="p.image" :alt="p.name" loading="lazy" style="width:100%; height:100%; object-fit:cover;"></template>
                                <template x-if="!p.image"><span aria-hidden="true">📦</span></template>
                            </div>
                        </template>
                        <div style="font-size:12.5px; font-weight:700; line-height:1.35; min-height:34px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;" x-text="p.name"></div>
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; margin-top:6px;">
                            <span class="tp-num" style="font-weight:800; color:var(--deep1); font-size:13.5px;" x-text="money(p.price)"></span>
                            <span x-show="cfg.showStock && p.track" style="font-size:10.5px; color:var(--ink2);" x-text="'เหลือ ' + p.stock"></span>
                        </div>
                    </button>
                </template>
            </div>
            <template x-if="filtered.length === 0">
                <x-seller-kit.empty icon="🔍" title="ไม่พบสินค้า" text="ลองค้นหาด้วยคำอื่น หรือเพิ่มสินค้าที่เมนูสินค้า" />
            </template>
        </div>

        {{-- ─────────── ตะกร้า + สรุปยอด ─────────── --}}
        <div class="tp-card" style="padding:16px; position:sticky; top:12px; min-width:0; flex:1 1 320px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div class="tp-section-h">🧺 รายการสินค้า <span class="tp-pill tp-pill-soft" x-text="itemCount + ' ชิ้น'"></span></div>
                <button type="button" x-show="cart.length" @click="clearCart()" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">ล้างทั้งหมด</button>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-top:12px; max-height:42vh; overflow-y:auto; padding:2px;">
                <template x-if="cart.length === 0">
                    <div style="text-align:center; padding:26px 8px; color:var(--ink2); font-size:13px;">
                        <div style="font-size:34px;" aria-hidden="true">🛒</div>
                        แตะสินค้าเพื่อเพิ่มลงตะกร้า
                    </div>
                </template>
                <template x-for="(item, i) in cart" :key="item.id">
                    <div class="tp-inset-sm" style="border-radius:14px; padding:10px 12px;">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <div style="min-width:0;">
                                <div style="font-size:12.5px; font-weight:700;" x-text="item.name"></div>
                                <div class="tp-num" style="font-size:11px; color:var(--ink2);" x-text="money(item.price) + ' × ' + item.quantity"></div>
                            </div>
                            <button type="button" @click="remove(i)" aria-label="ลบรายการ" style="border:0; background:none; cursor:pointer; color:var(--tp-bad, #d9534f); font-size:15px;">✕</button>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px; margin-top:8px;">
                            <button type="button" class="tp-icon-btn" style="width:32px; height:32px; border-radius:10px;" @click="step(i, -1)" aria-label="ลดจำนวน">−</button>
                            <input type="number" min="1" step="1" x-model.number="item.quantity" @change="normalize(i)" class="tp-input tp-num" style="width:64px; text-align:center; padding:6px 8px;" aria-label="จำนวน">
                            <button type="button" class="tp-icon-btn" style="width:32px; height:32px; border-radius:10px;" @click="step(i, 1)" aria-label="เพิ่มจำนวน">+</button>
                            <span class="tp-num" style="margin-left:auto; font-weight:800;" x-text="money(item.price * item.quantity)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <hr class="tp-divider" style="margin:14px 0;">

            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ยอดรวม</span><span class="tp-num" x-text="money(subtotal)"></span></div>
                <template x-if="cfg.allowDiscount">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <label for="pos-discount" style="color:var(--ink2);">ส่วนลด (บาท)</label>
                        <input id="pos-discount" type="number" min="0" step="0.01" x-model.number="discount" @change="clampDiscount()" class="tp-input tp-num" style="width:120px; text-align:right; padding:7px 10px;">
                    </div>
                </template>
                <template x-if="cfg.taxEnabled && cfg.taxRate > 0">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);" x-text="'VAT ' + cfg.taxRate + '%' + (cfg.taxInclusive ? ' (รวมในราคาแล้ว)' : '')"></span>
                        <span class="tp-num" x-text="money(tax)"></span>
                    </div>
                </template>
                <div style="display:flex; justify-content:space-between; align-items:baseline; padding-top:8px; border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent);">
                    <span style="font-weight:800;">รวมทั้งสิ้น</span>
                    <span class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1);" x-text="money(total)"></span>
                </div>
            </div>

            <button type="button" class="tp-btn tp-btn-primary" style="width:100%; height:52px; font-size:15px; margin-top:14px;"
                    :disabled="cart.length === 0" :style="{ opacity: cart.length === 0 ? .5 : 1, cursor: cart.length === 0 ? 'not-allowed' : 'pointer' }"
                    @click="openPay()">💳 ชำระเงิน</button>
        </div>
    </div>

    {{-- ─────────── หน้าต่างชำระเงิน (อยู่ใน x-data เดียวกับหน้าขาย) ─────────── --}}
    {{-- ตัวนอกมีแค่ x-show (ไม่มี display inline) เพื่อให้ Alpine คืนค่า display ได้ถูก — ตัวในเป็น flex จัดกลางจอ --}}
    <div x-show="payOpen" x-cloak x-transition.opacity @keydown.escape.window="closePay()">
      <div style="position:fixed; inset:0; z-index:90; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding:16px;"
           @click.self="closePay()" role="dialog" aria-modal="true" aria-labelledby="pos-pay-title">
        <div class="tp-card" style="width:100%; max-width:440px; padding:22px; max-height:92vh; overflow-y:auto; background:var(--surf);">
            <div id="pos-pay-title" style="font-size:19px; font-weight:800;">💳 ชำระเงิน</div>
            <div style="margin-top:12px; font-size:12px; color:var(--ink2);">ยอดที่ต้องชำระ</div>
            <div class="tp-num" style="font-size:32px; font-weight:800; color:var(--deep1);" x-text="money(total)"></div>

            <div style="margin-top:14px; font-size:12.5px; font-weight:700;">วิธีชำระเงิน</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:8px; margin-top:8px;">
                <template x-for="m in cfg.payMethods" :key="m">
                    <button type="button" class="tp-btn tp-btn-sm" @click="setMethod(m)" :class="method === m ? 'tp-btn-primary' : ''" x-text="cfg.payLabels[m]"></button>
                </template>
            </div>

            <label for="pos-paid" style="display:block; margin-top:14px; font-size:12.5px; font-weight:700;">จำนวนเงินที่รับ</label>
            <input id="pos-paid" type="number" min="0" step="0.01" x-model.number="paid" class="tp-input tp-num" style="margin-top:6px; font-size:18px;">
            <template x-if="method === 'cash'">
                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                    <template x-for="n in quickCash" :key="n">
                        <button type="button" class="tp-btn tp-btn-sm tp-num" @click="paid = n" x-text="money(n)"></button>
                    </template>
                </div>
            </template>
            <template x-if="change > 0">
                <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:baseline;">
                    <span style="font-size:12.5px; color:var(--ink2);">เงินทอน</span>
                    <span class="tp-num" style="font-size:22px; font-weight:800; color:var(--tp-ok, #4f9a74);" x-text="money(change)"></span>
                </div>
            </template>
            <div x-show="paid < total" style="margin-top:8px; font-size:12px; color:var(--tp-bad, #d9534f);">จำนวนเงินที่รับยังไม่ครบยอด</div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:14px;">
                <div>
                    <label for="pos-cname" style="font-size:12px; color:var(--ink2);">ชื่อลูกค้า (ไม่บังคับ)</label>
                    <input id="pos-cname" type="text" maxlength="255" x-model="customerName" class="tp-input" style="margin-top:4px;">
                </div>
                <div>
                    <label for="pos-cphone" style="font-size:12px; color:var(--ink2);">เบอร์โทร (ไม่บังคับ)</label>
                    <input id="pos-cphone" type="tel" maxlength="20" x-model="customerPhone" class="tp-input" style="margin-top:4px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1.4fr; gap:10px; margin-top:18px;">
                <button type="button" class="tp-btn" @click="closePay()" :disabled="busy">ยกเลิก</button>
                <button type="button" class="tp-btn tp-btn-primary" @click="submit()"
                        :disabled="busy || paid < total" :style="{ opacity: (busy || paid < total) ? .55 : 1 }">
                    <span x-show="!busy">✓ ยืนยันการขาย</span>
                    <span x-show="busy">กำลังบันทึก…</span>
                </button>
            </div>
        </div>
      </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // หน้าขายหน้าร้าน POS — คำนวณยอดด้วยเลขทศนิยม 2 ตำแหน่งเสมอ (กันเศษทศนิยมเพี้ยนข้ามเครื่อง)
    function posTerminal(products, cfg) {
        const r2 = (n) => Math.round((Number(n) || 0) * 100) / 100;
        const fmt = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return {
            products: products,
            cfg: cfg,
            cart: [],
            search: '',
            category: null,
            discount: 0,
            payOpen: false,
            method: cfg.payMethods[0] || 'cash',
            paid: 0,
            customerName: '',
            customerPhone: '',
            busy: false,

            get filtered() {
                const q = this.search.trim().toLowerCase();
                return this.products.filter((p) => {
                    const okCat = this.category === null || p.category_id === this.category;
                    const okQ = !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || p.barcode.toLowerCase().includes(q);
                    return okCat && okQ;
                });
            },
            get itemCount() { return this.cart.reduce((s, i) => s + (Number(i.quantity) || 0), 0); },
            get subtotal() { return r2(this.cart.reduce((s, i) => s + r2(i.price * i.quantity), 0)); },
            get net() { return r2(Math.max(0, this.subtotal - r2(this.discount))); },
            get tax() {
                if (!this.cfg.taxEnabled || !this.cfg.taxRate) return 0;
                return this.cfg.taxInclusive
                    ? r2(this.net * this.cfg.taxRate / (100 + this.cfg.taxRate))
                    : r2(this.net * this.cfg.taxRate / 100);
            },
            get total() { return this.cfg.taxEnabled && !this.cfg.taxInclusive ? r2(this.net + this.tax) : this.net; },
            get change() { return r2(Math.max(0, r2(this.paid) - this.total)); },
            get quickCash() {
                const t = this.total;
                const set = new Set([t, Math.ceil(t / 20) * 20, Math.ceil(t / 100) * 100, Math.ceil(t / 500) * 500, Math.ceil(t / 1000) * 1000]);
                return [...set].filter((n) => n >= t && n > 0).slice(0, 5);
            },

            money(n) { return '฿' + fmt.format(r2(n)); },
            isSoldOut(p) { return this.cfg.preventNegative && p.track && p.stock <= 0; },
            maxQty(p) { return this.cfg.preventNegative && p.track ? Math.max(0, Math.floor(p.stock)) : Infinity; },

            add(p) {
                if (this.isSoldOut(p)) return;
                const row = this.cart.find((i) => i.id === p.id);
                if (row) {
                    if (row.quantity + 1 > this.maxQty(p)) { window.showNotification('สินค้า ' + p.name + ' เหลือไม่พอ', 'warning'); return; }
                    row.quantity++;
                } else {
                    this.cart.push({ id: p.id, name: p.name, price: r2(p.price), quantity: 1, product: p });
                }
            },
            scanEnter() {
                const q = this.search.trim().toLowerCase();
                if (!q) return;
                const exact = this.products.find((p) => p.barcode.toLowerCase() === q || p.sku.toLowerCase() === q);
                const pick = exact || (this.filtered.length === 1 ? this.filtered[0] : null);
                if (pick) { this.add(pick); this.search = ''; }
            },
            remove(i) { this.cart.splice(i, 1); this.clampDiscount(); },
            step(i, d) {
                const row = this.cart[i];
                const next = row.quantity + d;
                if (next < 1) return;
                if (next > this.maxQty(row.product)) { window.showNotification('สินค้าเหลือไม่พอ', 'warning'); return; }
                row.quantity = next;
            },
            normalize(i) {
                const row = this.cart[i];
                let q = Math.floor(Number(row.quantity) || 1);
                q = Math.max(1, Math.min(q, this.maxQty(row.product)));
                row.quantity = q;
            },
            clampDiscount() {
                const max = r2(this.subtotal * this.cfg.maxDiscountPct / 100);
                this.discount = Math.min(Math.max(0, r2(this.discount)), max);
            },
            clearCart() {
                if (confirm('ต้องการล้างรายการทั้งหมดใช่หรือไม่?')) { this.cart = []; this.discount = 0; }
            },
            setMethod(m) { this.method = m; if (m !== 'cash') this.paid = this.total; },
            openPay() {
                if (!this.cart.length) return;
                this.clampDiscount();
                this.paid = this.total;
                this.payOpen = true;
            },
            closePay() { if (!this.busy) this.payOpen = false; },

            async submit() {
                // กันกดซ้ำ (double-tap) ระหว่างรอเซิร์ฟเวอร์
                if (this.busy || this.paid < this.total || !this.cart.length) return;
                this.busy = true;
                const payload = {
                    device_id: this.cfg.deviceId,
                    session_id: this.cfg.sessionId,
                    items: this.cart.map((i) => ({ product_id: i.id, quantity: i.quantity, unit_price: i.price, discount: 0 })),
                    subtotal: this.subtotal,
                    discount_amount: r2(this.discount),
                    tax_amount: this.tax,
                    total_amount: this.total,
                    payment_method: this.method,
                    payment_amount: r2(this.paid),
                    customer_name: this.customerName || null,
                    customer_phone: this.customerPhone || null,
                };
                try {
                    const res = await fetch(this.cfg.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        // ตัดสต็อกฝั่งหน้าจอให้ตรงกับเซิร์ฟเวอร์
                        this.cart.forEach((i) => { if (i.product.track) i.product.stock = r2(i.product.stock - i.quantity); });
                        this.cart = []; this.discount = 0; this.customerName = ''; this.customerPhone = '';
                        this.payOpen = false;
                        window.showNotification(data.message || 'บันทึกการขายสำเร็จ', 'success');
                        if (data.receipt_url) window.open(data.receipt_url, '_blank', 'noopener');
                    } else if (res.status === 422 && data.errors) {
                        window.showNotification(Object.values(data.errors)[0][0] || 'ข้อมูลไม่ถูกต้อง', 'error');
                    } else {
                        window.showNotification(data.message || 'บันทึกการขายไม่สำเร็จ กรุณาลองใหม่', 'error');
                    }
                } catch (e) {
                    window.showNotification('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่', 'error');
                } finally {
                    this.busy = false;
                    this.$nextTick(() => this.$refs.search && this.$refs.search.focus());
                }
            },
        };
    }
</script>
@endpush
