{{--
/**
 * Activity List Component - รายการกิจกรรมล่าสุดพร้อม Glass Effect
 *
 * @props
 * @param string $title หัวข้อของ list (default: "กิจกรรมล่าสุด")
 * @param string $icon Font Awesome icon class (default: "fas fa-history")
 * @param string $gradient Tailwind gradient classes (default: "from-purple-500 to-pink-600")
 *
 * @slot default - รายการ activity items
 *
 * @example พื้นฐาน
 * <x-arrow-x.activity.list title="กิจกรรมล่าสุด">
 *     <x-arrow-x.activity.item
 *         icon="fas fa-shopping-cart"
 *         iconBg="bg-green-500"
 *         title="มีคำสั่งซื้อใหม่ #1234"
 *         time="5 นาทีที่แล้ว"
 *     />
 *     <x-arrow-x.activity.item
 *         icon="fas fa-user-plus"
 *         iconBg="bg-blue-500"
 *         title="ผู้ใช้ใหม่ลงทะเบียน"
 *         time="15 นาทีที่แล้ว"
 *     />
 * </x-arrow-x.activity.list>
 *
 * @example ใช้กับ loop
 * <x-arrow-x.activity.list title="กิจกรรมล่าสุด">
 *     @foreach($activities as $activity)
 *         <x-arrow-x.activity.item
 *             :icon="$activity->icon"
 *             :iconBg="$activity->iconBg"
 *             :title="$activity->title"
 *             :time="$activity->created_at->diffForHumans()"
 *         />
 *     @endforeach
 * </x-arrow-x.activity.list>
 *
 * @tip ใช้ร่วมกับ activity.item component
 */
--}}

@props([
    'title' => 'กิจกรรมล่าสุด',
    'icon' => 'fas fa-history',
    'gradient' => 'from-purple-500 to-pink-600',
])

<div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-white/30 bg-gradient-to-r {{ $gradient }}/20">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br {{ $gradient }} rounded-lg flex items-center justify-center shadow-lg">
                <i class="{{ $icon }} text-white drop-shadow"></i>
            </div>
            <h3 class="text-xl font-bold text-white drop-shadow">{{ $title }}</h3>
        </div>
    </div>

    {{-- Activity Items --}}
    <div class="p-4 space-y-3 max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
        {{ $slot }}
    </div>
</div>

<style>
/**
 * Glass Fusion Effect - ความโปร่งใสพร้อม backdrop blur
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/**
 * Custom Scrollbar - Thin scrollbar สำหรับ activity list
 */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
