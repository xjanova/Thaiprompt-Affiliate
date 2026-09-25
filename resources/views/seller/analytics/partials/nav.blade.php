{{-- เมนูย่อยของหน้าวิเคราะห์ร้าน (ใช้ร่วมทุกหน้าในโฟลเดอร์ seller/analytics) --}}
<x-seller-kit.nav :items="[
    ['label' => 'ภาพรวม', 'route' => 'seller.analytics.index', 'icon' => '📊'],
    ['label' => 'AI วิเคราะห์', 'route' => 'seller.analytics.ai-insights', 'icon' => '🤖'],
    ['label' => 'กลุ่มลูกค้า', 'route' => 'seller.analytics.segmentation', 'icon' => '👥'],
    ['label' => 'ลูกค้ากลับมาซื้อ', 'route' => 'seller.analytics.cohort', 'icon' => '🔁'],
    ['label' => 'อันดับสินค้า', 'route' => 'seller.analytics.products', 'icon' => '🏆'],
    ['label' => 'ตั้งค่า', 'route' => 'seller.analytics.settings', 'icon' => '⚙️'],
]" />
