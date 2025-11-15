{{--
/**
 * Alert V3 Component - การแจ้งเตือนแบบ Glass Fusion
 *
 * @props
 * @param string $type ประเภท alert (success|error|warning|info) default: info
 * @param bool $dismissible สามารถปิดได้ (default: true)
 * @param string|null $title หัวข้อ (optional)
 * @param string|null $icon FontAwesome icon class (optional - auto ถ้าไม่ระบุ)
 *
 * @example
 * <x-arrow-x.alert-v3 type="success" title="สำเร็จ">
 *     บันทึกข้อมูลเรียบร้อยแล้ว
 * </x-arrow-x.alert-v3>
 *
 * @example Auto-dismiss
 * <x-arrow-x.alert-v3 type="error" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
 *     เกิดข้อผิดพลาด
 * </x-arrow-x.alert-v3>
 *
 * @tip Component นี้รองรับ dark mode อัตโนมัติ
 */
--}}

@props([
    'type' => 'info',
    'dismissible' => true,
    'title' => null,
    'icon' => null,
])

@php
    // กำหนดสีและ icon ตามประเภท
    $types = [
        'success' => [
            'gradient' => 'from-green-500 to-emerald-600',
            'icon' => 'fas fa-check-circle',
            'bg' => 'bg-green-500/20',
        ],
        'error' => [
            'gradient' => 'from-red-500 to-pink-600',
            'icon' => 'fas fa-exclamation-circle',
            'bg' => 'bg-red-500/20',
        ],
        'warning' => [
            'gradient' => 'from-yellow-500 to-orange-600',
            'icon' => 'fas fa-exclamation-triangle',
            'bg' => 'bg-yellow-500/20',
        ],
        'info' => [
            'gradient' => 'from-cyan-500 to-blue-600',
            'icon' => 'fas fa-info-circle',
            'bg' => 'bg-blue-500/20',
        ],
    ];

    $config = $types[$type] ?? $types['info'];
    $iconClass = $icon ?? $config['icon'];
@endphp

<div {{ $attributes->merge(['class' => "glass-fusion border border-white/30 rounded-xl overflow-hidden shadow-lg {$config['bg']}"]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">

    <div class="p-4">
        <div class="flex items-start gap-3">
            {{-- Icon --}}
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br {{ $config['gradient'] }} rounded-xl flex items-center justify-center shadow-lg">
                <i class="{{ $iconClass }} text-white drop-shadow"></i>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                @if($title)
                    <h4 class="text-white font-bold mb-1 drop-shadow">{{ $title }}</h4>
                @endif
                <div class="text-white/90 text-sm drop-shadow">
                    {{ $slot }}
                </div>
            </div>

            {{-- Close Button --}}
            @if($dismissible)
                <button @click="show = false"
                        type="button"
                        class="flex-shrink-0 p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95">
                    <i class="fas fa-times text-white/80 drop-shadow"></i>
                </button>
            @endif
        </div>
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
