{{--
/**
 * Language Switcher Component V3 - Premium Google Translate Integration
 *
 * Component สำหรับเปลี่ยนภาษาพร้อม Google Translate API
 * รองรับ 9 ภาษา: th, en, zh, ja, ko, vi, de, fr, es
 *
 * @props
 * - variant: string - รูปแบบ (dropdown/compact) [default: dropdown]
 * - showFlags: bool - แสดง flag icons [default: true]
 * - showText: bool - แสดงชื่อภาษา [default: true]
 *
 * @example
 * <x-arrow-x.language-switcher />
 * <x-arrow-x.language-switcher variant="compact" />
 *
 * @tip ใช้ $store.language สำหรับจัดการภาษา
 */
--}}

@props([
    'variant' => 'dropdown',
    'showFlags' => true,
    'showText' => true,
])

<div x-data="{ langOpen: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    {{-- Trigger Button (Premium Style) --}}
    <button
        @click="langOpen = !langOpen"
        type="button"
        class="p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95 relative"
        :title="$store.language.getCurrentLanguage().nativeName"
    >
        {{-- Current Language Flag --}}
        <span class="text-xl no-translate" x-text="$store.language.getCurrentLanguage().flag"></span>

        {{-- Translation Indicator (แสดงเมื่อกำลังแปล) --}}
        <div x-show="$store.language.isTranslating"
             class="absolute -top-1 -right-1 w-3 h-3 bg-gradient-to-r from-blue-400 to-purple-600 rounded-full animate-pulse shadow-lg shadow-blue-400/50">
        </div>
    </button>

    {{-- Dropdown Menu (Ultra Premium) --}}
    <div
        x-show="langOpen"
        @click.outside="langOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="absolute right-0 mt-2 w-64 glass-fusion rounded-2xl shadow-2xl border border-white/30 overflow-hidden z-50"
        x-cloak
    >
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-white/20 bg-gradient-to-r from-blue-500/20 to-purple-600/20">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-white drop-shadow no-translate">
                    <i class="fas fa-globe mr-2"></i>
                    เลือกภาษา
                </h3>

                {{-- Clear Cache Button --}}
                <button
                    @click="$store.language.clearCache()"
                    type="button"
                    class="p-1.5 rounded-lg hover:bg-white/20 transition-all text-white/80 hover:text-white"
                    title="Clear translation cache">
                    <i class="fas fa-sync text-xs"></i>
                </button>
            </div>
        </div>

        {{-- Languages List --}}
        <div class="py-2 max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-white/20">
            <template x-for="lang in $store.language.languages" :key="lang.code">
                <button
                    @click="$store.language.setLanguage(lang.code); langOpen = false"
                    type="button"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-all"
                    :class="$store.language.current === lang.code ? 'bg-gradient-to-r from-blue-500/30 to-purple-600/30' : ''"
                >
                    {{-- Flag --}}
                    <span class="text-2xl flex-shrink-0 no-translate" x-text="lang.flag"></span>

                    {{-- Language Info --}}
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-bold text-white drop-shadow no-translate" x-text="lang.nativeName"></p>
                        <p class="text-xs text-white/70 drop-shadow no-translate" x-text="lang.name"></p>
                    </div>

                    {{-- Check Icon (Current Language) --}}
                    <i x-show="$store.language.current === lang.code"
                       class="fas fa-check text-green-400 drop-shadow-lg animate-pulse"></i>
                </button>
            </template>
        </div>

        {{-- Footer Info --}}
        <div class="px-4 py-2 border-t border-white/20 bg-white/5">
            <div class="flex items-center justify-between text-xs text-white/60">
                <span class="no-translate">
                    <i class="fas fa-language mr-1"></i>
                    Google Translate
                </span>
                <span class="no-translate">
                    <span x-text="$store.language.languages.length"></span> ภาษา
                </span>
            </div>
        </div>
    </div>
</div>

<style>
/**
 * Glass Neumorphic Effect - สำหรับ button
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/**
 * Glass Fusion Effect - สำหรับ dropdown
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/**
 * Scrollbar Thin
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
