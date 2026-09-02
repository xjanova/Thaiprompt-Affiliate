@extends('layouts.admin-v4')

@section('title', 'ตั้งค่าระบบ Ticket')

@section('content')
{{--
    ⚙️ ตั้งค่าระบบ Ticket (ธีม V4 นวลทองคำ)

    ⚠️ ของเดิมหน้านี้เป็น "ฟอร์มหลอก" ทั้งหน้า:
       - controller settings() ไม่ส่งข้อมูลอะไรมาเลย ทุกช่อง hardcode ค่าไว้ในหน้า
       - controller updateSettings() เป็นเมธอดว่าง แต่ redirect พร้อมข้อความ
         "อัปเดตการตั้งค่าเรียบร้อยแล้ว"
       ⇒ แอดมินกดบันทึก เห็นว่าสำเร็จ แต่ไม่มีอะไรถูกเก็บ พอ refresh ค่ากลับเป็นเดิม
       รอบนี้ต่อกับตาราง settings (กลุ่ม tickets) ผ่าน Setting::get/set ให้เก็บได้จริง
--}}
@php
    // [key, label, ไอคอน] ของสวิตช์แต่ละกลุ่ม — วนลูปสร้างแทนการเขียนซ้ำ
    $generalToggles = [
        ['auto_assign_enabled', 'เปิดใช้การมอบหมายอัตโนมัติ', 'fa-user-check'],
        ['sla_enabled',         'เปิดใช้นโยบาย SLA',           'fa-clock'],
        ['allow_user_reopen',   'อนุญาตให้ผู้ใช้เปิด Ticket ใหม่', 'fa-rotate-left'],
    ];
    $notifyToggles = [
        ['notify_admin_new_ticket',   'แจ้งเตือนแอดมินเมื่อมี Ticket ใหม่',  'fa-bell'],
        ['notify_user_reply',         'แจ้งเตือนผู้ใช้เมื่อมีข้อความตอบกลับ', 'fa-comment'],
        ['notify_user_status_change', 'แจ้งเตือนเมื่อสถานะเปลี่ยน',          'fa-arrows-rotate'],
        ['notify_sla_breach',         'แจ้งเตือนเมื่อเลยกำหนด SLA',          'fa-triangle-exclamation'],
    ];
    $kbToggles = [
        ['suggest_kb_articles', 'แนะนำบทความฐานความรู้ให้ผู้ใช้', 'fa-lightbulb'],
        ['public_kb_access',    'เปิดฐานความรู้ให้คนทั่วไปอ่าน',   'fa-globe'],
    ];
    $priorityOptions = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'critical' => 'วิกฤต'];
    $weekDays = [1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ', 4 => 'พฤหัสบดี', 5 => 'ศุกร์', 6 => 'เสาร์', 7 => 'อาทิตย์'];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · ตั้งค่า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ตั้งค่าระบบ Ticket ⚙️</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ค่าเหล่านี้ถูกบันทึกลงฐานข้อมูลจริง (กลุ่ม tickets)</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
            <button type="submit" form="ticketSettingsForm" class="tp-btn tp-btn-sm tp-btn-primary" style="font-weight:700;">
                <i class="fas fa-save"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </div>

    {{-- ===== Flash ===== --}}
    @if(session('success'))
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #5aa07e;">
            <div style="display:flex; align-items:center; gap:10px; font-size:13.5px; color:var(--ink);">
                <i class="fas fa-circle-check" style="color:#5aa07e;"></i>{{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:10px; font-size:13.5px; color:var(--ink);">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i>{{ session('error') }}
            </div>
        </div>
    @endif
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="font-size:13.5px; font-weight:700; color:#d9534f; margin-bottom:6px;">
                <i class="fas fa-circle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง
            </div>
            <ul style="margin:0; padding-left:20px; font-size:12.5px; color:var(--ink2);">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="ticketSettingsForm" method="POST" action="{{ route('admin.tickets.settings.update') }}"
          style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ===== ทั่วไป ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-sliders"></i> การตั้งค่าทั่วไป</div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    ความสำคัญเริ่มต้นสำหรับ Ticket ใหม่
                </label>
                <div class="tp-well tp-input" style="padding:0; max-width:320px;">
                    <select name="default_priority"
                            style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        @foreach($priorityOptions as $key => $label)
                            <option value="{{ $key }}" {{ old('default_priority', $settings['default_priority']) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px;">
                @foreach($generalToggles as [$key, $label, $icon])
                    <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:13px 15px; cursor:pointer;">
                        {{-- hidden 0 นำหน้า: checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย --}}
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $settings[$key]) ? 'checked' : '' }}
                               style="accent-color:#e0a52e; width:16px; height:16px; cursor:pointer;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);">
                            <i class="fas {{ $icon }}" style="color:var(--ink2); margin-right:6px;"></i>{{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ===== การแจ้งเตือน ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-envelope"></i> การแจ้งเตือนทางอีเมล</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:12px;">
                @foreach($notifyToggles as [$key, $label, $icon])
                    <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:13px 15px; cursor:pointer;">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $settings[$key]) ? 'checked' : '' }}
                               style="accent-color:#5aa07e; width:16px; height:16px; cursor:pointer;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);">
                            <i class="fas {{ $icon }}" style="color:var(--ink2); margin-right:6px;"></i>{{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ===== ไฟล์แนบ ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-paperclip"></i> ไฟล์แนบ</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        ขนาดไฟล์สูงสุด (MB)
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="max_file_size" min="1" max="100" required
                               value="{{ old('max_file_size', $settings['max_file_size']) }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        นามสกุลไฟล์ที่อนุญาต
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="allowed_extensions" required
                               value="{{ old('allowed_extensions', $settings['allowed_extensions']) }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-family:monospace;">
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">คั่นด้วยจุลภาค เช่น jpg,png,pdf</div>
                </div>
            </div>

            <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:13px 15px; cursor:pointer;">
                <input type="hidden" name="scan_uploads" value="0">
                <input type="checkbox" name="scan_uploads" value="1" {{ old('scan_uploads', $settings['scan_uploads']) ? 'checked' : '' }}
                       style="accent-color:#d9534f; width:16px; height:16px; cursor:pointer;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">
                    <i class="fas fa-shield-virus" style="color:var(--ink2); margin-right:6px;"></i>สแกนไวรัสไฟล์ที่อัปโหลด
                </span>
            </label>
        </div>

        {{-- ===== ฐานความรู้ ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-book"></i> ฐานความรู้</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:12px;">
                @foreach($kbToggles as [$key, $label, $icon])
                    <label class="tp-well" style="display:flex; align-items:center; gap:10px; padding:13px 15px; cursor:pointer;">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $settings[$key]) ? 'checked' : '' }}
                               style="accent-color:#d6824a; width:16px; height:16px; cursor:pointer;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);">
                            <i class="fas {{ $icon }}" style="color:var(--ink2); margin-right:6px;"></i>{{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ===== เวลาทำการ ===== --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-business-time"></i> เวลาทำการ</div>
            <div style="font-size:11.5px; color:var(--ink2); margin-bottom:14px;">
                ใช้กับนโยบาย SLA ที่ตั้งค่าให้นับเฉพาะเวลาทำการ
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เวลาเปิดทำการ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="time" name="business_hours_start" required
                               value="{{ old('business_hours_start', $settings['business_hours_start']) }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เวลาปิดทำการ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="time" name="business_hours_end" required
                               value="{{ old('business_hours_end', $settings['business_hours_end']) }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                    </div>
                </div>
            </div>

            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:8px;">วันทำการ</label>
            @php $selectedDays = old('working_days', $settings['working_days']); @endphp
            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                @foreach($weekDays as $num => $dayLabel)
                    <label class="tp-well" style="display:flex; align-items:center; gap:8px; padding:10px 14px; cursor:pointer;">
                        <input type="checkbox" name="working_days[]" value="{{ $num }}"
                               {{ in_array($num, (array) $selectedDays) ? 'checked' : '' }}
                               style="accent-color:#5689b8; width:15px; height:15px; cursor:pointer;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $dayLabel }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ===== ปุ่มบันทึกล่าง ===== --}}
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary" style="font-weight:700; padding:11px 22px;">
                <i class="fas fa-save"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
