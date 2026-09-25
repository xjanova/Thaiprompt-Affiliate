{{--
 | ฟอร์มพนักงาน (ใช้ร่วม create / edit) — ช่องตรงกับ Seller\StaffController@store/update
 | ตัวแปร: $employee (Employee|null), $departments, $positions, $workShifts, $action, $method, $submitLabel
 --}}
@php
    $e = $employee ?? null;
    $isEdit = (bool) $e;
    $assign = $e?->posAssignment;
    $currentPerms = (array) old('pos_permissions', $assign?->pos_permissions ?? ['sales']);
    $permOptions = ['sales' => 'ขายสินค้า', 'refund' => 'คืนเงิน', 'discount' => 'ให้ส่วนลด', 'reports' => 'ดูรายงาน', 'inventory' => 'จัดการสินค้า', 'drawer' => 'เปิดลิ้นชักเงิน'];
    $types = ['full_time' => 'พนักงานประจำ', 'part_time' => 'พาร์ทไทม์', 'contract' => 'สัญญาจ้าง', 'intern' => 'นักศึกษาฝึกงาน', 'freelance' => 'ฟรีแลนซ์'];
    $statuses = ['active' => 'ทำงานอยู่', 'probation' => 'ทดลองงาน', 'notice_period' => 'แจ้งลาออก', 'resigned' => 'ลาออกแล้ว', 'terminated' => 'เลิกจ้าง'];
    $lbl = 'font-size:12.5px; font-weight:700;';
@endphp

<form method="POST" action="{{ $action }}" style="display:flex; flex-direction:column; gap:16px;" x-data="{ saving: false }" @submit="saving = true">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
        <div class="tp-section-h">🪪 ข้อมูลส่วนตัว</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
            <div>
                <label for="sf-fn" style="{{ $lbl }}">ชื่อ (อังกฤษ) <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="sf-fn" type="text" name="first_name" required maxlength="255" value="{{ old('first_name', $e->first_name ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-ln" style="{{ $lbl }}">นามสกุล (อังกฤษ) <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="sf-ln" type="text" name="last_name" required maxlength="255" value="{{ old('last_name', $e->last_name ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-fnth" style="{{ $lbl }}">ชื่อ (ไทย)</label>
                <input id="sf-fnth" type="text" name="first_name_th" maxlength="255" value="{{ old('first_name_th', $e->first_name_th ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-lnth" style="{{ $lbl }}">นามสกุล (ไทย)</label>
                <input id="sf-lnth" type="text" name="last_name_th" maxlength="255" value="{{ old('last_name_th', $e->last_name_th ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-nick" style="{{ $lbl }}">ชื่อเล่น</label>
                <input id="sf-nick" type="text" name="nickname" maxlength="100" value="{{ old('nickname', $e->nickname ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-phone" style="{{ $lbl }}">เบอร์โทร</label>
                <input id="sf-phone" type="tel" name="mobile_phone" maxlength="20" inputmode="tel" value="{{ old('mobile_phone', $e->mobile_phone ?? '') }}" class="tp-input tp-num" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-email" style="{{ $lbl }}">อีเมล</label>
                <input id="sf-email" type="email" name="personal_email" maxlength="255" value="{{ old('personal_email', $e->personal_email ?? '') }}" class="tp-input" style="margin-top:6px;">
            </div>
        </div>
    </div>

    <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
        <div class="tp-section-h">💼 การจ้างงาน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
            <div>
                <label for="sf-dept" style="{{ $lbl }}">แผนก @if($isEdit)<span style="color:var(--tp-bad, #d9534f);">*</span>@endif</label>
                <select id="sf-dept" name="department_id" class="tp-input" style="margin-top:6px;" @required($isEdit)>
                    @unless($isEdit)<option value="">— ใช้แผนก “ทั่วไป” —</option>@endunless
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected((string) old('department_id', $e->department_id ?? '') === (string) $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sf-pos" style="{{ $lbl }}">ตำแหน่ง @if($isEdit)<span style="color:var(--tp-bad, #d9534f);">*</span>@endif</label>
                <select id="sf-pos" name="position_id" class="tp-input" style="margin-top:6px;" @required($isEdit)>
                    @unless($isEdit)<option value="">— ใช้ตำแหน่ง “พนักงาน” —</option>@endunless
                    @foreach($positions as $pos)
                        <option value="{{ $pos->id }}" @selected((string) old('position_id', $e->position_id ?? '') === (string) $pos->id)>{{ $pos->title }}</option>
                    @endforeach
                </select>
            </div>
            @unless($isEdit)
                <div>
                    <label for="sf-hire" style="{{ $lbl }}">วันเริ่มงาน <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                    <input id="sf-hire" type="date" name="hire_date" required value="{{ old('hire_date', now()->toDateString()) }}" class="tp-input" style="margin-top:6px;">
                </div>
            @endunless
            <div>
                <label for="sf-type" style="{{ $lbl }}">ประเภทการจ้าง <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <select id="sf-type" name="employment_type" required class="tp-input" style="margin-top:6px;">
                    @foreach($types as $tKey => $tLabel)
                        <option value="{{ $tKey }}" @selected(old('employment_type', $e->employment_type ?? 'full_time') === $tKey)>{{ $tLabel }}</option>
                    @endforeach
                </select>
            </div>
            @if($isEdit)
                <div>
                    <label for="sf-status" style="{{ $lbl }}">สถานะ <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                    <select id="sf-status" name="employment_status" required class="tp-input" style="margin-top:6px;">
                        @foreach($statuses as $sKey => $sLabel)
                            <option value="{{ $sKey }}" @selected(old('employment_status', $e->employment_status ?? 'active') === $sKey)>{{ $sLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="sf-salary" style="{{ $lbl }}">เงินเดือน (บาท)</label>
                <input id="sf-salary" type="number" name="basic_salary" min="0" step="0.01" value="{{ old('basic_salary', $e ? (float) $e->basic_salary : '') }}" class="tp-input tp-num" style="margin-top:6px;">
            </div>
            <div>
                <label for="sf-shift" style="{{ $lbl }}">กะการทำงาน</label>
                <select id="sf-shift" name="work_shift_id" class="tp-input" style="margin-top:6px;">
                    <option value="">— ไม่กำหนด —</option>
                    @foreach($workShifts as $shift)
                        <option value="{{ $shift->id }}" @selected((string) old('work_shift_id', $assign->work_shift_id ?? '') === (string) $shift->id)>{{ $shift->name }} ({{ $shift->time_range }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
        <div class="tp-section-h">🔑 การใช้งานเครื่อง POS</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
            <div>
                <label for="sf-pin" style="{{ $lbl }}">รหัส PIN 4–6 หลัก</label>
                <input id="sf-pin" type="password" name="pin_code" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password"
                       placeholder="{{ $isEdit ? 'เว้นว่างถ้าไม่เปลี่ยน' : 'เช่น 1234' }}" class="tp-input tp-num" style="margin-top:6px;">
                <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">ใช้ลงเวลาและเข้าใช้เครื่อง POS · เก็บแบบเข้ารหัส ไม่มีใครเห็นตัวเลขจริง</div>
            </div>
            <div>
                <div style="{{ $lbl }}">สิทธิ์บนเครื่อง POS</div>
                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; margin-top:6px;">
                    @foreach($permOptions as $pKey => $pLabel)
                        <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:12px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="pos_permissions[]" value="{{ $pKey }}" @checked(in_array($pKey, $currentPerms, true)) style="width:16px; height:16px; accent-color:var(--accent1);">
                            {{ $pLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
        <a href="{{ $isEdit ? route('seller.staff.show', $e) : route('seller.staff.index') }}" class="tp-btn">ยกเลิก</a>
        <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">
            <span x-text="saving ? 'กำลังบันทึก…' : @js($submitLabel ?? 'บันทึก')"></span>
        </button>
    </div>
</form>
