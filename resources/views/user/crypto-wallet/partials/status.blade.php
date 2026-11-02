{{--
    ชิปสถานะ (ธุรกรรม · คำขอถอน · การแลกเปลี่ยน) — ครอบคลุมทุกค่าใน enum ของ 3 ตาราง
    ใช้: @include('user.crypto-wallet.partials.status', ['status' => $tx->status])
--}}
@php
    [$cwTone, $cwLabel] = match ((string) ($status ?? '')) {
        'completed' => ['ok', 'สำเร็จ'],
        'confirmed' => ['ok', 'ยืนยันแล้ว'],
        'approved' => ['info', 'อนุมัติแล้ว'],
        'pending' => ['warn', 'รอดำเนินการ'],
        'verifying' => ['warn', 'กำลังตรวจสอบ'],
        'processing' => ['warn', 'กำลังดำเนินการ'],
        'broadcasted' => ['warn', 'ส่งขึ้นเครือข่ายแล้ว'],
        'confirming' => ['warn', 'รอการยืนยัน'],
        'failed' => ['bad', 'ล้มเหลว'],
        'rejected' => ['bad', 'ถูกปฏิเสธ'],
        'cancelled' => ['', 'ยกเลิกแล้ว'],
        'refunded' => ['info', 'คืนเงินแล้ว'],
        default => ['', (string) ($status ?? '-')],
    };
@endphp
<span class="nvm-chip {{ $cwTone ? 'nvm-chip--'.$cwTone : '' }}">{{ $cwLabel }}</span>
