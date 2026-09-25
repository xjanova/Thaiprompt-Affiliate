{{-- เมนูย่อยระบบพนักงานของร้าน (ใช้ร่วมทุกหน้าในโฟลเดอร์ seller/staff) --}}
<x-seller-kit.nav :items="[
    ['label' => 'พนักงาน', 'route' => 'seller.staff.index', 'match' => ['seller.staff.index', 'seller.staff.show', 'seller.staff.edit', 'seller.staff.create'], 'icon' => '👥'],
    ['label' => 'แผนก', 'route' => 'seller.staff.departments', 'icon' => '🏢'],
    ['label' => 'ตำแหน่ง', 'route' => 'seller.staff.positions', 'icon' => '💼'],
    ['label' => 'กะการทำงาน', 'route' => 'seller.staff.shifts', 'icon' => '🕘'],
]" />
