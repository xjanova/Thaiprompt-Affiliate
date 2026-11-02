{{--
    แท็บเมนูไรเดอร์ธีมโนวา (ใช้ทุกหน้าใน /user/rider)
    ตัวแปร: $rider (Rider|null), $active (dashboard|jobs|earnings|documents|settings|status|register)
    - อนุมัติแล้ว/ถูกระงับ → แดชบอร์ด · งาน · รายได้ · เอกสาร · ตั้งค่า
    - รอตรวจ/ถูกปฏิเสธ/ไม่ใช้งาน → สถานะใบสมัคร · เอกสาร · แก้ไขใบสมัคร
    - ยังไม่สมัคร → ไม่มีแท็บ
--}}
@php
    $navActive = $active ?? '';
    $navStatus = $rider?->status;
    if ($navStatus === null) {
        $navItems = [];
    } elseif (in_array($navStatus, ['approved', 'suspended'], true)) {
        $navItems = [
            ['key' => 'dashboard', 'route' => 'user.rider.dashboard', 'icon' => 'home', 'label' => 'แดชบอร์ด'],
            ['key' => 'jobs', 'route' => 'user.rider.jobs', 'icon' => 'scooter', 'label' => 'งาน'],
            ['key' => 'earnings', 'route' => 'user.rider.earnings', 'icon' => 'cash', 'label' => 'รายได้'],
            ['key' => 'documents', 'route' => 'user.rider.documents', 'icon' => 'id', 'label' => 'เอกสาร'],
            ['key' => 'settings', 'route' => 'user.rider.settings', 'icon' => 'gear', 'label' => 'ตั้งค่า'],
        ];
    } else {
        $navItems = [
            ['key' => 'status', 'route' => 'user.rider.status', 'icon' => 'clock', 'label' => 'สถานะใบสมัคร'],
            ['key' => 'documents', 'route' => 'user.rider.documents', 'icon' => 'id', 'label' => 'เอกสาร'],
            ['key' => 'register', 'route' => 'user.rider.register', 'icon' => 'send', 'label' => $navStatus === 'pending' ? 'แก้ไขใบสมัคร' : 'ส่งใบสมัครใหม่'],
        ];
    }
@endphp
@if(count($navItems) > 0)
    <nav class="nvm-tabs" aria-label="เมนูไรเดอร์">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}" class="{{ $navActive === $item['key'] ? 'is-on' : '' }}" @if($navActive === $item['key']) aria-current="page" @endif>
                <svg class="nvm-ico"><use href="#nvm-i-{{ $item['icon'] }}"/></svg>{{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@endif
