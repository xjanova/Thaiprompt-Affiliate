{{--
    Arrow X Card - Gradient Card Component

    Card แบบ gradient background สำหรับเน้นข้อมูลสำคัญ

    @props
    - title: string|null - ชื่อหัวข้อ [optional]
    - gradient: string - ชุด gradient (purple/blue/green/orange/pink/red/dark) [default: purple]
    - textColor: string - สีตัวอักษร (white/dark) [default: white]
    - pattern: bool - เพิ่ม pattern background [default: false]
    - shadow: bool - เพิ่มเงา [default: true]
--}}

@props([
    'title' => null,
    'gradient' => 'purple',
    'textColor' => 'white',
    'pattern' => false,
    'shadow' => true,
])

@php
    // กำหนด gradient classes
    $gradientClasses = [
        'purple' => 'from-purple-500 via-pink-500 to-orange-500',
        'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
        'green' => 'from-green-500 via-emerald-500 to-teal-500',
        'orange' => 'from-orange-500 via-red-500 to-pink-500',
        'pink' => 'from-pink-500 via-rose-500 to-red-500',
        'red' => 'from-red-500 via-orange-500 to-yellow-500',
        'dark' => 'from-gray-800 via-gray-900 to-black',
    ];

    $gradientClass = $gradientClasses[$gradient] ?? $gradientClasses['purple'];

    // กำหนด text color classes
    $textClasses = $textColor === 'white'
        ? 'text-white'
        : 'text-gray-900 dark:text-gray-100';

    // Shadow class
    $shadowClass = $shadow ? 'shadow-2xl' : '';
@endphp

<div {{ $attributes->merge(['class' => "relative rounded-2xl overflow-hidden bg-gradient-to-br {$gradientClass} {$shadowClass} transition-all duration-300 hover:shadow-3xl hover:scale-105"]) }}>
    {{-- Pattern overlay (if enabled) --}}
    @if($pattern)
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    @endif

    <div class="relative z-10 p-6 {{ $textClasses }}">
        {{-- Title --}}
        @if($title)
            <h3 class="text-xl font-bold mb-4">
                {{ $title }}
            </h3>
        @endif

        {{-- Content --}}
        <div>
            {{ $slot }}
        </div>
    </div>
</div>
