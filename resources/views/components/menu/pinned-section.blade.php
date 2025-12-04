{{--
/**
 * Pinned Section Component - ส่วนแสดงเมนูที่ปักหมุดไว้
 *
 * แสดงเมนูที่ผู้ใช้ปักหมุดไว้ด้านบนสุดของ sidebar
 * สีเข้มกว่าเมนูปกติเพื่อให้เห็นชัดเจน
 *
 * @props
 * @param string $dashboardType ประเภท dashboard (admin, user, seller)
 *
 * @example
 * <x-menu.pinned-section dashboardType="admin" />
 *
 * @tip Component นี้จะซ่อนอัตโนมัติเมื่อไม่มีเมนูที่ปักหมุด
 */
--}}

@props([
    'dashboardType' => 'admin'
])

{{-- Pinned Menus Section --}}
<div
    x-data="{
        dashboardType: '{{ $dashboardType }}',

        // ดึงรายการเมนูที่ปักหมุด
        get pinnedMenus() {
            return $store.pinnedMenus.getPinnedMenus(this.dashboardType);
        },

        // ตรวจสอบว่ามีเมนูที่ปักหมุดหรือไม่
        get hasPinnedMenus() {
            return this.pinnedMenus.length > 0;
        }
    }"
    x-show="hasPinnedMenus"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    x-cloak
    class="mb-4"
>
    {{-- Section Header --}}
    <div class="flex items-center gap-2 px-3 py-2" x-show="$store.sidebar.shouldExpand" x-transition>
        <i class="fas fa-thumbtack text-amber-400 text-xs"></i>
        <span class="text-xs font-semibold text-white/70 uppercase tracking-wider">ปักหมุด</span>
        <span class="text-xs text-white/50" x-text="'(' + pinnedMenus.length + ')'"></span>

        {{-- Clear All Button --}}
        <button
            @click="if(confirm('ยกเลิกปักหมุดทั้งหมด?')) $store.pinnedMenus.clearAll(dashboardType)"
            type="button"
            class="ml-auto p-1 rounded hover:bg-white/20 text-white/40 hover:text-white/80 transition-all"
            title="ยกเลิกปักหมุดทั้งหมด"
        >
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    {{-- Pinned Menu Items --}}
    <div class="space-y-1.5">
        <template x-for="menu in pinnedMenus" :key="menu.key">
            <div
                x-data="{
                    showUnpinButton: false,
                    get isActive() {
                        // ตรวจสอบว่า URL ปัจจุบันตรงกับเมนูนี้หรือไม่
                        const currentPath = window.location.pathname;
                        const menuPath = menu.url.replace(/^\//, '');
                        return currentPath === menu.url ||
                               currentPath.startsWith('/' + menuPath + '/') ||
                               currentPath === '/' + menuPath;
                    }
                }"
                @mouseenter="if ($store.sidebar.isDesktop) showUnpinButton = true"
                @mouseleave="showUnpinButton = false"
                class="relative group"
            >
                {{-- Pinned Menu Link --}}
                <a
                    :href="menu.url"
                    @click="$store.sidebar.closeOnMenuClick()"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all transform relative"
                    :class="isActive
                        ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30 scale-105'
                        : 'bg-gradient-to-r from-blue-600/60 to-purple-700/60 text-white shadow-md hover:shadow-lg hover:from-blue-600/80 hover:to-purple-700/80 hover:scale-[1.02]'"
                >
                    {{-- Icon --}}
                    <template x-if="menu.icon">
                        <span class="w-5 text-center">
                            {{-- Check if Font Awesome or emoji --}}
                            <template x-if="menu.icon.startsWith('fa')">
                                <i :class="menu.icon + ' drop-shadow'"></i>
                            </template>
                            <template x-if="!menu.icon.startsWith('fa')">
                                <span class="text-lg drop-shadow" x-text="menu.icon"></span>
                            </template>
                        </span>
                    </template>

                    {{-- Label --}}
                    <span
                        x-show="$store.sidebar.shouldExpand"
                        x-transition
                        class="font-medium drop-shadow whitespace-nowrap flex-1 text-sm"
                        x-text="menu.label"
                    ></span>

                    {{-- Pin Icon --}}
                    <i
                        x-show="$store.sidebar.shouldExpand"
                        x-transition
                        class="fas fa-thumbtack text-amber-300/80 text-xs drop-shadow"
                    ></i>
                </a>

                {{-- Unpin Button (Desktop hover) --}}
                <button
                    x-show="showUnpinButton && $store.sidebar.shouldExpand"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-75"
                    x-transition:enter-end="opacity-100 scale-100"
                    @click.prevent.stop="$store.pinnedMenus.unpin(dashboardType, menu.key)"
                    type="button"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg bg-red-500/80 hover:bg-red-600 text-white transition-all z-10"
                    title="ยกเลิกปักหมุด"
                >
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </template>
    </div>

    {{-- Divider --}}
    <div class="mt-4 border-t border-white/20"></div>
</div>
