{{-- แท็บสลับหน้าจัดการการจัดส่ง (รอจัดส่ง / จัดส่งแล้ว / สำเร็จ) — ส่ง $active เข้ามา --}}
<nav class="sv4-tabs" aria-label="สถานะการจัดส่ง">
    <a href="{{ route('seller.orders.pending-shipping') }}" class="sv4-tab {{ ($active ?? '') === 'pending' ? 'on' : '' }}">📋 รอจัดส่ง</a>
    <a href="{{ route('seller.orders.shipped') }}" class="sv4-tab {{ ($active ?? '') === 'shipped' ? 'on' : '' }}">🚚 จัดส่งแล้ว</a>
    <a href="{{ route('seller.orders.delivered') }}" class="sv4-tab {{ ($active ?? '') === 'delivered' ? 'on' : '' }}">✅ ส่งสำเร็จ</a>
    <a href="{{ route('seller.orders.index') }}" class="sv4-tab">🧾 คำสั่งซื้อทั้งหมด</a>
</nav>
