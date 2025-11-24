{{--
    Booking Status Badge Component

    แสดง badge สำหรับสถานะการจองบริการ
    รองรับ dark mode และแสดงสีตามสถานะการจอง

    @props
    - status: string - สถานะการจอง (required)
    - size: string - ขนาด (xs/sm/md/lg) [default: sm]
    - variant: string - รูปแบบ (solid/soft/outline) [default: soft]
    - showIcon: bool - แสดงไอคอน [default: true]
    - showDot: bool - แสดง dot สำหรับสถานะที่กำลังดำเนินการ [default: true]
--}}

@props([
    'status' => 'pending',
    'size' => 'sm',
    'variant' => 'soft',
    'showIcon' => true,
    'showDot' => true,
])

@php
    // กำหนดข้อมูลสำหรับแต่ละสถานะ
    $statusConfig = [
        'pending' => [
            'text' => 'รอชำระเงิน',
            'color' => 'yellow',
            'icon' => 'fa-clock',
            'animate' => false,
        ],
        'paid' => [
            'text' => 'ชำระเงินแล้ว',
            'color' => 'blue',
            'icon' => 'fa-check-circle',
            'animate' => false,
        ],
        'notifying_provider' => [
            'text' => 'กำลังแจ้งเตือนผู้ให้บริการ',
            'color' => 'indigo',
            'icon' => 'fa-bell',
            'animate' => true,
        ],
        'waiting_provider' => [
            'text' => 'รอผู้ให้บริการตอบกลับ',
            'color' => 'orange',
            'icon' => 'fa-hourglass-half',
            'animate' => true,
        ],
        'provider_accepted' => [
            'text' => 'ผู้ให้บริการรับงาน',
            'color' => 'green',
            'icon' => 'fa-user-check',
            'animate' => false,
        ],
        'provider_rejected' => [
            'text' => 'ผู้ให้บริการปฏิเสธ',
            'color' => 'red',
            'icon' => 'fa-times-circle',
            'animate' => false,
        ],
        'provider_on_way' => [
            'text' => 'กำลังเดินทาง',
            'color' => 'purple',
            'icon' => 'fa-car',
            'animate' => true,
        ],
        'in_progress' => [
            'text' => 'กำลังให้บริการ',
            'color' => 'cyan',
            'icon' => 'fa-cog fa-spin',
            'animate' => true,
        ],
        'completed' => [
            'text' => 'เสร็จสิ้น',
            'color' => 'emerald',
            'icon' => 'fa-check-double',
            'animate' => false,
        ],
        'cancelled' => [
            'text' => 'ยกเลิก',
            'color' => 'gray',
            'icon' => 'fa-ban',
            'animate' => false,
        ],
    ];

    // ดึงข้อมูลสถานะ หรือใช้ default
    $config = $statusConfig[$status] ?? [
        'text' => 'ไม่ทราบสถานะ',
        'color' => 'gray',
        'icon' => 'fa-question-circle',
        'animate' => false,
    ];

    $color = $config['color'];
    $text = $config['text'];
    $icon = $config['icon'];
    $shouldAnimate = $config['animate'] && $showDot;

    // กำหนด variant classes ตามสี
    $variantClasses = [
        'solid' => [
            'yellow' => 'bg-yellow-600 text-white',
            'blue' => 'bg-blue-600 text-white',
            'indigo' => 'bg-indigo-600 text-white',
            'orange' => 'bg-orange-600 text-white',
            'green' => 'bg-green-600 text-white',
            'red' => 'bg-red-600 text-white',
            'purple' => 'bg-purple-600 text-white',
            'cyan' => 'bg-cyan-600 text-white',
            'emerald' => 'bg-emerald-600 text-white',
            'gray' => 'bg-gray-600 text-white',
        ],
        'soft' => [
            'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
            'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
            'indigo' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300',
            'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
            'green' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
            'red' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
            'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
            'cyan' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300',
            'emerald' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300',
            'gray' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300',
        ],
        'outline' => [
            'yellow' => 'border-2 border-yellow-600 text-yellow-600 dark:text-yellow-400',
            'blue' => 'border-2 border-blue-600 text-blue-600 dark:text-blue-400',
            'indigo' => 'border-2 border-indigo-600 text-indigo-600 dark:text-indigo-400',
            'orange' => 'border-2 border-orange-600 text-orange-600 dark:text-orange-400',
            'green' => 'border-2 border-green-600 text-green-600 dark:text-green-400',
            'red' => 'border-2 border-red-600 text-red-600 dark:text-red-400',
            'purple' => 'border-2 border-purple-600 text-purple-600 dark:text-purple-400',
            'cyan' => 'border-2 border-cyan-600 text-cyan-600 dark:text-cyan-400',
            'emerald' => 'border-2 border-emerald-600 text-emerald-600 dark:text-emerald-400',
            'gray' => 'border-2 border-gray-600 text-gray-600 dark:text-gray-400',
        ],
    ];

    $colorClass = $variantClasses[$variant][$color] ?? $variantClasses['soft']['gray'];

    // กำหนด size classes
    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-1 text-xs',
        'md' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-4 py-2 text-base',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];

    // Base classes
    $baseClasses = "inline-flex items-center gap-1.5 font-semibold whitespace-nowrap rounded-full transition-all duration-200";

    // Combine all classes
    $classes = "{$baseClasses} {$colorClass} {$sizeClass}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{-- Animated dot indicator สำหรับสถานะที่กำลังดำเนินการ --}}
    @if($shouldAnimate)
        <span class="inline-block w-2 h-2 rounded-full bg-current animate-pulse"></span>
    @endif

    {{-- Icon --}}
    @if($showIcon)
        <i class="fas {{ $icon }}"></i>
    @endif

    {{-- Text --}}
    <span>{{ $text }}</span>
</span>
