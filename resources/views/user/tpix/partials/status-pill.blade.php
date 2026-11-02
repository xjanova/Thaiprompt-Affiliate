{{-- ป้ายสถานะรายการ TPIX (ธีมโนวา) — รับ $status --}}
@php
    [$statusLabel, $statusTone] = [
        'pending' => ['รอดำเนินการ', 'warn'],
        'broadcasted' => ['ส่งเข้าเครือข่ายแล้ว', 'warn'],
        'confirming' => ['รอยืนยันบนเครือข่าย', 'warn'],
        'confirmed' => ['สำเร็จ', 'ok'],
        'completed' => ['สำเร็จ', 'ok'],
        'failed' => ['ไม่สำเร็จ', 'bad'],
        'cancelled' => ['ยกเลิกแล้ว', 'bad'],
    ][$status] ?? [$status, ''];
@endphp
<span class="nvm-chip {{ $statusTone ? 'nvm-chip--'.$statusTone : '' }}">{{ $statusLabel }}</span>
