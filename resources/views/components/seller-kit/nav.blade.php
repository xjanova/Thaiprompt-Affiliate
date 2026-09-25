{{--
 | แถบเมนูย่อยแบบแท็บ (เลื่อนแนวนอนได้บนมือถือ) ของแผงร้านค้า V4
 | ใช้: <x-seller-kit.nav :items="[
 |        ['label' => 'ภาพรวม', 'route' => 'seller.pos.index', 'icon' => '🏠'],
 |        ['label' => 'รายการขาย', 'route' => 'seller.pos.transactions', 'match' => 'seller.pos.transactions*'],
 |      ]" />
 | match = pattern สำหรับ request()->routeIs() (ไม่ใส่ = ใช้ชื่อ route ตรง ๆ)
 | รายการที่ route ไม่มีอยู่จริงจะถูกข้ามอัตโนมัติ (กันหน้า 500)
 --}}
@props(['items' => []])

<nav aria-label="เมนูย่อย" class="tp-card" style="padding:6px; display:flex; gap:4px; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:none;">
    @foreach($items as $kitItem)
        @continue(! \Illuminate\Support\Facades\Route::has($kitItem['route']))
        @php
            $kitActive = request()->routeIs($kitItem['match'] ?? $kitItem['route']);
        @endphp
        <a href="{{ route($kitItem['route'], $kitItem['params'] ?? []) }}"
           @if($kitActive) aria-current="page" @endif
           style="flex:none; display:inline-flex; align-items:center; gap:6px; padding:9px 13px; border-radius:12px; font-size:12.5px; font-weight:700; text-decoration:none; white-space:nowrap; transition:all .15s ease; {{ $kitActive ? 'color:var(--tp-on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:var(--raise);' : 'color:var(--ink2);' }}">
            @if(! empty($kitItem['icon']))<span aria-hidden="true">{{ $kitItem['icon'] }}</span>@endif
            <span>{{ $kitItem['label'] }}</span>
        </a>
    @endforeach
</nav>
