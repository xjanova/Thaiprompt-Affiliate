{{-- ป้ายสถานะการจ้างงาน · ตัวแปร: $status --}}
@php
    $staffStatus = [
        'active' => ['ทำงานอยู่', 'ok'],
        'probation' => ['ทดลองงาน', 'warn'],
        'notice_period' => ['แจ้งลาออก', 'info'],
        'resigned' => ['ลาออกแล้ว', 'muted'],
        'terminated' => ['เลิกจ้าง', 'bad'],
        'retired' => ['เกษียณ', 'muted'],
    ];
    [$ssLabel, $ssTone] = $staffStatus[$status] ?? [$status ?: '—', 'muted'];
@endphp
<x-seller-kit.pill :tone="$ssTone">{{ $ssLabel }}</x-seller-kit.pill>
