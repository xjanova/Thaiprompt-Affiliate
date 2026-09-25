{{--
 | การ์ดแจ้งผลการทำรายการ (session success/error/warning) + ข้อผิดพลาดของฟอร์ม
 | layout admin-v4 แสดง toast อยู่แล้ว — การ์ดนี้ค้างไว้ให้อ่านได้นานกว่า (สำคัญกับข้อความยาวเรื่องเงิน/งาน)
--}}
@foreach (['success' => ['--w-ok', 'fa-circle-check'], 'error' => ['--w-bad', 'fa-circle-exclamation'], 'warning' => ['--w-warn', 'fa-triangle-exclamation']] as $flashKey => [$flashVar, $flashIcon])
    @if (session($flashKey))
        <div class="tp-card" role="alert" style="padding:14px 18px; border-left:4px solid var({{ $flashVar }});">
            <div style="display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:var(--ink); line-height:1.6;">
                <i class="fas {{ $flashIcon }}" style="color:var({{ $flashVar }}); margin-top:3px;"></i>
                <span>{{ session($flashKey) }}</span>
            </div>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="tp-card" role="alert" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
        <div style="font-size:13.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">
            <i class="fas fa-triangle-exclamation" style="color:var(--w-bad);"></i> บันทึกไม่สำเร็จ กรุณาตรวจสอบข้อมูล
        </div>
        <ul style="margin:0; padding-left:20px; font-size:13px; color:var(--ink2); line-height:1.7;">
            @foreach ($errors->all() as $flashError)
                <li>{{ $flashError }}</li>
            @endforeach
        </ul>
    </div>
@endif
