@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'นำเข้าสินค้าจาก Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: นำเข้าสินค้าจาก Lazada (ธีม V4 "นวลทองคำ")
     ── แอดมินวางลิงก์ "หน้าสินค้า" Lazada (เลือกเอง) → ดึงข้อมูล (preview)
     ── ติ๊กเลือก + เลือกหมวดหมู่ → นำเข้าเป็นสินค้าจริงในร้าน
     ── รูปภาพ = ลิงก์ตรงจาก Lazada CDN
     ════════════════════════════════════════════════════════════ --}}
<div x-data="lazadaImport()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · อีคอมเมิร์ซ · นำเข้าสินค้า
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-cloud-download-alt" style="color:var(--accent1);"></i>
                นำเข้าสินค้าจาก Lazada
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">คัดลอกลิงก์ “หน้าสินค้า” ที่ต้องการจาก Lazada มาวาง แล้วเลือกนำเข้าเองได้เลย</p>
        </div>
        <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn tp-btn-sm">
            <i class="fas fa-box" style="color:var(--accent2);"></i>
            <span>ไปที่รายการสินค้า</span>
        </a>
    </div>

    {{-- ── วิธีใช้ ── --}}
    <div class="tp-card" style="border-left:4px solid var(--accent1);">
        <div style="font-weight:700;color:var(--ink);margin-bottom:8px;"><i class="fas fa-circle-info" style="color:var(--accent1);"></i> วิธีใช้</div>
        <ol style="margin:0;padding-left:20px;color:var(--ink2);font-size:.86rem;line-height:1.7;">
            <li>เปิด Lazada เลือกสินค้าที่ต้องการ → คัดลอกลิงก์หน้าสินค้า (เช่น <span class="tp-num" style="color:var(--ink);">https://www.lazada.co.th/products/...-i123.html</span>)</li>
            <li>วางลิงก์ลงช่องด้านล่าง (วางได้หลายลิงก์ บรรทัดละ 1 ลิงก์ สูงสุด 30 ลิงก์/ครั้ง)</li>
            <li>กด “ดึงข้อมูล” เพื่อพรีวิว → ติ๊กเลือก + เลือกหมวดหมู่ → กด “นำเข้าสินค้าที่เลือก”</li>
        </ol>
        <p class="tp-muted" style="margin:8px 0 0;font-size:.76rem;">* รูปภาพใช้ลิงก์ตรงจาก Lazada CDN &nbsp;|&nbsp; รองรับเฉพาะลิงก์ “หน้าสินค้า” ไม่รองรับลิงก์หน้าค้นหา/หมวดหมู่</p>
    </div>

    {{-- ── ฟอร์มวางลิงก์ ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:14px;">
        <div>
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">ลิงก์สินค้า Lazada (บรรทัดละ 1 ลิงก์)</label>
            <textarea x-model="urls" rows="5" spellcheck="false"
                class="tp-input tp-num"
                style="min-height:120px;resize:vertical;font-size:12.5px;line-height:1.6;"
                placeholder="https://www.lazada.co.th/products/...-i1234567890.html&#10;https://www.lazada.co.th/products/...-i0987654321.html"></textarea>
        </div>

        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px;">
            <div>
                <label class="tp-muted" style="display:block;font-size:.74rem;font-weight:600;margin-bottom:5px;">ราคาขั้นต่ำ (฿)</label>
                <input type="number" x-model.number="priceMin" min="0" class="tp-input tp-num" style="width:130px;">
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:.74rem;font-weight:600;margin-bottom:5px;">ราคาสูงสุด (฿)</label>
                <input type="number" x-model.number="priceMax" min="0" class="tp-input tp-num" style="width:130px;">
            </div>
            <button @click="preview()" :disabled="loading" type="button" class="tp-btn tp-btn-primary" style="height:44px;">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                <span x-text="loading ? 'กำลังดึงข้อมูล...' : 'ดึงข้อมูล'"></span>
            </button>
        </div>

        <p x-show="error" x-cloak x-text="error" style="display:none;color:#d9534f;font-size:.85rem;margin:0;"></p>
    </div>

    {{-- ── รายการที่ดึงไม่สำเร็จ ── --}}
    <div x-show="failed.length > 0" x-cloak class="tp-card" style="display:none;border-left:4px solid #d9534f;">
        <div style="font-weight:700;color:#d9534f;margin-bottom:8px;font-size:.9rem;">
            <i class="fas fa-triangle-exclamation"></i> ดึงข้อมูลไม่สำเร็จ <span x-text="failed.length"></span> ลิงก์
        </div>
        <ul style="margin:0;padding-left:18px;color:var(--ink2);font-size:.78rem;line-height:1.6;">
            <template x-for="(f, idx) in failed" :key="idx">
                <li style="word-break:break-all;"><span x-text="f.url"></span> — <span style="color:#d9534f;" x-text="f.error"></span></li>
            </template>
        </ul>
    </div>

    {{-- ── พรีวิวสินค้า ── --}}
    <div x-show="items.length > 0" x-cloak style="display:none;flex-direction:column;gap:14px;">
        {{-- แถบเครื่องมือ --}}
        <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;">
            <div style="display:flex;align-items:center;gap:12px;font-size:.86rem;color:var(--ink2);">
                <span>พบ <span class="tp-num" style="font-weight:700;color:var(--ink);" x-text="items.length"></span> รายการ</span>
                <span style="opacity:.4;">|</span>
                <span>เลือก <span class="tp-num" style="font-weight:700;color:var(--accent1);" x-text="selectedItems.length"></span> รายการ</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button @click="toggleAll(true)" type="button" class="tp-btn tp-btn-sm">เลือกทั้งหมด</button>
                <button @click="toggleAll(false)" type="button" class="tp-btn tp-btn-sm">ไม่เลือก</button>
            </div>
        </div>

        {{-- กริดการ์ดสินค้า --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px;">
            <template x-for="item in items" :key="item.item_id">
                <div class="tp-card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;"
                     :style="item.selected ? 'box-shadow: var(--card-shadow), 0 0 0 2px var(--accent1);' : ''">
                    {{-- รูป + checkbox + badges --}}
                    <div style="position:relative;">
                        <label style="position:absolute;top:10px;left:10px;z-index:5;"
                               :style="item.already_imported ? 'opacity:.45;cursor:not-allowed;' : 'cursor:pointer;'">
                            <input type="checkbox" x-model="item.selected" :disabled="item.already_imported"
                                   style="width:22px;height:22px;accent-color:var(--accent1);cursor:pointer;">
                        </label>
                        <div style="position:absolute;top:10px;right:10px;z-index:5;display:flex;flex-direction:column;align-items:flex-end;gap:5px;">
                            <span x-show="item.already_imported" class="tp-pill" style="background:var(--ink2);color:var(--surf);font-size:10px;font-weight:700;">นำเข้าแล้ว</span>
                            <span x-show="!item.in_range" class="tp-pill" style="background:#e0a52e;color:#fff;font-size:10px;font-weight:700;">นอกช่วงราคา</span>
                            <span x-show="item.variant_count > 0" class="tp-pill" style="background:#b79ae8;color:#fff;font-size:10px;font-weight:700;" x-text="item.variant_count + ' ตัวเลือก'"></span>
                        </div>
                        <div class="tp-inset" style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--surf);">
                            <img :src="item.main_image" :alt="item.name" loading="lazy"
                                 style="width:100%;height:100%;object-fit:contain;"
                                 onerror="this.style.opacity=0">
                        </div>
                    </div>

                    {{-- เนื้อหา --}}
                    <div style="padding:13px;display:flex;flex-direction:column;flex:1;gap:8px;">
                        <div class="tp-muted" style="font-size:.72rem;" x-show="item.brand" x-text="item.brand"></div>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" x-text="item.name"></div>

                        <div style="display:flex;align-items:baseline;gap:8px;">
                            <span class="tp-num" style="font-size:1.15rem;font-weight:800;color:var(--accent1);" x-text="'฿' + fmt(item.price)"></span>
                            <span x-show="item.compare_at_price" class="tp-num tp-muted" style="font-size:.78rem;text-decoration:line-through;" x-text="'฿' + fmt(item.compare_at_price)"></span>
                        </div>

                        <div class="tp-muted" style="font-size:.7rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-show="item.lazada_category" x-text="item.lazada_category"></div>

                        {{-- เลือกหมวดหมู่ --}}
                        <div style="margin-top:auto;padding-top:6px;">
                            <label class="tp-muted" style="display:block;font-size:.68rem;font-weight:600;margin-bottom:4px;">หมวดหมู่ในร้าน</label>
                            <select x-model.number="item.category_id" class="tp-input" style="font-size:.78rem;padding:8px 10px;">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <a :href="item.source_url" target="_blank" rel="noopener noreferrer"
                           class="tp-muted" style="font-size:.7rem;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fas fa-external-link-alt"></i> ดูบน Lazada
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- ปุ่มนำเข้า --}}
        <div style="position:sticky;bottom:16px;display:flex;justify-content:center;">
            <button @click="importSelected()" :disabled="importing || selectedItems.length === 0" type="button"
                    class="tp-btn" style="background:linear-gradient(135deg,#5aa07e,#4f9e7e);color:#fff;height:50px;padding:0 30px;font-size:.95rem;font-weight:700;border-radius:25px;box-shadow:var(--raise);">
                <i class="fas" :class="importing ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                <span x-text="importing ? 'กำลังนำเข้า...' : ('นำเข้าสินค้าที่เลือก (' + selectedItems.length + ')')"></span>
            </button>
        </div>
    </div>

    {{-- ── ผลการนำเข้า ── --}}
    <div x-show="results" x-cloak class="tp-card" style="display:none;flex-direction:column;gap:12px;">
        <h2 style="font-size:1.1rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-clipboard-check" style="color:#5aa07e;"></i> ผลการนำเข้า
        </h2>
        <p style="font-weight:600;color:var(--ink);font-size:.9rem;margin:0;" x-text="results?.summary"></p>

        <div x-show="results?.imported?.length" style="font-size:.85rem;">
            <div style="font-weight:700;color:#5aa07e;margin-bottom:5px;">✅ นำเข้าสำเร็จ</div>
            <ul style="margin:0;padding:0;list-style:none;color:var(--ink2);">
                <template x-for="(p, idx) in (results?.imported || [])" :key="idx">
                    <li style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid color-mix(in srgb, var(--ink2) 16%, transparent);">
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ink);" x-text="p.name"></span>
                        <span class="tp-num" style="color:var(--accent1);font-weight:700;white-space:nowrap;" x-text="'฿' + fmt(p.price)"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div x-show="results?.skipped?.length" style="font-size:.85rem;">
            <div style="font-weight:700;color:#e0a52e;margin-bottom:5px;">⏭️ ข้าม</div>
            <ul style="margin:0;padding-left:18px;color:var(--ink2);">
                <template x-for="(s, idx) in (results?.skipped || [])" :key="idx">
                    <li><span style="color:var(--ink);" x-text="s.name"></span> — <span x-text="s.reason"></span></li>
                </template>
            </ul>
        </div>

        <div x-show="results?.errors?.length" style="font-size:.8rem;">
            <div style="font-weight:700;color:#d9534f;margin-bottom:5px;">⚠️ ผิดพลาด</div>
            <ul style="margin:0;padding-left:18px;color:#d9534f;">
                <template x-for="(e, idx) in (results?.errors || [])" :key="idx">
                    <li style="word-break:break-all;"><span x-text="e.url"></span> — <span x-text="e.error"></span></li>
                </template>
            </ul>
        </div>

        <a href="{{ route('admin.ecommerce.products.index') }}" class="tp-btn tp-btn-primary tp-btn-sm" style="align-self:flex-start;">
            <i class="fas fa-box"></i> <span>ดูสินค้าทั้งหมด</span>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function lazadaImport() {
    return {
        urls: '',
        priceMin: {{ (int) ($priceMin ?? 200) }},
        priceMax: {{ (int) ($priceMax ?? 15000) }},
        loading: false,
        importing: false,
        items: [],
        failed: [],
        results: null,
        error: '',
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        get selectedItems() {
            return this.items.filter(i => i.selected && !i.already_imported);
        },

        fmt(n) {
            return Number(n || 0).toLocaleString('th-TH', { maximumFractionDigits: 2 });
        },

        toggleAll(val) {
            this.items.forEach(i => { if (!i.already_imported) i.selected = val; });
        },

        async preview() {
            if (!this.urls.trim()) { this.error = 'กรุณาวางลิงก์สินค้าอย่างน้อย 1 รายการ'; return; }
            this.loading = true; this.error = ''; this.results = null;
            try {
                const res = await fetch('{{ route('admin.ecommerce.lazada-import.preview') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ urls: this.urls, price_min: this.priceMin, price_max: this.priceMax })
                });
                const data = await res.json();
                if (!data.success) { this.error = data.message || 'ดึงข้อมูลไม่สำเร็จ'; this.items = []; this.failed = []; return; }
                this.items = (data.items || []).map(i => ({
                    ...i,
                    selected: i.in_range && !i.already_imported,
                    category_id: i.suggested_category_id || {{ (int) ($categories->first()->id ?? 0) }}
                }));
                this.failed = data.failed || [];
                if (this.items.length === 0 && this.failed.length === 0) this.error = 'ไม่พบสินค้าจากลิงก์ที่วางมา';
            } catch (e) {
                this.error = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        async importSelected() {
            const picks = this.selectedItems.map(i => ({ url: i.source_url, category_id: i.category_id }));
            if (picks.length === 0) { this.error = 'ยังไม่ได้เลือกสินค้า'; return; }
            this.importing = true; this.error = ''; this.results = null;
            try {
                const res = await fetch('{{ route('admin.ecommerce.lazada-import.import') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ items: picks })
                });
                const data = await res.json();
                this.results = data;
                const importedNames = new Set((data.imported || []).map(x => x.name));
                this.items.forEach(i => {
                    if (importedNames.has(i.name)) { i.already_imported = true; i.selected = false; }
                });
                if (window.Alpine && data.summary) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: data.summary } }));
                }
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            } catch (e) {
                this.error = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.importing = false;
            }
        }
    };
}
</script>
@endpush
