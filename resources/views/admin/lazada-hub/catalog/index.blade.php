@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'แคตตาล็อก Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — แคตตาล็อกสินค้า
     โชว์ ต้นทุน(Lazada) · markup% · ราคาเรา · กำไร · โหมด + แก้ราคา/โหมด inline + sync ราคา
     ════════════════════════════════════════════════════════════ --}}
@php
    $rowsForJs = $products->getCollection()->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'brand' => $p->brand,
        'image' => $p->main_image_url,
        'cost_price' => (float) $p->effective_cost,
        'selling_price' => (float) $p->effective_selling_price,
        'markup_percent' => (float) ($p->markup_percent ?? 0),
        'price_is_manual' => (bool) $p->price_is_manual,
        'fulfillment_mode' => $p->fulfillment_mode,
        'profit' => (float) $p->profit,
        'profit_percent' => (float) $p->profit_percent,
        'is_active' => (bool) $p->is_active,
        'commission_rate' => (float) ($p->commission_rate ?? 0),
        'source' => $p->source,
        'source_url' => $p->source_url,
    ])->values();
@endphp
<div x-data="lazadaCatalog(@js($rowsForJs))" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · แคตตาล็อก
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-layer-group" style="color:var(--accent1);"></i> แคตตาล็อกสินค้า
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">ต้นทุน · markup · ราคาเรา · กำไร — แก้ราคา/โหมดได้รายตัว</p>
        </div>
        <a href="{{ route('admin.lazada-hub.catalog.import') }}" class="tp-btn tp-btn-primary">
            <i class="fas fa-cloud-download-alt"></i> <span>นำเข้าสินค้า</span>
        </a>
    </div>

    {{-- ── สถิติ + ฟิลเตอร์ ── --}}
    <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;">
        <span style="font-size:.85rem;color:var(--ink2);">ทั้งหมด <b class="tp-num" style="color:var(--ink);">{{ number_format($stats['total']) }}</b></span>
        <span style="font-size:.85rem;color:var(--ink2);">affiliate <b class="tp-num" style="color:#b79ae8;">{{ number_format($stats['affiliate']) }}</b></span>
        <span style="font-size:.85rem;color:var(--ink2);">ขายเอง <b class="tp-num" style="color:#5aa07e;">{{ number_format($stats['resell']) }}</b></span>
        <form method="GET" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
            <select name="mode" class="tp-input" style="width:auto;padding:8px 12px;font-size:.82rem;" onchange="this.form.submit()">
                <option value="">ทุกโหมด</option>
                <option value="affiliate" @selected(($filters['mode'] ?? '')==='affiliate')>Affiliate</option>
                <option value="resell" @selected(($filters['mode'] ?? '')==='resell')>ขายเอง</option>
            </select>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ค้นหาชื่อ/แบรนด์" class="tp-input" style="width:180px;padding:8px 12px;font-size:.82rem;">
            <button class="tp-btn tp-btn-sm"><i class="fas fa-magnifying-glass"></i></button>
        </form>
    </div>

    {{-- ── ตาราง ── --}}
    @if($products->total() === 0)
        <div class="tp-card" style="text-align:center;padding:40px 20px;color:var(--ink2);">
            <i class="fas fa-box-open" style="font-size:2rem;opacity:.4;"></i>
            <p style="margin:12px 0 0;">ยังไม่มีสินค้าในแคตตาล็อก — กด "นำเข้าสินค้า" เพื่อเริ่ม</p>
        </div>
    @else
        <div class="tp-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.84rem;min-width:880px;">
                    <thead>
                        <tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                            <th style="padding:11px 12px;font-weight:600;">สินค้า</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:right;">ต้นทุน</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:center;">markup</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:right;">ราคาเรา</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:right;">กำไร</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:center;">โหมด</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:center;">สถานะ</th>
                            <th style="padding:11px 12px;font-weight:600;text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in rows" :key="r.id">
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);">
                                {{-- สินค้า --}}
                                <td style="padding:10px 12px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="tp-inset" style="width:42px;height:42px;border-radius:9px;overflow:hidden;flex-shrink:0;background:var(--surf);">
                                            <img :src="r.image || '/images/no-image.png'" :alt="r.name" loading="lazy" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.opacity=0">
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-weight:600;color:var(--ink);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="r.name"></div>
                                            <a :href="r.source_url" target="_blank" rel="noopener" class="tp-muted" style="font-size:.7rem;text-decoration:none;" x-show="r.source_url"><i class="fas fa-external-link-alt"></i> Lazada</a>
                                        </div>
                                    </div>
                                </td>
                                {{-- ต้นทุน --}}
                                <td class="tp-num" style="padding:10px 12px;text-align:right;color:var(--ink2);" x-text="'฿' + fmt(r.cost_price)"></td>
                                {{-- markup --}}
                                <td class="tp-num" style="padding:10px 12px;text-align:center;color:var(--ink2);">
                                    <span x-show="!r.price_is_manual" x-text="r.markup_percent + '%'"></span>
                                    <span x-show="r.price_is_manual" class="tp-pill" style="background:#e0a52e;color:#fff;font-size:9px;">ตั้งเอง</span>
                                </td>
                                {{-- ราคาเรา --}}
                                <td class="tp-num" style="padding:10px 12px;text-align:right;font-weight:700;color:var(--accent1);" x-text="'฿' + fmt(r.selling_price)"></td>
                                {{-- กำไร --}}
                                <td class="tp-num" style="padding:10px 12px;text-align:right;">
                                    <div :style="r.fulfillment_mode==='resell' ? 'color:#5aa07e;font-weight:700;' : 'color:var(--ink2);'">
                                        <span x-show="r.fulfillment_mode==='resell'" x-text="'฿' + fmt(r.profit)"></span>
                                        <span x-show="r.fulfillment_mode==='affiliate'" x-text="'คอม ' + r.commission_rate + '%'"></span>
                                    </div>
                                    <div class="tp-muted" style="font-size:.66rem;" x-show="r.fulfillment_mode==='resell'" x-text="r.profit_percent + '%'"></div>
                                </td>
                                {{-- โหมด --}}
                                <td style="padding:10px 12px;text-align:center;">
                                    <span class="tp-pill" :style="r.fulfillment_mode==='affiliate' ? 'background:#b79ae8;color:#fff;' : 'background:#5aa07e;color:#fff;'" style="font-size:10px;font-weight:700;" x-text="r.fulfillment_mode==='affiliate' ? 'Affiliate' : 'ขายเอง'"></span>
                                </td>
                                {{-- สถานะ --}}
                                <td style="padding:10px 12px;text-align:center;">
                                    <span class="tp-pill" :style="r.is_active ? 'background:#5aa07e;color:#fff;' : 'background:var(--ink2);color:#fff;'" style="font-size:10px;" x-text="r.is_active ? 'เปิด' : 'ปิด'"></span>
                                </td>
                                {{-- จัดการ --}}
                                <td style="padding:10px 12px;text-align:right;white-space:nowrap;">
                                    <button class="tp-btn tp-btn-sm" @click="openEdit(r)" title="แก้ราคา/โหมด"><i class="fas fa-pen"></i></button>
                                    <button class="tp-btn tp-btn-sm" @click="syncPrice(r)" :disabled="busy===r.id" title="ดึงราคาล่าสุด"><i class="fas" :class="busy===r.id ? 'fa-spinner fa-spin' : 'fa-rotate'"></i></button>
                                    <button class="tp-btn tp-btn-sm" @click="remove(r)" style="color:#d9534f;" title="ลบ"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $products->links() }}</div>
    @endif

    {{-- ── Modal แก้ไข ── --}}
    <div x-show="editing" x-cloak @keydown.escape.window="editing=null"
         style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px;">
        <div class="tp-card" @click.outside="editing=null" style="max-width:420px;width:100%;display:flex;flex-direction:column;gap:14px;" x-show="editing" x-transition>
            <div style="font-weight:800;color:var(--ink);font-size:1.05rem;" x-text="form.name"></div>

            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.82rem;margin-bottom:6px;">โหมด</label>
                <div style="display:flex;gap:8px;">
                    <label class="tp-inset" :style="form.fulfillment_mode==='affiliate' ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : ''" style="flex:1;padding:9px;border-radius:10px;cursor:pointer;text-align:center;font-size:.82rem;color:var(--ink);">
                        <input type="radio" value="affiliate" x-model="form.fulfillment_mode" style="display:none;"> 🟣 Affiliate
                    </label>
                    <label class="tp-inset" :style="form.fulfillment_mode==='resell' ? 'box-shadow:inset 0 0 0 2px var(--accent1);' : ''" style="flex:1;padding:9px;border-radius:10px;cursor:pointer;text-align:center;font-size:.82rem;color:var(--ink);">
                        <input type="radio" value="resell" x-model="form.fulfillment_mode" style="display:none;"> 🟢 ขายเอง
                    </label>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;font-size:.74rem;color:var(--ink2);">
                <span>ต้นทุน <b class="tp-num" style="color:var(--ink);" x-text="'฿'+fmt(form.cost_price)"></b></span>
            </div>

            <div>
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.82rem;margin-bottom:6px;">markup (%)</label>
                <input type="number" step="0.01" min="0" max="1000" x-model.number="form.markup_percent" :disabled="form.price_is_manual" class="tp-input tp-num">
            </div>

            <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--ink);cursor:pointer;">
                <input type="checkbox" x-model="form.price_is_manual" style="width:17px;height:17px;accent-color:var(--accent1);">
                ตั้งราคาเอง (ไม่ให้ sync ทับ)
            </label>

            <div x-show="form.price_is_manual">
                <label style="display:block;font-weight:600;color:var(--ink);font-size:.82rem;margin-bottom:6px;">ราคาเรา (฿)</label>
                <input type="number" step="0.01" min="0" x-model.number="form.selling_price" class="tp-input tp-num">
            </div>

            <p class="tp-muted" style="font-size:.74rem;margin:0;" x-show="!form.price_is_manual">
                ราคาเรา ≈ <b class="tp-num" x-text="'฿'+fmt(preview())"></b> (ต้นทุน + markup, ปัดเศษตามตั้งค่า)
            </p>

            <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--ink);cursor:pointer;">
                <input type="checkbox" x-model="form.is_active" style="width:17px;height:17px;accent-color:var(--accent1);"> เปิดใช้งาน
            </label>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button class="tp-btn" @click="editing=null">ยกเลิก</button>
                <button class="tp-btn tp-btn-primary" @click="save()" :disabled="saving">
                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> <span>บันทึก</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function lazadaCatalog(initialRows) {
    return {
        rows: initialRows,
        editing: null,
        form: {},
        saving: false,
        busy: null,
        rounding: @js($defaults['rounding']),
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        base: '{{ url('admin/lazada-hub/catalog') }}',

        fmt(n) { return Number(n || 0).toLocaleString('th-TH', { maximumFractionDigits: 2 }); },

        round(p) {
            if (p <= 0) return 0;
            if (this.rounding === 'baht') return Math.ceil(p);
            if (this.rounding === 'ten') return Math.ceil(p / 10) * 10;
            if (this.rounding === 'nine') return Math.ceil(p / 10) * 10 - 1;
            return Math.round(p * 100) / 100;
        },
        preview() {
            return this.round(Number(this.form.cost_price || 0) * (1 + Number(this.form.markup_percent || 0) / 100));
        },

        openEdit(r) {
            this.form = { ...r };
            this.editing = true;
        },

        async save() {
            this.saving = true;
            try {
                const res = await fetch(`${this.base}/${this.form.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({
                        fulfillment_mode: this.form.fulfillment_mode,
                        markup_percent: this.form.markup_percent,
                        selling_price: this.form.selling_price,
                        price_is_manual: this.form.price_is_manual,
                        is_active: this.form.is_active,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.applyRow(data.product);
                    this.editing = null;
                    this.notify(data.message || 'บันทึกแล้ว', 'success');
                } else {
                    this.notify(data.message || 'บันทึกไม่สำเร็จ', 'error');
                }
            } catch (e) { this.notify('ผิดพลาด: ' + e.message, 'error'); }
            this.saving = false;
        },

        async syncPrice(r) {
            this.busy = r.id;
            try {
                const res = await fetch(`${this.base}/${r.id}/sync-price`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                });
                const data = await res.json();
                if (data.success) { this.applyRow(data.product); this.notify(data.message, 'success'); }
                else this.notify(data.message || 'sync ไม่สำเร็จ', 'error');
            } catch (e) { this.notify('ผิดพลาด: ' + e.message, 'error'); }
            this.busy = null;
        },

        async remove(r) {
            if (!confirm('ลบ "' + r.name + '" ออกจากแคตตาล็อก?')) return;
            try {
                const res = await fetch(`${this.base}/${r.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                });
                const data = await res.json();
                if (data.success) { this.rows = this.rows.filter(x => x.id !== r.id); this.notify('ลบแล้ว', 'success'); }
            } catch (e) { this.notify('ผิดพลาด: ' + e.message, 'error'); }
        },

        applyRow(payload) {
            const i = this.rows.findIndex(x => x.id === payload.id);
            if (i !== -1) this.rows[i] = { ...this.rows[i], ...payload };
        },

        notify(message, type) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
        }
    };
}
</script>
@endpush
