@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'นำเข้าเข้าแคตตาล็อก Lazada')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: Lazada Hub — นำเข้าเข้าแคตตาล็อก (วางลิงก์ / scrape)
     ต่างจาก "นำเข้าจาก Lazada" เดิม: อันนี้เข้า marketplace_products (catalog ของ Hub)
     ════════════════════════════════════════════════════════════ --}}
<div x-data="catalogImport()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                <a href="{{ route('admin.lazada-hub.catalog.index') }}" style="color:var(--accent2);text-decoration:none;">แคตตาล็อก</a> · นำเข้า
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-cloud-download-alt" style="color:var(--accent1);"></i> นำเข้าเข้าแคตตาล็อก
            </h1>
            <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">วางลิงก์หน้าสินค้า Lazada → ระบบดึงต้นทุน + คำนวณราคาขายให้</p>
        </div>
        <a href="{{ route('admin.lazada-hub.catalog.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-layer-group"></i> <span>ดูแคตตาล็อก</span></a>
    </div>

    {{-- ── ฟอร์มวางลิงก์ ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:14px;">
        <div>
            <label style="display:block;font-weight:600;color:var(--ink);font-size:.85rem;margin-bottom:6px;">ลิงก์สินค้า Lazada (บรรทัดละ 1 ลิงก์ สูงสุด 30)</label>
            <textarea x-model="urls" rows="5" spellcheck="false" class="tp-input tp-num" style="min-height:120px;resize:vertical;font-size:12.5px;line-height:1.6;"
                placeholder="https://www.lazada.co.th/products/...-i1234567890.html"></textarea>
        </div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
            <button @click="preview()" :disabled="loading" type="button" class="tp-btn tp-btn-primary" style="height:44px;">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                <span x-text="loading ? 'กำลังดึง...' : 'ดึงข้อมูล'"></span>
            </button>
            <span class="tp-muted" style="font-size:.78rem;">markup เริ่มต้น <b class="tp-num" style="color:var(--ink);">{{ $defaults['markup'] }}%</b> · โหมดเริ่มต้น <b style="color:var(--ink);">{{ $defaults['mode']==='affiliate' ? 'Affiliate' : 'ขายเอง' }}</b></span>
        </div>
        <p x-show="error" x-cloak x-text="error" style="display:none;color:#d9534f;font-size:.85rem;margin:0;"></p>
    </div>

    {{-- ── ดึงไม่สำเร็จ ── --}}
    <div x-show="failed.length > 0" x-cloak class="tp-card" style="display:none;border-left:4px solid #d9534f;">
        <div style="font-weight:700;color:#d9534f;margin-bottom:8px;font-size:.9rem;"><i class="fas fa-triangle-exclamation"></i> ดึงไม่สำเร็จ <span x-text="failed.length"></span> ลิงก์</div>
        <ul style="margin:0;padding-left:18px;color:var(--ink2);font-size:.78rem;line-height:1.6;">
            <template x-for="(f, i) in failed" :key="i"><li style="word-break:break-all;"><span x-text="f.url"></span> — <span style="color:#d9534f;" x-text="f.error"></span></li></template>
        </ul>
    </div>

    {{-- ── พรีวิว ── --}}
    <div x-show="items.length > 0" x-cloak style="display:none;flex-direction:column;gap:14px;">
        <div class="tp-card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
            <span style="font-size:.86rem;color:var(--ink2);">พบ <b class="tp-num" style="color:var(--ink);" x-text="items.length"></b> · เลือก <b class="tp-num" style="color:var(--accent1);" x-text="selected.length"></b></span>
            <div style="display:flex;gap:8px;">
                <button @click="toggleAll(true)" type="button" class="tp-btn tp-btn-sm">เลือกทั้งหมด</button>
                <button @click="toggleAll(false)" type="button" class="tp-btn tp-btn-sm">ไม่เลือก</button>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px;">
            <template x-for="item in items" :key="item.item_id">
                <div class="tp-card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;"
                     :style="item.selected ? 'box-shadow: var(--card-shadow), 0 0 0 2px var(--accent1);' : ''">
                    <div style="position:relative;">
                        <label style="position:absolute;top:10px;left:10px;z-index:5;" :style="item.already ? 'opacity:.45;' : 'cursor:pointer;'">
                            <input type="checkbox" x-model="item.selected" :disabled="item.already" style="width:22px;height:22px;accent-color:var(--accent1);">
                        </label>
                        <span x-show="item.already" class="tp-pill" style="position:absolute;top:10px;right:10px;z-index:5;background:var(--ink2);color:var(--surf);font-size:10px;font-weight:700;">มีแล้ว</span>
                        <div class="tp-inset" style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--surf);">
                            <img :src="item.main_image" :alt="item.name" loading="lazy" style="width:100%;height:100%;object-fit:contain;" onerror="this.style.opacity=0">
                        </div>
                    </div>
                    <div style="padding:13px;display:flex;flex-direction:column;flex:1;gap:7px;">
                        <div class="tp-muted" style="font-size:.72rem;" x-show="item.brand" x-text="item.brand"></div>
                        <div style="font-size:.84rem;font-weight:600;color:var(--ink);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" x-text="item.name"></div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:6px;margin-top:2px;">
                            <span class="tp-muted" style="font-size:.72rem;">ต้นทุน <span class="tp-num" x-text="'฿'+fmt(item.cost_price)"></span></span>
                            <span class="tp-num" style="font-size:1.05rem;font-weight:800;color:var(--accent1);" x-text="'฿'+fmt(item.selling_price)"></span>
                        </div>
                        <div style="margin-top:auto;padding-top:4px;">
                            <label class="tp-muted" style="display:block;font-size:.68rem;font-weight:600;margin-bottom:4px;">โหมด</label>
                            <select x-model="item.mode" class="tp-input" style="font-size:.78rem;padding:7px 10px;">
                                <option value="affiliate">🟣 Affiliate (ได้ค่าคอม)</option>
                                <option value="resell">🟢 ขายเอง (บวกกำไร)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div style="position:sticky;bottom:16px;display:flex;justify-content:center;">
            <button @click="importSelected()" :disabled="importing || selected.length===0" type="button" class="tp-btn"
                    style="background:linear-gradient(135deg,#5aa07e,#4f9e7e);color:#fff;height:50px;padding:0 30px;font-size:.95rem;font-weight:700;border-radius:25px;box-shadow:var(--raise);">
                <i class="fas" :class="importing ? 'fa-spinner fa-spin' : 'fa-download'"></i>
                <span x-text="importing ? 'กำลังนำเข้า...' : ('นำเข้าที่เลือก ('+selected.length+')')"></span>
            </button>
        </div>
    </div>

    {{-- ── ผลลัพธ์ ── --}}
    <div x-show="results" x-cloak class="tp-card" style="display:none;flex-direction:column;gap:10px;">
        <h2 style="font-size:1.05rem;font-weight:800;color:var(--ink);margin:0;"><i class="fas fa-clipboard-check" style="color:#5aa07e;"></i> ผลการนำเข้า</h2>
        <p style="font-weight:600;color:var(--ink);font-size:.9rem;margin:0;" x-text="results?.summary"></p>
        <a href="{{ route('admin.lazada-hub.catalog.index') }}" class="tp-btn tp-btn-primary tp-btn-sm" style="align-self:flex-start;"><i class="fas fa-layer-group"></i> <span>ไปที่แคตตาล็อก</span></a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function catalogImport() {
    return {
        urls: '',
        loading: false,
        importing: false,
        items: [],
        failed: [],
        results: null,
        error: '',
        defaultMode: @js($defaults['mode']),
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        get selected() { return this.items.filter(i => i.selected && !i.already); },
        fmt(n) { return Number(n || 0).toLocaleString('th-TH', { maximumFractionDigits: 2 }); },
        toggleAll(v) { this.items.forEach(i => { if (!i.already) i.selected = v; }); },

        async preview() {
            if (!this.urls.trim()) { this.error = 'กรุณาวางลิงก์อย่างน้อย 1 รายการ'; return; }
            this.loading = true; this.error = ''; this.results = null;
            try {
                const res = await fetch('{{ route('admin.lazada-hub.catalog.import-preview') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ urls: this.urls })
                });
                const data = await res.json();
                if (!data.success) { this.error = data.message || 'ดึงไม่สำเร็จ'; this.items = []; this.failed = []; return; }
                this.items = (data.items || []).map(i => ({ ...i, selected: !i.already, mode: this.defaultMode }));
                this.failed = data.failed || [];
                if (this.items.length === 0 && this.failed.length === 0) this.error = 'ไม่พบสินค้าจากลิงก์ที่วาง';
            } catch (e) { this.error = 'ผิดพลาด: ' + e.message; }
            this.loading = false;
        },

        async importSelected() {
            const picks = this.selected.map(i => ({ url: i.source_url, mode: i.mode }));
            if (picks.length === 0) return;
            this.importing = true; this.error = ''; this.results = null;
            try {
                const res = await fetch('{{ route('admin.lazada-hub.catalog.import-store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ items: picks })
                });
                const data = await res.json();
                this.results = data;
                const names = new Set((data.imported || []).map(x => x.name));
                this.items.forEach(i => { if (names.has(i.name)) { i.already = true; i.selected = false; } });
                if (data.summary) window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: data.summary } }));
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            } catch (e) { this.error = 'ผิดพลาด: ' + e.message; }
            this.importing = false;
        }
    };
}
</script>
@endpush
