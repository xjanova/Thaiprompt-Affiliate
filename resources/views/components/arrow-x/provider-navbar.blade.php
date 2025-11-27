{{--
/**
 * Provider Navbar V3 Component - Top Navigation Bar สำหรับ Provider Dashboard
 *
 * @example
 * <x-arrow-x.provider-navbar />
 */
--}}

<header class="glass-fusion border-b border-white/30 flex items-center justify-between px-4 md:px-6 relative z-10"
         style="height: var(--arrow-x-navbar-height, 64px)">
    {{-- Left Section: Logo & Title --}}
    <div class="flex items-center gap-4">
        {{-- Mobile Menu Toggle --}}
        <button @click="$store.sidebar.toggle()"
                type="button"
                class="md:hidden p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95">
            <i class="fas fa-bars text-white text-lg drop-shadow"></i>
        </button>

        {{-- Provider Badge --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-user-tie text-white"></i>
            </div>
            <div class="hidden sm:block">
                <h1 class="text-lg font-bold text-white drop-shadow-lg">
                    @yield('page-title', 'Provider Dashboard')
                </h1>
                <p class="text-xs text-white/70">ระบบผู้ให้บริการ</p>
            </div>
        </div>
    </div>

    {{-- Right Section: Actions --}}
    <div class="flex items-center gap-2 md:gap-4">
        {{-- Status Toggle --}}
        @php
            $provider = \App\Models\ServiceProvider::where('user_id', auth()->id())->first();
        @endphp
        @if($provider && $provider->verification_status === 'approved')
            <div x-data="{ status: '{{ $provider->status }}' }"
                 class="hidden sm:flex items-center gap-2">
                <span class="text-sm text-white/80">สถานะ:</span>
                <button @click="
                    status = status === 'available' ? 'offline' : 'available';
                    fetch('{{ route('provider.toggle-availability') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    }).then(r => r.json()).then(d => {
                        if (d.success) $dispatch('notify', { type: 'success', message: d.message });
                    });
                "
                class="px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
                :class="status === 'available'
                    ? 'bg-green-500 text-white hover:bg-green-600'
                    : 'bg-gray-500/50 text-white/80 hover:bg-gray-500'">
                    <i class="fas fa-circle text-xs mr-1"
                       :class="status === 'available' ? 'text-green-200' : 'text-gray-300'"></i>
                    <span x-text="status === 'available' ? 'พร้อมรับงาน' : 'ออฟไลน์'"></span>
                </button>
            </div>
        @endif

        {{-- Dark Mode Toggle --}}
        <button @click="$store.theme.toggle()"
                type="button"
                class="p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                :title="$store.theme.isDark ? 'เปลี่ยนเป็นโหมดสว่าง' : 'เปลี่ยนเป็นโหมดมืด'">
            <i x-show="!$store.theme.isDark" class="fas fa-moon text-white drop-shadow"></i>
            <i x-show="$store.theme.isDark" class="fas fa-sun text-yellow-300 drop-shadow"></i>
        </button>

        {{-- User Profile Dropdown --}}
        @php
            $user = Auth::user();
        @endphp
        <div x-data="{ profileOpen: false }" class="relative">
            <button @click="profileOpen = !profileOpen"
                    type="button"
                    class="flex items-center gap-2 p-2 pr-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-105 active:scale-95">
                {{-- Avatar --}}
                <img src="{{ $user->profile_picture_url }}"
                     alt="{{ $user->name }}"
                     class="w-8 h-8 rounded-lg object-cover shadow-lg"
                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=10b981&color=fff&size=64';">
                <span class="hidden md:block text-white font-medium text-sm drop-shadow">{{ $user->name }}</span>
                <i class="fas fa-chevron-down text-white/60 text-xs drop-shadow transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''"></i>
            </button>

            {{-- Profile Dropdown --}}
            <div x-show="profileOpen"
                 x-cloak
                 @click.outside="profileOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute top-full right-0 mt-2 w-64 glass-dropdown rounded-2xl shadow-2xl border border-white/30 overflow-hidden z-[9999]">

                {{-- User Info Header --}}
                <div class="px-4 py-4 bg-gradient-to-br from-emerald-600/30 to-cyan-600/30 border-b border-white/20">
                    <div class="flex items-center gap-3">
                        <img src="{{ $user->profile_picture_url }}"
                             alt="{{ $user->name }}"
                             class="w-12 h-12 rounded-xl object-cover shadow-lg"
                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=10b981&color=fff&size=96';">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-white text-sm drop-shadow truncate">{{ $user->name }}</p>
                            <p class="text-xs text-white/70 truncate">{{ $user->email }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 bg-emerald-500/80 rounded-full text-xs font-medium text-white mt-1">
                                <i class="fas fa-user-tie mr-1"></i>
                                ผู้ให้บริการ
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Menu Items --}}
                <div class="px-2 py-2 border-b border-white/20">
                    <a href="{{ route('provider.profile.edit') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-emerald-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">โปรไฟล์</span>
                    </a>

                    <a href="{{ route('provider.settings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-emerald-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">ตั้งค่า</span>
                    </a>

                    <a href="{{ route('user.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-blue-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">กลับหน้า User</span>
                    </a>
                </div>

                {{-- Logout --}}
                <div class="px-2 py-2">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-red-500/20 transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-red-500/30 group-hover:text-red-300 transition-all">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span class="text-white text-sm drop-shadow group-hover:text-red-300">ออกจากระบบ</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

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
 * Glass Dropdown Effect - เข้มขึ้นสำหรับ dropdown menus
 */
.glass-dropdown {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

/**
 * Glass Neumorphic Effect - สำหรับ buttons
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
