{{-- หน้าแสดงสัญลักษณ์ตามหมวดหมู่ --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="dreamCategory()" x-init="init()">

    {{-- ==================== Header ==================== --}}
    <section class="relative py-10 md:py-14">
        <div class="container mx-auto px-4">

            {{-- Breadcrumb --}}
            <div class="mb-6">
                <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                    <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                    <span>/</span>
                    <a href="{{ route('horoscope.dream.index') }}" class="hover:text-purple-200 transition">ทำนายฝัน</a>
                    <span>/</span>
                    <span class="text-purple-200/80">{{ $category->name_th }}</span>
                </nav>
            </div>

            {{-- Category Header Card --}}
            <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-white/10 overflow-hidden">
                {{-- เรืองแสงพื้นหลัง --}}
                <div class="absolute top-0 right-0 w-48 h-48 rounded-bl-full opacity-15"
                     style="background: linear-gradient(135deg, {{ $category->color_hex ?? '#818cf8' }}, transparent);"></div>

                <div class="relative flex flex-col sm:flex-row items-center gap-5">
                    {{-- ไอคอน --}}
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl border border-white/10 shrink-0"
                         style="background: linear-gradient(135deg, {{ $category->color_hex ?? '#818cf8' }}30, {{ $category->color_hex ?? '#818cf8' }}10); box-shadow: 0 0 30px {{ $category->color_hex ?? '#818cf8' }}25;">
                        {{ $category->icon }}
                    </div>

                    {{-- ข้อมูลหมวด --}}
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl md:text-3xl font-black text-white">ทำนายฝัน — {{ $category->name_th }}</h1>
                        @if($category->description_th)
                            <p class="text-purple-300/60 text-sm mt-1 max-w-lg">{{ $category->description_th }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/70">
                                📖 {{ $symbols->total() }} สัญลักษณ์
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ตัวกรอง ==================== --}}
    <section class="container mx-auto px-4 mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-3">
            {{-- ค้นหาในหมวด --}}
            <div class="relative flex-1 w-full sm:w-auto">
                <input type="text"
                       x-model="filterQuery"
                       placeholder="🔍 ค้นหาในหมวดนี้..."
                       class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-purple-300/30 text-sm focus:outline-none focus:border-indigo-500/50 transition">
            </div>

            {{-- ตัวกรอง ลางดี/ร้าย --}}
            <div class="flex gap-2">
                <button @click="omenFilter = 'all'"
                        :class="omenFilter === 'all' ? 'bg-white/15 border-white/30 text-white' : 'bg-white/5 border-white/5 text-purple-300/50 hover:text-white'"
                        class="px-3 py-2 rounded-xl text-xs font-bold border transition">
                    ทั้งหมด
                </button>
                <button @click="omenFilter = 'good'"
                        :class="omenFilter === 'good' ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-300' : 'bg-white/5 border-white/5 text-purple-300/50 hover:text-white'"
                        class="px-3 py-2 rounded-xl text-xs font-bold border transition">
                    ✅ ลางดี
                </button>
                <button @click="omenFilter = 'bad'"
                        :class="omenFilter === 'bad' ? 'bg-amber-500/20 border-amber-500/30 text-amber-300' : 'bg-white/5 border-white/5 text-purple-300/50 hover:text-white'"
                        class="px-3 py-2 rounded-xl text-xs font-bold border transition">
                    ⚠️ ระวัง
                </button>
            </div>
        </div>
    </section>

    {{-- ==================== รายการสัญลักษณ์ ==================== --}}
    <section class="container mx-auto px-4 mb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($symbols as $symbol)
                <div class="symbol-card bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 hover:bg-white/10 transition-all duration-200 cursor-pointer"
                     data-keyword="{{ $symbol->keyword_th }}"
                     data-omen="{{ $symbol->is_good_omen ? 'good' : 'bad' }}"
                     x-show="matchFilter('{{ addslashes($symbol->keyword_th) }}', {{ $symbol->is_good_omen ? 'true' : 'false' }})"
                     x-transition
                     @click="showDetail({
                         keyword: '{{ addslashes($symbol->keyword_th) }}',
                         meaning: '{{ addslashes($symbol->meaning_th) }}',
                         lucky_numbers: {{ json_encode($symbol->lucky_numbers ?? []) }},
                         is_good_omen: {{ $symbol->is_good_omen ? 'true' : 'false' }},
                         intensity: {{ $symbol->intensity }},
                         category_icon: '{{ $category->icon }}'
                     })">

                    <div class="flex items-start gap-4">
                        {{-- สถานะ ลางดี/ร้าย --}}
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg
                                    {{ $symbol->is_good_omen ? 'bg-emerald-500/20 border border-emerald-500/20' : 'bg-amber-500/20 border border-amber-500/20' }}">
                            {{ $symbol->is_good_omen ? '✅' : '⚠️' }}
                        </div>

                        {{-- เนื้อหา --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-white font-bold text-sm">ฝันเห็น{{ $symbol->keyword_th }}</h3>
                                {{-- ความเข้มข้น --}}
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <div class="w-2.5 h-1 rounded-full {{ $i <= $symbol->intensity ? 'bg-indigo-500' : 'bg-white/10' }}"></div>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-purple-300/50 text-xs line-clamp-2">{{ $symbol->meaning_th }}</p>
                        </div>

                        {{-- เลขเด็ด --}}
                        @if(!empty($symbol->lucky_numbers))
                            <div class="shrink-0 flex flex-col gap-1 items-end">
                                @foreach(array_slice($symbol->lucky_numbers, 0, 3) as $num)
                                    <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 rounded-lg text-xs font-mono font-bold min-w-[28px] text-center">{{ $num }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ไม่พบผลลัพธ์ (เมื่อ filter) --}}
        <div x-show="isFilterActive && visibleCount === 0" x-transition class="text-center py-12">
            <div class="text-4xl mb-3">🔍</div>
            <p class="text-white font-bold">ไม่พบสัญลักษณ์ที่ตรงกับตัวกรอง</p>
            <p class="text-purple-300/50 text-sm mt-1">ลองเปลี่ยนคำค้นหาหรือตัวกรอง</p>
        </div>

        {{-- Pagination --}}
        @if($symbols->hasPages())
            <div class="mt-8">
                {{ $symbols->links() }}
            </div>
        @endif
    </section>

    {{-- ==================== Modal รายละเอียดสัญลักษณ์ ==================== --}}
    <div x-show="detailSymbol"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="detailSymbol = null">
        <div x-show="detailSymbol"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-3xl p-6 md:p-8 max-w-md w-full shadow-2xl">
            <template x-if="detailSymbol">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl" x-text="detailSymbol.category_icon || '{{ $category->icon }}'"></span>
                            <h3 class="text-white text-xl font-black" x-text="'ฝันเห็น' + detailSymbol.keyword"></h3>
                        </div>
                        <button @click="detailSymbol = null" class="text-purple-300/50 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="mb-4">
                        <span x-show="detailSymbol.is_good_omen" class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-sm font-bold">✅ ลางดี</span>
                        <span x-show="!detailSymbol.is_good_omen" class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-sm font-bold">⚠️ ควรระวัง</span>
                    </div>

                    <div class="bg-white/5 rounded-2xl p-4 mb-4">
                        <h4 class="text-purple-300/60 text-xs font-bold mb-2">ความหมาย</h4>
                        <p class="text-purple-100 text-sm leading-relaxed" x-text="detailSymbol.meaning"></p>
                    </div>

                    <template x-if="detailSymbol.lucky_numbers && detailSymbol.lucky_numbers.length > 0">
                        <div class="mb-4">
                            <h4 class="text-purple-300/60 text-xs font-bold mb-2">🎯 เลขเด็ด</h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="num in detailSymbol.lucky_numbers" :key="num">
                                    <span class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 bg-gradient-to-br from-amber-500/30 to-orange-500/30 border border-amber-500/30 rounded-xl text-amber-200 font-mono font-black text-lg shadow-lg shadow-amber-500/10" x-text="num"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center gap-2 text-purple-300/50 text-xs">
                        <span>ความเข้มข้น:</span>
                        <div class="flex gap-0.5">
                            <template x-for="i in 5" :key="i">
                                <div class="w-4 h-1.5 rounded-full transition" :class="i <= detailSymbol.intensity ? 'bg-indigo-500' : 'bg-white/10'"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ==================== Sidebar หมวดหมู่อื่น ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>📂</span> หมวดหมู่อื่นๆ
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
            @foreach($allCategories as $cat)
                <a href="{{ route('horoscope.dream.category', $cat->slug) }}"
                   class="group text-center p-3 rounded-xl transition-all duration-200
                          {{ $cat->id === $category->id
                              ? 'bg-white/15 border border-white/30 ring-2 ring-indigo-500/30'
                              : 'bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/20' }}">
                    <div class="text-xl group-hover:scale-110 transition-transform">{{ $cat->icon }}</div>
                    <div class="text-white text-[10px] mt-0.5 font-medium">{{ $cat->name_th }}</div>
                    <div class="text-purple-300/40 text-[9px]">{{ $cat->dream_symbols_count }}</div>
                </a>
            @endforeach
        </div>
    </section>

</div>

<script>
function dreamCategory() {
    return {
        filterQuery: '',
        omenFilter: 'all',
        detailSymbol: null,
        visibleCount: {{ $symbols->count() }},

        get isFilterActive() {
            return this.filterQuery.length > 0 || this.omenFilter !== 'all';
        },

        init() {
            // ติดตามจำนวนที่แสดง
            this.$watch('filterQuery', () => this.countVisible());
            this.$watch('omenFilter', () => this.countVisible());
        },

        /**
         * กรองสัญลักษณ์
         */
        matchFilter(keyword, isGoodOmen) {
            // กรองตามคำค้นหา
            if (this.filterQuery.length > 0) {
                if (!keyword.includes(this.filterQuery)) return false;
            }

            // กรองตาม omen
            if (this.omenFilter === 'good' && !isGoodOmen) return false;
            if (this.omenFilter === 'bad' && isGoodOmen) return false;

            return true;
        },

        /**
         * นับจำนวนที่แสดง
         */
        countVisible() {
            this.$nextTick(() => {
                const cards = document.querySelectorAll('.symbol-card');
                let count = 0;
                cards.forEach(card => {
                    if (card.style.display !== 'none') count++;
                });
                this.visibleCount = count;
            });
        },

        /**
         * แสดง modal รายละเอียด
         */
        showDetail(data) {
            this.detailSymbol = data;
        },
    };
}
</script>
@endsection
