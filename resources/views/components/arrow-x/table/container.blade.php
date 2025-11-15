{{--
/**
 * Table Container V3 Component - กรอบตารางแบบ Glass Fusion
 *
 * @props
 * @param string|null $title หัวข้อตาราง (optional)
 * @param bool $rounded มุมโค้ง (default: true)
 *
 * @example
 * <x-arrow-x.table.container title="รายการผู้ใช้งาน">
 *     <table>...</table>
 * </x-arrow-x.table.container>
 *
 * @tip Component นี้รองรับ dark mode อัตโนมัติ
 * @tip ใช้ glass-fusion effect พร้อม responsive scrolling
 */
--}}

@props([
    'title' => null,
    'rounded' => true,
])

<div {{ $attributes->merge(['class' => 'glass-fusion border border-white/30 overflow-hidden ' . ($rounded ? 'rounded-2xl' : '')]) }}>
    @if($title)
        {{-- Table Header --}}
        <div class="px-6 py-4 border-b border-white/30">
            <h3 class="text-lg font-bold text-white drop-shadow-lg">
                {{ $title }}
            </h3>
        </div>
    @endif

    {{-- Table Content with Horizontal Scroll --}}
    <div class="overflow-x-auto">
        {{ $slot }}
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

/**
 * Custom Scrollbar
 */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.4);
}
</style>
