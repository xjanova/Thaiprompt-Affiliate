{{--
 | กล่องแสดงข้อผิดพลาดจากการตรวจฟอร์ม (validation) ของแผงผู้ขาย V4
 | ข้อความสำเร็จ/ผิดพลาดทั่วไป layout seller-v4 แสดงเป็น toast ให้แล้ว
 --}}
@if($errors->any())
    <div class="tp-card" role="alert" style="padding:15px 18px; background:color-mix(in srgb, var(--tp-bad, #d9534f) 10%, var(--card-bg)); border:1px solid color-mix(in srgb, var(--tp-bad, #d9534f) 32%, transparent);">
        <div style="display:flex; gap:12px; align-items:flex-start;">
            <span style="font-size:20px; line-height:1;">⚠️</span>
            <div style="min-width:0;">
                <div style="font-weight:800; font-size:13.5px; color:var(--ink);">กรุณาตรวจสอบข้อมูลอีกครั้ง</div>
                <ul style="margin:6px 0 0; padding-left:18px; color:var(--tp-bad, #d9534f); font-size:12.5px; display:flex; flex-direction:column; gap:2px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
