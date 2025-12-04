{{--
/**
 * Pinnable Menu Item Component - เมนูที่สามารถปักหมุดได้
 *
 * @props
 * @param string $menuKey รหัสเมนู (unique identifier)
 * @param string $label ชื่อเมนู
 * @param string $icon ไอคอน (Font Awesome class หรือ emoji)
 * @param string $url URL ของเมนู
 * @param string|null $route Route name (optional)
 * @param string $dashboardType ประเภท dashboard (admin, user, seller)
 * @param bool $isActive เป็นเมนูที่ active อยู่หรือไม่
 * @param bool $isPinned ถูกปักหมุดอยู่หรือไม่ (optional - จะตรวจสอบจาก store)
 * @param mixed $badge Badge text/count (optional)
 *
 * @example
 * <x-menu.pinnable-menu-item
 *     menuKey="admin.dashboard"
 *     label="แดชบอร์ด"
 *     icon="fas fa-home"
 *     url="/admin"
 *     dashboardType="admin"
 *     :isActive="request()->routeIs('admin.dashboard')"
 * />
 *
 * @tip Component นี้รองรับ:
 * - Desktop: hover แสดง pin button
 * - Mobile: long press เพื่อปักหมุด
 * - Pinned items จะมีสีเข้มกว่าปกติ
 */
--}}

@props([
    'menuKey' => '',
    'label' => '',
    'icon' => '',
    'url' => '#',
    'route' => null,
    'dashboardType' => 'admin',
    'isActive' => false,
    'isPinned' => null,
    'badge' => null,
    'showInPinnedSection' => false, // แสดงใน Pinned Section (สีเข้มกว่า)
])

@php
    // สร้าง unique ID สำหรับ Alpine x-data
    $uniqueId = 'menu_' . md5($menuKey);

    // เช็คว่าเป็น Font Awesome icon หรือ emoji
    $isFontAwesome = str_starts_with($icon, 'fa');
@endphp

{{-- Menu Item Container with pin interaction --}}
<div
    x-data="{
        showPinButton: false,
        longPressTimer: null,
        menuKey: '{{ $menuKey }}',
        dashboardType: '{{ $dashboardType }}',
        menuData: {
            label: '{{ addslashes($label) }}',
            icon: '{{ addslashes($icon) }}',
            url: '{{ $url }}',
            route: '{{ $route }}'
        },

        // ตรวจสอบว่าปักหมุดอยู่หรือไม่
        get isPinned() {
            return $store.pinnedMenus.isPinned(this.dashboardType, this.menuKey);
        },

        // Desktop: แสดง pin button เมื่อ hover
        onMouseEnter() {
            if ($store.sidebar.isDesktop) {
                this.showPinButton = true;
            }
        },

        onMouseLeave() {
            this.showPinButton = false;
        },

        // Mobile: Long press เพื่อ toggle pin
        onTouchStart(e) {
            if (!$store.sidebar.isDesktop) {
                this.longPressTimer = setTimeout(() => {
                    this.togglePin();
                    // Haptic feedback (if available)
                    if (navigator.vibrate) {
                        navigator.vibrate(50);
                    }
                }, 500); // 500ms long press
            }
        },

        onTouchEnd() {
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },

        // Toggle pin status
        togglePin() {
            const wasPinned = this.isPinned;
            $store.pinnedMenus.toggle(this.dashboardType, this.menuKey, this.menuData);

            // แสดง feedback
            this.showPinFeedback(!wasPinned);
        },

        // แสดง feedback เมื่อปักหมุด/ยกเลิก
        showPinFeedback(pinned) {
            // สร้าง toast notification (ถ้ามี)
            const message = pinned ? 'ปักหมุดเมนูแล้ว 📌' : 'ยกเลิกปักหมุดแล้ว';
            if (window.showToast) {
                window.showToast(message, pinned ? 'success' : 'info');
            }
        }
    }"
    @mouseenter="onMouseEnter()"
    @mouseleave="onMouseLeave()"
    @touchstart="onTouchStart($event)"
    @touchend="onTouchEnd()"
    @touchcancel="onTouchEnd()"
    class="relative group"
>
    {{-- Menu Link --}}
    <a
        href="{{ $url }}"
        @click="$store.sidebar.closeOnMenuClick()"
        class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform relative
            @if($showInPinnedSection)
                {{-- Pinned Section: สีเข้มกว่า --}}
                {{ $isActive
                    ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30 scale-105'
                    : 'bg-gradient-to-r from-blue-600/70 to-purple-700/70 text-white shadow-md hover:shadow-lg hover:scale-105' }}
            @else
                {{-- Normal Section --}}
                {{ $isActive
                    ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105'
                    : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}
            @endif
        "
        :class="{
            'ring-2 ring-amber-400/50': isPinned && !{{ $showInPinnedSection ? 'true' : 'false' }}
        }"
    >
        {{-- Icon --}}
        @if($icon)
            @if($isFontAwesome)
                <i class="{{ $icon }} w-5 text-center drop-shadow"></i>
            @else
                <span class="w-5 text-center text-lg drop-shadow">{{ $icon }}</span>
            @endif
        @endif

        {{-- Label --}}
        <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap flex-1">
            {{ $label }}
        </span>

        {{-- Badge --}}
        @if($badge)
            <span x-show="$store.sidebar.shouldExpand" x-transition class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full shadow-lg">
                {{ $badge }}
            </span>
        @endif

        {{-- Pin Indicator (แสดงเมื่อปักหมุดไว้ - เฉพาะ Normal Section) --}}
        @if(!$showInPinnedSection)
            <span
                x-show="$store.sidebar.shouldExpand && isPinned"
                x-transition
                class="text-amber-400 drop-shadow"
            >
                <i class="fas fa-thumbtack text-xs"></i>
            </span>
        @endif
    </a>

    {{-- Pin/Unpin Button (Desktop - แสดงเมื่อ hover) --}}
    <button
        x-show="showPinButton && $store.sidebar.shouldExpand"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-75"
        @click.prevent.stop="togglePin()"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg transition-all z-10"
        :class="isPinned
            ? 'bg-amber-500/80 hover:bg-amber-600 text-white'
            : 'bg-white/20 hover:bg-white/40 text-white/80 hover:text-white'"
        :title="isPinned ? 'ยกเลิกปักหมุด' : 'ปักหมุด'"
    >
        <i class="fas fa-thumbtack text-xs" :class="{ 'rotate-45': !isPinned }"></i>
    </button>

    {{-- Mobile Long Press Hint (แสดงครั้งแรก) --}}
    <div
        x-show="!$store.sidebar.isDesktop && !isPinned"
        x-cloak
        class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] text-white/50 whitespace-nowrap opacity-0 group-active:opacity-100 transition-opacity pointer-events-none"
    >
        กดค้างเพื่อปักหมุด
    </div>
</div>
