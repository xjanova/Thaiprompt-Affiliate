{{--
 | ชุดเครื่องมือกลางของหน้า V4 ฝั่งแอดมิน (ไรเดอร์ / งานไรเดอร์ / ตลาดสด)
 |
 | - โทนสีสถานะ (--w-ok / --w-warn / --w-bad / --w-info / --w-violet) อิงพาเลตต์ toast ของ layout admin-v4
 |   ทุกสีมีค่าสำรองอยู่ใน var() เท่านั้น → Theme Studio ตั้ง --tp-ok ฯลฯ ทับได้ภายหลัง
 | - โมดัลยืนยันการกระทำ (ส่งฟอร์ม POST จริง + CSRF) เรียกได้จากทุกปุ่มด้วย
 |     $dispatch('w1-action', { url, title, message, reason: 'required'|'optional'|'none', confirm, tone, fields: {...}, checkbox: {...} })
 |   ปุ่มที่เรียกต้องอยู่ใต้ x-data สักตัว (หน้า V4 ครอบ root ด้วย x-data="{}" แล้ว)
 |
 | ใช้งาน: @include('admin.riders.partials.v4-kit') หนึ่งครั้งต่อหน้า (ภายใน section content)
--}}
@once
@push('styles')
<style>
    :root {
        --w-ok: var(--tp-ok, #5aa07e);
        --w-warn: var(--tp-warn, #e0a52e);
        --w-bad: var(--tp-bad, #d9534f);
        --w-info: var(--tp-info, #5689b8);
        --w-violet: var(--tp-violet, #8c6fd6);
        --w-on: var(--tp-on-accent, #fff);
    }
    .w1-row { transition: background .15s ease; }
    .w1-row:hover { background: color-mix(in srgb, var(--accent1) 7%, transparent); }
    .w1-link { color: var(--deep1); text-decoration: none; font-weight: 600; }
    .w1-link:hover { text-decoration: underline; }
</style>
@endpush

{{-- โมดัลยืนยันการกระทำ (component ลอยของตัวเอง — มี x-data ของตัวเอง) --}}
<div x-data="w1ActionModal()" x-show="open" x-cloak
     @w1-action.window="show($event.detail)"
     @keydown.escape.window="close()">
    <div style="position:fixed; inset:0; z-index:90; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,.52); -webkit-backdrop-filter:blur(2px); backdrop-filter:blur(2px);"
         @click.self="close()">
        <form method="POST" :action="url" class="tp-card" style="width:100%; max-width:480px; padding:22px; max-height:calc(100vh - 32px); overflow-y:auto;"
              @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
            @csrf
            <template x-for="(value, name) in fields" :key="name">
                <input type="hidden" :name="name" :value="value">
            </template>

            <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:14px;">
                <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"
                      :style="{ background: 'linear-gradient(135deg, var(' + toneVar() + '), color-mix(in srgb, var(' + toneVar() + ') 70%, var(--ink)))' }">
                    <i class="fas" :class="icon"></i>
                </span>
                <div style="flex:1; min-width:0;">
                    <div class="tp-section-h" style="font-size:16px;" x-text="title"></div>
                    <p style="margin:6px 0 0; font-size:13px; color:var(--ink2); line-height:1.6; white-space:pre-line;" x-text="message"></p>
                </div>
                <button type="button" class="tp-icon-btn" style="width:34px; height:34px;" @click="close()" title="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <template x-if="reasonMode !== 'none'">
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        <span x-text="reasonLabel"></span>
                        <span x-show="reasonMode === 'required'" style="color:var(--w-bad);">*</span>
                    </label>
                    <textarea name="reason" rows="3" class="tp-input" style="resize:vertical;"
                              x-model="reason"
                              :required="reasonMode === 'required'"
                              :minlength="reasonMode === 'required' ? 3 : null"
                              maxlength="500"
                              :placeholder="placeholder"></textarea>
                    <div style="font-size:11px; color:var(--ink2); margin-top:4px; text-align:right;" x-text="reason.length + ' / 500'"></div>
                </div>
            </template>

            <template x-if="checkbox">
                <label style="display:flex; align-items:flex-start; gap:10px; padding:11px 13px; border-radius:13px; box-shadow:var(--inset-sm); margin-bottom:14px; cursor:pointer; font-size:13px;">
                    <input type="checkbox" value="1" :name="checkbox.name" x-model="checkboxChecked" style="margin-top:3px; accent-color:var(--accent1); width:17px; height:17px;">
                    <span>
                        <span style="font-weight:600;" x-text="checkbox.label"></span>
                        <span x-show="checkbox.hint" style="display:block; color:var(--ink2); font-size:12px; margin-top:2px;" x-text="checkbox.hint"></span>
                    </span>
                </label>
            </template>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="tp-btn" style="flex:1; min-width:120px;" @click="close()">ยกเลิก</button>
                <button type="submit" class="tp-btn" style="flex:1; min-width:120px; color:var(--w-on);"
                        :style="{ background: 'linear-gradient(135deg, var(' + toneVar() + '), color-mix(in srgb, var(' + toneVar() + ') 72%, var(--ink)))', opacity: blocked() ? .5 : 1, cursor: blocked() ? 'not-allowed' : 'pointer' }"
                        :disabled="blocked()">
                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : icon"></i>
                    <span x-text="submitting ? 'กำลังดำเนินการ...' : confirmLabel"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    /* โมดัลยืนยันการกระทำของหน้าแอดมิน V4 — ส่งฟอร์มจริง (ไม่ใช้ AJAX) ผลลัพธ์กลับมาเป็น flash */
    function w1ActionModal() {
        return {
            open: false,
            submitting: false,
            url: '',
            title: '',
            message: '',
            icon: 'fa-circle-question',
            tone: 'info',
            confirmLabel: 'ยืนยัน',
            reasonMode: 'none',
            reasonLabel: 'เหตุผล',
            placeholder: '',
            reason: '',
            fields: {},
            checkbox: null,
            checkboxChecked: false,
            show(detail) {
                const d = detail || {};
                this.url = d.url || '';
                this.title = d.title || 'ยืนยันการทำรายการ';
                this.message = d.message || '';
                this.icon = d.icon || 'fa-circle-question';
                this.tone = d.tone || 'info';
                this.confirmLabel = d.confirm || 'ยืนยัน';
                this.reasonMode = d.reason || 'none';
                this.reasonLabel = d.reasonLabel || 'เหตุผล';
                this.placeholder = d.placeholder || 'ระบุเหตุผล (บันทึกในประวัติ)';
                this.reason = d.reasonValue || '';
                this.fields = d.fields || {};
                this.checkbox = d.checkbox || null;
                this.checkboxChecked = !!(d.checkbox && d.checkbox.checked);
                this.submitting = false;
                this.open = !!this.url;
            },
            close() {
                if (this.submitting) return;
                this.open = false;
            },
            blocked() {
                return this.submitting || (this.reasonMode === 'required' && this.reason.trim().length < 3);
            },
            toneVar() {
                const map = { ok: '--w-ok', warn: '--w-warn', bad: '--w-bad', info: '--w-info', violet: '--w-violet', gold: '--accent1' };
                return map[this.tone] || '--w-info';
            },
        };
    }
</script>
@endpush
@endonce
