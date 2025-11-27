{{--
/**
 * Provider Sidebar V3 Component - Sidebar สำหรับ Provider Dashboard
 *
 * @example
 * <x-arrow-x.provider-sidebar />
 */
--}}

{{-- Sidebar Container --}}
<aside x-data="{ collapsed: $store.sidebar.collapsed }"
       x-show="$store.sidebar.open"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed md:relative inset-y-0 left-0 z-50 md:z-auto flex flex-col h-full glass-sidebar border-r border-white/30 shadow-2xl"
       :class="collapsed ? 'w-20' : 'w-72'"
       @click.outside="$store.sidebar.close()">

    {{-- Logo Header --}}
    <div class="flex items-center justify-between p-4 border-b border-white/20">
        <a href="{{ route('provider.dashboard') }}" class="flex items-center gap-3" x-show="!collapsed">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-user-tie text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-white drop-shadow">Provider</h1>
                <p class="text-xs text-white/60">ระบบผู้ให้บริการ</p>
            </div>
        </a>
        <a href="{{ route('provider.dashboard') }}" class="flex items-center justify-center" x-show="collapsed">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-user-tie text-white text-lg"></i>
            </div>
        </a>

        {{-- Collapse Toggle (Desktop) --}}
        <button @click="$store.sidebar.toggleCollapse()"
                class="hidden md:flex p-2 rounded-lg hover:bg-white/20 transition-all">
            <i class="fas fa-chevron-left text-white transition-transform duration-200"
               :class="collapsed ? 'rotate-180' : ''"></i>
        </button>

        {{-- Close Button (Mobile) --}}
        <button @click="$store.sidebar.close()"
                class="md:hidden p-2 rounded-lg hover:bg-white/20 transition-all">
            <i class="fas fa-times text-white"></i>
        </button>
    </div>

    {{-- Menu Items --}}
    <nav class="flex-1 overflow-y-auto p-4 space-y-2">
        {{-- Dashboard --}}
        <a href="{{ route('provider.dashboard') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.dashboard') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.dashboard') ? 'bg-emerald-500/50' : 'bg-white/10 group-hover:bg-emerald-500/30' }}">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">แดชบอร์ด</span>
        </a>

        {{-- Bookings --}}
        <a href="{{ route('provider.bookings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.bookings.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.bookings.*') ? 'bg-blue-500/50' : 'bg-white/10 group-hover:bg-blue-500/30' }}">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">รายการจอง</span>
            @php
                $provider = \App\Models\ServiceProvider::where('user_id', auth()->id())->first();
                $pendingCount = $provider ? $provider->bookings()->whereIn('status', ['notifying_provider', 'waiting_provider'])->count() : 0;
            @endphp
            @if($pendingCount > 0)
                <span x-show="!collapsed"
                      class="ml-auto px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full animate-pulse">
                    {{ $pendingCount }}
                </span>
            @endif
        </a>

        {{-- Pending Jobs --}}
        <a href="{{ route('provider.bookings.pending') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.bookings.pending') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.bookings.pending') ? 'bg-yellow-500/50' : 'bg-white/10 group-hover:bg-yellow-500/30' }}">
                <i class="fas fa-bell"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">งานรอตอบรับ</span>
        </a>

        {{-- Divider --}}
        <div class="border-t border-white/20 my-4"></div>

        {{-- Earnings --}}
        <a href="{{ route('provider.earnings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.earnings.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.earnings.*') ? 'bg-green-500/50' : 'bg-white/10 group-hover:bg-green-500/30' }}">
                <i class="fas fa-wallet"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">รายได้</span>
        </a>

        {{-- Statistics --}}
        <a href="{{ route('provider.stats.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.stats.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.stats.*') ? 'bg-purple-500/50' : 'bg-white/10 group-hover:bg-purple-500/30' }}">
                <i class="fas fa-chart-bar"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">สถิติ</span>
        </a>

        {{-- Divider --}}
        <div class="border-t border-white/20 my-4"></div>

        {{-- Profile --}}
        <a href="{{ route('provider.profile.edit') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.profile.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.profile.*') ? 'bg-indigo-500/50' : 'bg-white/10 group-hover:bg-indigo-500/30' }}">
                <i class="fas fa-user-circle"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">โปรไฟล์</span>
        </a>

        {{-- Settings --}}
        <a href="{{ route('provider.settings.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('provider.settings.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all
                        {{ request()->routeIs('provider.settings.*') ? 'bg-gray-500/50' : 'bg-white/10 group-hover:bg-gray-500/30' }}">
                <i class="fas fa-cog"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">ตั้งค่า</span>
        </a>
    </nav>

    {{-- Bottom Section --}}
    <div class="p-4 border-t border-white/20">
        {{-- Back to User Dashboard --}}
        <a href="{{ route('user.dashboard') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-white/80 hover:bg-white/10 hover:text-white group">
            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center group-hover:bg-blue-500/30 transition-all">
                <i class="fas fa-arrow-left"></i>
            </div>
            <span x-show="!collapsed" class="font-medium">กลับหน้า User</span>
        </a>
    </div>
</aside>

{{-- Overlay for Mobile --}}
<div x-show="$store.sidebar.open"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="$store.sidebar.close()"
     class="md:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40">
</div>

<style>
/**
 * Glass Sidebar Effect
 */
.glass-sidebar {
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
</style>
