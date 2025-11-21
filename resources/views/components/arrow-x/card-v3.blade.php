{{--
/**
 * Card V3 Component - การ์ดแบบ 3D Glass Fusion
 *
 * @props
 * @param string|null $title หัวข้อการ์ด (optional)
 * @param string $gradient สีเฉดไล่สำหรับ glow effect (default: from-blue-500 to-purple-600)
 * @param bool $hover เอฟเฟกต์ hover 3D (default: true)
 * @param bool $glow เอฟเฟกต์เรืองแสง (default: true)
 * @param string|null $icon FontAwesome icon class (optional)
 *
 * @slot default เนื้อหาการ์ด
 * @slot footer ส่วน footer (optional)
 *
 * @example
 * <x-arrow-x.card-v3 title="สถิติผู้ใช้งาน" icon="fas fa-users" gradient="from-green-500 to-emerald-600">
 *     <p>จำนวนผู้ใช้: 1,234</p>
 * </x-arrow-x.card-v3>
 *
 * @tip Component นี้มี 3D transform effect เมื่อ hover
 */
--}}

@props([
    'title' => null,
    'gradient' => 'from-blue-500 to-purple-600',
    'hover' => true,
    'glow' => true,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'group relative']) }}>
    {{-- Glow Effect Background --}}
    @if($glow)
        <div class="absolute inset-0 bg-gradient-to-br {{ $gradient }} rounded-2xl blur-xl opacity-60 transition-all duration-500 group-hover:opacity-80 -z-10"></div>
    @endif

    {{-- Card Container --}}
    <div class="glass-fusion border border-white/30 rounded-2xl overflow-hidden shadow-2xl transition-all duration-500 {{ $hover ? 'transform group-hover:scale-[1.02]' : '' }}">
        {{-- Header --}}
        @if($title || $icon)
            <div class="px-6 py-4 border-b border-white/30 flex items-center gap-3">
                @if($icon)
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center shadow-lg">
                        <i class="{{ $icon }} text-white text-xl drop-shadow"></i>
                    </div>
                @endif

                @if($title)
                    <h3 class="text-lg font-bold text-white drop-shadow-lg">
                        {{ $title }}
                    </h3>
                @endif
            </div>
        @endif

        {{-- Body --}}
        <div class="p-6 text-white">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @if(isset($footer))
            <div class="px-6 py-4 border-t border-white/30">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>

<style>
/**
 * Glass Fusion Effect
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
