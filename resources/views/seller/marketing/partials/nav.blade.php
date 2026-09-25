{{-- เมนูย่อยศูนย์การตลาดของร้าน (ใช้ร่วม: marketing / coupons / store-rating / achievements) --}}
<x-seller-kit.nav :items="[
    ['label' => 'ภาพรวมการตลาด', 'route' => 'seller.marketing.index', 'icon' => '📢'],
    ['label' => 'คูปองร้าน', 'route' => 'seller.coupons.index', 'match' => 'seller.coupons.*', 'icon' => '🎟️'],
    ['label' => 'โปรโมทสินค้าใหม่', 'route' => 'seller.marketing.select-product', 'icon' => '🚀'],
    ['label' => 'ประวัติโปรโมท', 'route' => 'seller.marketing.promotion-history', 'icon' => '🕘'],
    ['label' => 'Official Shop', 'route' => 'seller.marketing.official-products', 'icon' => '🏅'],
    ['label' => 'การแจ้งเตือน', 'route' => 'seller.marketing.warnings', 'icon' => '⚠️'],
    ['label' => 'คะแนนร้าน', 'route' => 'seller.store-rating.index', 'match' => 'seller.store-rating.*', 'icon' => '⭐'],
    ['label' => 'รางวัลร้าน', 'route' => 'seller.achievements.index', 'match' => 'seller.achievements.*', 'icon' => '🏆'],
]" />
