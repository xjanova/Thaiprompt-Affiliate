{{-- Navigation สำหรับหมวดหมู่ดูดวง --}}
<nav class="relative z-20 bg-black/20 backdrop-blur-xl border-b border-white/10 sticky top-0">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            {{-- โลโก้ --}}
            <a href="{{ route('horoscope.home') }}"
               class="flex items-center gap-2 text-white font-bold text-lg hover:text-purple-300 transition shrink-0">
                <span class="text-2xl">🔮</span>
                <span class="hidden sm:inline">ดูดวง</span>
            </a>

            {{-- เมนูหมวดหมู่ (Desktop) --}}
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('horoscope.home') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.home') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10 hover:text-white' }}">
                    🏠 หน้าแรก
                </a>
                <a href="{{ route('horoscope.daily.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.daily.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10 hover:text-white' }}">
                    ⭐ ดวงรายวัน
                </a>
                <a href="{{ route('horoscope.tarot.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.tarot.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10 hover:text-white' }}">
                    🃏 ไพ่ทาโรต์
                </a>
                <a href="{{ route('horoscope.numerology.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.numerology.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10 hover:text-white' }}">
                    🔢 เลขศาสตร์
                </a>
                <a href="{{ route('horoscope.dream.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.dream.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10 hover:text-white' }}">
                    💭 ทำนายฝัน
                </a>
            </div>

            {{-- ปุ่มกลับหน้าแรก --}}
            <a href="/"
               class="text-purple-300/70 hover:text-white text-sm transition hidden md:flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับหน้าหลัก
            </a>

            {{-- เมนู Mobile Toggle --}}
            <button @click="$dispatch('toggle-mobile-menu')"
                    class="md:hidden text-white p-2 rounded-lg hover:bg-white/10 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        {{-- เมนู Mobile --}}
        <div x-data="{ open: false }"
             @toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-collapse
             class="md:hidden pb-4">
            <div class="flex flex-col gap-1">
                <a href="{{ route('horoscope.home') }}"
                   class="px-4 py-3 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.home') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10' }}">
                    🏠 หน้าแรก
                </a>
                <a href="{{ route('horoscope.daily.index') }}"
                   class="px-4 py-3 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.daily.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10' }}">
                    ⭐ ดวงรายวัน 12 ราศี
                </a>
                <a href="{{ route('horoscope.tarot.index') }}"
                   class="px-4 py-3 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.tarot.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10' }}">
                    🃏 ไพ่ทาโรต์
                </a>
                <a href="{{ route('horoscope.numerology.index') }}"
                   class="px-4 py-3 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.numerology.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10' }}">
                    🔢 เลขศาสตร์
                </a>
                <a href="{{ route('horoscope.dream.index') }}"
                   class="px-4 py-3 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs('horoscope.dream.*') ? 'bg-purple-600/50 text-white' : 'text-purple-200/80 hover:bg-white/10' }}">
                    💭 ทำนายฝัน
                </a>
                <div class="border-t border-white/10 mt-2 pt-2">
                    <a href="/" class="px-4 py-3 rounded-lg text-sm text-purple-300/70 hover:text-white hover:bg-white/10 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
