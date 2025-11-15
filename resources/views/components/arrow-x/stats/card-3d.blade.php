{{--
/**
 * 3D Stat Card Component - Card สำหรับแสดงสถิติแบบ 3D พร้อม Glow Effect
 *
 * @props
 * @param string|int $value ค่าสถิติที่จะแสดง (เช่น "1,234" หรือ "฿45,678")
 * @param string $label คำอธิบายสถิติ (เช่น "ผู้ใช้งานทั้งหมด")
 * @param string $icon Font Awesome icon class (default: "fas fa-chart-line")
 * @param string $gradient Tailwind gradient classes (default: "from-blue-500 to-cyan-600")
 * @param float|null $change เปอร์เซ็นต์การเปลี่ยนแปลง (+12.5 หรือ -3.2) (optional)
 * @param string|null $href URL ที่จะ link ไป (optional)
 *
 * @example พื้นฐาน
 * <x-arrow-x.stats.card-3d
 *     value="1,234"
 *     label="ผู้ใช้งานทั้งหมด"
 *     icon="fas fa-users"
 *     gradient="from-blue-500 to-cyan-600"
 * />
 *
 * @example พร้อม change percentage
 * <x-arrow-x.stats.card-3d
 *     :value="number_format($totalUsers)"
 *     label="ผู้ใช้งานทั้งหมด"
 *     icon="fas fa-users"
 *     gradient="from-blue-500 to-cyan-600"
 *     :change="12.5"
 *     href="{{ route('admin.users.index') }}"
 * />
 *
 * @example Negative change
 * <x-arrow-x.stats.card-3d
 *     value="89"
 *     label="คำสั่งซื้อใหม่"
 *     icon="fas fa-shopping-cart"
 *     gradient="from-purple-500 to-pink-600"
 *     :change="-3.2"
 * />
 *
 * @tip ใช้ gradient ต่างๆ เพื่อแยกประเภทสถิติ (blue=users, green=success, purple=premium, orange=revenue)
 * @tip ถ้าไม่ใส่ href card จะไม่สามารถคลิกได้
 */
--}}

@props([
    'value',
    'label',
    'icon' => 'fas fa-chart-line',
    'gradient' => 'from-blue-500 to-cyan-600',
    'change' => null,
    'href' => null,
])

@php
    $isPositiveChange = $change !== null && $change >= 0;
    $changeColor = $isPositiveChange ? 'from-green-400 to-emerald-600' : 'from-red-400 to-pink-600';
@endphp

@if($href)
    <a href="{{ $href }}" class="block group perspective-1000">
@else
    <div class="group perspective-1000">
@endif

        <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
            {{-- Glow Effect พื้นหลังเรืองแสง --}}
            <div class="absolute inset-0 bg-gradient-to-br {{ $gradient }} rounded-2xl blur-xl opacity-60 group-hover:opacity-80 transition-opacity"></div>

            {{-- Card Body --}}
            <div class="relative glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
                <div class="flex items-center justify-between mb-4">
                    {{-- Icon with Gradient Background --}}
                    <div class="w-12 h-12 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center shadow-lg transform group-hover:rotate-6 transition-transform">
                        <i class="{{ $icon }} text-white text-xl drop-shadow"></i>
                    </div>

                    {{-- Change Badge --}}
                    @if($change !== null)
                        <span class="px-2 py-1 bg-gradient-to-r {{ $changeColor }} text-white rounded-lg text-xs font-bold shadow-lg">
                            {{ $change > 0 ? '+' : '' }}{{ number_format($change, 1) }}%
                        </span>
                    @endif
                </div>

                {{-- Value ค่าสถิติหลัก --}}
                <h3 class="text-3xl font-bold text-white mb-1 drop-shadow-lg">
                    {{ $value }}
                </h3>

                {{-- Label คำอธิบาย --}}
                <p class="text-sm text-white/90 drop-shadow">{{ $label }}</p>

                {{-- Link Indicator --}}
                @if($href)
                    <div class="mt-3 flex items-center text-xs text-white/70 group-hover:text-white transition-colors">
                        <span class="drop-shadow">ดูรายละเอียด</span>
                        <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform drop-shadow"></i>
                    </div>
                @endif
            </div>
        </div>

@if($href)
    </a>
@else
    </div>
@endif

<style>
/**
 * Perspective Effect - สร้างมุมมอง 3D
 */
.perspective-1000 {
    perspective: 1000px;
}

/**
 * 3D Rotation - หมุน Y axis
 */
.rotate-y-2 {
    transform: rotateY(2deg);
}

/**
 * Glass Fusion Effect - ความโปร่งใสพร้อม backdrop blur
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
