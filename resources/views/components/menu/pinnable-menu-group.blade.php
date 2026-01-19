{{--
/**
 * Pinnable Menu Group Component - เมนูหลักที่มี submenu และสามารถปักหมุดได้
 *
 * @props
 * @param string $menuKey รหัสเมนู (unique identifier)
 * @param string $label ชื่อเมนู
 * @param string $icon ไอคอน (Font Awesome class หรือ emoji)
 * @param string $dashboardType ประเภท dashboard (admin, user, seller)
 * @param bool $isActive เป็นเมนูที่ active อยู่หรือไม่
 * @param array $submenu รายการ submenu items
 * @param string $defaultUrl URL เริ่มต้นเมื่อคลิกจาก pinned section
 *
 * @example
 * <x-menu.pinnable-menu-group
 *     menuKey="admin.users"
 *     label="ผู้ใช้งาน"
 *     icon="fas fa-users"
 *     dashboardType="admin"
 *     :isActive="request()->routeIs('admin.users.*')"
 *     :submenu="[['label' => 'รายชื่อ', 'url' => '/admin/users'], ...]"
 *     defaultUrl="/admin/users"
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
    'dashboardType' => 'admin',
    'isActive' => false,
    'submenu' => [],
    'defaultUrl' => '#',
    'activeGradient' => 'from-blue-500/50 to-purple-600/50',
])

@php
    // เช็คว่าเป็น Font Awesome icon หรือ emoji
    $isFontAwesome = str_starts_with($icon, 'fa');
@endphp

{{-- Menu Group Container with pin interaction --}}
<div
    class="space-y-1"
    data-menu-active="{{ $isActive ? 'true' : 'false' }}"
    data-menu-type="parent"
    data-menu-key="{{ $menuKey }}"
    x-data="{
        menuOpen: {{ $isActive ? 'true' : 'false' }},
        showPinButton: false,
        longPressTimer: null,
        menuKey: '{{ $menuKey }}',
        dashboardType: '{{ $dashboardType }}',
        menuData: {
            label: '{{ addslashes($label) }}',
            icon: '{{ addslashes($icon) }}',
            url: '{{ $defaultUrl }}',
            route: null,
            hasSubmenu: true
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
            const message = pinned ? 'ปักหมุดเมนูแล้ว 📌' : 'ยกเลิกปักหมุดแล้ว';
            if (window.showToast) {
                window.showToast(message, pinned ? 'success' : 'info');
            }
        }
    }"
>
    {{-- Menu Header Button --}}
    <div
        class="relative"
        @mouseenter="onMouseEnter()"
        @mouseleave="onMouseLeave()"
        @touchstart="onTouchStart($event)"
        @touchend="onTouchEnd()"
        @touchcancel="onTouchEnd()"
    >
        <button
            @click="menuOpen = !menuOpen"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform"
            :class="{
                'bg-gradient-to-r {{ $activeGradient }} text-white shadow-lg': {{ $isActive ? 'true' : 'false' }},
                'glass-neu text-white/90 hover:bg-white/20 hover:scale-105': !{{ $isActive ? 'true' : 'false' }},
                'ring-2 ring-amber-400/50': isPinned
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
            <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">
                {{ $label }}
            </span>

            {{-- Pin Indicator --}}
            <span
                x-show="$store.sidebar.shouldExpand && isPinned"
                x-transition
                class="text-amber-400 drop-shadow mr-1"
            >
                <i class="fas fa-thumbtack text-xs"></i>
            </span>

            {{-- Chevron --}}
            <i x-show="$store.sidebar.shouldExpand && menuOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
            <i x-show="$store.sidebar.shouldExpand && !menuOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
        </button>

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
            class="absolute right-10 top-1/2 -translate-y-1/2 p-1.5 rounded-lg transition-all z-10"
            :class="isPinned
                ? 'bg-amber-500/80 hover:bg-amber-600 text-white'
                : 'bg-white/20 hover:bg-white/40 text-white/80 hover:text-white'"
            :title="isPinned ? 'ยกเลิกปักหมุด' : 'ปักหมุด'"
        >
            <i class="fas fa-thumbtack text-xs" :class="{ 'rotate-45': !isPinned }"></i>
        </button>
    </div>

    {{-- Submenu --}}
    <div x-show="menuOpen" x-collapse x-cloak class="ml-8 space-y-1">
        @foreach($submenu as $child)
            @php
                $childUrl = $child['url'] ?? '#';
                $childPath = ltrim($childUrl, '/');
                $isChildActive = request()->is($childPath) || request()->is($childPath . '/*');
            @endphp
            <a href="{{ $childUrl }}"
               @click="$store.sidebar.closeOnMenuClick()"
               data-menu-active="{{ $isChildActive ? 'true' : 'false' }}"
               data-menu-type="submenu"
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ $isChildActive ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                @if(isset($child['icon']))
                    <i class="{{ $child['icon'] }} w-4 text-center drop-shadow"></i>
                @else
                    <div class="w-1.5 h-1.5 rounded-full {{ $isChildActive ? 'bg-white' : 'bg-white/50' }}"></div>
                @endif
                <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">{{ $child['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
