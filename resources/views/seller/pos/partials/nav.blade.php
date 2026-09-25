{{-- เมนูย่อยของระบบ POS (ใช้ร่วมทุกหน้าในโฟลเดอร์ seller/pos) --}}
<x-seller-kit.nav :items="[
    ['label' => 'ภาพรวม', 'route' => 'seller.pos.index', 'icon' => '🏠'],
    ['label' => 'ขายหน้าร้าน', 'route' => 'seller.pos.terminal', 'icon' => '🛒'],
    ['label' => 'รายการขาย', 'route' => 'seller.pos.transactions', 'match' => 'seller.pos.transactions*', 'icon' => '🧾'],
    ['label' => 'เซสชันการขาย', 'route' => 'seller.pos.sessions', 'match' => 'seller.pos.sessions*', 'icon' => '🔐'],
    ['label' => 'อุปกรณ์', 'route' => 'seller.pos.devices', 'match' => 'seller.pos.devices*', 'icon' => '📱'],
    ['label' => 'เครื่อง POS', 'route' => 'seller.pos.terminals', 'match' => ['seller.pos.terminals*', 'seller.pos.api-keys*'], 'icon' => '🖥️'],
    ['label' => 'หมวดหมู่', 'route' => 'seller.pos.categories', 'match' => 'seller.pos.categories*', 'icon' => '🗂️'],
    ['label' => 'โฆษณาจอลูกค้า', 'route' => 'seller.pos.advertisements', 'match' => 'seller.pos.advertisements*', 'icon' => '📺'],
    ['label' => 'ฉลากบาร์โค้ด', 'route' => 'seller.pos.labels.index', 'match' => 'seller.pos.labels.*', 'icon' => '🏷️'],
    ['label' => 'รายงาน', 'route' => 'seller.pos.analytics', 'icon' => '📈'],
    ['label' => 'ตั้งค่า', 'route' => 'seller.pos.settings', 'icon' => '⚙️'],
]" />
