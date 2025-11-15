{{--
    Arrow X Modal Component

    Modal dialog แบบ modern พร้อม backdrop และ animations

    @props
    - id: string - Unique ID สำหรับ modal
    - title: string|null - หัวข้อ modal [optional]
    - size: string - ขนาด (sm/md/lg/xl/full) [default: md]
    - closeButton: bool - แสดงปุ่มปิด [default: true]
    - backdrop: string - ประเภท backdrop (blur/dark/light) [default: blur]
    - position: string - ตำแหน่ง (center/top) [default: center]

    @slots
    - header: หัวข้อ modal (override title)
    - footer: ส่วนท้าย modal (ปุ่มต่างๆ)
    - default: เนื้อหา modal

    @example
    <x-arrow-x.modal id="exampleModal" title="ตัวอย่าง Modal">
        <p>เนื้อหา modal...</p>
        <x-slot:footer>
            <x-arrow-x.button>ตกลง</x-arrow-x.button>
        </x-slot:footer>
    </x-arrow-x.modal>

    // เปิด modal ด้วย Alpine.js:
    <button @click="$dispatch('open-modal', 'exampleModal')">เปิด</button>
--}}

@props([
    'id' => 'modal',
    'title' => null,
    'size' => 'md',
    'closeButton' => true,
    'backdrop' => 'blur',
    'position' => 'center',
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => 'max-w-md',
        'md' => 'max-w-2xl',
        'lg' => 'max-w-4xl',
        'xl' => 'max-w-6xl',
        'full' => 'max-w-full mx-4',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Position classes
    $positionClasses = [
        'center' => 'items-center',
        'top' => 'items-start pt-20',
    ];

    $positionClass = $positionClasses[$position] ?? $positionClasses['center'];

    // Backdrop classes
    $backdropClasses = [
        'blur' => 'bg-gray-900/50 backdrop-blur-md',
        'dark' => 'bg-gray-900/80',
        'light' => 'bg-white/80',
    ];

    $backdropClass = $backdropClasses[$backdrop] ?? $backdropClasses['blur'];
@endphp

<div
    x-data="{ open: false }"
    @open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    @close-modal.window="if ($event.detail === '{{ $id }}') open = false"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    {{ $attributes }}
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 {{ $backdropClass }}"
    ></div>

    {{-- Modal Container --}}
    <div class="flex min-h-screen {{ $positionClass }} justify-center px-4 py-8">
        {{-- Modal Content --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            @click.stop
            class="relative w-full {{ $sizeClass }} bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
        >
            {{-- Header --}}
            @if($title || isset($header) || $closeButton)
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex-1">
                        @if(isset($header))
                            {{ $header }}
                        @elseif($title)
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $title }}
                            </h3>
                        @endif
                    </div>

                    @if($closeButton)
                        <button
                            @click="open = false"
                            class="ml-4 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            aria-label="ปิด"
                        >
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-6 max-h-[calc(100vh-16rem)] overflow-y-auto">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
