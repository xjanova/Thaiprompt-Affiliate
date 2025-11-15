{{--
/**
 * Modal V3 Component - Modal Dialog แบบ Glass Fusion
 *
 * @props
 * @param string $id Unique ID สำหรับ modal
 * @param string|null $title หัวข้อ modal (optional)
 * @param string $size ขนาด modal (sm|md|lg|xl|full) default: md
 * @param bool $closeButton แสดงปุ่มปิด (default: true)
 *
 * @slot default เนื้อหา modal
 * @slot footer ส่วน footer (optional)
 *
 * @example
 * <x-arrow-x.modal-v3 id="userModal" title="เพิ่มผู้ใช้งาน">
 *     <p>เนื้อหา modal...</p>
 *
 *     <x-slot:footer>
 *         <button>บันทึก</button>
 *         <button>ยกเลิก</button>
 *     </x-slot:footer>
 * </x-arrow-x.modal-v3>
 *
 * @tip ใช้ Alpine.js เปิด/ปิด: x-data="{ userModal: false }" @click="userModal = true"
 */
--}}

@props([
    'id' => 'modal',
    'title' => null,
    'size' => 'md',
    'closeButton' => true,
])

@php
    // กำหนดความกว้างตามขนาด
    $sizes = [
        'sm' => 'max-w-md',
        'md' => 'max-w-2xl',
        'lg' => 'max-w-4xl',
        'xl' => 'max-w-6xl',
        'full' => 'max-w-full mx-4',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div x-show="{{ $id }}"
     x-cloak
     @keydown.escape.window="{{ $id }} = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="{{ $id }}-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="{{ $id }}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="{{ $id }} = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- Modal Container --}}
    <div class="flex min-h-full items-center justify-center p-4">
        {{-- Modal Content --}}
        <div x-show="{{ $id }}"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="glass-fusion border border-white/30 rounded-2xl shadow-2xl w-full {{ $sizeClass }} overflow-hidden">

            {{-- Header --}}
            @if($title || $closeButton)
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/30">
                    @if($title)
                        <h3 id="{{ $id }}-title" class="text-xl font-bold text-white drop-shadow-lg">
                            {{ $title }}
                        </h3>
                    @endif

                    @if($closeButton)
                        <button @click="{{ $id }} = false"
                                type="button"
                                class="p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95">
                            <i class="fas fa-times text-white drop-shadow"></i>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-4 text-white">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="px-6 py-4 border-t border-white/30 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
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

/**
 * x-cloak - ซ่อน element ก่อน Alpine.js โหลดเสร็จ
 */
[x-cloak] {
    display: none !important;
}
</style>
