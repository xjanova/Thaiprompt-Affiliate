{{--
/**
 * Activity Item Component - รายการกิจกรรมแต่ละรายการ
 *
 * @props
 * @param string $icon Font Awesome icon class (required)
 * @param string $iconBg Background color class สำหรับ icon (required)
 * @param string $title หัวข้อกิจกรรม (required)
 * @param string $time เวลาที่ผ่านมา (required)
 * @param string|null $description คำอธิบายเพิ่มเติม (optional)
 * @param string|null $href URL ที่จะ link ไป (optional)
 *
 * @example พื้นฐาน
 * <x-arrow-x.activity.item
 *     icon="fas fa-shopping-cart"
 *     iconBg="bg-green-500"
 *     title="มีคำสั่งซื้อใหม่ #1234"
 *     time="5 นาทีที่แล้ว"
 * />
 *
 * @example พร้อม description และ link
 * <x-arrow-x.activity.item
 *     icon="fas fa-user-plus"
 *     iconBg="bg-blue-500"
 *     title="ผู้ใช้ใหม่ลงทะเบียน"
 *     description="john@example.com"
 *     time="15 นาทีที่แล้ว"
 *     href="{{ route('admin.users.show', $user->id) }}"
 * />
 *
 * @tip ใช้สี icon ที่แตกต่างกันเพื่อแยกประเภทกิจกรรม (green=success, blue=info, red=warning)
 */
--}}

@props([
    'icon',
    'iconBg',
    'title',
    'time',
    'description' => null,
    'href' => null,
])

@if($href)
    <a href="{{ $href }}" class="flex items-start gap-3 p-3 glass-neu rounded-lg hover:bg-white/20 transition-all hover:scale-[1.02] cursor-pointer">
@else
    <div class="flex items-start gap-3 p-3 glass-neu rounded-lg">
@endif
        {{-- Icon --}}
        <div class="flex-shrink-0 w-10 h-10 {{ $iconBg }} rounded-lg flex items-center justify-center shadow-lg">
            <i class="{{ $icon }} text-white drop-shadow"></i>
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate drop-shadow">
                {{ $title }}
            </p>

            @if($description)
                <p class="text-xs text-white/70 line-clamp-2 mt-1">
                    {{ $description }}
                </p>
            @endif

            <p class="text-xs text-white/50 mt-1">
                {{ $time }}
            </p>
        </div>

        {{-- Arrow Indicator (if link) --}}
        @if($href)
            <div class="flex-shrink-0">
                <i class="fas fa-chevron-right text-white/50 text-xs drop-shadow"></i>
            </div>
        @endif

@if($href)
    </a>
@else
    </div>
@endif

<style>
/**
 * Glass Neumorphic Effect - สำหรับ activity items
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
