{{-- ป้ายสถานะรายการ TPIX — รับ $status --}}
@php
    [$statusLabel, $statusColor] = [
        'pending' => ['รอดำเนินการ', 'var(--accent1)'],
        'broadcasted' => ['ส่งเข้าเครือข่ายแล้ว', 'var(--accent1)'],
        'confirming' => ['รอยืนยันบนเครือข่าย', 'var(--accent1)'],
        'confirmed' => ['สำเร็จ', '#5aa07e'],
        'completed' => ['สำเร็จ', '#5aa07e'],
        'failed' => ['ไม่สำเร็จ', '#d9534f'],
        'cancelled' => ['ยกเลิกแล้ว', '#d9534f'],
    ][$status] ?? [$status, 'var(--ink2)'];
@endphp
<span class="tp-pill" style="background:color-mix(in srgb, {{ $statusColor }} 16%, transparent); color:{{ $statusColor }}; font-size:11.5px; font-weight:700;">{{ $statusLabel }}</span>
