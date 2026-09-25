{{--
    แท็บเมนูไรเดอร์ (ใช้ทุกหน้าใน /user/rider)
    ตัวแปร: $rider (Rider|null), $active (dashboard|jobs|earnings|documents|settings|status|register)
    - อนุมัติแล้ว/ถูกระงับ → แดชบอร์ด · งาน · รายได้ · เอกสาร · ตั้งค่า
    - รอตรวจ/ถูกปฏิเสธ → สถานะใบสมัคร · เอกสาร · แก้ไขใบสมัคร
--}}
@php
    $navActive = $active ?? '';
    $navStatus = $rider?->status;
    if ($navStatus === null) {
        $navItems = [];
    } elseif (in_array($navStatus, ['approved', 'suspended'], true)) {
        $navItems = [
            ['key' => 'dashboard', 'route' => 'user.rider.dashboard', 'icon' => 'fa-gauge-high', 'label' => 'แดชบอร์ด'],
            ['key' => 'jobs', 'route' => 'user.rider.jobs', 'icon' => 'fa-route', 'label' => 'งาน'],
            ['key' => 'earnings', 'route' => 'user.rider.earnings', 'icon' => 'fa-coins', 'label' => 'รายได้'],
            ['key' => 'documents', 'route' => 'user.rider.documents', 'icon' => 'fa-folder-open', 'label' => 'เอกสาร'],
            ['key' => 'settings', 'route' => 'user.rider.settings', 'icon' => 'fa-sliders', 'label' => 'ตั้งค่า'],
        ];
    } else {
        $navItems = [
            ['key' => 'status', 'route' => 'user.rider.status', 'icon' => 'fa-hourglass-half', 'label' => 'สถานะใบสมัคร'],
            ['key' => 'documents', 'route' => 'user.rider.documents', 'icon' => 'fa-folder-open', 'label' => 'เอกสาร'],
            ['key' => 'register', 'route' => 'user.rider.register', 'icon' => 'fa-pen-to-square', 'label' => $navStatus === 'pending' ? 'แก้ไขใบสมัคร' : 'ส่งใบสมัครใหม่'],
        ];
    }
@endphp
@if(count($navItems) > 0)
    <nav class="rd-nav" aria-label="เมนูไรเดอร์">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}" class="{{ $navActive === $item['key'] ? 'on' : '' }}" @if($navActive === $item['key']) aria-current="page" @endif>
                <i class="fas {{ $item['icon'] }}"></i> {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@endif
