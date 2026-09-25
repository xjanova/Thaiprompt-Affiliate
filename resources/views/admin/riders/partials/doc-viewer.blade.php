{{--
 | ตัวดูเอกสารไรเดอร์ (ลอยทับหน้า — มี x-data ของตัวเอง)
 | ไฟล์เปิดผ่าน route แอดมิน admin.riders.document เท่านั้น (ไฟล์อยู่ private disk ไม่มีลิงก์สาธารณะ)
 | เรียกใช้: $dispatch('w1-doc', { items: [{ label, url }], index: 0 })
 | รองรับ: เลื่อนซ้าย/ขวา (ปุ่มลูกศรคีย์บอร์ดด้วย), หมุน 90°, ซูม, เปิดไฟล์ในแท็บใหม่ (ไฟล์ HEIC บางเบราว์เซอร์แสดงในหน้าไม่ได้)
--}}
@once
<div x-data="w1DocViewer()" x-show="open" x-cloak
     @w1-doc.window="show($event.detail)"
     @keydown.escape.window="open = false"
     @keydown.arrow-right.window="if (open) next()"
     @keydown.arrow-left.window="if (open) prev()">
    <div style="position:fixed; inset:0; z-index:95; display:flex; flex-direction:column; background:rgba(8,6,4,.9);">
        <div style="display:flex; align-items:center; gap:10px; padding:12px 16px; color:var(--w-on);">
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:15px;" x-text="current().label || 'เอกสาร'"></div>
                <div style="font-size:12px; opacity:.75;" x-text="(index + 1) + ' / ' + items.length"></div>
            </div>
            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="rotate = (rotate + 90) % 360" title="หมุน"><i class="fas fa-rotate-right"></i></button>
            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="zoom = !zoom" title="ซูม"><i class="fas" :class="zoom ? 'fa-magnifying-glass-minus' : 'fa-magnifying-glass-plus'"></i></button>
            <a :href="current().url" target="_blank" rel="noopener" class="tp-icon-btn" style="width:38px; height:38px;" title="เปิดในแท็บใหม่"><i class="fas fa-up-right-from-square"></i></a>
            <button type="button" class="tp-icon-btn" style="width:38px; height:38px;" @click="open = false" title="ปิด"><i class="fas fa-xmark"></i></button>
        </div>
        <div style="flex:1; min-height:0; display:flex; align-items:center; justify-content:center; gap:10px; padding:0 10px 16px;">
            <button type="button" class="tp-icon-btn" x-show="items.length > 1" @click="prev()" title="ก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
            <div style="flex:1; height:100%; overflow:auto; display:flex; align-items:center; justify-content:center;" @click.self="open = false">
                <template x-if="!failed">
                    <img :src="current().url" :alt="current().label" x-on:error="failed = true" x-on:load="failed = false"
                         :style="{ transform: 'rotate(' + rotate + 'deg)', maxWidth: zoom ? 'none' : '100%', maxHeight: zoom ? 'none' : '100%', width: zoom ? '160%' : 'auto', transition: 'transform .2s ease', borderRadius: '10px', objectFit: 'contain' }">
                </template>
                <template x-if="failed">
                    <div class="tp-card" style="max-width:380px; text-align:center; padding:22px;">
                        <i class="fas fa-file-image" style="font-size:30px; color:var(--ink2); margin-bottom:10px;"></i>
                        <p style="margin:0 0 12px; font-size:13.5px; color:var(--ink);">แสดงไฟล์นี้ในหน้าไม่ได้ (อาจเป็นไฟล์ HEIC หรือไฟล์หาย)</p>
                        <a :href="current().url" target="_blank" rel="noopener" class="tp-btn tp-btn-primary"><i class="fas fa-download"></i> เปิดไฟล์ในแท็บใหม่</a>
                    </div>
                </template>
            </div>
            <button type="button" class="tp-icon-btn" x-show="items.length > 1" @click="next()" title="ถัดไป"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /* ตัวดูเอกสารไรเดอร์ในหลังบ้าน */
    function w1DocViewer() {
        return {
            open: false,
            items: [],
            index: 0,
            rotate: 0,
            zoom: false,
            failed: false,
            show(detail) {
                const d = detail || {};
                this.items = (d.items || []).filter((item) => item && item.url);
                if (!this.items.length) return;
                this.index = Math.min(Math.max(0, d.index || 0), this.items.length - 1);
                this.reset();
                this.open = true;
            },
            current() {
                return this.items[this.index] || { label: '', url: '' };
            },
            reset() {
                this.rotate = 0;
                this.zoom = false;
                this.failed = false;
            },
            next() {
                if (!this.items.length) return;
                this.index = (this.index + 1) % this.items.length;
                this.reset();
            },
            prev() {
                if (!this.items.length) return;
                this.index = (this.index - 1 + this.items.length) % this.items.length;
                this.reset();
            },
        };
    }
</script>
@endpush
@endonce
