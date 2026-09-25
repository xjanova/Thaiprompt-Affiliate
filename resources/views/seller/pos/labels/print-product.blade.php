@extends('layouts.seller-v4')

@section('title', 'พิมพ์ฉลากสินค้า')

@php
    $labelConfig = [
        'productsUrl' => route('seller.pos.labels.api.products'),
        'printUrl' => route('seller.pos.labels.api.print'),
        'previewUrl' => route('seller.pos.labels.preview'),
        'defaultTemplate' => $templates->count() === 1 ? (string) $templates->first()->id : '',
    ];
@endphp

@section('content')
<div x-data="productLabelPrinter(@js($labelConfig))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="พิมพ์ฉลากสินค้า" icon="🏷️" crumb="ร้านค้า · POS · ฉลากบาร์โค้ด"
                         subtitle="เลือกสินค้า กำหนดจำนวนดวง แล้วกดพิมพ์ — ระบบจะเปิดหน้าตัวอย่างสำหรับสั่งพิมพ์">
        <a href="{{ route('seller.pos.labels.history') }}" class="tp-btn tp-btn-sm">📜 ประวัติการพิมพ์</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        {{-- รายการสินค้าของร้าน --}}
        <div class="tp-card" style="padding:0; overflow:hidden; flex:2 1 440px; min-width:0;">
            <div style="display:flex; flex-wrap:wrap; gap:10px; padding:14px 16px;">
                <input type="search" x-model="search" @input.debounce.400ms="fetchProducts(1)" class="tp-input" style="flex:1 1 220px;" placeholder="ค้นหาชื่อ บาร์โค้ด หรือ SKU" aria-label="ค้นหาสินค้า">
                <select x-model.number="perPage" @change="fetchProducts(1)" class="tp-input" style="width:auto;" aria-label="จำนวนต่อหน้า">
                    <option value="20">20 รายการ</option>
                    <option value="50">50 รายการ</option>
                    <option value="100">100 รายการ</option>
                </select>
            </div>

            <div style="max-height:60vh; overflow-y:auto;">
                <template x-if="loading">
                    <div style="padding:40px; text-align:center; color:var(--ink2); font-size:13px;"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดสินค้า…</div>
                </template>
                <template x-if="!loading && products.length === 0">
                    <div style="padding:40px; text-align:center; color:var(--ink2); font-size:13px;">📭 ไม่พบสินค้า</div>
                </template>
                <template x-for="p in products" :key="p.id">
                    <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <div class="tp-inset-sm" style="width:52px; height:52px; border-radius:12px; overflow:hidden; display:grid; place-items:center; flex:none;">
                            <template x-if="p.image"><img :src="p.image" :alt="p.name" loading="lazy" style="width:100%; height:100%; object-fit:cover;"></template>
                            <template x-if="!p.image"><span aria-hidden="true">📦</span></template>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:13.5px;" x-text="p.name"></div>
                            <div class="tp-num" style="font-size:11px; color:var(--ink2);" x-text="'บาร์โค้ด ' + (p.barcode || '—') + ' · SKU ' + (p.sku || '—')"></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="tp-num" style="font-weight:800; color:var(--deep1);" x-text="money(p.price)"></div>
                            <button type="button" class="tp-btn tp-btn-sm" style="margin-top:4px;" @click="add(p)">＋ เพิ่ม</button>
                        </div>
                    </div>
                </template>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); font-size:12.5px; color:var(--ink2);">
                <span x-text="'ทั้งหมด ' + meta.total + ' รายการ'"></span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" @click="fetchProducts(meta.current_page - 1)" :disabled="meta.current_page <= 1" :style="{ opacity: meta.current_page <= 1 ? .4 : 1 }" aria-label="หน้าก่อน">‹</button>
                    <span class="tp-num" x-text="meta.current_page + ' / ' + meta.last_page"></span>
                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" @click="fetchProducts(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" :style="{ opacity: meta.current_page >= meta.last_page ? .4 : 1 }" aria-label="หน้าถัดไป">›</button>
                </div>
            </div>
        </div>

        {{-- สินค้าที่เลือก --}}
        <div class="tp-card" style="flex:1 1 300px; min-width:0; position:sticky; top:12px; display:flex; flex-direction:column; gap:12px;">
            <div class="tp-section-h">🧾 สินค้าที่เลือก <span class="tp-pill tp-pill-soft" x-text="selected.length + ' รายการ'"></span></div>

            <div style="display:flex; flex-direction:column; gap:8px; max-height:36vh; overflow-y:auto; padding:2px;">
                <template x-if="selected.length === 0">
                    <div style="text-align:center; padding:20px 0; color:var(--ink2); font-size:13px;">ยังไม่ได้เลือกสินค้า</div>
                </template>
                <template x-for="(item, i) in selected" :key="item.product_id">
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <span style="font-size:12.5px; font-weight:700;" x-text="item.name"></span>
                            <button type="button" @click="selected.splice(i, 1)" aria-label="ลบรายการ" style="border:0; background:none; cursor:pointer; color:var(--tp-bad, #d9534f);">✕</button>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:6px; font-size:12px; color:var(--ink2);">
                            จำนวนดวง
                            <input type="number" min="1" max="500" x-model.number="item.quantity" @change="item.quantity = Math.max(1, Math.min(500, Math.floor(item.quantity || 1)))" class="tp-input tp-num" style="width:80px; padding:6px 8px; text-align:center;">
                        </div>
                    </div>
                </template>
            </div>

            <div>
                <label for="lbl-template" style="font-size:12.5px; font-weight:700;">Template</label>
                <select id="lbl-template" x-model="template" class="tp-input" style="margin-top:6px;">
                    <option value="">— เลือก Template —</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }} ({{ rtrim(rtrim(number_format((float) $template->paper_width, 1), '0'), '.') }}×{{ rtrim(rtrim(number_format((float) $template->paper_height, 1), '0'), '.') }} มม.)</option>
                    @endforeach
                </select>
                @if($templates->isEmpty())
                    <div style="font-size:11.5px; color:var(--tp-warn, #c98a1b); margin-top:6px;">ยังไม่มี Template ฉลากสินค้าในระบบ กรุณาติดต่อผู้ดูแลระบบ</div>
                @endif
            </div>

            <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px; display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:var(--ink2);">ฉลากทั้งหมด</span>
                <span class="tp-num" style="font-weight:800;" x-text="totalLabels + ' ดวง'"></span>
            </div>

            <button type="button" class="tp-btn tp-btn-primary" style="height:48px;" @click="print()"
                    :disabled="busy || selected.length === 0 || !template" :style="{ opacity: (busy || selected.length === 0 || !template) ? .5 : 1 }">
                <span x-text="busy ? 'กำลังเตรียม…' : '🖨️ พิมพ์ฉลาก'"></span>
            </button>
            <button type="button" class="tp-btn" @click="if (selected.length && confirm('ล้างรายการที่เลือกทั้งหมด?')) selected = []">ล้างรายการ</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // หน้าเลือกสินค้าพิมพ์ฉลาก — ดึงเฉพาะสินค้าของร้านผ่าน API ของผู้ขาย
    function productLabelPrinter(cfg) {
        const fmt = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return {
            products: [],
            selected: [],
            search: '',
            perPage: 20,
            loading: false,
            busy: false,
            template: cfg.defaultTemplate,
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
            reqId: 0,

            get totalLabels() { return this.selected.reduce((s, i) => s + (Number(i.quantity) || 0), 0); },
            money(n) { return '฿' + fmt.format(Number(n) || 0); },

            init() { this.fetchProducts(1); },

            async fetchProducts(page) {
                if (page < 1 || (page > this.meta.last_page && this.meta.last_page > 0 && page !== 1)) return;
                const my = ++this.reqId; // กันผลลัพธ์เก่ามาทับผลลัพธ์ใหม่ตอนพิมพ์ค้นหาเร็ว ๆ
                this.loading = true;
                try {
                    const params = new URLSearchParams({ page: page, per_page: this.perPage, search: this.search });
                    const res = await fetch(cfg.productsUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (my !== this.reqId) return;
                    if (data.success) {
                        this.products = data.data;
                        this.meta = data.meta || this.meta;
                    } else {
                        this.products = [];
                        window.showNotification(data.message || 'โหลดสินค้าไม่สำเร็จ', 'error');
                    }
                } catch (e) {
                    if (my === this.reqId) window.showNotification('โหลดสินค้าไม่สำเร็จ กรุณาลองใหม่', 'error');
                } finally {
                    if (my === this.reqId) this.loading = false;
                }
            },

            add(p) {
                const row = this.selected.find((s) => s.product_id === p.id);
                if (row) { row.quantity = Math.min(500, row.quantity + 1); return; }
                this.selected.push({ product_id: p.id, name: p.name, quantity: 1 });
            },

            async print() {
                if (this.busy || !this.selected.length || !this.template) return;
                this.busy = true;
                try {
                    // 1) บันทึกประวัติการพิมพ์
                    const res = await fetch(cfg.printUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            template_id: this.template,
                            print_type: 'product_label',
                            products: this.selected.map((s) => ({ product_id: s.product_id, quantity: s.quantity })),
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        window.showNotification(data.message || 'บันทึกการพิมพ์ไม่สำเร็จ', 'error');
                        return;
                    }
                    // 2) เปิดหน้าตัวอย่างสำหรับสั่งพิมพ์ในแท็บใหม่
                    const url = new URL(cfg.previewUrl, window.location.origin);
                    url.searchParams.set('template_id', this.template);
                    this.selected.forEach((s, i) => {
                        url.searchParams.set('products[' + i + '][product_id]', s.product_id);
                        url.searchParams.set('products[' + i + '][quantity]', s.quantity);
                    });
                    window.open(url.toString(), '_blank', 'noopener');
                    window.showNotification('เปิดหน้าตัวอย่างฉลากแล้ว', 'success');
                    this.selected = [];
                } catch (e) {
                    window.showNotification('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่', 'error');
                } finally {
                    this.busy = false;
                }
            },
        };
    }
</script>
@endpush
